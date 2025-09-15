# 16. Royalty & Rights Notes Implementation Plan

## Feature Overview

Royalty & Rights Notes provide large studios with a comprehensive system to track royalty splits, ownership details, and rights information for each project. This feature enables proper documentation of contributor percentages, detailed notes about ownership arrangements, and seamless export capabilities for legal and accounting purposes.

### Core Functionality
- **Royalty Split Management**: Define percentage splits among contributors
- **Rights Documentation**: Track publishing, mechanical, and performance rights
- **Contributor Database**: Maintain detailed contributor information and roles
- **Export Integration**: Include royalty data in project archives and deliverables
- **Validation System**: Ensure splits total 100% with comprehensive validation
- **Historical Tracking**: Maintain version history of royalty changes
- **Template System**: Create reusable split templates for common arrangements

## Database Schema

### Royalty Splits Table
```sql
CREATE TABLE royalty_splits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    contributor_name VARCHAR(255) NOT NULL,
    contributor_email VARCHAR(255) NULL,
    contributor_role ENUM('songwriter', 'producer', 'performer', 'engineer', 'publisher', 'label', 'other') NOT NULL,
    percentage DECIMAL(5,2) NOT NULL CHECK (percentage >= 0 AND percentage <= 100),
    split_type ENUM('publishing', 'mechanical', 'performance', 'master', 'sync') NOT NULL DEFAULT 'publishing',
    notes TEXT NULL,
    contact_info JSON NULL,
    is_primary_writer BOOLEAN NOT NULL DEFAULT false,
    is_verified BOOLEAN NOT NULL DEFAULT false,
    created_by BIGINT UNSIGNED NOT NULL,
    verified_by BIGINT UNSIGNED NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    INDEX idx_royalty_splits_project_id (project_id),
    INDEX idx_royalty_splits_contributor_email (contributor_email),
    INDEX idx_royalty_splits_split_type (split_type)
);
```

### Rights Information Table
```sql
CREATE TABLE project_rights (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    rights_type ENUM('publishing', 'mechanical', 'performance', 'synchronization', 'master', 'neighboring') NOT NULL,
    ownership_details TEXT NOT NULL,
    territory VARCHAR(255) NOT NULL DEFAULT 'worldwide',
    duration VARCHAR(255) NULL,
    restrictions TEXT NULL,
    registration_info JSON NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_project_rights_project_id (project_id),
    INDEX idx_project_rights_type (rights_type)
);
```

### Royalty Templates Table
```sql
CREATE TABLE royalty_templates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    template_data JSON NOT NULL,
    is_public BOOLEAN NOT NULL DEFAULT false,
    usage_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_royalty_templates_user_id (user_id),
    INDEX idx_royalty_templates_public (is_public)
);
```

### Royalty History Table
```sql
CREATE TABLE royalty_split_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    action ENUM('created', 'updated', 'deleted', 'verified') NOT NULL,
    before_data JSON NULL,
    after_data JSON NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    change_reason TEXT NULL,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id),
    INDEX idx_royalty_history_project_id (project_id),
    INDEX idx_royalty_history_occurred_at (occurred_at)
);
```

## Service Architecture

### RoyaltySplitService
```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\RoyaltySplit;
use App\Models\RoyaltyTemplate;
use App\Models\RoyaltySplitHistory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoyaltySplitService
{
    public function createRoyaltySplit(Project $project, array $data, User $createdBy): RoyaltySplit
    {
        $this->validateSplitData($data);
        
        DB::beginTransaction();
        
        try {
            $royaltySplit = RoyaltySplit::create([
                'project_id' => $project->id,
                'contributor_name' => $data['contributor_name'],
                'contributor_email' => $data['contributor_email'] ?? null,
                'contributor_role' => $data['contributor_role'],
                'percentage' => $data['percentage'],
                'split_type' => $data['split_type'] ?? 'publishing',
                'notes' => $data['notes'] ?? null,
                'contact_info' => $data['contact_info'] ?? null,
                'is_primary_writer' => $data['is_primary_writer'] ?? false,
                'created_by' => $createdBy->id,
            ]);
            
            $this->validateTotalPercentage($project, $royaltySplit->split_type);
            
            $this->recordHistory($project, 'created', null, $royaltySplit->toArray(), $createdBy, $data['change_reason'] ?? null);
            
            DB::commit();
            
            return $royaltySplit;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function updateRoyaltySplit(RoyaltySplit $royaltySplit, array $data, User $updatedBy): RoyaltySplit
    {
        $this->validateSplitData($data);
        
        DB::beginTransaction();
        
        try {
            $beforeData = $royaltySplit->toArray();
            
            $royaltySplit->update([
                'contributor_name' => $data['contributor_name'],
                'contributor_email' => $data['contributor_email'] ?? null,
                'contributor_role' => $data['contributor_role'],
                'percentage' => $data['percentage'],
                'split_type' => $data['split_type'] ?? $royaltySplit->split_type,
                'notes' => $data['notes'] ?? null,
                'contact_info' => $data['contact_info'] ?? null,
                'is_primary_writer' => $data['is_primary_writer'] ?? false,
                'is_verified' => false, // Reset verification on changes
                'verified_by' => null,
                'verified_at' => null,
            ]);
            
            $this->validateTotalPercentage($royaltySplit->project, $royaltySplit->split_type);
            
            $this->recordHistory(
                $royaltySplit->project,
                'updated',
                $beforeData,
                $royaltySplit->fresh()->toArray(),
                $updatedBy,
                $data['change_reason'] ?? null
            );
            
            DB::commit();
            
            return $royaltySplit->fresh();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function deleteRoyaltySplit(RoyaltySplit $royaltySplit, User $deletedBy, ?string $reason = null): bool
    {
        DB::beginTransaction();
        
        try {
            $beforeData = $royaltySplit->toArray();
            
            $this->recordHistory(
                $royaltySplit->project,
                'deleted',
                $beforeData,
                null,
                $deletedBy,
                $reason
            );
            
            $royaltySplit->delete();
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function verifyRoyaltySplit(RoyaltySplit $royaltySplit, User $verifiedBy): RoyaltySplit
    {
        $beforeData = $royaltySplit->toArray();
        
        $royaltySplit->update([
            'is_verified' => true,
            'verified_by' => $verifiedBy->id,
            'verified_at' => now(),
        ]);
        
        $this->recordHistory(
            $royaltySplit->project,
            'verified',
            $beforeData,
            $royaltySplit->fresh()->toArray(),
            $verifiedBy
        );
        
        return $royaltySplit->fresh();
    }
    
    public function bulkCreateFromTemplate(Project $project, RoyaltyTemplate $template, User $createdBy): Collection
    {
        $templateData = $template->template_data;
        $createdSplits = collect();
        
        DB::beginTransaction();
        
        try {
            foreach ($templateData['splits'] as $splitData) {
                $royaltySplit = $this->createRoyaltySplit($project, $splitData, $createdBy);
                $createdSplits->push($royaltySplit);
            }
            
            // Update template usage count
            $template->increment('usage_count');
            
            DB::commit();
            
            return $createdSplits;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function exportRoyaltyData(Project $project, string $format = 'json'): array
    {
        $splits = RoyaltySplit::where('project_id', $project->id)
            ->with(['creator', 'verifier'])
            ->orderBy('split_type')
            ->orderBy('percentage', 'desc')
            ->get();
        
        $rights = $project->projectRights()->with('creator')->get();
        
        $exportData = [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'created_at' => $project->created_at,
            ],
            'royalty_splits' => $splits->groupBy('split_type')->map(function ($typeSplits) {
                return [
                    'total_percentage' => $typeSplits->sum('percentage'),
                    'splits' => $typeSplits->map(function ($split) {
                        return [
                            'contributor_name' => $split->contributor_name,
                            'contributor_email' => $split->contributor_email,
                            'contributor_role' => $split->contributor_role,
                            'percentage' => $split->percentage,
                            'notes' => $split->notes,
                            'contact_info' => $split->contact_info,
                            'is_primary_writer' => $split->is_primary_writer,
                            'is_verified' => $split->is_verified,
                            'verified_at' => $split->verified_at,
                            'created_at' => $split->created_at,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
            'rights_information' => $rights->map(function ($right) {
                return [
                    'rights_type' => $right->rights_type,
                    'ownership_details' => $right->ownership_details,
                    'territory' => $right->territory,
                    'duration' => $right->duration,
                    'restrictions' => $right->restrictions,
                    'registration_info' => $right->registration_info,
                    'created_at' => $right->created_at,
                ];
            })->toArray(),
            'export_metadata' => [
                'exported_at' => now(),
                'format' => $format,
                'total_splits_count' => $splits->count(),
                'verification_status' => $this->getVerificationStatus($splits),
            ],
        ];
        
        return match($format) {
            'csv' => $this->convertToCSV($exportData),
            'pdf' => $this->generatePDF($exportData),
            default => $exportData,
        };
    }
    
    public function getRoyaltySummary(Project $project): array
    {
        $splits = RoyaltySplit::where('project_id', $project->id)->get();
        
        $summary = [];
        
        foreach ($splits->groupBy('split_type') as $splitType => $typeSplits) {
            $totalPercentage = $typeSplits->sum('percentage');
            $verifiedCount = $typeSplits->where('is_verified', true)->count();
            
            $summary[$splitType] = [
                'total_percentage' => $totalPercentage,
                'split_count' => $typeSplits->count(),
                'verified_count' => $verifiedCount,
                'verification_percentage' => $typeSplits->count() > 0 ? ($verifiedCount / $typeSplits->count()) * 100 : 0,
                'is_complete' => $totalPercentage == 100.00,
                'contributors' => $typeSplits->map(function ($split) {
                    return [
                        'name' => $split->contributor_name,
                        'role' => $split->contributor_role,
                        'percentage' => $split->percentage,
                        'is_verified' => $split->is_verified,
                    ];
                })->toArray(),
            ];
        }
        
        return $summary;
    }
    
    protected function validateSplitData(array $data): void
    {
        $rules = [
            'contributor_name' => 'required|string|max:255',
            'contributor_email' => 'nullable|email|max:255',
            'contributor_role' => 'required|in:songwriter,producer,performer,engineer,publisher,label,other',
            'percentage' => 'required|numeric|min:0|max:100',
            'split_type' => 'in:publishing,mechanical,performance,master,sync',
            'notes' => 'nullable|string|max:1000',
            'contact_info' => 'nullable|array',
            'is_primary_writer' => 'boolean',
        ];
        
        $validator = \Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }
    
    protected function validateTotalPercentage(Project $project, string $splitType): void
    {
        $totalPercentage = RoyaltySplit::where('project_id', $project->id)
            ->where('split_type', $splitType)
            ->sum('percentage');
        
        if ($totalPercentage > 100.00) {
            throw new \InvalidArgumentException("Total percentage for {$splitType} splits cannot exceed 100%. Current total: {$totalPercentage}%");
        }
    }
    
    protected function recordHistory(
        Project $project,
        string $action,
        ?array $beforeData,
        ?array $afterData,
        User $changedBy,
        ?string $reason = null
    ): void {
        RoyaltySplitHistory::create([
            'project_id' => $project->id,
            'action' => $action,
            'before_data' => $beforeData,
            'after_data' => $afterData,
            'changed_by' => $changedBy->id,
            'change_reason' => $reason,
            'occurred_at' => now(),
        ]);
    }
    
    protected function getVerificationStatus(Collection $splits): array
    {
        $total = $splits->count();
        $verified = $splits->where('is_verified', true)->count();
        
        return [
            'total_splits' => $total,
            'verified_splits' => $verified,
            'unverified_splits' => $total - $verified,
            'verification_percentage' => $total > 0 ? ($verified / $total) * 100 : 0,
        ];
    }
    
    protected function convertToCSV(array $exportData): string
    {
        $csvData = [];
        $csvData[] = ['Project Name', $exportData['project']['name']];
        $csvData[] = ['Export Date', $exportData['export_metadata']['exported_at']];
        $csvData[] = [];
        
        foreach ($exportData['royalty_splits'] as $splitType => $typeData) {
            $csvData[] = [strtoupper($splitType) . ' SPLITS'];
            $csvData[] = ['Contributor', 'Role', 'Percentage', 'Email', 'Notes', 'Verified'];
            
            foreach ($typeData['splits'] as $split) {
                $csvData[] = [
                    $split['contributor_name'],
                    $split['contributor_role'],
                    $split['percentage'] . '%',
                    $split['contributor_email'] ?? '',
                    $split['notes'] ?? '',
                    $split['is_verified'] ? 'Yes' : 'No',
                ];
            }
            
            $csvData[] = ['TOTAL', '', $typeData['total_percentage'] . '%'];
            $csvData[] = [];
        }
        
        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    protected function generatePDF(array $exportData): string
    {
        // Implementation would use a PDF library like DomPDF or similar
        // This is a simplified version showing the structure
        
        $html = view('exports.royalty-splits-pdf', compact('exportData'))->render();
        
        // Generate PDF from HTML
        $pdf = \PDF::loadHTML($html);
        
        return $pdf->output();
    }
}
```

### RoyaltyTemplateService
```php
<?php

namespace App\Services;

use App\Models\RoyaltyTemplate;
use App\Models\User;
use Illuminate\Support\Collection;

class RoyaltyTemplateService
{
    public function createTemplate(User $user, array $data): RoyaltyTemplate
    {
        $this->validateTemplateData($data);
        
        return RoyaltyTemplate::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'template_data' => $data['template_data'],
            'is_public' => $data['is_public'] ?? false,
        ]);
    }
    
    public function updateTemplate(RoyaltyTemplate $template, array $data): RoyaltyTemplate
    {
        $this->validateTemplateData($data);
        
        $template->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $template->description,
            'template_data' => $data['template_data'],
            'is_public' => $data['is_public'] ?? $template->is_public,
        ]);
        
        return $template->fresh();
    }
    
    public function getAvailableTemplates(User $user): Collection
    {
        return RoyaltyTemplate::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('is_public', true);
        })
        ->orderBy('usage_count', 'desc')
        ->orderBy('name')
        ->get();
    }
    
    public function getPopularTemplates(int $limit = 10): Collection
    {
        return RoyaltyTemplate::where('is_public', true)
            ->where('usage_count', '>', 0)
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
    }
    
    protected function validateTemplateData(array $data): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'template_data' => 'required|array',
            'template_data.splits' => 'required|array|min:1',
            'template_data.splits.*.contributor_name' => 'required|string|max:255',
            'template_data.splits.*.contributor_role' => 'required|in:songwriter,producer,performer,engineer,publisher,label,other',
            'template_data.splits.*.percentage' => 'required|numeric|min:0|max:100',
            'template_data.splits.*.split_type' => 'in:publishing,mechanical,performance,master,sync',
            'is_public' => 'boolean',
        ];
        
        $validator = \Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
        
        // Validate that template splits total 100% for each split type
        $this->validateTemplateTotals($data['template_data']['splits']);
    }
    
    protected function validateTemplateTotals(array $splits): void
    {
        $splitsByType = collect($splits)->groupBy('split_type');
        
        foreach ($splitsByType as $splitType => $typeSplits) {
            $total = collect($typeSplits)->sum('percentage');
            
            if ($total > 100.00) {
                throw new \InvalidArgumentException("Total percentage for {$splitType} splits in template cannot exceed 100%. Current total: {$total}%");
            }
        }
    }
}
```

## Models

### RoyaltySplit Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoyaltySplit extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'project_id',
        'contributor_name',
        'contributor_email',
        'contributor_role',
        'percentage',
        'split_type',
        'notes',
        'contact_info',
        'is_primary_writer',
        'is_verified',
        'created_by',
        'verified_by',
        'verified_at',
    ];
    
    protected $casts = [
        'percentage' => 'decimal:2',
        'contact_info' => 'array',
        'is_primary_writer' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
    
    public function getFormattedPercentageAttribute(): string
    {
        return number_format($this->percentage, 2) . '%';
    }
    
    public function isComplete(): bool
    {
        return !empty($this->contributor_name) && 
               !empty($this->contributor_role) && 
               $this->percentage > 0;
    }
    
    public function canBeVerified(): bool
    {
        return $this->isComplete() && !$this->is_verified;
    }
}
```

### ProjectRights Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRights extends Model
{
    use HasFactory;
    
    protected $table = 'project_rights';
    
    protected $fillable = [
        'project_id',
        'rights_type',
        'ownership_details',
        'territory',
        'duration',
        'restrictions',
        'registration_info',
        'created_by',
    ];
    
    protected $casts = [
        'registration_info' => 'array',
    ];
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

### RoyaltyTemplate Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoyaltyTemplate extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'template_data',
        'is_public',
        'usage_count',
    ];
    
    protected $casts = [
        'template_data' => 'array',
        'is_public' => 'boolean',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function getTotalSplitsAttribute(): int
    {
        return count($this->template_data['splits'] ?? []);
    }
    
    public function getSplitTypesAttribute(): array
    {
        $splits = $this->template_data['splits'] ?? [];
        return array_unique(array_column($splits, 'split_type'));
    }
}
```

### RoyaltySplitHistory Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoyaltySplitHistory extends Model
{
    use HasFactory;
    
    protected $table = 'royalty_split_history';
    
    protected $fillable = [
        'project_id',
        'action',
        'before_data',
        'after_data',
        'changed_by',
        'change_reason',
        'occurred_at',
    ];
    
    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'occurred_at' => 'datetime',
    ];
    
    public $timestamps = false;
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
```

## UI Implementation

### Royalty Management Livewire Component
```php
<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\RoyaltySplit;
use App\Models\ProjectRights;
use App\Models\RoyaltyTemplate;
use App\Services\RoyaltySplitService;
use App\Services\RoyaltyTemplateService;
use Livewire\Component;

class ManageRoyalties extends Component
{
    public Project $project;
    public $showCreateSplitModal = false;
    public $showCreateRightsModal = false;
    public $showTemplateModal = false;
    public $showExportModal = false;
    public $editingSplit = null;
    public $editingRights = null;
    
    // Split form fields
    public $contributorName = '';
    public $contributorEmail = '';
    public $contributorRole = 'songwriter';
    public $percentage = '';
    public $splitType = 'publishing';
    public $notes = '';
    public $contactInfo = [];
    public $isPrimaryWriter = false;
    public $changeReason = '';
    
    // Rights form fields
    public $rightsType = 'publishing';
    public $ownershipDetails = '';
    public $territory = 'worldwide';
    public $duration = '';
    public $restrictions = '';
    public $registrationInfo = [];
    
    // Template fields
    public $selectedTemplate = null;
    public $templateName = '';
    public $templateDescription = '';
    public $makeTemplatePublic = false;
    
    // Export fields
    public $exportFormat = 'json';
    
    protected $rules = [
        'contributorName' => 'required|string|max:255',
        'contributorEmail' => 'nullable|email|max:255',
        'contributorRole' => 'required|in:songwriter,producer,performer,engineer,publisher,label,other',
        'percentage' => 'required|numeric|min:0|max:100',
        'splitType' => 'required|in:publishing,mechanical,performance,master,sync',
        'notes' => 'nullable|string|max:1000',
        'isPrimaryWriter' => 'boolean',
        'rightsType' => 'required|in:publishing,mechanical,performance,synchronization,master,neighboring',
        'ownershipDetails' => 'required|string|max:1000',
        'territory' => 'required|string|max:255',
        'duration' => 'nullable|string|max:255',
        'restrictions' => 'nullable|string|max:1000',
    ];
    
    public function mount(Project $project)
    {
        $this->project = $project;
    }
    
    public function createSplit()
    {
        $this->validate([
            'contributorName' => 'required|string|max:255',
            'contributorRole' => 'required|in:songwriter,producer,performer,engineer,publisher,label,other',
            'percentage' => 'required|numeric|min:0|max:100',
            'splitType' => 'required|in:publishing,mechanical,performance,master,sync',
        ]);
        
        try {
            $royaltySplitService = app(RoyaltySplitService::class);
            
            $data = [
                'contributor_name' => $this->contributorName,
                'contributor_email' => $this->contributorEmail ?: null,
                'contributor_role' => $this->contributorRole,
                'percentage' => $this->percentage,
                'split_type' => $this->splitType,
                'notes' => $this->notes ?: null,
                'contact_info' => $this->contactInfo ?: null,
                'is_primary_writer' => $this->isPrimaryWriter,
                'change_reason' => $this->changeReason ?: null,
            ];
            
            $royaltySplitService->createRoyaltySplit($this->project, $data, auth()->user());
            
            $this->resetSplitForm();
            $this->showCreateSplitModal = false;
            
            session()->flash('success', 'Royalty split created successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    
    public function editSplit(RoyaltySplit $split)
    {
        $this->editingSplit = $split;
        $this->contributorName = $split->contributor_name;
        $this->contributorEmail = $split->contributor_email ?? '';
        $this->contributorRole = $split->contributor_role;
        $this->percentage = $split->percentage;
        $this->splitType = $split->split_type;
        $this->notes = $split->notes ?? '';
        $this->contactInfo = $split->contact_info ?? [];
        $this->isPrimaryWriter = $split->is_primary_writer;
        
        $this->showCreateSplitModal = true;
    }
    
    public function updateSplit()
    {
        $this->validate([
            'contributorName' => 'required|string|max:255',
            'contributorRole' => 'required|in:songwriter,producer,performer,engineer,publisher,label,other',
            'percentage' => 'required|numeric|min:0|max:100',
            'splitType' => 'required|in:publishing,mechanical,performance,master,sync',
        ]);
        
        try {
            $royaltySplitService = app(RoyaltySplitService::class);
            
            $data = [
                'contributor_name' => $this->contributorName,
                'contributor_email' => $this->contributorEmail ?: null,
                'contributor_role' => $this->contributorRole,
                'percentage' => $this->percentage,
                'split_type' => $this->splitType,
                'notes' => $this->notes ?: null,
                'contact_info' => $this->contactInfo ?: null,
                'is_primary_writer' => $this->isPrimaryWriter,
                'change_reason' => $this->changeReason ?: null,
            ];
            
            $royaltySplitService->updateRoyaltySplit($this->editingSplit, $data, auth()->user());
            
            $this->resetSplitForm();
            $this->showCreateSplitModal = false;
            $this->editingSplit = null;
            
            session()->flash('success', 'Royalty split updated successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    
    public function verifySplit(RoyaltySplit $split)
    {
        try {
            $royaltySplitService = app(RoyaltySplitService::class);
            $royaltySplitService->verifyRoyaltySplit($split, auth()->user());
            
            session()->flash('success', 'Royalty split verified successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    
    public function deleteSplit(RoyaltySplit $split)
    {
        try {
            $royaltySplitService = app(RoyaltySplitService::class);
            $royaltySplitService->deleteRoyaltySplit($split, auth()->user(), 'Deleted via UI');
            
            session()->flash('success', 'Royalty split deleted successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    
    public function applyTemplate()
    {
        if (!$this->selectedTemplate) {
            session()->flash('error', 'Please select a template to apply.');
            return;
        }
        
        try {
            $template = RoyaltyTemplate::find($this->selectedTemplate);
            $royaltySplitService = app(RoyaltySplitService::class);
            
            $royaltySplitService->bulkCreateFromTemplate($this->project, $template, auth()->user());
            
            $this->showTemplateModal = false;
            $this->selectedTemplate = null;
            
            session()->flash('success', 'Template applied successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    
    public function saveAsTemplate()
    {
        $this->validate([
            'templateName' => 'required|string|max:255',
            'templateDescription' => 'nullable|string|max:500',
        ]);
        
        try {
            $splits = RoyaltySplit::where('project_id', $this->project->id)->get();
            
            if ($splits->isEmpty()) {
                session()->flash('error', 'No royalty splits to save as template.');
                return;
            }
            
            $templateData = [
                'splits' => $splits->map(function ($split) {
                    return [
                        'contributor_name' => $split->contributor_name,
                        'contributor_role' => $split->contributor_role,
                        'percentage' => $split->percentage,
                        'split_type' => $split->split_type,
                        'notes' => $split->notes,
                        'is_primary_writer' => $split->is_primary_writer,
                    ];
                })->toArray(),
            ];
            
            $data = [
                'name' => $this->templateName,
                'description' => $this->templateDescription,
                'template_data' => $templateData,
                'is_public' => $this->makeTemplatePublic,
            ];
            
            $royaltyTemplateService = app(RoyaltyTemplateService::class);
            $royaltyTemplateService->createTemplate(auth()->user(), $data);
            
            $this->templateName = '';
            $this->templateDescription = '';
            $this->makeTemplatePublic = false;
            
            session()->flash('success', 'Template saved successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    
    public function exportRoyaltyData()
    {
        try {
            $royaltySplitService = app(RoyaltySplitService::class);
            $exportData = $royaltySplitService->exportRoyaltyData($this->project, $this->exportFormat);
            
            $fileName = "royalty-splits-{$this->project->id}-" . now()->format('Y-m-d');
            
            return match($this->exportFormat) {
                'csv' => response()->streamDownload(function () use ($exportData) {
                    echo $exportData;
                }, $fileName . '.csv', ['Content-Type' => 'text/csv']),
                'pdf' => response()->streamDownload(function () use ($exportData) {
                    echo $exportData;
                }, $fileName . '.pdf', ['Content-Type' => 'application/pdf']),
                default => response()->json($exportData)->download($fileName . '.json'),
            };
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    
    protected function resetSplitForm()
    {
        $this->contributorName = '';
        $this->contributorEmail = '';
        $this->contributorRole = 'songwriter';
        $this->percentage = '';
        $this->splitType = 'publishing';
        $this->notes = '';
        $this->contactInfo = [];
        $this->isPrimaryWriter = false;
        $this->changeReason = '';
    }
    
    public function render()
    {
        $splits = RoyaltySplit::where('project_id', $this->project->id)
            ->with(['creator', 'verifier'])
            ->orderBy('split_type')
            ->orderBy('percentage', 'desc')
            ->get();
        
        $rights = ProjectRights::where('project_id', $this->project->id)
            ->with('creator')
            ->orderBy('rights_type')
            ->get();
        
        $royaltySplitService = app(RoyaltySplitService::class);
        $summary = $royaltySplitService->getRoyaltySummary($this->project);
        
        $royaltyTemplateService = app(RoyaltyTemplateService::class);
        $templates = $royaltyTemplateService->getAvailableTemplates(auth()->user());
        
        return view('livewire.project.manage-royalties', compact('splits', 'rights', 'summary', 'templates'));
    }
}
```

### Royalty Management Blade Template
```blade
{{-- resources/views/livewire/project/manage-royalties.blade.php --}}
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <flux:heading size="lg">Royalty & Rights Management</flux:heading>
        
        <div class="flex gap-2">
            <flux:button wire:click="$set('showTemplateModal', true)" variant="ghost">
                <flux:icon name="template" class="size-4" />
                Templates
            </flux:button>
            
            <flux:button wire:click="$set('showExportModal', true)" variant="ghost">
                <flux:icon name="download" class="size-4" />
                Export
            </flux:button>
            
            <flux:button wire:click="$set('showCreateSplitModal', true)" variant="primary">
                <flux:icon name="plus" class="size-4" />
                Add Split
            </flux:button>
        </div>
    </div>
    
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($summary as $splitType => $typeData)
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-900 capitalize">
                        {{ str_replace('_', ' ', $splitType) }}
                    </h3>
                    
                    @if($typeData['is_complete'])
                        <flux:badge color="green" size="sm">
                            <flux:icon name="check-circle" class="size-3" />
                            Complete
                        </flux:badge>
                    @else
                        <flux:badge color="yellow" size="sm">
                            {{ $typeData['total_percentage'] }}%
                        </flux:badge>
                    @endif
                </div>
                
                <div class="space-y-1 text-sm text-gray-600">
                    <div>{{ $typeData['split_count'] }} contributors</div>
                    <div>{{ $typeData['verified_count'] }} verified</div>
                    <div class="text-xs">
                        {{ number_format($typeData['verification_percentage'], 1) }}% verification rate
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    {{-- Splits by Type --}}
    @foreach($summary as $splitType => $typeData)
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 capitalize">
                        {{ str_replace('_', ' ', $splitType) }} Splits
                    </h3>
                    
                    <div class="text-right">
                        <div class="text-2xl font-bold {{ $typeData['is_complete'] ? 'text-green-600' : 'text-orange-600' }}">
                            {{ $typeData['total_percentage'] }}%
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $typeData['split_count'] }} splits
                        </div>
                    </div>
                </div>
            </div>
            
            @if(!empty($typeData['contributors']))
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contributor
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Role
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Percentage
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($splits->where('split_type', $splitType) as $split)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $split->contributor_name }}
                                                @if($split->is_primary_writer)
                                                    <flux:badge color="blue" size="sm" class="ml-1">Primary</flux:badge>
                                                @endif
                                            </div>
                                            @if($split->contributor_email)
                                                <div class="text-sm text-gray-500">{{ $split->contributor_email }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <flux:text size="sm" class="capitalize">{{ str_replace('_', ' ', $split->contributor_role) }}</flux:text>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <flux:text size="sm" class="font-semibold">{{ $split->formatted_percentage }}</flux:text>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($split->is_verified)
                                            <flux:badge color="green" size="sm">
                                                <flux:icon name="check-circle" class="size-3" />
                                                Verified
                                            </flux:badge>
                                        @else
                                            <flux:badge color="gray" size="sm">
                                                <flux:icon name="clock" class="size-3" />
                                                Pending
                                            </flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <flux:dropdown>
                                            <flux:button size="sm" variant="ghost">
                                                <flux:icon name="ellipsis-horizontal" class="size-4" />
                                            </flux:button>
                                            
                                            <flux:dropdown.menu>
                                                <flux:dropdown.item wire:click="editSplit({{ $split->id }})">
                                                    <flux:icon name="pencil" class="size-4" />
                                                    Edit
                                                </flux:dropdown.item>
                                                
                                                @if($split->canBeVerified())
                                                    <flux:dropdown.item wire:click="verifySplit({{ $split->id }})">
                                                        <flux:icon name="check-circle" class="size-4" />
                                                        Verify
                                                    </flux:dropdown.item>
                                                @endif
                                                
                                                <flux:dropdown.item 
                                                    wire:click="deleteSplit({{ $split->id }})"
                                                    wire:confirm="Are you sure you want to delete this royalty split?"
                                                    variant="danger"
                                                >
                                                    <flux:icon name="trash" class="size-4" />
                                                    Delete
                                                </flux:dropdown.item>
                                            </flux:dropdown.menu>
                                        </flux:dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-4">
                    <flux:text class="text-gray-500">No {{ $splitType }} splits defined yet.</flux:text>
                </div>
            @endif
        </div>
    @endforeach
    
    {{-- Create/Edit Split Modal --}}
    <flux:modal wire:model="showCreateSplitModal" name="manage-split">
        <flux:modal.header>
            <flux:heading size="lg">
                {{ $editingSplit ? 'Edit' : 'Add' }} Royalty Split
            </flux:heading>
        </flux:modal.header>
        
        <form wire:submit="{{ $editingSplit ? 'updateSplit' : 'createSplit' }}" class="space-y-6">
            <flux:modal.body class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Contributor Name *</flux:label>
                        <flux:input wire:model="contributorName" placeholder="John Doe" />
                        <flux:error name="contributorName" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input wire:model="contributorEmail" type="email" placeholder="john@example.com" />
                        <flux:error name="contributorEmail" />
                    </flux:field>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:field>
                        <flux:label>Role *</flux:label>
                        <flux:select wire:model="contributorRole">
                            <option value="songwriter">Songwriter</option>
                            <option value="producer">Producer</option>
                            <option value="performer">Performer</option>
                            <option value="engineer">Engineer</option>
                            <option value="publisher">Publisher</option>
                            <option value="label">Label</option>
                            <option value="other">Other</option>
                        </flux:select>
                        <flux:error name="contributorRole" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Split Type *</flux:label>
                        <flux:select wire:model="splitType">
                            <option value="publishing">Publishing</option>
                            <option value="mechanical">Mechanical</option>
                            <option value="performance">Performance</option>
                            <option value="master">Master</option>
                            <option value="sync">Sync</option>
                        </flux:select>
                        <flux:error name="splitType" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Percentage *</flux:label>
                        <flux:input wire:model="percentage" type="number" min="0" max="100" step="0.01" placeholder="25.00" />
                        <flux:error name="percentage" />
                    </flux:field>
                </div>
                
                <flux:field>
                    <flux:checkbox wire:model="isPrimaryWriter">
                        Primary Writer
                    </flux:checkbox>
                </flux:field>
                
                <flux:field>
                    <flux:label>Notes</flux:label>
                    <flux:textarea wire:model="notes" placeholder="Additional notes about this split..." rows="3" />
                    <flux:error name="notes" />
                </flux:field>
                
                <flux:field>
                    <flux:label>Change Reason</flux:label>
                    <flux:input wire:model="changeReason" placeholder="Reason for this change (optional)" />
                </flux:field>
            </flux:modal.body>
            
            <flux:modal.footer>
                <flux:button type="button" wire:click="$set('showCreateSplitModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                
                <flux:button type="submit" variant="primary">
                    {{ $editingSplit ? 'Update' : 'Create' }} Split
                </flux:button>
            </flux:modal.footer>
        </form>
    </flux:modal>
    
    {{-- Template Modal --}}
    <flux:modal wire:model="showTemplateModal" name="template-modal">
        <flux:modal.header>
            <flux:heading size="lg">Royalty Templates</flux:heading>
        </flux:modal.header>
        
        <flux:modal.body class="space-y-6">
            <div class="space-y-4">
                <flux:heading size="sm">Apply Template</flux:heading>
                
                <flux:field>
                    <flux:label>Select Template</flux:label>
                    <flux:select wire:model="selectedTemplate">
                        <option value="">Choose a template...</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">
                                {{ $template->name }} ({{ $template->total_splits }} splits)
                            </option>
                        @endforeach
                    </flux:select>
                </flux:field>
                
                <flux:button wire:click="applyTemplate" variant="primary" :disabled="!$selectedTemplate">
                    Apply Template
                </flux:button>
            </div>
            
            <flux:separator />
            
            <div class="space-y-4">
                <flux:heading size="sm">Save Current Splits as Template</flux:heading>
                
                <flux:field>
                    <flux:label>Template Name</flux:label>
                    <flux:input wire:model="templateName" placeholder="My Template" />
                </flux:field>
                
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="templateDescription" placeholder="Template description..." rows="2" />
                </flux:field>
                
                <flux:field>
                    <flux:checkbox wire:model="makeTemplatePublic">
                        Make template public (other users can use it)
                    </flux:checkbox>
                </flux:field>
                
                <flux:button wire:click="saveAsTemplate" variant="primary" :disabled="!$templateName">
                    Save as Template
                </flux:button>
            </div>
        </flux:modal.body>
    </flux:modal>
    
    {{-- Export Modal --}}
    <flux:modal wire:model="showExportModal" name="export-modal">
        <flux:modal.header>
            <flux:heading size="lg">Export Royalty Data</flux:heading>
        </flux:modal.header>
        
        <flux:modal.body class="space-y-6">
            <flux:field>
                <flux:label>Export Format</flux:label>
                <flux:select wire:model="exportFormat">
                    <option value="json">JSON</option>
                    <option value="csv">CSV</option>
                    <option value="pdf">PDF Report</option>
                </flux:select>
            </flux:field>
            
            <flux:text size="sm" class="text-gray-600">
                Export includes all royalty splits, rights information, and verification status for this project.
            </flux:text>
        </flux:modal.body>
        
        <flux:modal.footer>
            <flux:button type="button" wire:click="$set('showExportModal', false)" variant="ghost">
                Cancel
            </flux:button>
            
            <flux:button wire:click="exportRoyaltyData" variant="primary">
                <flux:icon name="download" class="size-4" />
                Export Data
            </flux:button>
        </flux:modal.footer>
    </flux:modal>
</div>
```

## Testing Strategy

### Feature Tests
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\RoyaltySplit;
use App\Models\RoyaltyTemplate;
use App\Services\RoyaltySplitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoyaltyRightsNotesTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Project $project;
    protected RoyaltySplitService $royaltySplitService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->royaltySplitService = app(RoyaltySplitService::class);
    }
    
    public function test_user_can_create_royalty_split()
    {
        $splitData = [
            'contributor_name' => 'John Songwriter',
            'contributor_email' => 'john@example.com',
            'contributor_role' => 'songwriter',
            'percentage' => 50.00,
            'split_type' => 'publishing',
            'notes' => 'Lead songwriter',
            'is_primary_writer' => true,
        ];
        
        $split = $this->royaltySplitService->createRoyaltySplit($this->project, $splitData, $this->user);
        
        expect($split->contributor_name)->toBe('John Songwriter');
        expect($split->percentage)->toBe(50.00);
        expect($split->is_primary_writer)->toBeTrue();
        expect($split->is_verified)->toBeFalse();
        
        $this->assertDatabaseHas('royalty_splits', [
            'project_id' => $this->project->id,
            'contributor_name' => 'John Songwriter',
            'percentage' => 50.00,
        ]);
        
        $this->assertDatabaseHas('royalty_split_history', [
            'project_id' => $this->project->id,
            'action' => 'created',
            'changed_by' => $this->user->id,
        ]);
    }
    
    public function test_total_percentage_validation_prevents_exceeding_100_percent()
    {
        // Create first split of 60%
        $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'First Contributor',
            'contributor_role' => 'songwriter',
            'percentage' => 60.00,
            'split_type' => 'publishing',
        ], $this->user);
        
        // Try to create second split of 50% (would total 110%)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total percentage for publishing splits cannot exceed 100%');
        
        $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'Second Contributor',
            'contributor_role' => 'songwriter',
            'percentage' => 50.00,
            'split_type' => 'publishing',
        ], $this->user);
    }
    
    public function test_different_split_types_are_tracked_separately()
    {
        // Create 100% publishing split
        $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'Publisher',
            'contributor_role' => 'publisher',
            'percentage' => 100.00,
            'split_type' => 'publishing',
        ], $this->user);
        
        // Should be able to create 100% mechanical split
        $mechanicalSplit = $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'Mechanical Owner',
            'contributor_role' => 'songwriter',
            'percentage' => 100.00,
            'split_type' => 'mechanical',
        ], $this->user);
        
        expect($mechanicalSplit)->not->toBeNull();
        
        $summary = $this->royaltySplitService->getRoyaltySummary($this->project);
        expect($summary['publishing']['total_percentage'])->toBe(100.00);
        expect($summary['mechanical']['total_percentage'])->toBe(100.00);
    }
    
    public function test_royalty_split_verification()
    {
        $split = RoyaltySplit::factory()->create([
            'project_id' => $this->project->id,
            'is_verified' => false,
        ]);
        
        $verifier = User::factory()->create();
        
        $verifiedSplit = $this->royaltySplitService->verifyRoyaltySplit($split, $verifier);
        
        expect($verifiedSplit->is_verified)->toBeTrue();
        expect($verifiedSplit->verified_by)->toBe($verifier->id);
        expect($verifiedSplit->verified_at)->not->toBeNull();
        
        $this->assertDatabaseHas('royalty_split_history', [
            'project_id' => $this->project->id,
            'action' => 'verified',
            'changed_by' => $verifier->id,
        ]);
    }
    
    public function test_royalty_template_creation_and_application()
    {
        // Create some splits first
        $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'Writer 1',
            'contributor_role' => 'songwriter',
            'percentage' => 50.00,
            'split_type' => 'publishing',
        ], $this->user);
        
        $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'Writer 2',
            'contributor_role' => 'songwriter',
            'percentage' => 50.00,
            'split_type' => 'publishing',
        ], $this->user);
        
        // Create template from existing splits
        $templateData = [
            'name' => '50/50 Split Template',
            'description' => 'Equal split between two writers',
            'template_data' => [
                'splits' => [
                    [
                        'contributor_name' => 'Writer A',
                        'contributor_role' => 'songwriter',
                        'percentage' => 50.00,
                        'split_type' => 'publishing',
                    ],
                    [
                        'contributor_name' => 'Writer B',
                        'contributor_role' => 'songwriter',
                        'percentage' => 50.00,
                        'split_type' => 'publishing',
                    ],
                ],
            ],
            'is_public' => true,
        ];
        
        $templateService = app(\App\Services\RoyaltyTemplateService::class);
        $template = $templateService->createTemplate($this->user, $templateData);
        
        expect($template->name)->toBe('50/50 Split Template');
        expect($template->total_splits)->toBe(2);
        expect($template->usage_count)->toBe(0);
        
        // Apply template to new project
        $newProject = Project::factory()->create(['user_id' => $this->user->id]);
        
        $createdSplits = $this->royaltySplitService->bulkCreateFromTemplate($newProject, $template, $this->user);
        
        expect($createdSplits)->toHaveCount(2);
        expect($template->fresh()->usage_count)->toBe(1);
        
        $projectSplits = RoyaltySplit::where('project_id', $newProject->id)->get();
        expect($projectSplits->sum('percentage'))->toBe(100.00);
    }
    
    public function test_royalty_data_export()
    {
        // Create test data
        $split1 = $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'Main Writer',
            'contributor_role' => 'songwriter',
            'percentage' => 75.00,
            'split_type' => 'publishing',
            'notes' => 'Primary songwriter',
            'is_primary_writer' => true,
        ], $this->user);
        
        $split2 = $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'Co-Writer',
            'contributor_role' => 'songwriter',
            'percentage' => 25.00,
            'split_type' => 'publishing',
        ], $this->user);
        
        // Verify one split
        $this->royaltySplitService->verifyRoyaltySplit($split1, $this->user);
        
        // Export data
        $exportData = $this->royaltySplitService->exportRoyaltyData($this->project, 'json');
        
        expect($exportData['project']['id'])->toBe($this->project->id);
        expect($exportData['royalty_splits']['publishing']['total_percentage'])->toBe(100.00);
        expect($exportData['royalty_splits']['publishing']['splits'])->toHaveCount(2);
        expect($exportData['export_metadata']['total_splits_count'])->toBe(2);
        expect($exportData['export_metadata']['verification_status']['verified_splits'])->toBe(1);
        expect($exportData['export_metadata']['verification_status']['unverified_splits'])->toBe(1);
        
        // Test CSV export
        $csvData = $this->royaltySplitService->exportRoyaltyData($this->project, 'csv');
        expect($csvData)->toBeString();
        expect($csvData)->toContain('Main Writer');
        expect($csvData)->toContain('75%');
    }
    
    public function test_royalty_summary_calculation()
    {
        // Create mixed splits
        $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'Publisher',
            'contributor_role' => 'publisher',
            'percentage' => 100.00,
            'split_type' => 'publishing',
        ], $this->user);
        
        $split1 = $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'Performer 1',
            'contributor_role' => 'performer',
            'percentage' => 60.00,
            'split_type' => 'performance',
        ], $this->user);
        
        $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'Performer 2',
            'contributor_role' => 'performer',
            'percentage' => 30.00,
            'split_type' => 'performance',
        ], $this->user);
        
        // Verify one split
        $this->royaltySplitService->verifyRoyaltySplit($split1, $this->user);
        
        $summary = $this->royaltySplitService->getRoyaltySummary($this->project);
        
        expect($summary['publishing']['total_percentage'])->toBe(100.00);
        expect($summary['publishing']['is_complete'])->toBeTrue();
        expect($summary['publishing']['split_count'])->toBe(1);
        expect($summary['publishing']['verified_count'])->toBe(0);
        
        expect($summary['performance']['total_percentage'])->toBe(90.00);
        expect($summary['performance']['is_complete'])->toBeFalse();
        expect($summary['performance']['split_count'])->toBe(2);
        expect($summary['performance']['verified_count'])->toBe(1);
        expect($summary['performance']['verification_percentage'])->toBe(50.0);
    }
    
    public function test_livewire_component_creates_royalty_split()
    {
        $this->actingAs($this->user);
        
        Livewire::test(\App\Livewire\Project\ManageRoyalties::class, ['project' => $this->project])
            ->set('contributorName', 'Test Songwriter')
            ->set('contributorEmail', 'test@example.com')
            ->set('contributorRole', 'songwriter')
            ->set('percentage', 100)
            ->set('splitType', 'publishing')
            ->set('notes', 'Solo writer')
            ->set('isPrimaryWriter', true)
            ->call('createSplit')
            ->assertHasNoErrors()
            ->assertSet('showCreateSplitModal', false);
        
        $this->assertDatabaseHas('royalty_splits', [
            'project_id' => $this->project->id,
            'contributor_name' => 'Test Songwriter',
            'percentage' => 100.00,
            'is_primary_writer' => true,
        ]);
    }
    
    public function test_historical_tracking_of_changes()
    {
        $split = $this->royaltySplitService->createRoyaltySplit($this->project, [
            'contributor_name' => 'Original Name',
            'contributor_role' => 'songwriter',
            'percentage' => 50.00,
            'split_type' => 'publishing',
        ], $this->user);
        
        // Update the split
        $this->royaltySplitService->updateRoyaltySplit($split, [
            'contributor_name' => 'Updated Name',
            'contributor_role' => 'songwriter',
            'percentage' => 60.00,
            'split_type' => 'publishing',
            'change_reason' => 'Name correction and percentage adjustment',
        ], $this->user);
        
        // Check history records
        $history = \App\Models\RoyaltySplitHistory::where('project_id', $this->project->id)
            ->orderBy('occurred_at')
            ->get();
        
        expect($history)->toHaveCount(2);
        
        $createHistory = $history->first();
        expect($createHistory->action)->toBe('created');
        expect($createHistory->before_data)->toBeNull();
        expect($createHistory->after_data['contributor_name'])->toBe('Original Name');
        
        $updateHistory = $history->last();
        expect($updateHistory->action)->toBe('updated');
        expect($updateHistory->before_data['contributor_name'])->toBe('Original Name');
        expect($updateHistory->after_data['contributor_name'])->toBe('Updated Name');
        expect($updateHistory->change_reason)->toBe('Name correction and percentage adjustment');
    }
}
```

## Implementation Steps

### Phase 1: Core Data Architecture (Week 1-2)
1. **Database Schema Implementation**
   - Create royalty_splits migration with validation constraints
   - Create project_rights migration for rights tracking
   - Create royalty_templates migration for reusable splits
   - Create royalty_split_history migration for audit trail
   - Run migrations and verify data integrity

2. **Service Layer Development**
   - Implement RoyaltySplitService with CRUD operations
   - Add percentage validation and split type management
   - Create RoyaltyTemplateService for template operations
   - Implement historical tracking and audit trail
   - Add comprehensive validation and error handling

3. **Model Implementation**
   - Create RoyaltySplit model with relationships
   - Create ProjectRights, RoyaltyTemplate, and RoyaltySplitHistory models
   - Add computed properties and validation methods
   - Implement model factories for testing

### Phase 2: UI Development (Week 3-4)
1. **Livewire Components**
   - Create ManageRoyalties component for split management
   - Implement real-time percentage calculation and validation
   - Add template application and creation functionality
   - Create export modal with format selection

2. **Blade Templates**
   - Design royalty management interface with Flux UI
   - Create summary cards showing completion status
   - Implement responsive tables for split display
   - Add modals for create/edit operations

3. **Integration with Project Views**
   - Add royalty management tab to project pages
   - Create royalty summary widget for project dashboard
   - Add quick actions for common operations

### Phase 3: Advanced Features (Week 5-6)
1. **Template System**
   - Build template creation from existing splits
   - Implement public template sharing
   - Add template usage tracking and analytics
   - Create template management interface

2. **Export System**
   - Implement JSON export with complete metadata
   - Create CSV export for spreadsheet compatibility
   - Add PDF report generation with professional formatting
   - Integrate with project archive system

3. **Verification System**
   - Add split verification workflow
   - Implement verification status tracking
   - Create verification history and audit trail
   - Add bulk verification operations

### Phase 4: Testing & Documentation (Week 7-8)
1. **Comprehensive Testing**
   - Write feature tests for all CRUD operations
   - Create unit tests for service classes
   - Add integration tests for template system
   - Test export functionality and validation

2. **Documentation & Training**
   - Create user documentation for royalty management
   - Add tooltips and help text to interface
   - Document export formats and data structure
   - Create template library and examples

## Security Considerations

### Data Validation
- **Percentage Validation**: Ensure splits never exceed 100% per type
- **Input Sanitization**: Validate all contributor information and notes
- **Email Validation**: Verify email formats for contributor contacts
- **Type Safety**: Enforce strict split type and role enumerations

### Access Control
- **Project Authorization**: Verify user ownership before royalty operations
- **Template Permissions**: Control access to private vs public templates
- **Verification Rights**: Implement proper authorization for split verification
- **Export Security**: Ensure only authorized users can export sensitive data

### Audit Trail
- **Complete History**: Track all changes with before/after states
- **User Attribution**: Record who made each change and when
- **Change Reasons**: Require justification for significant modifications
- **Data Retention**: Maintain historical records for compliance

### Export Security
- **Data Sanitization**: Remove sensitive information from exports
- **Access Logging**: Track all export operations for security monitoring
- **Format Validation**: Ensure export formats cannot be manipulated
- **File Security**: Generate secure, temporary download links

This implementation provides a comprehensive royalty and rights management system that enables studios to properly track ownership, manage splits, and maintain compliance with industry standards while ensuring data security and auditability.