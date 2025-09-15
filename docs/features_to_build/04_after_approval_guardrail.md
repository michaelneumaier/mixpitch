# After Approval Guardrail Implementation Plan

## Overview

Protect both musicians and producers from scope creep by implementing revision round management with clear boundaries and payment controls. Once a project is approved, additional revision requests require new paid rounds, ensuring fair compensation while maintaining professional relationships.

## UX/UI Implementation

### Project Settings Configuration

**Location**: Project creation and settings management  
**Current**: Unlimited revision cycles without clear boundaries  
**New**: Configurable revision policies with transparent pricing

```blade
{{-- Revision policy configuration in project settings --}}
<flux:card class="p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Revision Policy</h3>
        <flux:badge variant="info" size="sm">
            {{ $project->workflow_type === 'client_management' ? 'Client Project' : 'Standard Project' }}
        </flux:badge>
    </div>
    
    <div class="space-y-6">
        {{-- Included revisions --}}
        <flux:field>
            <flux:label>Included Revisions</flux:label>
            <div class="flex items-center space-x-4">
                <flux:input 
                    type="number" 
                    wire:model.defer="revisionPolicy.included_rounds"
                    min="0" 
                    max="10"
                    class="w-20"
                />
                <flux:text size="sm" class="text-slate-600">
                    revision rounds included in base price
                </flux:text>
            </div>
            <flux:text size="sm" class="text-slate-500">
                Standard: 2-3 rounds. Premium: 5+ rounds.
            </flux:text>
        </flux:field>
        
        {{-- Additional revision pricing --}}
        <flux:field>
            <flux:label>Additional Revision Price</flux:label>
            <div class="flex items-center space-x-4">
                <div class="flex">
                    <span class="inline-flex items-center px-3 py-2 border border-r-0 border-slate-300 bg-slate-50 text-slate-500 text-sm rounded-l-md">
                        $
                    </span>
                    <flux:input 
                        type="number" 
                        wire:model.defer="revisionPolicy.additional_round_price"
                        min="0" 
                        step="0.01"
                        class="rounded-l-none"
                        placeholder="150.00"
                    />
                </div>
                <flux:text size="sm" class="text-slate-600">
                    per additional revision round
                </flux:text>
            </div>
        </flux:field>
        
        {{-- Revision scope --}}
        <flux:field>
            <flux:label>Revision Scope Guidelines</flux:label>
            <flux:textarea 
                wire:model.defer="revisionPolicy.scope_guidelines"
                rows="3"
                placeholder="e.g., Minor mix adjustments, level changes, basic EQ tweaks. Major arrangement changes or re-recording require separate quote."
            />
            <flux:text size="sm" class="text-slate-500">
                Help set client expectations about what constitutes a revision
            </flux:text>
        </flux:field>
        
        {{-- Auto-approval settings --}}
        <div class="p-4 bg-slate-50 rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium">Approval Settings</h4>
            </div>
            
            <div class="space-y-3">
                <div class="flex items-center space-x-2">
                    <flux:checkbox wire:model.defer="revisionPolicy.lock_on_approval" />
                    <flux:label>Lock comments when project is approved</flux:label>
                </div>
                
                <div class="flex items-center space-x-2">
                    <flux:checkbox wire:model.defer="revisionPolicy.require_payment_for_additional" />
                    <flux:label>Require payment before additional revisions</flux:label>
                </div>
                
                <div class="flex items-center space-x-2">
                    <flux:checkbox wire:model.defer="revisionPolicy.auto_invoice_additional" />
                    <flux:label>Automatically create invoices for additional rounds</flux:label>
                </div>
            </div>
        </div>
    </div>
</flux:card>
```

### Approval Status Display

```blade
{{-- Project approval status and revision tracking --}}
<div class="bg-white border border-slate-200 rounded-lg p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Project Status</h3>
        <div class="flex items-center space-x-2">
            @if($project->currentRevisionRound)
                <flux:badge variant="primary">
                    Round {{ $project->currentRevisionRound->round_number }}
                </flux:badge>
            @endif
            <flux:badge 
                variant="{{ $project->approval_status === 'approved' ? 'success' : 'warning' }}"
            >
                {{ ucfirst($project->approval_status) }}
            </flux:badge>
        </div>
    </div>
    
    {{-- Revision progress --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between text-sm">
            <span class="text-slate-600">Revisions Used</span>
            <span class="font-medium">
                {{ $project->completed_revision_rounds }} / {{ $project->revisionPolicy->included_rounds }}
                @if($project->additional_rounds_purchased > 0)
                    (+ {{ $project->additional_rounds_purchased }} additional)
                @endif
            </span>
        </div>
        
        <div class="w-full bg-slate-200 rounded-full h-2">
            <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" 
                 style="width: {{ min(100, ($project->completed_revision_rounds / max(1, $project->revisionPolicy->included_rounds)) * 100) }}%">
            </div>
        </div>
        
        @if($project->completed_revision_rounds >= $project->revisionPolicy->included_rounds)
            <flux:callout variant="warning" class="text-sm">
                <flux:icon name="exclamation-triangle" size="sm" />
                <div class="ml-2">
                    <strong>Included revisions exhausted.</strong>
                    Additional revision rounds will incur a ${{ $project->revisionPolicy->additional_round_price }} charge.
                </div>
            </flux:callout>
        @endif
    </div>
</div>
```

### Approval Action Interface

```blade
{{-- Enhanced approval interface with revision controls --}}
<div class="space-y-4">
    @if($project->approval_status === 'pending' || $project->approval_status === 'in_revision')
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-6">
            <h4 class="font-semibold text-green-900 mb-3">Ready to Approve?</h4>
            <p class="text-sm text-green-700 mb-4">
                Approving this project will lock the current state and prevent further revisions 
                without additional payment.
            </p>
            
            <div class="flex items-center space-x-3">
                <flux:button 
                    wire:click="approveProject" 
                    variant="primary"
                    size="sm"
                >
                    <flux:icon name="check" size="sm" />
                    Approve Project
                </flux:button>
                
                <flux:button 
                    wire:click="requestRevisions" 
                    variant="outline"
                    size="sm"
                >
                    <flux:icon name="edit" size="sm" />
                    Request Revisions
                </flux:button>
            </div>
        </div>
    @elseif($project->approval_status === 'approved')
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-semibold text-amber-900">Project Approved</h4>
                <flux:badge variant="success">
                    Approved {{ $project->approved_at->diffForHumans() }}
                </flux:badge>
            </div>
            
            <p class="text-sm text-amber-700 mb-4">
                This project has been approved and comments are locked. 
                Additional changes will require a new revision round.
            </p>
            
            <div class="flex items-center space-x-3">
                <flux:button 
                    wire:click="requestAdditionalRevisions" 
                    variant="primary"
                    size="sm"
                >
                    <flux:icon name="plus" size="sm" />
                    Request More Changes
                    <span class="ml-1 text-xs">
                        (${{ $project->revisionPolicy->additional_round_price }})
                    </span>
                </flux:button>
                
                <flux:button 
                    wire:click="downloadFinalFiles" 
                    variant="outline"
                    size="sm"
                >
                    <flux:icon name="download" size="sm" />
                    Download Final Files
                </flux:button>
            </div>
        </div>
    @endif
</div>
```

### Additional Revision Request Modal

```blade
{{-- Modal for requesting additional paid revisions --}}
<flux:modal wire:model="showAdditionalRevisionModal" size="lg">
    <flux:modal.header>
        <h2 class="text-xl font-semibold">Request Additional Revisions</h2>
    </flux:modal.header>
    
    <flux:modal.body class="p-6 space-y-6">
        {{-- Cost breakdown --}}
        <div class="bg-slate-50 rounded-lg p-4">
            <h3 class="font-medium mb-3">Cost Breakdown</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span>Additional revision round:</span>
                    <span class="font-medium">${{ $project->revisionPolicy->additional_round_price }}</span>
                </div>
                @if($platformFee > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Platform fee ({{ $platformFeePercentage }}%):</span>
                        <span>${{ $platformFee }}</span>
                    </div>
                @endif
                <div class="flex justify-between border-t pt-2 font-medium">
                    <span>Total:</span>
                    <span>${{ $totalCost }}</span>
                </div>
            </div>
        </div>
        
        {{-- Revision scope --}}
        <flux:field>
            <flux:label>Revision Details</flux:label>
            <flux:textarea 
                wire:model.defer="additionalRevisionRequest.details"
                rows="4"
                placeholder="Please describe what changes you'd like to make..."
                required
            />
            <flux:text size="sm" class="text-slate-500">
                Be specific about the changes you need to help the producer understand the scope
            </flux:text>
        </flux:field>
        
        {{-- Scope guidelines reminder --}}
        @if($project->revisionPolicy->scope_guidelines)
            <flux:callout variant="info">
                <flux:icon name="information-circle" size="sm" />
                <div class="ml-2">
                    <strong>Revision Scope Guidelines:</strong>
                    <p class="text-sm mt-1">{{ $project->revisionPolicy->scope_guidelines }}</p>
                </div>
            </flux:callout>
        @endif
        
        {{-- Payment method selection --}}
        <flux:field>
            <flux:label>Payment Method</flux:label>
            <div class="space-y-2">
                @foreach($paymentMethods as $method)
                    <label class="flex items-center space-x-3 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                        <input 
                            type="radio" 
                            wire:model="selectedPaymentMethod" 
                            value="{{ $method->id }}"
                            class="text-indigo-600"
                        />
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium">•••• {{ $method->last4 }}</span>
                            <span class="text-xs text-slate-500">{{ $method->brand }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
        </flux:field>
    </flux:modal.body>
    
    <flux:modal.footer class="flex justify-between">
        <flux:button wire:click="$set('showAdditionalRevisionModal', false)" variant="outline">
            Cancel
        </flux:button>
        <flux:button 
            wire:click="processAdditionalRevisionPayment" 
            variant="primary"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove>Pay ${{ $totalCost }} & Request Revisions</span>
            <span wire:loading>Processing...</span>
        </flux:button>
    </flux:modal.footer>
</flux:modal>
```

## Database Schema

### New Table: `revision_policies`

```php
Schema::create('revision_policies', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->integer('included_rounds')->default(2); // Number of included revision rounds
    $table->decimal('additional_round_price', 10, 2); // Price per additional round
    $table->text('scope_guidelines')->nullable(); // What constitutes a revision
    $table->boolean('lock_on_approval')->default(true); // Lock comments on approval
    $table->boolean('require_payment_for_additional')->default(true);
    $table->boolean('auto_invoice_additional')->default(false);
    $table->json('notification_settings')->nullable(); // When to notify about revisions
    $table->timestamps();
    
    $table->index('project_id');
});
```

### New Table: `revision_rounds`

```php
Schema::create('revision_rounds', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->integer('round_number'); // 1, 2, 3, etc.
    $table->enum('round_type', ['included', 'additional', 'bonus']); 
    $table->enum('status', ['active', 'completed', 'cancelled']);
    $table->text('request_details')->nullable(); // What was requested in this round
    $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('requested_at')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('approved_at')->nullable();
    $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('completed_at')->nullable();
    $table->decimal('charge_amount', 10, 2)->nullable(); // Amount charged for this round
    $table->foreignId('payment_intent_id')->nullable(); // Stripe payment intent
    $table->json('metadata')->nullable(); // Additional round metadata
    $table->timestamps();
    
    $table->index(['project_id', 'round_number']);
    $table->index(['status', 'requested_at']);
    $table->unique(['project_id', 'round_number']);
});
```

### New Table: `revision_payments`

```php
Schema::create('revision_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('revision_round_id')->constrained()->onDelete('cascade');
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Payer
    $table->string('stripe_payment_intent_id')->unique();
    $table->decimal('amount', 10, 2);
    $table->decimal('platform_fee', 10, 2)->nullable();
    $table->string('currency', 3)->default('USD');
    $table->enum('status', ['pending', 'succeeded', 'failed', 'cancelled']);
    $table->json('payment_metadata')->nullable(); // Stripe metadata
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
    
    $table->index(['project_id', 'status']);
    $table->index(['user_id', 'paid_at']);
    $table->index('stripe_payment_intent_id');
});
```

### Extend `projects` table

```php
Schema::table('projects', function (Blueprint $table) {
    $table->enum('approval_status', ['pending', 'in_revision', 'approved', 'cancelled'])
          ->default('pending')->after('status');
    $table->timestamp('approved_at')->nullable()->after('approval_status');
    $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
    $table->integer('completed_revision_rounds')->default(0);
    $table->integer('additional_rounds_purchased')->default(0);
    $table->boolean('comments_locked')->default(false);
    
    $table->index(['approval_status', 'approved_at']);
});
```

## Service Layer Architecture

### New Service: `RevisionRoundService`

```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\RevisionRound;
use App\Models\RevisionPayment;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevisionRoundService
{
    protected PaymentService $paymentService;
    protected NotificationService $notificationService;
    
    public function __construct(
        PaymentService $paymentService,
        NotificationService $notificationService
    ) {
        $this->paymentService = $paymentService;
        $this->notificationService = $notificationService;
    }
    
    public function requestRevision(
        Project $project, 
        User $requestedBy, 
        string $details,
        bool $isAdditional = false
    ): RevisionRound {
        
        DB::beginTransaction();
        
        try {
            // Determine round number and type
            $roundNumber = $project->revisionRounds()->max('round_number') + 1;
            $roundType = $this->determineRoundType($project, $isAdditional);
            
            // Create revision round
            $round = RevisionRound::create([
                'project_id' => $project->id,
                'round_number' => $roundNumber,
                'round_type' => $roundType,
                'status' => 'active',
                'request_details' => $details,
                'requested_by' => $requestedBy->id,
                'requested_at' => now(),
                'charge_amount' => $this->calculateRoundCharge($project, $roundType),
            ]);
            
            // Update project status
            $project->update([
                'approval_status' => 'in_revision',
                'comments_locked' => false,
            ]);
            
            // Handle payment for additional rounds
            if ($roundType === 'additional' && $round->charge_amount > 0) {
                $this->processAdditionalRoundPayment($round, $requestedBy);
            }
            
            // Send notifications
            $this->notificationService->sendRevisionRequestNotification($round);
            
            // Log the revision request
            Log::info('Revision round requested', [
                'project_id' => $project->id,
                'round_id' => $round->id,
                'round_number' => $roundNumber,
                'type' => $roundType,
                'requested_by' => $requestedBy->id,
                'charge_amount' => $round->charge_amount,
            ]);
            
            DB::commit();
            
            return $round;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create revision round', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    public function approveProject(Project $project, User $approvedBy): void
    {
        DB::beginTransaction();
        
        try {
            // Complete current revision round if any
            $currentRound = $project->revisionRounds()
                ->where('status', 'active')
                ->latest('round_number')
                ->first();
                
            if ($currentRound) {
                $currentRound->update([
                    'status' => 'completed',
                    'completed_by' => $approvedBy->id,
                    'completed_at' => now(),
                ]);
                
                $project->increment('completed_revision_rounds');
            }
            
            // Update project approval status
            $project->update([
                'approval_status' => 'approved',
                'approved_by' => $approvedBy->id,
                'approved_at' => now(),
                'comments_locked' => $project->revisionPolicy->lock_on_approval ?? true,
            ]);
            
            // Send approval notifications
            $this->notificationService->sendProjectApprovalNotification($project);
            
            // Log the approval
            Log::info('Project approved', [
                'project_id' => $project->id,
                'approved_by' => $approvedBy->id,
                'total_rounds' => $project->completed_revision_rounds,
            ]);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    protected function determineRoundType(Project $project, bool $forceAdditional = false): string
    {
        if ($forceAdditional) {
            return 'additional';
        }
        
        $completedRounds = $project->completed_revision_rounds;
        $includedRounds = $project->revisionPolicy->included_rounds ?? 2;
        
        return $completedRounds < $includedRounds ? 'included' : 'additional';
    }
    
    protected function calculateRoundCharge(Project $project, string $roundType): float
    {
        if ($roundType === 'included' || $roundType === 'bonus') {
            return 0.0;
        }
        
        return $project->revisionPolicy->additional_round_price ?? 0.0;
    }
    
    protected function processAdditionalRoundPayment(RevisionRound $round, User $payer): void
    {
        if ($round->charge_amount <= 0) {
            return;
        }
        
        // Create Stripe payment intent
        $paymentIntent = $this->paymentService->createPaymentIntent([
            'amount' => $round->charge_amount * 100, // Convert to cents
            'currency' => 'usd',
            'customer' => $payer->stripe_customer_id,
            'metadata' => [
                'type' => 'revision_round',
                'project_id' => $round->project_id,
                'revision_round_id' => $round->id,
            ],
        ]);
        
        // Create payment record
        RevisionPayment::create([
            'revision_round_id' => $round->id,
            'project_id' => $round->project_id,
            'user_id' => $payer->id,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'amount' => $round->charge_amount,
            'platform_fee' => $this->calculatePlatformFee($round->charge_amount),
            'status' => 'pending',
        ]);
        
        $round->update([
            'payment_intent_id' => $paymentIntent->id,
        ]);
    }
    
    protected function calculatePlatformFee(float $amount): float
    {
        $feePercentage = config('billing.platform_fee_percentage', 5.0);
        return round($amount * ($feePercentage / 100), 2);
    }
    
    public function handlePaymentSuccess(string $paymentIntentId): void
    {
        $payment = RevisionPayment::where('stripe_payment_intent_id', $paymentIntentId)->first();
        
        if (!$payment) {
            Log::warning('Payment success webhook for unknown payment intent', [
                'payment_intent_id' => $paymentIntentId,
            ]);
            return;
        }
        
        DB::beginTransaction();
        
        try {
            // Update payment status
            $payment->update([
                'status' => 'succeeded',
                'paid_at' => now(),
            ]);
            
            // Update project
            $payment->project->increment('additional_rounds_purchased');
            
            // Send confirmation notifications
            $this->notificationService->sendRevisionPaymentConfirmation($payment);
            
            Log::info('Revision payment processed successfully', [
                'payment_id' => $payment->id,
                'project_id' => $payment->project_id,
                'amount' => $payment->amount,
            ]);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process revision payment success', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

### Enhanced PitchWorkflowService Integration

```php
// Add revision round awareness to existing PitchWorkflowService

public function submitPitchForReview(Pitch $pitch, User $user, array $files = []): void
{
    // ... existing logic ...
    
    // Check if project has active revision round
    $activeRound = $pitch->project->revisionRounds()
        ->where('status', 'active')
        ->latest('round_number')
        ->first();
        
    if ($activeRound) {
        // Associate pitch submission with revision round
        $pitch->update([
            'revision_round_id' => $activeRound->id,
            'metadata' => array_merge($pitch->metadata ?? [], [
                'revision_context' => $activeRound->request_details,
                'round_number' => $activeRound->round_number,
            ]),
        ]);
    }
    
    // ... rest of existing logic ...
}
```

## Livewire Components

### Revision Round Manager

```php
<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\RevisionRound;
use App\Services\RevisionRoundService;
use Livewire\Component;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;

class RevisionRoundManager extends Component
{
    public Project $project;
    public $showAdditionalRevisionModal = false;
    public $additionalRevisionRequest = [
        'details' => '',
    ];
    public $selectedPaymentMethod = null;
    public $paymentMethods = [];
    
    protected $rules = [
        'additionalRevisionRequest.details' => 'required|string|min:10|max:1000',
        'selectedPaymentMethod' => 'required|exists:payment_methods,id',
    ];
    
    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadPaymentMethods();
    }
    
    public function approveProject(RevisionRoundService $service)
    {
        $this->authorize('update', $this->project);
        
        try {
            $service->approveProject($this->project, auth()->user());
            
            $this->project->refresh();
            
            Toaster::success('Project approved successfully! Comments are now locked.');
            
            $this->dispatch('project-approved');
            
        } catch (\Exception $e) {
            Toaster::error('Failed to approve project: ' . $e->getMessage());
        }
    }
    
    public function requestRevisions(RevisionRoundService $service)
    {
        $this->authorize('update', $this->project);
        
        $this->validate([
            'revisionDetails' => 'required|string|min:10|max:1000',
        ]);
        
        try {
            $service->requestRevision(
                $this->project,
                auth()->user(),
                $this->revisionDetails,
                false // Not additional, use included rounds first
            );
            
            $this->reset('revisionDetails');
            $this->project->refresh();
            
            Toaster::success('Revision requested successfully!');
            
            $this->dispatch('revision-requested');
            
        } catch (\Exception $e) {
            Toaster::error('Failed to request revision: ' . $e->getMessage());
        }
    }
    
    public function requestAdditionalRevisions()
    {
        $this->authorize('update', $this->project);
        
        $this->showAdditionalRevisionModal = true;
    }
    
    public function processAdditionalRevisionPayment(RevisionRoundService $service)
    {
        $this->validate();
        
        try {
            $round = $service->requestRevision(
                $this->project,
                auth()->user(),
                $this->additionalRevisionRequest['details'],
                true // Force additional round
            );
            
            // Handle payment intent creation and processing
            $this->redirectToPayment($round);
            
        } catch (\Exception $e) {
            Toaster::error('Failed to process request: ' . $e->getMessage());
        }
    }
    
    protected function redirectToPayment(RevisionRound $round)
    {
        // Redirect to Stripe checkout or handle payment confirmation
        return redirect()->route('revision.payment', [
            'round' => $round->id,
            'payment_intent' => $round->payment_intent_id,
        ]);
    }
    
    protected function loadPaymentMethods()
    {
        // Load user's saved payment methods from Stripe
        $this->paymentMethods = auth()->user()->paymentMethods ?? [];
    }
    
    public function getTotalCostProperty()
    {
        $baseAmount = $this->project->revisionPolicy->additional_round_price ?? 0;
        $platformFee = $this->getPlatformFeeProperty();
        
        return $baseAmount + $platformFee;
    }
    
    public function getPlatformFeeProperty()
    {
        $baseAmount = $this->project->revisionPolicy->additional_round_price ?? 0;
        $feePercentage = config('billing.platform_fee_percentage', 5.0);
        
        return round($baseAmount * ($feePercentage / 100), 2);
    }
    
    public function getPlatformFeePercentageProperty()
    {
        return config('billing.platform_fee_percentage', 5.0);
    }
    
    #[On('echo:project.{project.id},RevisionPaymentProcessed')]
    public function handlePaymentProcessed($data)
    {
        $this->project->refresh();
        $this->showAdditionalRevisionModal = false;
        $this->reset('additionalRevisionRequest');
        
        Toaster::success('Payment processed! You can now request additional revisions.');
    }
    
    public function render()
    {
        return view('livewire.project.revision-round-manager');
    }
}
```

### Revision Policy Settings Component

```php
<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\RevisionPolicy;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class RevisionPolicySettings extends Component
{
    public Project $project;
    public $revisionPolicy = [
        'included_rounds' => 2,
        'additional_round_price' => 150.00,
        'scope_guidelines' => '',
        'lock_on_approval' => true,
        'require_payment_for_additional' => true,
        'auto_invoice_additional' => false,
    ];
    
    protected $rules = [
        'revisionPolicy.included_rounds' => 'required|integer|min:0|max:10',
        'revisionPolicy.additional_round_price' => 'required|numeric|min:0|max:9999.99',
        'revisionPolicy.scope_guidelines' => 'nullable|string|max:1000',
        'revisionPolicy.lock_on_approval' => 'boolean',
        'revisionPolicy.require_payment_for_additional' => 'boolean',
        'revisionPolicy.auto_invoice_additional' => 'boolean',
    ];
    
    public function mount(Project $project)
    {
        $this->project = $project;
        
        if ($project->revisionPolicy) {
            $this->revisionPolicy = [
                'included_rounds' => $project->revisionPolicy->included_rounds,
                'additional_round_price' => $project->revisionPolicy->additional_round_price,
                'scope_guidelines' => $project->revisionPolicy->scope_guidelines,
                'lock_on_approval' => $project->revisionPolicy->lock_on_approval,
                'require_payment_for_additional' => $project->revisionPolicy->require_payment_for_additional,
                'auto_invoice_additional' => $project->revisionPolicy->auto_invoice_additional,
            ];
        }
    }
    
    public function saveRevisionPolicy()
    {
        $this->authorize('update', $this->project);
        
        $this->validate();
        
        try {
            RevisionPolicy::updateOrCreate(
                ['project_id' => $this->project->id],
                $this->revisionPolicy
            );
            
            $this->project->refresh();
            
            Toaster::success('Revision policy updated successfully!');
            
        } catch (\Exception $e) {
            Toaster::error('Failed to update revision policy: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.project.revision-policy-settings');
    }
}
```

## Integration with Existing Workflow

### Enhanced Comment System

```php
// Modify existing comment functionality to respect approval locks

public function addComment(string $content): void
{
    // Check if comments are locked due to approval
    if ($this->project->comments_locked && $this->project->approval_status === 'approved') {
        throw new \Exception('Comments are locked. Please request additional revisions to continue feedback.');
    }
    
    // ... existing comment logic ...
}
```

### Project Status Integration

```php
// Add revision round awareness to project status display

public function getStatusDisplayAttribute(): string
{
    return match($this->approval_status) {
        'pending' => 'Awaiting Initial Review',
        'in_revision' => 'Revision Round ' . $this->revisionRounds()->where('status', 'active')->latest()->first()?->round_number ?? 'Active',
        'approved' => 'Approved' . ($this->comments_locked ? ' (Locked)' : ''),
        'cancelled' => 'Cancelled',
        default => 'Unknown Status'
    };
}
```

## Stripe Integration

### Payment Intent Handling

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RevisionRoundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class RevisionPaymentWebhookController extends Controller
{
    public function handleStripeWebhook(Request $request, RevisionRoundService $service)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
            
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $service->handlePaymentSuccess($event->data->object->id);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailure($event->data->object->id);
                    break;
            }
            
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error('Revision payment webhook failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            
            return response()->json(['error' => 'Webhook failed'], 400);
        }
    }
    
    protected function handlePaymentFailure(string $paymentIntentId): void
    {
        $payment = \App\Models\RevisionPayment::where('stripe_payment_intent_id', $paymentIntentId)->first();
        
        if ($payment) {
            $payment->update(['status' => 'failed']);
            
            // Notify user of payment failure
            // Cancel the revision round if payment was required
            $payment->revisionRound->update(['status' => 'cancelled']);
        }
    }
}
```

## Testing Strategy

### Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\RevisionPolicy;
use App\Services\RevisionRoundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevisionRoundTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_creates_included_revision_round()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        RevisionPolicy::create([
            'project_id' => $project->id,
            'included_rounds' => 2,
            'additional_round_price' => 150.00,
        ]);
        
        $service = app(RevisionRoundService::class);
        $round = $service->requestRevision($project, $user, 'Please adjust the mix levels');
        
        $this->assertDatabaseHas('revision_rounds', [
            'project_id' => $project->id,
            'round_number' => 1,
            'round_type' => 'included',
            'charge_amount' => 0,
        ]);
        
        $this->assertEquals('in_revision', $project->fresh()->approval_status);
    }
    
    public function test_creates_additional_revision_round_with_payment()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'completed_revision_rounds' => 2,
        ]);
        
        RevisionPolicy::create([
            'project_id' => $project->id,
            'included_rounds' => 2,
            'additional_round_price' => 150.00,
        ]);
        
        $service = app(RevisionRoundService::class);
        $round = $service->requestRevision($project, $user, 'Need major changes', true);
        
        $this->assertDatabaseHas('revision_rounds', [
            'project_id' => $project->id,
            'round_type' => 'additional',
            'charge_amount' => 150.00,
        ]);
        
        $this->assertNotNull($round->payment_intent_id);
    }
    
    public function test_locks_comments_on_approval()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        RevisionPolicy::create([
            'project_id' => $project->id,
            'lock_on_approval' => true,
        ]);
        
        $service = app(RevisionRoundService::class);
        $service->approveProject($project, $user);
        
        $project->refresh();
        
        $this->assertEquals('approved', $project->approval_status);
        $this->assertTrue($project->comments_locked);
        $this->assertNotNull($project->approved_at);
    }
    
    public function test_prevents_comments_when_locked()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'approval_status' => 'approved',
            'comments_locked' => true,
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Comments are locked');
        
        // Attempt to add comment should fail
        $commentService = app(\App\Services\CommentService::class);
        $commentService->addComment($project, $user, 'This should fail');
    }
}
```

### Livewire Component Tests

```php
<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Project\RevisionRoundManager;
use App\Models\Project;
use App\Models\User;
use App\Models\RevisionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RevisionRoundManagerTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_approve_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $this->actingAs($user);
        
        Livewire::test(RevisionRoundManager::class, ['project' => $project])
            ->call('approveProject')
            ->assertDispatched('project-approved');
            
        $this->assertEquals('approved', $project->fresh()->approval_status);
    }
    
    public function test_shows_additional_revision_modal()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'approval_status' => 'approved',
        ]);
        
        RevisionPolicy::create([
            'project_id' => $project->id,
            'additional_round_price' => 150.00,
        ]);
        
        $this->actingAs($user);
        
        Livewire::test(RevisionRoundManager::class, ['project' => $project])
            ->call('requestAdditionalRevisions')
            ->assertSet('showAdditionalRevisionModal', true)
            ->assertSee('$150.00');
    }
}
```

## Implementation Steps

### Phase 1: Core Infrastructure (Week 1)
1. Create database migrations for revision policies, rounds, and payments
2. Implement basic `RevisionRoundService` with round creation logic
3. Extend existing project model with approval status fields
4. Set up Stripe integration for additional revision payments

### Phase 2: Workflow Integration (Week 2)
1. Integrate revision rounds with existing `PitchWorkflowService`
2. Implement comment locking mechanism
3. Add revision round awareness to project status displays
4. Create payment processing and webhook handling

### Phase 3: UI Components (Week 3)
1. Build `RevisionRoundManager` Livewire component
2. Create revision policy settings interface
3. Implement additional revision request modal with payment
4. Add revision progress tracking to project dashboard

### Phase 4: Payment & Billing (Week 4)
1. Complete Stripe integration with payment intents
2. Implement automatic invoicing for additional rounds
3. Add payment confirmation and failure handling
4. Create financial reporting for revision revenue

### Phase 5: Advanced Features (Week 5)
1. Add revision round analytics and reporting
2. Implement automated policy suggestions based on project type
3. Create revision history and audit trails
4. Add integration with accounting export features

## Business Benefits

### For Musicians/Clients
- Clear expectations about revision costs upfront
- Protection against unlimited scope creep
- Professional project boundaries
- Transparent pricing for additional work

### For Producers
- Fair compensation for additional work
- Protected time and effort investment
- Clear project completion criteria
- Reduced client management overhead

### For Platform
- Additional revenue stream through revision payments
- Higher project completion rates
- Improved user satisfaction through clear boundaries
- Professional workflow management

This implementation creates a professional revision management system that protects both parties while maintaining creative flexibility through a transparent, paid additional revision process.