# Accounting-Friendly Exports Implementation Plan

## Feature Overview

The Accounting-Friendly Exports feature provides comprehensive financial reporting and data export capabilities for MixPitch. This enables users to seamlessly integrate project financials with their accounting software, generate tax-compliant reports, and automate bookkeeping workflows.

### Core Functionality
- **CSV Exports**: Payout reconciliation, invoice line items, project ledger
- **Accounting Software Integration**: QuickBooks, Xero, Wave import compatibility
- **Webhook Automation**: Zapier/Make.com integration
- **Direct API Integration**: QuickBooks Online API
- **Compliance Support**: UBL/Peppol export for EU tax compliance

## Technical Architecture

### Database Schema

```sql
-- Accounting export configurations
CREATE TABLE accounting_export_configs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('csv', 'quickbooks', 'xero', 'wave', 'webhook') NOT NULL,
    config JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_type (user_id, type),
    INDEX idx_active (is_active)
);

-- Export job tracking
CREATE TABLE accounting_exports (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    config_id BIGINT UNSIGNED NULL,
    type VARCHAR(50) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    filters JSON NULL,
    file_path VARCHAR(500) NULL,
    download_url VARCHAR(500) NULL,
    records_count INT UNSIGNED DEFAULT 0,
    error_message TEXT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (config_id) REFERENCES accounting_export_configs(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_expires (expires_at)
);

-- Webhook delivery tracking
CREATE TABLE accounting_webhook_deliveries (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    export_id BIGINT UNSIGNED NOT NULL,
    webhook_url VARCHAR(500) NOT NULL,
    payload JSON NOT NULL,
    response_status INT NULL,
    response_body TEXT NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (export_id) REFERENCES accounting_exports(id) ON DELETE CASCADE,
    INDEX idx_export_delivered (export_id, delivered_at)
);

-- Tax compliance metadata
CREATE TABLE tax_compliance_records (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    transaction_id BIGINT UNSIGNED NOT NULL,
    country_code CHAR(2) NOT NULL,
    tax_rate DECIMAL(5,4) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    vat_number VARCHAR(50) NULL,
    compliance_data JSON NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_transaction (transaction_id),
    INDEX idx_country_date (country_code, created_at)
);
```

### Service Architecture

#### AccountingExportService
```php
<?php

namespace App\Services;

use App\Models\AccountingExport;
use App\Models\AccountingExportConfig;
use App\Models\Transaction;
use App\Jobs\ProcessAccountingExport;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AccountingExportService
{
    public function createExport(
        int $userId,
        string $type,
        array $filters,
        ?int $configId = null
    ): AccountingExport {
        $export = AccountingExport::create([
            'user_id' => $userId,
            'config_id' => $configId,
            'type' => $type,
            'status' => 'pending',
            'filters' => $filters,
            'expires_at' => now()->addDays(7),
        ]);

        ProcessAccountingExport::dispatch($export);

        return $export;
    }

    public function generatePayoutReconciliation(AccountingExport $export): string
    {
        $transactions = $this->getFilteredTransactions($export);
        
        $csvData = [];
        $csvData[] = [
            'Date',
            'Project ID',
            'Project Name',
            'Producer Name',
            'Producer Email',
            'Gross Amount',
            'Platform Fee',
            'Net Payout',
            'Stripe Transfer ID',
            'Status',
            'Tax Country',
            'Tax Rate',
            'Tax Amount'
        ];

        foreach ($transactions as $transaction) {
            $taxRecord = $transaction->taxComplianceRecord;
            
            $csvData[] = [
                $transaction->created_at->format('Y-m-d'),
                $transaction->project_id,
                $transaction->project->name,
                $transaction->recipient->name,
                $transaction->recipient->email,
                number_format($transaction->amount / 100, 2),
                number_format($transaction->platform_fee / 100, 2),
                number_format(($transaction->amount - $transaction->platform_fee) / 100, 2),
                $transaction->stripe_transfer_id,
                $transaction->status,
                $taxRecord?->country_code ?? '',
                $taxRecord?->tax_rate ? number_format($taxRecord->tax_rate * 100, 2) . '%' : '',
                $taxRecord?->tax_amount ? number_format($taxRecord->tax_amount / 100, 2) : ''
            ];
        }

        return $this->generateCsvFile($export, 'payout_reconciliation', $csvData);
    }

    public function generateInvoiceLineItems(AccountingExport $export): string
    {
        $transactions = $this->getFilteredTransactions($export);
        
        $csvData = [];
        $csvData[] = [
            'Invoice Date',
            'Invoice Number',
            'Client Name',
            'Client Email',
            'Project Name',
            'Service Description',
            'Amount',
            'Currency',
            'Tax Rate',
            'Tax Amount',
            'Total Amount'
        ];

        foreach ($transactions->where('type', 'payment') as $transaction) {
            $taxRecord = $transaction->taxComplianceRecord;
            
            $csvData[] = [
                $transaction->created_at->format('Y-m-d'),
                'MP-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT),
                $transaction->payer->name,
                $transaction->payer->email,
                $transaction->project->name,
                'Music Production Services - ' . $transaction->project->name,
                number_format($transaction->amount / 100, 2),
                'USD',
                $taxRecord?->tax_rate ? number_format($taxRecord->tax_rate * 100, 2) . '%' : '0%',
                $taxRecord?->tax_amount ? number_format($taxRecord->tax_amount / 100, 2) : '0.00',
                number_format(($transaction->amount + ($taxRecord?->tax_amount ?? 0)) / 100, 2)
            ];
        }

        return $this->generateCsvFile($export, 'invoice_line_items', $csvData);
    }

    public function generateProjectLedger(AccountingExport $export): string
    {
        $projects = $this->getFilteredProjects($export);
        
        $csvData = [];
        $csvData[] = [
            'Project ID',
            'Project Name',
            'Created Date',
            'Completion Date',
            'Client Name',
            'Producer Name',
            'Total Revenue',
            'Platform Fee',
            'Producer Payout',
            'Status',
            'Workflow Type'
        ];

        foreach ($projects as $project) {
            $revenue = $project->transactions()->where('type', 'payment')->sum('amount');
            $platformFee = $project->transactions()->sum('platform_fee');
            $payout = $project->transactions()->where('type', 'payout')->sum('amount');
            
            $csvData[] = [
                $project->id,
                $project->name,
                $project->created_at->format('Y-m-d'),
                $project->completed_at?->format('Y-m-d') ?? '',
                $project->user->name,
                $project->approvedPitch?->user->name ?? '',
                number_format($revenue / 100, 2),
                number_format($platformFee / 100, 2),
                number_format($payout / 100, 2),
                $project->status,
                $project->workflow_type
            ];
        }

        return $this->generateCsvFile($export, 'project_ledger', $csvData);
    }

    private function generateCsvFile(AccountingExport $export, string $type, array $data): string
    {
        $filename = sprintf(
            'mixpitch_%s_%s_%s.csv',
            $type,
            $export->user_id,
            now()->format('Y-m-d_H-i-s')
        );
        
        $path = "accounting-exports/{$filename}";
        
        $csvContent = '';
        foreach ($data as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }
        
        Storage::disk('s3')->put($path, $csvContent);
        
        $export->update([
            'file_path' => $path,
            'download_url' => Storage::disk('s3')->temporaryUrl($path, now()->addDays(7)),
            'records_count' => count($data) - 1, // Subtract header row
        ]);
        
        return $path;
    }

    private function getFilteredTransactions(AccountingExport $export)
    {
        $query = Transaction::with(['project', 'payer', 'recipient', 'taxComplianceRecord'])
            ->where('user_id', $export->user_id);

        if ($export->filters['date_from'] ?? null) {
            $query->where('created_at', '>=', $export->filters['date_from']);
        }

        if ($export->filters['date_to'] ?? null) {
            $query->where('created_at', '<=', $export->filters['date_to']);
        }

        if ($export->filters['status'] ?? null) {
            $query->where('status', $export->filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    private function getFilteredProjects(AccountingExport $export)
    {
        $query = Project::with(['user', 'approvedPitch.user', 'transactions'])
            ->where('user_id', $export->user_id);

        if ($export->filters['date_from'] ?? null) {
            $query->where('created_at', '>=', $export->filters['date_from']);
        }

        if ($export->filters['date_to'] ?? null) {
            $query->where('created_at', '<=', $export->filters['date_to']);
        }

        if ($export->filters['status'] ?? null) {
            $query->where('status', $export->filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
```

#### QuickBooksIntegrationService
```php
<?php

namespace App\Services;

use App\Models\AccountingExportConfig;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;

class QuickBooksIntegrationService
{
    private DataService $dataService;

    public function __construct(AccountingExportConfig $config)
    {
        $this->dataService = DataService::Configure([
            'auth_mode' => 'oauth2',
            'ClientID' => config('services.quickbooks.client_id'),
            'ClientSecret' => config('services.quickbooks.client_secret'),
            'accessTokenKey' => $config->config['access_token'],
            'refreshTokenKey' => $config->config['refresh_token'],
            'QBORealmID' => $config->config['realm_id'],
            'baseUrl' => config('services.quickbooks.base_url')
        ]);
    }

    public function createInvoice(array $transactionData): ?string
    {
        $customer = $this->findOrCreateCustomer($transactionData['client']);
        $item = $this->findOrCreateServiceItem();

        $invoice = Invoice::create([
            'Line' => [
                [
                    'Amount' => $transactionData['amount'],
                    'DetailType' => 'SalesItemLineDetail',
                    'SalesItemLineDetail' => [
                        'ItemRef' => ['value' => $item->Id],
                        'Qty' => 1,
                        'UnitPrice' => $transactionData['amount']
                    ]
                ]
            ],
            'CustomerRef' => ['value' => $customer->Id]
        ]);

        $result = $this->dataService->Add($invoice);
        
        return $result ? $result->Id : null;
    }

    public function syncPayments(array $payments): array
    {
        $results = [];
        
        foreach ($payments as $payment) {
            try {
                $qbPayment = $this->createPayment($payment);
                $results[] = [
                    'transaction_id' => $payment['id'],
                    'quickbooks_id' => $qbPayment->Id,
                    'status' => 'success'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'transaction_id' => $payment['id'],
                    'error' => $e->getMessage(),
                    'status' => 'failed'
                ];
            }
        }
        
        return $results;
    }
}
```

### Background Jobs

#### ProcessAccountingExport Job
```php
<?php

namespace App\Jobs;

use App\Models\AccountingExport;
use App\Services\AccountingExportService;
use App\Services\WebhookDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAccountingExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private AccountingExport $export
    ) {}

    public function handle(
        AccountingExportService $exportService,
        WebhookDeliveryService $webhookService
    ): void {
        try {
            $this->export->update(['status' => 'processing']);

            $filePath = match ($this->export->type) {
                'payout_reconciliation' => $exportService->generatePayoutReconciliation($this->export),
                'invoice_line_items' => $exportService->generateInvoiceLineItems($this->export),
                'project_ledger' => $exportService->generateProjectLedger($this->export),
                default => throw new \InvalidArgumentException('Unknown export type: ' . $this->export->type)
            };

            $this->export->update(['status' => 'completed']);

            // Send webhook if configured
            if ($this->export->config && $this->export->config->type === 'webhook') {
                $webhookService->deliverExport($this->export);
            }

        } catch (\Exception $e) {
            $this->export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
```

## UI Implementation

### Main Exports Page Component
```php
<?php

namespace App\Livewire\Accounting;

use App\Models\AccountingExport;
use App\Models\AccountingExportConfig;
use App\Services\AccountingExportService;
use Livewire\Component;
use Livewire\WithPagination;

class ExportsManager extends Component
{
    use WithPagination;

    public string $selectedType = 'payout_reconciliation';
    public array $filters = [
        'date_from' => '',
        'date_to' => '',
        'status' => ''
    ];
    public bool $showFilters = false;

    public function mount()
    {
        $this->filters['date_from'] = now()->subMonth()->format('Y-m-d');
        $this->filters['date_to'] = now()->format('Y-m-d');
    }

    public function generateExport(AccountingExportService $exportService)
    {
        $export = $exportService->createExport(
            auth()->id(),
            $this->selectedType,
            array_filter($this->filters)
        );

        $this->dispatch('export-created', [
            'message' => 'Export generation started. You\'ll receive a notification when ready.',
            'exportId' => $export->id
        ]);

        $this->resetPage();
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function render()
    {
        $exports = AccountingExport::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        $configs = AccountingExportConfig::where('user_id', auth()->id())
            ->where('is_active', true)
            ->get();

        return view('livewire.accounting.exports-manager', [
            'exports' => $exports,
            'configs' => $configs
        ]);
    }
}
```

### Blade Template
```blade
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">Accounting Exports</flux:heading>
            <flux:text variant="muted">
                Generate reports for your accounting software and tax compliance
            </flux:text>
        </div>
        
        <flux:button wire:click="$toggle('showFilters')" variant="outline" size="sm">
            <flux:icon icon="adjustments-horizontal" class="w-4 h-4" />
            Filters
        </flux:button>
    </div>

    {{-- Export Generation Card --}}
    <flux:card>
        <flux:card.header>
            <flux:heading size="base">Generate New Export</flux:heading>
        </flux:card.header>
        
        <flux:card.body class="space-y-4">
            {{-- Export Type Selection --}}
            <flux:field>
                <flux:label>Export Type</flux:label>
                <flux:select wire:model.live="selectedType">
                    <option value="payout_reconciliation">Payout Reconciliation</option>
                    <option value="invoice_line_items">Invoice Line Items</option>
                    <option value="project_ledger">Project Ledger</option>
                </flux:select>
                <flux:description>
                    @switch($selectedType)
                        @case('payout_reconciliation')
                            Detailed payout information for producer payments and fees
                            @break
                        @case('invoice_line_items') 
                            Client billing information formatted for accounting software
                            @break
                        @case('project_ledger')
                            Complete project financial summary with revenue and costs
                            @break
                    @endswitch
                </flux:description>
            </flux:field>

            {{-- Filters Panel --}}
            @if($showFilters)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <flux:field>
                        <flux:label>From Date</flux:label>
                        <flux:input type="date" wire:model="filters.date_from" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>To Date</flux:label>
                        <flux:input type="date" wire:model="filters.date_to" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="filters.status">
                            <option value="">All Statuses</option>
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </flux:select>
                    </flux:field>
                </div>
            @endif

            <flux:button wire:click="generateExport" variant="primary" class="w-full">
                <flux:icon icon="document-arrow-down" class="w-4 h-4" />
                Generate Export
            </flux:button>
        </flux:card.body>
    </flux:card>

    {{-- Recent Exports --}}
    <flux:card>
        <flux:card.header>
            <flux:heading size="base">Recent Exports</flux:heading>
        </flux:card.header>
        
        <flux:table>
            <flux:table.header>
                <flux:table.row>
                    <flux:table.cell>Type</flux:table.cell>
                    <flux:table.cell>Generated</flux:table.cell>
                    <flux:table.cell>Records</flux:table.cell>
                    <flux:table.cell>Status</flux:table.cell>
                    <flux:table.cell>Actions</flux:table.cell>
                </flux:table.row>
            </flux:table.header>
            
            <flux:table.body>
                @forelse($exports as $export)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="font-medium">
                                {{ str_replace('_', ' ', title_case($export->type)) }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $export->created_at->format('M j, Y g:i A') }}
                            </div>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            {{ $export->created_at->diffForHumans() }}
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            {{ number_format($export->records_count) }}
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <flux:badge 
                                variant="{{ $export->status === 'completed' ? 'success' : ($export->status === 'failed' ? 'danger' : 'warning') }}"
                                size="sm"
                            >
                                {{ ucfirst($export->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            @if($export->status === 'completed' && $export->download_url)
                                <flux:button 
                                    href="{{ $export->download_url }}" 
                                    variant="outline" 
                                    size="sm"
                                    target="_blank"
                                >
                                    <flux:icon icon="arrow-down-tray" class="w-4 h-4" />
                                    Download
                                </flux:button>
                            @elseif($export->status === 'processing')
                                <div class="flex items-center text-sm text-gray-500">
                                    <flux:icon icon="clock" class="w-4 h-4 mr-1 animate-spin" />
                                    Processing...
                                </div>
                            @elseif($export->status === 'failed')
                                <flux:button 
                                    wire:click="retryExport({{ $export->id }})" 
                                    variant="outline" 
                                    size="sm"
                                >
                                    <flux:icon icon="arrow-path" class="w-4 h-4" />
                                    Retry
                                </flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-gray-500 py-8">
                            No exports generated yet. Create your first export above.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.body>
        </flux:table>
        
        {{ $exports->links() }}
    </flux:card>

    {{-- Integration Configurations --}}
    @if($configs->isNotEmpty())
        <flux:card>
            <flux:card.header>
                <flux:heading size="base">Active Integrations</flux:heading>
            </flux:card.header>
            
            <flux:card.body>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($configs as $config)
                        <div class="flex items-center justify-between p-3 border rounded-lg">
                            <div class="flex items-center space-x-3">
                                <flux:icon 
                                    icon="{{ $this->getIntegrationIcon($config->type) }}" 
                                    class="w-6 h-6 text-gray-600"
                                />
                                <div>
                                    <div class="font-medium">{{ ucfirst($config->type) }}</div>
                                    <div class="text-sm text-gray-500">{{ $config->name }}</div>
                                </div>
                            </div>
                            
                            <flux:badge variant="success" size="sm">Active</flux:badge>
                        </div>
                    @endforeach
                </div>
            </flux:card.body>
        </flux:card>
    @endif
</div>

@script
<script>
    $wire.on('export-created', (data) => {
        // Show success notification
        window.dispatchEvent(new CustomEvent('notify', {
            detail: {
                type: 'success',
                message: data.message
            }
        }));
        
        // Auto-refresh the page after a short delay to show the new export
        setTimeout(() => {
            $wire.$refresh();
        }, 2000);
    });
</script>
@endscript
```

### Integration Setup Component
```php
<?php

namespace App\Livewire\Accounting;

use App\Models\AccountingExportConfig;
use Livewire\Component;

class IntegrationSetup extends Component
{
    public string $selectedIntegration = '';
    public array $config = [];
    public bool $showSetup = false;

    public function selectIntegration(string $type)
    {
        $this->selectedIntegration = $type;
        $this->showSetup = true;
        $this->config = $this->getDefaultConfig($type);
    }

    public function saveIntegration()
    {
        $this->validate([
            'selectedIntegration' => 'required|in:quickbooks,xero,wave,webhook',
            'config.name' => 'required|string|max:255',
        ]);

        AccountingExportConfig::create([
            'user_id' => auth()->id(),
            'name' => $this->config['name'],
            'type' => $this->selectedIntegration,
            'config' => $this->config,
            'is_active' => true,
        ]);

        $this->dispatch('integration-saved');
        $this->reset();
    }

    private function getDefaultConfig(string $type): array
    {
        return match ($type) {
            'webhook' => [
                'name' => '',
                'webhook_url' => '',
                'secret_key' => str()->random(32),
                'events' => ['export.completed']
            ],
            'quickbooks' => [
                'name' => '',
                'auto_sync' => false,
                'create_customers' => true,
                'create_items' => true
            ],
            default => ['name' => '']
        };
    }

    public function render()
    {
        return view('livewire.accounting.integration-setup');
    }
}
```

## API Endpoints

### Export Management API
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAccountingExportRequest;
use App\Models\AccountingExport;
use App\Services\AccountingExportService;
use Illuminate\Http\JsonResponse;

class AccountingExportController extends Controller
{
    public function __construct(
        private AccountingExportService $exportService
    ) {}

    public function index(): JsonResponse
    {
        $exports = AccountingExport::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return response()->json($exports);
    }

    public function store(CreateAccountingExportRequest $request): JsonResponse
    {
        $export = $this->exportService->createExport(
            auth()->id(),
            $request->type,
            $request->filters ?? [],
            $request->config_id
        );

        return response()->json($export, 201);
    }

    public function show(AccountingExport $export): JsonResponse
    {
        $this->authorize('view', $export);

        return response()->json($export->load('config'));
    }

    public function download(AccountingExport $export)
    {
        $this->authorize('view', $export);

        if ($export->status !== 'completed' || !$export->download_url) {
            abort(404, 'Export not ready for download');
        }

        return redirect($export->download_url);
    }
}
```

### Webhook Integration
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountingExportConfig;
use App\Services\WebhookDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    public function handleZapierWebhook(
        Request $request,
        WebhookDeliveryService $webhookService
    ): JsonResponse {
        // Zapier webhook handling for export triggers
        $validated = $request->validate([
            'trigger_event' => 'required|string',
            'export_config_id' => 'required|exists:accounting_export_configs,id'
        ]);

        $config = AccountingExportConfig::findOrFail($validated['export_config_id']);
        
        // Trigger export based on webhook event
        $export = $this->exportService->createExport(
            $config->user_id,
            $config->config['default_export_type'] ?? 'project_ledger',
            []
        );

        return response()->json([
            'success' => true,
            'export_id' => $export->id,
            'status' => $export->status
        ]);
    }

    public function deliverExportWebhook(
        Request $request,
        WebhookDeliveryService $webhookService
    ): JsonResponse {
        // Internal webhook for completed exports
        $validated = $request->validate([
            'export_id' => 'required|exists:accounting_exports,id'
        ]);

        $export = AccountingExport::findOrFail($validated['export_id']);
        
        if ($export->config && $export->config->type === 'webhook') {
            $webhookService->deliverExport($export);
        }

        return response()->json(['success' => true]);
    }
}
```

## Testing Strategy

### Feature Tests
```php
<?php

namespace Tests\Feature\Accounting;

use App\Jobs\ProcessAccountingExport;
use App\Models\AccountingExport;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountingExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountingExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_payout_reconciliation_export(): void
    {
        Queue::fake();
        
        $user = User::factory()->create();
        
        // Create test transactions
        Transaction::factory()
            ->count(5)
            ->for($user)
            ->create(['type' => 'payout']);

        $response = $this->actingAs($user)
            ->postJson('/api/accounting/exports', [
                'type' => 'payout_reconciliation',
                'filters' => [
                    'date_from' => now()->subMonth()->format('Y-m-d'),
                    'date_to' => now()->format('Y-m-d')
                ]
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'type',
            'status',
            'created_at'
        ]);

        Queue::assertPushed(ProcessAccountingExport::class);
    }

    public function test_export_generates_correct_csv_format(): void
    {
        Storage::fake('s3');
        
        $user = User::factory()->create();
        $transactions = Transaction::factory()
            ->count(3)
            ->for($user)
            ->create(['type' => 'payout']);

        $export = AccountingExport::factory()->create([
            'user_id' => $user->id,
            'type' => 'payout_reconciliation'
        ]);

        $service = new AccountingExportService();
        $filePath = $service->generatePayoutReconciliation($export);

        Storage::disk('s3')->assertExists($filePath);
        
        $csvContent = Storage::disk('s3')->get($filePath);
        $lines = explode("\n", trim($csvContent));
        
        // Should have header + 3 data rows
        $this->assertCount(4, $lines);
        
        // Check header format
        $this->assertStringContainsString('Date', $lines[0]);
        $this->assertStringContainsString('Producer Name', $lines[0]);
        $this->assertStringContainsString('Net Payout', $lines[0]);
    }

    public function test_export_respects_date_filters(): void
    {
        $user = User::factory()->create();
        
        // Create transactions in different months
        $oldTransaction = Transaction::factory()
            ->for($user)
            ->create([
                'type' => 'payout',
                'created_at' => now()->subMonths(2)
            ]);
            
        $recentTransaction = Transaction::factory()
            ->for($user)
            ->create([
                'type' => 'payout',
                'created_at' => now()->subDays(5)
            ]);

        $export = AccountingExport::factory()->create([
            'user_id' => $user->id,
            'type' => 'payout_reconciliation',
            'filters' => [
                'date_from' => now()->subMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d')
            ]
        ]);

        $service = new AccountingExportService();
        $service->generatePayoutReconciliation($export);

        // CSV should only include recent transaction
        $csvContent = Storage::disk('s3')->get($export->fresh()->file_path);
        
        $this->assertStringContainsString((string)$recentTransaction->id, $csvContent);
        $this->assertStringNotContainsString((string)$oldTransaction->id, $csvContent);
    }

    public function test_failed_export_sets_error_status(): void
    {
        $user = User::factory()->create();
        
        $export = AccountingExport::factory()->create([
            'user_id' => $user->id,
            'type' => 'invalid_type'
        ]);

        $job = new ProcessAccountingExport($export);
        
        $this->expectException(\InvalidArgumentException::class);
        $job->handle(new AccountingExportService(), app(WebhookDeliveryService::class));
        
        $this->assertEquals('failed', $export->fresh()->status);
        $this->assertNotNull($export->fresh()->error_message);
    }
}
```

### Unit Tests
```php
<?php

namespace Tests\Unit\Services;

use App\Models\AccountingExport;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountingExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountingExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_export_with_correct_attributes(): void
    {
        $user = User::factory()->create();
        $service = new AccountingExportService();

        $export = $service->createExport(
            $user->id,
            'project_ledger',
            ['status' => 'completed']
        );

        $this->assertInstanceOf(AccountingExport::class, $export);
        $this->assertEquals($user->id, $export->user_id);
        $this->assertEquals('project_ledger', $export->type);
        $this->assertEquals(['status' => 'completed'], $export->filters);
        $this->assertEquals('pending', $export->status);
        $this->assertNotNull($export->expires_at);
    }

    public function test_csv_generation_includes_all_required_columns(): void
    {
        Storage::fake('s3');
        
        $user = User::factory()->create();
        $export = AccountingExport::factory()->create([
            'user_id' => $user->id,
            'type' => 'invoice_line_items'
        ]);

        $service = new AccountingExportService();
        $filePath = $service->generateInvoiceLineItems($export);

        $csvContent = Storage::disk('s3')->get($filePath);
        $headerLine = explode("\n", $csvContent)[0];

        $requiredColumns = [
            'Invoice Date',
            'Client Name',
            'Project Name',
            'Amount',
            'Tax Rate'
        ];

        foreach ($requiredColumns as $column) {
            $this->assertStringContainsString($column, $headerLine);
        }
    }
}
```

## Implementation Steps

### Phase 1: Core Infrastructure (Week 1)
1. **Database Migration Setup**
   - Create accounting export tables
   - Add tax compliance tracking
   - Update existing transaction table with platform fee tracking

2. **Service Architecture**
   - Implement `AccountingExportService` with CSV generation
   - Create background job for export processing
   - Add file storage and temporary URL generation

3. **Basic API Endpoints**
   - Create/list/download exports
   - Basic authentication and authorization

### Phase 2: UI Implementation (Week 2)
1. **Livewire Components**
   - Main exports manager component
   - Export type selection and filtering
   - Real-time status updates

2. **Blade Templates**
   - Responsive export interface using Flux UI
   - Progress indicators and download links
   - Integration status dashboard

3. **Frontend Polish**
   - Loading states and error handling
   - Auto-refresh for export status
   - File download optimization

### Phase 3: External Integrations (Week 3)
1. **QuickBooks Integration**
   - OAuth2 setup and token management
   - Customer/item synchronization
   - Invoice creation API

2. **Webhook System**
   - Zapier/Make.com webhook endpoints
   - Delivery tracking and retry logic
   - Security with signature verification

3. **Additional Accounting Software**
   - Xero API integration
   - Wave accounting support
   - Generic CSV format compatibility

### Phase 4: Advanced Features (Week 4)
1. **Tax Compliance**
   - Multi-country tax rate support
   - UBL/Peppol export format
   - VAT number validation

2. **Automation & Scheduling**
   - Scheduled export generation
   - Auto-delivery to integrations
   - Smart export suggestions

3. **Analytics & Reporting**
   - Export usage analytics
   - Financial insights dashboard
   - Audit trail and compliance reporting

## Security Considerations

### Data Protection
- **Export Encryption**: All CSV files encrypted at rest in S3
- **Temporary URLs**: Downloads expire after 7 days automatically
- **Access Control**: User isolation and role-based permissions
- **Audit Logging**: Complete trail of export generation and access

### Integration Security
- **OAuth2 Flow**: Secure token management for accounting software APIs
- **Webhook Signatures**: Cryptographic verification of webhook payloads
- **API Rate Limiting**: Prevent abuse of export generation
- **Data Sanitization**: Remove sensitive data before external transmission

### Compliance Features
- **GDPR Compliance**: Data retention policies and deletion
- **SOX Compliance**: Immutable audit trails for financial data
- **PCI Compliance**: Secure handling of payment information
- **Tax Regulations**: Multi-jurisdiction tax reporting support

This comprehensive implementation plan provides MixPitch users with professional-grade accounting integration capabilities while maintaining the platform's focus on security, user experience, and technical excellence.