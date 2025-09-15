# One-Click Archive + Invoice Implementation Plan

## Feature Overview

The One-Click Archive + Invoice feature streamlines project completion by automatically archiving deliverables, generating professional invoices, and sending summary emails to all stakeholders. This feature reduces administrative overhead for audio professionals while ensuring proper project closure and documentation.

### Core Functionality
- **Automated Project Archival**: Collect and package all project deliverables
- **Invoice Generation**: Create professional invoices with line items and tax calculations
- **Stakeholder Notifications**: Send completion summaries to clients and team members
- **Cloud Storage Integration**: Archive files to cost-effective storage tiers
- **Delivery Package Creation**: Generate downloadable packages with organized deliverables
- **Financial Integration**: Connect with existing payment and accounting systems

## Technical Architecture

### Database Schema

```sql
-- Project archival tracking and metadata
CREATE TABLE project_archives (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    archive_name VARCHAR(255) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    archive_type ENUM('standard', 'premium', 'custom') DEFAULT 'standard',
    total_files INT UNSIGNED DEFAULT 0,
    total_size_bytes BIGINT UNSIGNED DEFAULT 0,
    compressed_size_bytes BIGINT UNSIGNED NULL,
    archive_path VARCHAR(500) NULL,
    download_url VARCHAR(500) NULL,
    download_expires_at TIMESTAMP NULL,
    metadata JSON DEFAULT '{}',
    error_message TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_status (project_id, status),
    INDEX idx_user_archives (user_id, created_at),
    INDEX idx_download_expires (download_expires_at)
);

-- Archive file manifests and organization
CREATE TABLE project_archive_files (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    archive_id BIGINT UNSIGNED NOT NULL,
    original_file_id BIGINT UNSIGNED NULL,
    file_path VARCHAR(500) NOT NULL,
    original_filename VARCHAR(500) NOT NULL,
    archive_filename VARCHAR(500) NOT NULL,
    file_type ENUM('project_file', 'pitch_file', 'generated_file', 'metadata') NOT NULL,
    file_size_bytes BIGINT UNSIGNED NOT NULL,
    mime_type VARCHAR(100) NULL,
    category VARCHAR(100) NULL,
    description TEXT NULL,
    is_primary_deliverable BOOLEAN DEFAULT FALSE,
    sort_order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (archive_id) REFERENCES project_archives(id) ON DELETE CASCADE,
    INDEX idx_archive_type (archive_id, file_type),
    INDEX idx_primary_deliverables (archive_id, is_primary_deliverable)
);

-- Invoice generation and tracking
CREATE TABLE project_invoices (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    archive_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    invoice_date DATE NOT NULL,
    due_date DATE NULL,
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    currency VARCHAR(3) DEFAULT 'USD',
    subtotal_amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_terms VARCHAR(100) NULL,
    notes TEXT NULL,
    invoice_data JSON DEFAULT '{}',
    sent_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    payment_method VARCHAR(50) NULL,
    payment_reference VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (archive_id) REFERENCES project_archives(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_invoice (project_id),
    INDEX idx_client_invoices (client_id, invoice_date),
    INDEX idx_status_due (status, due_date),
    INDEX idx_invoice_number (invoice_number)
);

-- Invoice line items and services
CREATE TABLE project_invoice_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    invoice_id BIGINT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    quantity DECIMAL(8,2) DEFAULT 1.00,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    item_type ENUM('service', 'revision', 'rush_fee', 'discount', 'other') DEFAULT 'service',
    metadata JSON DEFAULT '{}',
    sort_order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (invoice_id) REFERENCES project_invoices(id) ON DELETE CASCADE,
    INDEX idx_invoice_items (invoice_id, sort_order)
);

-- Archive delivery configurations and templates
CREATE TABLE archive_delivery_templates (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    file_organization JSON NOT NULL,
    included_file_types JSON DEFAULT '[]',
    metadata_options JSON DEFAULT '{}',
    compression_level ENUM('none', 'standard', 'maximum') DEFAULT 'standard',
    delivery_method ENUM('download', 'email', 'cloud_sync') DEFAULT 'download',
    retention_days INT UNSIGNED DEFAULT 30,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_templates (user_id, is_active),
    INDEX idx_default_template (user_id, is_default)
);

-- Completion notification tracking
CREATE TABLE project_completion_notifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    archive_id BIGINT UNSIGNED NULL,
    invoice_id BIGINT UNSIGNED NULL,
    recipient_type ENUM('client', 'producer', 'team_member', 'external') NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) NULL,
    notification_type ENUM('completion_summary', 'archive_ready', 'invoice_sent') NOT NULL,
    subject VARCHAR(500) NOT NULL,
    message_content TEXT NOT NULL,
    status ENUM('pending', 'sent', 'delivered', 'bounced', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    opened_at TIMESTAMP NULL,
    clicked_at TIMESTAMP NULL,
    error_message TEXT NULL,
    metadata JSON DEFAULT '{}',
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (archive_id) REFERENCES project_archives(id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_id) REFERENCES project_invoices(id) ON DELETE SET NULL,
    INDEX idx_project_notifications (project_id, notification_type),
    INDEX idx_status_pending (status, created_at)
);
```

### Service Architecture

#### ProjectArchivalService
```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectArchive;
use App\Models\ProjectArchiveFile;
use App\Models\ArchiveDeliveryTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ProjectArchivalService
{
    public function createProjectArchive(
        Project $project,
        User $user,
        ?int $templateId = null,
        array $customOptions = []
    ): ProjectArchive {
        // Get or create default template
        $template = $templateId 
            ? ArchiveDeliveryTemplate::findOrFail($templateId)
            : $this->getDefaultTemplate($user);

        // Create archive record
        $archive = ProjectArchive::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'archive_name' => $this->generateArchiveName($project),
            'status' => 'pending',
            'archive_type' => $customOptions['type'] ?? 'standard',
            'metadata' => array_merge($template->metadata_options, $customOptions)
        ]);

        // Queue the archival process
        ProcessProjectArchive::dispatch($archive, $template);

        return $archive;
    }

    public function processArchive(ProjectArchive $archive, ArchiveDeliveryTemplate $template): void
    {
        try {
            $archive->update(['status' => 'processing']);

            // Collect all deliverable files
            $files = $this->collectProjectFiles($archive->project, $template);
            
            // Organize files according to template
            $organizedFiles = $this->organizeFiles($files, $template->file_organization);
            
            // Create archive structure
            $archivePath = $this->createArchiveStructure($archive, $organizedFiles, $template);
            
            // Compress if needed
            if ($template->compression_level !== 'none') {
                $archivePath = $this->compressArchive($archive, $archivePath, $template->compression_level);
            }
            
            // Generate download URL
            $downloadUrl = $this->generateDownloadUrl($archive, $archivePath, $template->retention_days);
            
            // Update archive record
            $archive->update([
                'status' => 'completed',
                'archive_path' => $archivePath,
                'download_url' => $downloadUrl,
                'download_expires_at' => now()->addDays($template->retention_days),
                'compressed_size_bytes' => Storage::disk('s3')->size($archivePath)
            ]);

            Log::info("Project archive created successfully", [
                'archive_id' => $archive->id,
                'project_id' => $archive->project_id,
                'file_count' => $archive->total_files
            ]);

        } catch (\Exception $e) {
            $archive->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            Log::error("Project archive failed", [
                'archive_id' => $archive->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function collectProjectFiles(Project $project, ArchiveDeliveryTemplate $template): array
    {
        $files = [];
        $includedTypes = $template->included_file_types;

        // Project files (original uploads)
        if (in_array('project_files', $includedTypes)) {
            foreach ($project->projectFiles as $file) {
                $files[] = [
                    'type' => 'project_file',
                    'model' => $file,
                    'path' => $file->file_path,
                    'original_name' => $file->original_filename,
                    'display_name' => $file->display_name,
                    'category' => 'source_material',
                    'is_primary' => true
                ];
            }
        }

        // Pitch files (final deliverables)
        if (in_array('pitch_files', $includedTypes)) {
            $approvedPitch = $project->approvedPitch;
            if ($approvedPitch) {
                foreach ($approvedPitch->pitchFiles as $file) {
                    $files[] = [
                        'type' => 'pitch_file',
                        'model' => $file,
                        'path' => $file->file_path,
                        'original_name' => $file->original_filename,
                        'display_name' => $file->display_name,
                        'category' => 'final_deliverable',
                        'is_primary' => true
                    ];
                }
            }
        }

        // Generated metadata files
        if (in_array('metadata', $includedTypes)) {
            $files = array_merge($files, $this->generateMetadataFiles($project));
        }

        // Additional files (contracts, notes, etc.)
        if (in_array('additional_files', $includedTypes)) {
            $files = array_merge($files, $this->collectAdditionalFiles($project));
        }

        return $files;
    }

    private function organizeFiles(array $files, array $organization): array
    {
        $organized = [];

        foreach ($files as $file) {
            $category = $file['category'];
            $folderPath = $organization[$category] ?? 'Other';
            
            // Replace placeholders in folder path
            $folderPath = $this->replacePlaceholders($folderPath, $file);
            
            $organized[$folderPath][] = $file;
        }

        return $organized;
    }

    private function createArchiveStructure(
        ProjectArchive $archive, 
        array $organizedFiles, 
        ArchiveDeliveryTemplate $template
    ): string {
        $tempDir = sys_get_temp_dir() . '/mixpitch_archive_' . $archive->id;
        $this->ensureDirectoryExists($tempDir);

        $totalFiles = 0;
        $totalSize = 0;

        foreach ($organizedFiles as $folderPath => $files) {
            $fullFolderPath = $tempDir . '/' . $folderPath;
            $this->ensureDirectoryExists($fullFolderPath);

            foreach ($files as $file) {
                $sourceFile = Storage::disk('s3')->path($file['path']);
                $targetFile = $fullFolderPath . '/' . $this->sanitizeFilename($file['display_name']);
                
                // Copy file to archive structure
                if (Storage::disk('s3')->exists($file['path'])) {
                    $content = Storage::disk('s3')->get($file['path']);
                    file_put_contents($targetFile, $content);
                    
                    $fileSize = filesize($targetFile);
                    $totalSize += $fileSize;
                    $totalFiles++;

                    // Record file in manifest
                    ProjectArchiveFile::create([
                        'archive_id' => $archive->id,
                        'original_file_id' => $file['model']->id ?? null,
                        'file_path' => $folderPath . '/' . basename($targetFile),
                        'original_filename' => $file['original_name'],
                        'archive_filename' => basename($targetFile),
                        'file_type' => $file['type'],
                        'file_size_bytes' => $fileSize,
                        'mime_type' => mime_content_type($targetFile),
                        'category' => $file['category'],
                        'is_primary_deliverable' => $file['is_primary'],
                        'description' => $file['description'] ?? null
                    ]);
                }
            }
        }

        // Update archive totals
        $archive->update([
            'total_files' => $totalFiles,
            'total_size_bytes' => $totalSize
        ]);

        return $tempDir;
    }

    private function compressArchive(ProjectArchive $archive, string $sourcePath, string $compressionLevel): string
    {
        $zipPath = sys_get_temp_dir() . '/archive_' . $archive->id . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception("Cannot create ZIP archive: {$zipPath}");
        }

        // Set compression method based on level
        $compressionMethod = match ($compressionLevel) {
            'none' => ZipArchive::CM_STORE,
            'standard' => ZipArchive::CM_DEFAULT,
            'maximum' => ZipArchive::CM_BZIP2,
            default => ZipArchive::CM_DEFAULT
        };

        $this->addDirectoryToZip($zip, $sourcePath, '', $compressionMethod);
        $zip->close();

        // Upload compressed archive to S3
        $s3Path = "archives/{$archive->id}/" . basename($zipPath);
        Storage::disk('s3')->putFileAs(
            dirname($s3Path),
            $zipPath,
            basename($s3Path)
        );

        // Clean up temporary files
        $this->cleanupDirectory($sourcePath);
        unlink($zipPath);

        return $s3Path;
    }

    private function generateDownloadUrl(ProjectArchive $archive, string $archivePath, int $retentionDays): string
    {
        $expiresAt = now()->addDays($retentionDays);
        
        return Storage::disk('s3')->temporaryUrl($archivePath, $expiresAt);
    }

    private function generateMetadataFiles(Project $project): array
    {
        $files = [];

        // Project information JSON
        $projectInfo = [
            'project_name' => $project->name,
            'created_at' => $project->created_at->toISOString(),
            'completed_at' => now()->toISOString(),
            'client' => $project->user->name,
            'producer' => $project->approvedPitch?->user->name,
            'workflow_type' => $project->workflow_type,
            'total_duration' => $project->pitches->sum('processing_duration_days'),
            'revision_count' => $project->pitchEvents->where('event_type', 'revision_requested')->count()
        ];

        $tempPath = sys_get_temp_dir() . '/project_info_' . $project->id . '.json';
        file_put_contents($tempPath, json_encode($projectInfo, JSON_PRETTY_PRINT));

        $files[] = [
            'type' => 'generated_file',
            'model' => null,
            'path' => $tempPath,
            'original_name' => 'project_info.json',
            'display_name' => 'Project Information.json',
            'category' => 'metadata',
            'is_primary' => false,
            'description' => 'Project metadata and completion details'
        ];

        // README file with project details
        $readmeContent = $this->generateProjectReadme($project);
        $readmePath = sys_get_temp_dir() . '/README_' . $project->id . '.txt';
        file_put_contents($readmePath, $readmeContent);

        $files[] = [
            'type' => 'generated_file',
            'model' => null,
            'path' => $readmePath,
            'original_name' => 'README.txt',
            'display_name' => 'README.txt',
            'category' => 'metadata',
            'is_primary' => false,
            'description' => 'Project overview and file organization'
        ];

        return $files;
    }

    private function generateProjectReadme(Project $project): string
    {
        $readme = "PROJECT: {$project->name}\n";
        $readme .= "=" . str_repeat("=", strlen($project->name)) . "\n\n";
        $readme .= "Client: {$project->user->name}\n";
        $readme .= "Created: {$project->created_at->format('F j, Y')}\n";
        $readme .= "Completed: " . now()->format('F j, Y') . "\n";
        
        if ($project->approvedPitch) {
            $readme .= "Producer: {$project->approvedPitch->user->name}\n";
        }
        
        $readme .= "\nPROJECT DESCRIPTION:\n";
        $readme .= $project->description ?: "No description provided.\n";
        
        $readme .= "\nFILE ORGANIZATION:\n";
        $readme .= "- Source_Material/: Original files uploaded by client\n";
        $readme .= "- Final_Deliverables/: Processed audio files from producer\n";
        $readme .= "- Project_Info/: Metadata and project documentation\n";
        
        $readme .= "\nNOTES:\n";
        $readme .= "This archive contains all files related to the completed project.\n";
        $readme .= "For support or questions, please contact the MixPitch team.\n";

        return $readme;
    }

    private function generateArchiveName(Project $project): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $project->name);
        $date = now()->format('Y-m-d');
        
        return "{$safeName}_Archive_{$date}";
    }

    private function getDefaultTemplate(User $user): ArchiveDeliveryTemplate
    {
        return ArchiveDeliveryTemplate::where('user_id', $user->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first() ?? $this->createDefaultTemplate($user);
    }

    private function createDefaultTemplate(User $user): ArchiveDeliveryTemplate
    {
        return ArchiveDeliveryTemplate::create([
            'user_id' => $user->id,
            'name' => 'Standard Archive',
            'description' => 'Default archive template with organized folder structure',
            'file_organization' => [
                'source_material' => 'Source_Material',
                'final_deliverable' => 'Final_Deliverables',
                'metadata' => 'Project_Info'
            ],
            'included_file_types' => ['project_files', 'pitch_files', 'metadata'],
            'metadata_options' => [
                'include_project_info' => true,
                'include_readme' => true,
                'include_file_manifest' => true
            ],
            'compression_level' => 'standard',
            'delivery_method' => 'download',
            'retention_days' => 30,
            'is_default' => true,
            'is_active' => true
        ]);
    }

    // Helper methods
    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);
    }

    private function addDirectoryToZip(ZipArchive $zip, string $source, string $destination, int $compressionMethod): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $zip->addEmptyDir($destination . $file->getFilename() . '/');
            } elseif ($file->isFile()) {
                $zip->addFile($file->getRealPath(), $destination . $file->getFilename());
                $zip->setCompressionName($destination . $file->getFilename(), $compressionMethod);
            }
        }
    }

    private function cleanupDirectory(string $path): void
    {
        if (is_dir($path)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $fileinfo->isDir() ? rmdir($fileinfo->getRealPath()) : unlink($fileinfo->getRealPath());
            }
            
            rmdir($path);
        }
    }

    private function replacePlaceholders(string $path, array $file): string
    {
        $placeholders = [
            '{project_name}' => $file['model']?->project->name ?? 'Unknown',
            '{date}' => now()->format('Y-m-d'),
            '{category}' => $file['category'],
            '{type}' => $file['type']
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $path);
    }

    private function collectAdditionalFiles(Project $project): array
    {
        // This would collect any additional files like contracts, agreements, etc.
        // Implementation depends on where these files are stored
        return [];
    }
}
```

#### ProjectInvoiceService
```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectInvoice;
use App\Models\ProjectInvoiceItem;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProjectInvoiceService
{
    public function generateProjectInvoice(
        Project $project,
        User $client,
        array $lineItems,
        array $options = []
    ): ProjectInvoice {
        $invoice = ProjectInvoice::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id, // Producer/studio
            'client_id' => $client->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'due_date' => $options['due_date'] ?? now()->addDays(30)->toDateString(),
            'currency' => $options['currency'] ?? 'USD',
            'payment_terms' => $options['payment_terms'] ?? 'Net 30',
            'notes' => $options['notes'] ?? null
        ]);

        $subtotal = 0;
        $sortOrder = 0;

        foreach ($lineItems as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $subtotal += $lineTotal;

            ProjectInvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'line_total' => $lineTotal,
                'item_type' => $item['type'] ?? 'service',
                'metadata' => $item['metadata'] ?? [],
                'sort_order' => $sortOrder++
            ]);
        }

        // Calculate tax if applicable
        $taxRate = $options['tax_rate'] ?? 0;
        $taxAmount = $subtotal * ($taxRate / 100);
        
        // Apply discount if applicable
        $discountAmount = $options['discount_amount'] ?? 0;
        
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        $invoice->update([
            'subtotal_amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount
        ]);

        return $invoice;
    }

    public function generateInvoicePDF(ProjectInvoice $invoice): string
    {
        $html = view('invoices.pdf', compact('invoice'))->render();
        
        // Use a PDF library like DomPDF or wkhtmltopdf
        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);
        
        $filename = "invoice_{$invoice->invoice_number}.pdf";
        $path = "invoices/{$invoice->id}/{$filename}";
        
        Storage::disk('s3')->put($path, $pdf->output());
        
        return $path;
    }

    public function sendInvoiceEmail(ProjectInvoice $invoice): void
    {
        $pdfPath = $this->generateInvoicePDF($invoice);
        
        // Send email with invoice attachment
        // Implementation would use Laravel Mail with PDF attachment
        
        $invoice->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }

    private function generateInvoiceNumber(): string
    {
        $lastInvoice = ProjectInvoice::latest('id')->first();
        $nextNumber = $lastInvoice ? (int)substr($lastInvoice->invoice_number, -6) + 1 : 1;
        
        return 'INV-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function calculateProjectCosts(Project $project): array
    {
        $baseCost = 0;
        $revisionCosts = 0;
        $rushFees = 0;

        // Base project cost
        if ($project->approvedPitch && $project->approvedPitch->quoted_price) {
            $baseCost = $project->approvedPitch->quoted_price;
        }

        // Revision costs (if applicable based on workflow)
        $revisionEvents = $project->pitchEvents()
            ->where('event_type', 'revision_requested')
            ->count();

        // Calculate revision fees based on project settings or defaults
        $includedRevisions = $project->included_revisions ?? 2;
        $extraRevisions = max(0, $revisionEvents - $includedRevisions);
        $revisionRate = $project->revision_rate ?? 50; // Default revision rate
        
        $revisionCosts = $extraRevisions * $revisionRate;

        // Rush fees if applicable
        if ($project->is_rush_order) {
            $rushFees = $baseCost * 0.5; // 50% rush fee
        }

        return [
            'base_cost' => $baseCost,
            'revision_costs' => $revisionCosts,
            'rush_fees' => $rushFees,
            'subtotal' => $baseCost + $revisionCosts + $rushFees,
            'extra_revisions' => $extraRevisions
        ];
    }
}
```

#### ProjectCompletionService
```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectArchive;
use App\Models\ProjectInvoice;
use App\Models\ProjectCompletionNotification;
use App\Jobs\SendProjectCompletionNotifications;

class ProjectCompletionService
{
    public function __construct(
        private ProjectArchivalService $archivalService,
        private ProjectInvoiceService $invoiceService
    ) {}

    public function completeProject(
        Project $project,
        array $options = []
    ): array {
        // Validate project can be completed
        $this->validateProjectCompletion($project);

        $results = [];

        try {
            // 1. Create project archive
            if ($options['create_archive'] ?? true) {
                $archive = $this->archivalService->createProjectArchive(
                    $project,
                    $project->user,
                    $options['archive_template_id'] ?? null,
                    $options['archive_options'] ?? []
                );
                $results['archive'] = $archive;
            }

            // 2. Generate invoice if balance is zero or invoice requested
            if ($this->shouldGenerateInvoice($project, $options)) {
                $invoice = $this->generateProjectInvoice($project, $options);
                $results['invoice'] = $invoice;
            }

            // 3. Update project status
            $project->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // 4. Send completion notifications
            if ($options['send_notifications'] ?? true) {
                $this->queueCompletionNotifications($project, $results);
            }

            return $results;

        } catch (\Exception $e) {
            Log::error("Project completion failed", [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function validateProjectCompletion(Project $project): void
    {
        // Check if project has approved pitch
        if (!$project->approvedPitch) {
            throw new \InvalidArgumentException('Project must have an approved pitch to be completed.');
        }

        // Check if project is already completed
        if ($project->status === 'completed') {
            throw new \InvalidArgumentException('Project is already completed.');
        }

        // Check if there are outstanding revisions
        $pendingRevisions = $project->pitchEvents()
            ->where('event_type', 'revision_requested')
            ->whereNull('resolved_at')
            ->exists();

        if ($pendingRevisions) {
            throw new \InvalidArgumentException('Project has pending revision requests.');
        }

        // Check payment status for paid projects
        if ($project->workflow_type !== 'contest' && $project->requires_payment) {
            $outstandingBalance = $this->calculateOutstandingBalance($project);
            if ($outstandingBalance > 0) {
                throw new \InvalidArgumentException('Project has outstanding payment balance.');
            }
        }
    }

    private function shouldGenerateInvoice(Project $project, array $options): bool
    {
        // Always generate invoice if explicitly requested
        if ($options['generate_invoice'] ?? false) {
            return true;
        }

        // Generate invoice for billable projects
        if ($project->workflow_type !== 'contest' && $project->approvedPitch?->quoted_price > 0) {
            return true;
        }

        return false;
    }

    private function generateProjectInvoice(Project $project, array $options): ProjectInvoice
    {
        $costs = $this->invoiceService->calculateProjectCosts($project);
        
        $lineItems = [];

        // Base service line item
        if ($costs['base_cost'] > 0) {
            $lineItems[] = [
                'description' => "Audio production services for '{$project->name}'",
                'quantity' => 1,
                'unit_price' => $costs['base_cost'],
                'type' => 'service'
            ];
        }

        // Revision charges
        if ($costs['extra_revisions'] > 0) {
            $lineItems[] = [
                'description' => "Additional revisions ({$costs['extra_revisions']} x $" . ($project->revision_rate ?? 50) . ")",
                'quantity' => $costs['extra_revisions'],
                'unit_price' => $project->revision_rate ?? 50,
                'type' => 'revision'
            ];
        }

        // Rush fees
        if ($costs['rush_fees'] > 0) {
            $lineItems[] = [
                'description' => 'Rush delivery fee (50%)',
                'quantity' => 1,
                'unit_price' => $costs['rush_fees'],
                'type' => 'rush_fee'
            ];
        }

        $invoiceOptions = array_merge([
            'due_date' => now()->addDays(30)->toDateString(),
            'payment_terms' => 'Net 30',
            'tax_rate' => $options['tax_rate'] ?? 0,
            'notes' => "Invoice for completed project: {$project->name}"
        ], $options['invoice_options'] ?? []);

        return $this->invoiceService->generateProjectInvoice(
            $project,
            $project->user, // Client
            $lineItems,
            $invoiceOptions
        );
    }

    private function queueCompletionNotifications(Project $project, array $results): void
    {
        $notifications = [];

        // Notify client
        $notifications[] = [
            'recipient_type' => 'client',
            'recipient_email' => $project->user->email,
            'recipient_name' => $project->user->name,
            'notification_type' => 'completion_summary'
        ];

        // Notify producer
        if ($project->approvedPitch) {
            $notifications[] = [
                'recipient_type' => 'producer',
                'recipient_email' => $project->approvedPitch->user->email,
                'recipient_name' => $project->approvedPitch->user->name,
                'notification_type' => 'completion_summary'
            ];
        }

        // Create notification records
        foreach ($notifications as $notification) {
            ProjectCompletionNotification::create([
                'project_id' => $project->id,
                'archive_id' => $results['archive']->id ?? null,
                'invoice_id' => $results['invoice']->id ?? null,
                'recipient_type' => $notification['recipient_type'],
                'recipient_email' => $notification['recipient_email'],
                'recipient_name' => $notification['recipient_name'],
                'notification_type' => $notification['notification_type'],
                'subject' => $this->generateNotificationSubject($project, $notification['notification_type']),
                'message_content' => $this->generateNotificationContent($project, $notification, $results),
                'status' => 'pending'
            ]);
        }

        // Queue notification sending
        SendProjectCompletionNotifications::dispatch($project);
    }

    private function generateNotificationSubject(Project $project, string $type): string
    {
        return match ($type) {
            'completion_summary' => "Project Completed: {$project->name}",
            'archive_ready' => "Archive Ready: {$project->name}",
            'invoice_sent' => "Invoice: {$project->name}",
            default => "Project Update: {$project->name}"
        };
    }

    private function generateNotificationContent(Project $project, array $notification, array $results): string
    {
        $content = "Dear {$notification['recipient_name']},\n\n";
        $content .= "We're pleased to inform you that the project '{$project->name}' has been completed successfully.\n\n";

        if (isset($results['archive'])) {
            $content .= "Project Archive:\n";
            $content .= "Your project files have been organized and packaged for download.\n";
            $content .= "Download Link: {$results['archive']->download_url}\n";
            $content .= "Available until: {$results['archive']->download_expires_at->format('F j, Y')}\n\n";
        }

        if (isset($results['invoice'])) {
            $content .= "Invoice Information:\n";
            $content .= "Invoice Number: {$results['invoice']->invoice_number}\n";
            $content .= "Amount: \${$results['invoice']->total_amount}\n";
            $content .= "Due Date: {$results['invoice']->due_date}\n\n";
        }

        $content .= "Project Summary:\n";
        $content .= "- Client: {$project->user->name}\n";
        if ($project->approvedPitch) {
            $content .= "- Producer: {$project->approvedPitch->user->name}\n";
        }
        $content .= "- Completed: " . now()->format('F j, Y') . "\n";
        $content .= "- Total Duration: " . $project->created_at->diffInDays(now()) . " days\n\n";

        $content .= "Thank you for using MixPitch!\n\n";
        $content .= "Best regards,\n";
        $content .= "The MixPitch Team";

        return $content;
    }

    private function calculateOutstandingBalance(Project $project): float
    {
        // Calculate total project cost
        $totalCost = $this->invoiceService->calculateProjectCosts($project)['subtotal'];
        
        // Calculate total payments received
        $paidAmount = $project->transactions()
            ->where('type', 'payment')
            ->where('status', 'completed')
            ->sum('amount') / 100; // Convert from cents

        return max(0, $totalCost - $paidAmount);
    }
}
```

## UI Implementation

### One-Click Complete Component
```php
<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\ArchiveDeliveryTemplate;
use App\Services\ProjectCompletionService;
use Livewire\Component;

class OneClickComplete extends Component
{
    public Project $project;
    public bool $showCompletionModal = false;
    public bool $createArchive = true;
    public bool $generateInvoice = true;
    public bool $sendNotifications = true;
    public ?int $selectedTemplateId = null;
    public array $completionOptions = [];
    public bool $isProcessing = false;

    protected $rules = [
        'createArchive' => 'boolean',
        'generateInvoice' => 'boolean', 
        'sendNotifications' => 'boolean',
        'selectedTemplateId' => 'nullable|exists:archive_delivery_templates,id'
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadDefaultOptions();
    }

    public function initiateCompletion()
    {
        // Check if project can be completed
        if (!$this->canCompleteProject()) {
            $this->addError('completion', 'Project cannot be completed at this time. Please check all requirements.');
            return;
        }

        $this->showCompletionModal = true;
    }

    public function completeProject(ProjectCompletionService $completionService)
    {
        $this->validate();
        $this->isProcessing = true;

        try {
            $options = [
                'create_archive' => $this->createArchive,
                'generate_invoice' => $this->generateInvoice,
                'send_notifications' => $this->sendNotifications,
                'archive_template_id' => $this->selectedTemplateId,
                'archive_options' => $this->completionOptions['archive'] ?? [],
                'invoice_options' => $this->completionOptions['invoice'] ?? []
            ];

            $results = $completionService->completeProject($this->project, $options);

            $this->showCompletionModal = false;
            $this->isProcessing = false;

            $this->dispatch('project-completed', [
                'message' => 'Project completed successfully!',
                'results' => $results
            ]);

            // Redirect to completed project view
            return redirect()->route('projects.show', $this->project);

        } catch (\Exception $e) {
            $this->isProcessing = false;
            $this->addError('completion', 'Completion failed: ' . $e->getMessage());
        }
    }

    public function estimateArchiveSize()
    {
        $totalSize = 0;
        
        // Calculate size of project files
        foreach ($this->project->projectFiles as $file) {
            $totalSize += $file->file_size_bytes ?? 0;
        }
        
        // Calculate size of pitch files
        if ($this->project->approvedPitch) {
            foreach ($this->project->approvedPitch->pitchFiles as $file) {
                $totalSize += $file->file_size_bytes ?? 0;
            }
        }

        return [
            'total_files' => $this->project->projectFiles->count() + 
                            ($this->project->approvedPitch?->pitchFiles->count() ?? 0),
            'total_size_mb' => round($totalSize / (1024 * 1024), 2),
            'estimated_download_size_mb' => round(($totalSize * 0.7) / (1024 * 1024), 2) // Assuming 30% compression
        ];
    }

    private function canCompleteProject(): bool
    {
        // Check basic requirements
        if (!$this->project->approvedPitch) {
            return false;
        }

        if ($this->project->status === 'completed') {
            return false;
        }

        // Check for pending revisions
        $pendingRevisions = $this->project->pitchEvents()
            ->where('event_type', 'revision_requested')
            ->whereNull('resolved_at')
            ->exists();

        return !$pendingRevisions;
    }

    private function loadDefaultOptions()
    {
        // Set default completion options based on project type
        $this->generateInvoice = $this->project->workflow_type !== 'contest';
        
        // Load user's default archive template
        $defaultTemplate = ArchiveDeliveryTemplate::where('user_id', auth()->id())
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        $this->selectedTemplateId = $defaultTemplate?->id;
    }

    public function render()
    {
        $archiveTemplates = ArchiveDeliveryTemplate::where('user_id', auth()->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $archiveEstimate = $this->estimateArchiveSize();

        return view('livewire.project.one-click-complete', [
            'archiveTemplates' => $archiveTemplates,
            'archiveEstimate' => $archiveEstimate
        ]);
    }
}
```

### Blade Template
```blade
<div>
    {{-- Completion Trigger Button --}}
    @if($canCompleteProject())
        <flux:button 
            wire:click="initiateCompletion"
            variant="primary"
            size="lg"
            class="w-full"
            :disabled="$isProcessing"
        >
            @if($isProcessing)
                <flux:icon icon="arrow-path" class="w-5 h-5 animate-spin" />
                Processing...
            @else
                <flux:icon icon="check-circle" class="w-5 h-5" />
                Complete Project & Archive
            @endif
        </flux:button>
    @else
        <flux:callout variant="warning">
            <flux:icon icon="exclamation-triangle" class="w-5 h-5" />
            <strong>Project Not Ready for Completion</strong>
            <p>Please ensure all revisions are complete and the project has been approved.</p>
        </flux:callout>
    @endif

    <flux:error name="completion" />

    {{-- Completion Configuration Modal --}}
    @if($showCompletionModal)
        <flux:modal wire:model="showCompletionModal" size="2xl">
            <flux:modal.header>
                <flux:heading>Complete Project: {{ $project->name }}</flux:heading>
            </flux:modal.header>
            
            <flux:modal.body>
                <div class="space-y-6">
                    {{-- Project Summary --}}
                    <flux:card>
                        <flux:card.header>
                            <flux:heading size="base">Project Summary</flux:heading>
                        </flux:card.header>
                        <flux:card.body>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium">Client:</span> {{ $project->user->name }}
                                </div>
                                <div>
                                    <span class="font-medium">Producer:</span> 
                                    {{ $project->approvedPitch?->user->name ?? 'N/A' }}
                                </div>
                                <div>
                                    <span class="font-medium">Duration:</span> 
                                    {{ $project->created_at->diffInDays(now()) }} days
                                </div>
                                <div>
                                    <span class="font-medium">Files:</span> 
                                    {{ $archiveEstimate['total_files'] }} files 
                                    ({{ $archiveEstimate['total_size_mb'] }}MB)
                                </div>
                            </div>
                        </flux:card.body>
                    </flux:card>

                    {{-- Archive Configuration --}}
                    <div class="space-y-4">
                        <flux:field>
                            <flux:checkbox wire:model.live="createArchive">
                                <span class="font-medium">Create Project Archive</span>
                                <flux:description>
                                    Package all project files into an organized, downloadable archive
                                </flux:description>
                            </flux:checkbox>
                        </flux:field>

                        @if($createArchive)
                            <div class="ml-6 space-y-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <flux:field>
                                    <flux:label>Archive Template</flux:label>
                                    <flux:select wire:model="selectedTemplateId">
                                        <option value="">Default Organization</option>
                                        @foreach($archiveTemplates as $template)
                                            <option value="{{ $template->id }}">
                                                {{ $template->name }}
                                                @if($template->is_default) (Default) @endif
                                            </option>
                                        @endforeach
                                    </flux:select>
                                    <flux:description>
                                        Choose how files should be organized in the archive
                                    </flux:description>
                                </flux:field>

                                <div class="text-sm text-gray-600">
                                    <div class="flex items-center justify-between">
                                        <span>Estimated archive size:</span>
                                        <span class="font-medium">{{ $archiveEstimate['estimated_download_size_mb'] }}MB</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Download availability:</span>
                                        <span class="font-medium">30 days</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Invoice Configuration --}}
                    <div class="space-y-4">
                        <flux:field>
                            <flux:checkbox wire:model.live="generateInvoice">
                                <span class="font-medium">Generate Invoice</span>
                                <flux:description>
                                    Create a professional invoice for project services
                                </flux:description>
                            </flux:checkbox>
                        </flux:field>

                        @if($generateInvoice)
                            <div class="ml-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                @php
                                    $costs = app(App\Services\ProjectInvoiceService::class)->calculateProjectCosts($project);
                                @endphp
                                
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span>Base Service:</span>
                                        <span>${{ number_format($costs['base_cost'], 2) }}</span>
                                    </div>
                                    
                                    @if($costs['revision_costs'] > 0)
                                        <div class="flex justify-between">
                                            <span>Extra Revisions ({{ $costs['extra_revisions'] }}):</span>
                                            <span>${{ number_format($costs['revision_costs'], 2) }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($costs['rush_fees'] > 0)
                                        <div class="flex justify-between">
                                            <span>Rush Fee:</span>
                                            <span>${{ number_format($costs['rush_fees'], 2) }}</span>
                                        </div>
                                    @endif
                                    
                                    <div class="border-t pt-2 font-medium flex justify-between">
                                        <span>Total:</span>
                                        <span>${{ number_format($costs['subtotal'], 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Notification Configuration --}}
                    <flux:field>
                        <flux:checkbox wire:model="sendNotifications">
                            <span class="font-medium">Send Completion Notifications</span>
                            <flux:description>
                                Email project completion summary to client and producer
                            </flux:description>
                        </flux:checkbox>
                    </flux:field>
                </div>
            </flux:modal.body>
            
            <flux:modal.footer>
                <flux:button 
                    wire:click="$set('showCompletionModal', false)" 
                    variant="outline"
                    :disabled="$isProcessing"
                >
                    Cancel
                </flux:button>
                
                <flux:button 
                    wire:click="completeProject"
                    variant="primary"
                    :disabled="$isProcessing"
                >
                    @if($isProcessing)
                        <flux:icon icon="arrow-path" class="w-4 h-4 animate-spin" />
                        Processing...
                    @else
                        <flux:icon icon="check-circle" class="w-4 h-4" />
                        Complete Project
                    @endif
                </flux:button>
            </flux:modal.footer>
        </flux:modal>
    @endif
</div>

@script
<script>
    $wire.on('project-completed', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: {
                type: 'success',
                message: data.message,
                duration: 5000
            }
        }));

        // Show completion celebration
        setTimeout(() => {
            window.dispatchEvent(new CustomEvent('project-celebration', {
                detail: { project: '{{ $project->name }}' }
            }));
        }, 1000);
    });
</script>
@endscript
```

### Archive Template Manager Component
```php
<?php

namespace App\Livewire\Settings;

use App\Models\ArchiveDeliveryTemplate;
use Livewire\Component;

class ArchiveTemplateManager extends Component
{
    public $templates = [];
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public ?ArchiveDeliveryTemplate $editingTemplate = null;
    
    public array $templateForm = [
        'name' => '',
        'description' => '',
        'file_organization' => [
            'source_material' => 'Source_Material',
            'final_deliverable' => 'Final_Deliverables', 
            'metadata' => 'Project_Info'
        ],
        'included_file_types' => ['project_files', 'pitch_files', 'metadata'],
        'compression_level' => 'standard',
        'retention_days' => 30,
        'is_default' => false
    ];

    protected $rules = [
        'templateForm.name' => 'required|string|max:255',
        'templateForm.description' => 'nullable|string|max:500',
        'templateForm.compression_level' => 'required|in:none,standard,maximum',
        'templateForm.retention_days' => 'required|integer|min:1|max:365',
        'templateForm.is_default' => 'boolean'
    ];

    public function mount()
    {
        $this->loadTemplates();
    }

    public function createTemplate()
    {
        $this->validate();

        // If setting as default, unset other defaults
        if ($this->templateForm['is_default']) {
            ArchiveDeliveryTemplate::where('user_id', auth()->id())
                ->update(['is_default' => false]);
        }

        ArchiveDeliveryTemplate::create(array_merge(
            $this->templateForm,
            ['user_id' => auth()->id()]
        ));

        $this->reset(['showCreateModal', 'templateForm']);
        $this->loadTemplates();

        $this->dispatch('template-created', [
            'message' => 'Archive template created successfully!'
        ]);
    }

    public function editTemplate(int $templateId)
    {
        $this->editingTemplate = ArchiveDeliveryTemplate::where('user_id', auth()->id())
            ->findOrFail($templateId);

        $this->templateForm = [
            'name' => $this->editingTemplate->name,
            'description' => $this->editingTemplate->description,
            'file_organization' => $this->editingTemplate->file_organization,
            'included_file_types' => $this->editingTemplate->included_file_types,
            'compression_level' => $this->editingTemplate->compression_level,
            'retention_days' => $this->editingTemplate->retention_days,
            'is_default' => $this->editingTemplate->is_default
        ];

        $this->showEditModal = true;
    }

    public function updateTemplate()
    {
        $this->validate();

        // If setting as default, unset other defaults
        if ($this->templateForm['is_default']) {
            ArchiveDeliveryTemplate::where('user_id', auth()->id())
                ->where('id', '!=', $this->editingTemplate->id)
                ->update(['is_default' => false]);
        }

        $this->editingTemplate->update($this->templateForm);

        $this->reset(['showEditModal', 'editingTemplate', 'templateForm']);
        $this->loadTemplates();

        $this->dispatch('template-updated', [
            'message' => 'Archive template updated successfully!'
        ]);
    }

    public function deleteTemplate(int $templateId)
    {
        $template = ArchiveDeliveryTemplate::where('user_id', auth()->id())
            ->findOrFail($templateId);

        $template->delete();
        $this->loadTemplates();

        $this->dispatch('template-deleted', [
            'message' => 'Archive template deleted successfully!'
        ]);
    }

    private function loadTemplates()
    {
        $this->templates = ArchiveDeliveryTemplate::where('user_id', auth()->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.settings.archive-template-manager');
    }
}
```

## Testing Strategy

### Feature Tests
```php
<?php

namespace Tests\Feature\ProjectCompletion;

use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\ProjectFile;
use App\Models\PitchFile;
use App\Services\ProjectCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_complete_project_with_archive_and_invoice(): void
    {
        Storage::fake('s3');
        
        $client = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->for($client)->create(['status' => 'in_progress']);
        
        $pitch = Pitch::factory()->for($producer)->for($project)->create([
            'status' => 'approved',
            'quoted_price' => 500
        ]);
        
        $project->update(['approved_pitch_id' => $pitch->id]);

        // Add some files
        ProjectFile::factory()->for($project)->create();
        PitchFile::factory()->for($pitch)->create();

        $service = new ProjectCompletionService(
            app(ProjectArchivalService::class),
            app(ProjectInvoiceService::class)
        );

        $results = $service->completeProject($project, [
            'create_archive' => true,
            'generate_invoice' => true,
            'send_notifications' => false // Skip notifications in test
        ]);

        $this->assertEquals('completed', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->completed_at);
        $this->assertArrayHasKey('archive', $results);
        $this->assertArrayHasKey('invoice', $results);
        
        $this->assertDatabaseHas('project_archives', [
            'project_id' => $project->id,
            'status' => 'pending' // Will be processed by job
        ]);
        
        $this->assertDatabaseHas('project_invoices', [
            'project_id' => $project->id,
            'total_amount' => 500
        ]);
    }

    public function test_cannot_complete_project_without_approved_pitch(): void
    {
        $client = User::factory()->create();
        $project = Project::factory()->for($client)->create(['status' => 'pending']);

        $service = new ProjectCompletionService(
            app(ProjectArchivalService::class),
            app(ProjectInvoiceService::class)
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project must have an approved pitch to be completed.');

        $service->completeProject($project);
    }

    public function test_cannot_complete_already_completed_project(): void
    {
        $client = User::factory()->create();
        $project = Project::factory()->for($client)->create([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        $service = new ProjectCompletionService(
            app(ProjectArchivalService::class),
            app(ProjectInvoiceService::class)
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project is already completed.');

        $service->completeProject($project);
    }

    public function test_invoice_calculation_includes_revisions_and_fees(): void
    {
        $client = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->for($client)->create([
            'revision_rate' => 75,
            'is_rush_order' => true
        ]);
        
        $pitch = Pitch::factory()->for($producer)->for($project)->create([
            'status' => 'approved',
            'quoted_price' => 1000
        ]);
        
        $project->update(['approved_pitch_id' => $pitch->id]);

        // Add revision events
        $project->pitchEvents()->create([
            'event_type' => 'revision_requested',
            'user_id' => $client->id,
            'metadata' => ['revision_number' => 1]
        ]);
        
        $project->pitchEvents()->create([
            'event_type' => 'revision_requested',
            'user_id' => $client->id,
            'metadata' => ['revision_number' => 2]
        ]);

        $invoiceService = app(ProjectInvoiceService::class);
        $costs = $invoiceService->calculateProjectCosts($project);

        $this->assertEquals(1000, $costs['base_cost']);
        $this->assertEquals(0, $costs['revision_costs']); // First 2 included
        $this->assertEquals(500, $costs['rush_fees']); // 50% of base
        $this->assertEquals(1500, $costs['subtotal']);
    }

    public function test_archive_organizes_files_correctly(): void
    {
        Storage::fake('s3');
        
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        
        // Create test files
        Storage::disk('s3')->put('project-files/test1.wav', 'fake content');
        Storage::disk('s3')->put('pitch-files/test2.wav', 'fake content');
        
        $projectFile = ProjectFile::factory()->for($project)->create([
            'file_path' => 'project-files/test1.wav',
            'original_filename' => 'test1.wav'
        ]);

        $service = app(ProjectArchivalService::class);
        $archive = $service->createProjectArchive($project, $user);

        $this->assertDatabaseHas('project_archives', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'pending'
        ]);
    }
}
```

### Unit Tests
```php
<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Pitch;
use App\Services\ProjectInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_unique_invoice_numbers(): void
    {
        $service = new ProjectInvoiceService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('generateInvoiceNumber');
        $method->setAccessible(true);

        $invoice1 = $method->invoke($service);
        $invoice2 = $method->invoke($service);

        $this->assertNotEquals($invoice1, $invoice2);
        $this->assertMatchesRegularExpression('/INV-\d{6}/', $invoice1);
    }

    public function test_calculates_project_costs_correctly(): void
    {
        $project = Project::factory()->make([
            'revision_rate' => 50,
            'is_rush_order' => false
        ]);

        $pitch = Pitch::factory()->make(['quoted_price' => 500]);
        $project->setRelation('approvedPitch', $pitch);
        
        // Mock pitch events for revisions
        $project->setRelation('pitchEvents', collect([
            (object)['event_type' => 'revision_requested'],
            (object)['event_type' => 'revision_requested'],
            (object)['event_type' => 'revision_requested'], // 3 total, 1 extra
        ]));

        $service = new ProjectInvoiceService();
        $costs = $service->calculateProjectCosts($project);

        $this->assertEquals(500, $costs['base_cost']);
        $this->assertEquals(50, $costs['revision_costs']); // 1 extra × $50
        $this->assertEquals(0, $costs['rush_fees']);
        $this->assertEquals(550, $costs['subtotal']);
        $this->assertEquals(1, $costs['extra_revisions']);
    }

    public function test_applies_rush_fee_correctly(): void
    {
        $project = Project::factory()->make(['is_rush_order' => true]);
        $pitch = Pitch::factory()->make(['quoted_price' => 1000]);
        $project->setRelation('approvedPitch', $pitch);
        $project->setRelation('pitchEvents', collect());

        $service = new ProjectInvoiceService();
        $costs = $service->calculateProjectCosts($project);

        $this->assertEquals(1000, $costs['base_cost']);
        $this->assertEquals(500, $costs['rush_fees']); // 50% of base
        $this->assertEquals(1500, $costs['subtotal']);
    }
}
```

## Implementation Steps

### Phase 1: Core Archival System (Week 1)
1. **Database Setup**
   - Create archival and invoice tables
   - Add completion notification system
   - Set up archive template management

2. **Archival Service**
   - Implement file collection and organization
   - Create ZIP compression system
   - Add S3 storage integration with temporary URLs

3. **Background Processing**
   - Create archive processing job
   - Implement file transfer and compression
   - Add error handling and retry logic

### Phase 2: Invoice Generation (Week 2)
1. **Invoice Service**
   - Cost calculation algorithms
   - PDF generation with professional templates
   - Integration with existing payment system

2. **Completion Workflow**
   - Project validation and completion logic
   - Automatic archive and invoice generation
   - Status update and workflow integration

3. **Email Notifications**
   - Completion summary emails
   - Archive download notifications
   - Invoice delivery system

### Phase 3: UI Implementation (Week 3)
1. **One-Click Component**
   - Project completion interface
   - Configuration options and validation
   - Real-time progress tracking

2. **Archive Management**
   - Template creation and editing
   - Archive history and download management
   - Storage usage tracking

3. **Integration Points**
   - Connect with existing project workflow
   - Add to project management interface
   - Dashboard integration

### Phase 4: Polish and Optimization (Week 4)
1. **Performance Optimization**
   - Large file handling improvements
   - Parallel processing for archives
   - Storage tier optimization

2. **Advanced Features**
   - Custom archive organization
   - Automated retention policies
   - Bulk project completion

3. **Testing and Validation**
   - Comprehensive test coverage
   - Load testing for large projects
   - End-to-end workflow validation

## Security Considerations

### File Security
- **Access Control**: Temporary URLs with expiration for archive downloads
- **Storage Isolation**: User-specific archive paths and permissions
- **Virus Scanning**: Integration with existing ClamAV scanning
- **Audit Trail**: Complete logging of archive creation and access

### Financial Security
- **Invoice Integrity**: Cryptographic signatures for invoice authenticity
- **Payment Validation**: Balance verification before completion
- **Tax Compliance**: Proper tax calculation and reporting
- **Fraud Prevention**: Completion validation and approval workflows

### Data Privacy
- **Archive Retention**: Automatic cleanup of expired archives
- **Personal Data**: GDPR-compliant handling of client information
- **Secure Deletion**: Proper removal of sensitive data
- **Access Logging**: Complete audit trail of archive access

This comprehensive implementation plan provides professional project completion capabilities while maintaining MixPitch's focus on workflow efficiency and user experience.