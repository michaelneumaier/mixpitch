<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;
    use Sluggable;

    // Constants for project statuses
    const STATUS_UNPUBLISHED = 'unpublished';

    const STATUS_OPEN = 'open';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    // Workflow Types
    const WORKFLOW_TYPE_STANDARD = 'standard';

    const WORKFLOW_TYPE_CONTEST = 'contest';

    const WORKFLOW_TYPE_DIRECT_HIRE = 'direct_hire';

    const WORKFLOW_TYPE_CLIENT_MANAGEMENT = 'client_management';

    // Visibility Levels
    const VISIBILITY_PUBLIC = 'public';

    const VISIBILITY_UNLISTED = 'unlisted';

    const VISIBILITY_PRIVATE = 'private';

    const VISIBILITY_INVITE_ONLY = 'invite_only';

    // Default Currency
    const DEFAULT_CURRENCY = 'USD';

    /**
     * The maximum storage allowed per project in bytes (1GB)
     */
    const MAX_STORAGE_BYTES = 1073741824; // 1GB in bytes

    /**
     * The maximum file size allowed per upload in bytes (200MB)
     */
    const MAX_FILE_SIZE_BYTES = 209715200; // 200MB in bytes

    protected $fillable = [
        'user_id',
        'name',
        'title',
        'description',
        'genre',
        'subgenre',
        'mood',
        'tempo',
        'key',
        'duration',
        'reference_tracks',
        'collaboration_type',
        'budget',
        'currency',
        'deadline',
        'status',
        'workflow_type',
        'is_featured',
        'is_urgent',
        'required_experience_level',
        'preferred_daw',
        'notes',
        'is_published',
        'published_at',
        'is_private',
        'privacy_set_at',
        'visibility_level',
        'privacy_settings',
        'access_code',
        'privacy_month_year',
        'slug',
        'submission_deadline',
        'judging_deadline',
        'judging_finalized_at',
        'results_announced_at',
        'results_announced_by',
        'judging_notes',
        'submissions_closed_early_at',
        'submissions_closed_early_by',
        'early_closure_reason',
        'show_submissions_publicly',
        'max_submissions_per_user',
        'allow_anonymous_submissions',
        'require_submission_notes',
        'prize_amount',
        'prize_currency',
        'prize_description',
        'legacy_prize_amount',
        'legacy_prize_currency',
        'legacy_prize_description',
        'auto_select_winner',
        'submission_form_intro',
        'image_path',
        'artist_name',
        'project_type',
        'project_type_id',
        'target_producer_id',
        'client_email',
        'client_name',
        'client_user_id',
        'payment_amount',
        'completed_at',
        // License fields
        'license_template_id',
        'custom_license_terms',
        'license_notes',
        'license_status',
        'license_signed_at',
        'license_signature_ip',
        'requires_license_agreement',
        'license_jurisdiction',
        'license_content_hash',
        // Reddit fields
        'reddit_post_id',
        'reddit_permalink',
        'reddit_posted_at',
        'auto_allow_access',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'client_user_id' => 'integer',
        'collaboration_type' => 'array',
        'is_published' => 'boolean',
        'is_private' => 'boolean',
        'privacy_set_at' => 'datetime',
        'privacy_settings' => 'array',
        'completed_at' => 'datetime',
        'deadline' => 'datetime',
        'target_producer_id' => 'integer',
        'project_type_id' => 'integer',
        'prize_amount' => 'decimal:2',
        'submission_deadline' => 'datetime',
        'judging_deadline' => 'datetime',
        'judging_finalized_at' => 'datetime',
        'results_announced_at' => 'datetime',
        'submissions_closed_early_at' => 'datetime',
        'show_submissions_publicly' => 'boolean',
        'auto_allow_access' => 'boolean',
        // License casts
        'custom_license_terms' => 'array',
        'license_signed_at' => 'datetime',
        'requires_license_agreement' => 'boolean',
        // Reddit casts
        'reddit_posted_at' => 'datetime',
    ];

    protected $attributes = [
        'workflow_type' => self::WORKFLOW_TYPE_STANDARD,
        'status' => self::STATUS_UNPUBLISHED,
        'is_published' => false,
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function projectType()
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function isOwnedByUser(User $user)
    {
        return $this->user_id == $user->id;
    }

    /**
     * Publish the project
     *
     * @return void
     */
    public function publish()
    {
        // Client Management projects should never be published
        if ($this->isClientManagement()) {
            // Allow status transition for workflow purposes, but keep is_published false
            if ($this->status === self::STATUS_UNPUBLISHED) {
                $this->status = self::STATUS_OPEN;
            }
            // Explicitly keep is_published as false for Client Management
            $this->is_published = false;
            $this->save();

            return;
        }

        $this->is_published = true;

        // Only change status if it's not already completed
        if ($this->status === self::STATUS_UNPUBLISHED) {
            $this->status = self::STATUS_OPEN;
        }

        $this->save();
    }

    /**
     * Unpublish the project
     *
     * @return void
     */
    public function unpublish()
    {
        // Client Management projects are already unpublished by design
        if ($this->isClientManagement()) {
            // Keep is_published as false but allow status changes for workflow
            $this->is_published = false;
            // Don't change status for Client Management - let workflow handle it
            $this->save();

            return;
        }

        $this->is_published = false;

        // If the project is not completed, set status to unpublished
        if ($this->status !== self::STATUS_COMPLETED) {
            $this->status = self::STATUS_UNPUBLISHED;
        }

        $this->save();
    }

    public function hasPreviewTrack()
    {
        if ($this->preview_track) {
            return true;
        } else {
            return false;
        }
    }

    public function previewTrack()
    {
        return $this->hasOne(ProjectFile::class, 'id', 'preview_track');
    }

    public function previewTrackPath()
    {
        if ($this->hasPreviewTrack()) {
            $track = $this->previewTrack;
            try {
                // Check if the storage driver supports temporaryUrl
                if (method_exists(Storage::disk('s3'), 'temporaryUrl')) {
                    return Storage::disk('s3')->temporaryUrl(
                        $track->file_path,
                        now()->addMinutes(15)
                    );
                } else {
                    // Fallback for storage drivers that don't support temporary URLs
                    return Storage::disk('s3')->url($track->file_path);
                }
            } catch (Exception $e) {
                Log::error('Error getting signed preview track path', [
                    'track_id' => $this->preview_track,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        } else {
            return null;
        }
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function projectFiles()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function pitches()
    {
        return $this->hasMany(Pitch::class);
    }

    public function pitchFiles()
    {
        return $this->hasManyThrough(PitchFile::class, Pitch::class);
    }

    public function userPitch($userId)
    {
        return $this->pitches()->where('user_id', $userId)->with('project')->first();
    }

    /**
     * Check if the project is open and accepting new pitches.
     */
    public function isOpenForPitches(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    // ========== LICENSE RELATIONSHIPS ==========

    /**
     * Get the license template for this project
     */
    public function licenseTemplate()
    {
        return $this->belongsTo(LicenseTemplate::class);
    }

    /**
     * Get license signatures for this project
     */
    public function licenseSignatures()
    {
        return $this->hasMany(LicenseSignature::class);
    }

    /**
     * Get active license signatures
     */
    public function activeLicenseSignatures()
    {
        return $this->licenseSignatures()->active();
    }

    // ========== LICENSE METHODS ==========

    /**
     * Check if project requires license agreement
     */
    public function requiresLicenseAgreement(): bool
    {
        return $this->requires_license_agreement;
    }

    /**
     * Check if license is signed
     */
    public function hasSignedLicense(): bool
    {
        return $this->license_status === 'active' && $this->license_signed_at;
    }

    /**
     * Check if license is pending signature
     */
    public function isLicensePending(): bool
    {
        return $this->license_status === 'pending' && $this->requiresLicenseAgreement();
    }

    /**
     * Get effective license terms
     */
    public function getEffectiveLicenseTerms(): array
    {
        if (! empty($this->custom_license_terms)) {
            return $this->custom_license_terms;
        }

        if ($this->licenseTemplate) {
            return $this->licenseTemplate->terms ?? [];
        }

        return [];
    }

    /**
     * Generate license content for this project
     */
    public function getLicenseContent(): string
    {
        if ($this->licenseTemplate) {
            return $this->licenseTemplate->generateLicenseContent($this);
        }

        // Fallback to basic license if no template
        return $this->generateBasicLicenseContent();
    }

    /**
     * Generate a basic license content
     */
    private function generateBasicLicenseContent(): string
    {
        return "License Agreement for Project: {$this->name}\n\n".
               "Project Owner: {$this->user->name}\n".
               'Date: '.now()->format('F j, Y')."\n\n".
               "This license governs the use of work created for this project.\n".
               "Terms are subject to the project owner's requirements and applicable law.";
    }

    /**
     * Generate license agreement data
     */
    public function generateLicenseAgreement(): array
    {
        return [
            'project' => [
                'id' => $this->id,
                'name' => $this->name,
                'type' => $this->project_type,
                'owner' => $this->user->name,
            ],
            'template' => $this->licenseTemplate ? [
                'id' => $this->licenseTemplate->id,
                'name' => $this->licenseTemplate->name,
                'category' => $this->licenseTemplate->category,
            ] : null,
            'content' => $this->getLicenseContent(),
            'terms' => $this->getEffectiveLicenseTerms(),
            'jurisdiction' => $this->license_jurisdiction ?? 'US',
            'requires_signature' => $this->requiresLicenseAgreement(),
            'status' => $this->license_status,
        ];
    }

    /**
     * Create or update license signature
     */
    public function signLicense(User $user, array $signatureData): LicenseSignature
    {
        // Create the signature
        $signature = LicenseSignature::createFromProject($this, $user, $signatureData);

        // Update project status
        $this->update([
            'license_status' => 'active',
            'license_signed_at' => now(),
            'license_signature_ip' => request()->ip(),
            'license_content_hash' => hash('sha256', $this->getLicenseContent()),
        ]);

        // Increment template usage
        if ($this->licenseTemplate) {
            $this->licenseTemplate->incrementUsage();
        }

        return $signature;
    }

    /**
     * Check if user has signed the license
     */
    public function hasUserSignedLicense(User $user): bool
    {
        return $this->activeLicenseSignatures()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Get license status color class
     */
    public function getLicenseStatusColorClass(): string
    {
        return match ($this->license_status) {
            'active' => 'text-green-600 bg-green-100',
            'pending' => 'text-yellow-600 bg-yellow-100',
            'expired' => 'text-red-600 bg-red-100',
            'revoked' => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    /**
     * Get license status label
     */
    public function getLicenseStatusLabel(): string
    {
        return match ($this->license_status) {
            'active' => 'Active',
            'pending' => 'Pending Signature',
            'expired' => 'Expired',
            'revoked' => 'Revoked',
            default => 'No License',
        };
    }

    /**
     * Create custom license template from project terms
     */
    public function createCustomLicenseTemplate(): ?LicenseTemplate
    {
        if (empty($this->custom_license_terms)) {
            return null;
        }

        return LicenseTemplate::create([
            'user_id' => $this->user_id,
            'name' => "Custom License for {$this->name}",
            'content' => $this->generateBasicLicenseContent(),
            'terms' => $this->custom_license_terms,
            'category' => LicenseTemplate::CATEGORY_GENERAL,
            'description' => "Auto-generated custom license for project: {$this->name}",
            'is_active' => false, // Don't make it active by default
            'usage_stats' => ['created' => now()->toISOString(), 'times_used' => 1],
        ]);
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }

    /**
     * Get the full URL for the project image
     *
     * @return string|null
     */
    public function getImageUrlAttribute()
    {
        if (! $this->image_path) {
            return null;
        }

        try {
            // Check if the storage driver supports temporaryUrl
            if (method_exists(Storage::disk('s3'), 'temporaryUrl')) {
                return Storage::disk('s3')->temporaryUrl(
                    $this->image_path,
                    now()->addHours(1) // Longer expiration for images since they're used in UI
                );
            } else {
                // Fallback for storage drivers that don't support temporary URLs
                return Storage::disk('s3')->url($this->image_path);
            }
        } catch (Exception $e) {
            Log::error('Error getting signed project image URL', [
                'project_id' => $this->id,
                'image_path' => $this->image_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if the project has available storage capacity
     *
     * @param  int  $additionalBytes  Additional bytes to check if they would fit
     * @return bool
     */
    public function hasStorageCapacity($additionalBytes = 0)
    {
        // Use the project owner's subscription storage limit
        $storageLimit = $this->getStorageLimit();

        return ($this->total_storage_used + $additionalBytes) <= $storageLimit;
    }

    /**
     * Get the storage limit for this project based on owner's subscription
     *
     * @return int Storage limit in bytes
     */
    public function getStorageLimit(): int
    {
        // Check if user relationship is loaded, if not load it
        if (! $this->relationLoaded('user')) {
            $this->load('user');
        }

        if ($this->user) {
            return $this->user->getProjectStorageLimit();
        }

        // Fallback to default if no user or user has no subscription limits
        return self::MAX_STORAGE_BYTES;
    }

    /**
     * Get remaining storage capacity in bytes
     *
     * @return int
     */
    public function getRemainingStorageBytes()
    {
        $storageLimit = $this->getStorageLimit();
        $remaining = $storageLimit - $this->total_storage_used;

        return max(0, $remaining);
    }

    /**
     * Get the percentage of storage used
     *
     * @return float
     */
    public function getStorageUsedPercentage()
    {
        $storageLimit = $this->getStorageLimit();

        return round(($this->total_storage_used / $storageLimit) * 100, 2);
    }

    /**
     * Check if a file size is within the allowed limit
     *
     * @param  int  $fileSize  File size in bytes
     * @return bool
     */
    public static function isFileSizeAllowed($fileSize)
    {
        return $fileSize <= self::MAX_FILE_SIZE_BYTES;
    }

    /**
     * Get user-friendly message about storage limits
     *
     * @return string
     */
    public function getStorageLimitMessage()
    {
        $used = Number::fileSize($this->total_storage_used, precision: 2);
        $total = Number::fileSize($this->getStorageLimit(), precision: 2);
        $remaining = Number::fileSize($this->getRemainingStorageBytes(), precision: 2);

        return "Using $used of $total ($remaining available)";
    }

    /**
     * Atomically increment the total storage used.
     */
    public function incrementStorageUsed(int $bytes): bool
    {
        return $this->increment('total_storage_used', $bytes);
    }

    /**
     * Atomically decrement the total storage used.
     */
    public function decrementStorageUsed(int $bytes): bool
    {
        return $this->update([
            'total_storage_used' => DB::raw("GREATEST(0, total_storage_used - $bytes)"),
        ]);
    }

    /**
     * Get storage used by project files (client uploads) in bytes
     */
    public function getProjectFilesStorageUsed(): int
    {
        return $this->files()->sum('size') ?? 0;
    }

    /**
     * Get storage used by pitch files (producer uploads) in bytes
     */
    public function getPitchFilesStorageUsed(): int
    {
        return $this->pitches()->with('files')->get()
            ->flatMap(fn ($pitch) => $pitch->files)
            ->sum('size') ?? 0;
    }

    /**
     * Get combined storage usage for client management projects
     */
    public function getCombinedStorageUsed(): int
    {
        if ($this->isClientManagement()) {
            return $this->getProjectFilesStorageUsed() + $this->getPitchFilesStorageUsed();
        }

        // For other project types, use the existing total_storage_used
        return $this->total_storage_used ?? 0;
    }

    /**
     * Get storage breakdown for client management projects
     */
    public function getStorageBreakdown(): array
    {
        if (! $this->isClientManagement()) {
            return [
                'total' => $this->total_storage_used ?? 0,
                'project_files' => 0,
                'pitch_files' => 0,
            ];
        }

        $projectFiles = $this->getProjectFilesStorageUsed();
        $pitchFiles = $this->getPitchFilesStorageUsed();

        return [
            'total' => $projectFiles + $pitchFiles,
            'project_files' => $projectFiles,
            'pitch_files' => $pitchFiles,
        ];
    }

    /**
     * Scope a query to apply filters and sorting.
     */
    public function scopeFilterAndSort(Builder $query, array $filters): Builder
    {
        // Apply filters
        $query->when($filters['genres'] ?? null, function ($q, $genres) {
            $q->whereIn('genre', $genres);
        });

        $query->when($filters['statuses'] ?? null, function ($q, $statuses) {
            $q->whereIn('status', $statuses);
        });

        $query->when($filters['projectTypes'] ?? null, function ($q, $projectTypes) {
            $q->whereIn('workflow_type', $projectTypes);
        });

        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        });

        $minBudget = $filters['min_budget'] ?? null;
        $maxBudget = $filters['max_budget'] ?? null;
        $query->when($minBudget && $maxBudget, function ($q) use ($minBudget, $maxBudget) {
            $q->whereBetween('budget', [(int) $minBudget, (int) $maxBudget]);
        })->when($minBudget && ! $maxBudget, function ($q) use ($minBudget) {
            $q->where('budget', '>=', (int) $minBudget);
        })->when(! $minBudget && $maxBudget, function ($q) use ($maxBudget) {
            $q->where('budget', '<=', (int) $maxBudget);
        });

        $deadlineStart = $filters['deadline_start'] ?? null;
        $deadlineEnd = $filters['deadline_end'] ?? null;

        // Try using Carbon objects directly for comparison
        $start = $deadlineStart ? \Carbon\Carbon::parse($deadlineStart)->startOfDay() : null;
        $end = $deadlineEnd ? \Carbon\Carbon::parse($deadlineEnd)->endOfDay() : null;

        $query->when($start && $end, function ($q) use ($start, $end) {
            $q->whereBetween('deadline', [$start, $end]);
        })->when($start && ! $end, function ($q) use ($start) {
            $q->where('deadline', '>=', $start); // where() should handle Carbon comparison
        })->when(! $start && $end, function ($q) use ($end) {
            $q->where('deadline', '<=', $end); // where() should handle Carbon comparison
        });

        // Collaboration Type Filtering (JSON array)
        $query->when($filters['selected_collaboration_types'] ?? null, function ($q, $types) {
            // Ensure $types is an array
            if (! is_array($types) || empty($types)) {
                return;
            }

            // Check if we're using SQLite
            $isSqlite = DB::connection()->getDriverName() === 'sqlite';

            if ($isSqlite) {
                // SQLite-compatible alternative approach
                // This is a fallback that works in SQLite but might be less efficient
                $q->where(function (Builder $subQuery) use ($types) {
                    foreach ($types as $type) {
                        // Use LIKE for SQLite as a simple workaround
                        // This is less precise but allows filtering to work
                        $subQuery->orWhere('collaboration_type', 'LIKE', '%"'.$type.'"%');
                    }
                });
            } else {
                // Standard approach for MySQL/PostgreSQL
                $q->where(function (Builder $subQuery) use ($types) {
                    foreach ($types as $type) {
                        $subQuery->orWhereJsonContains('collaboration_type', $type);
                    }
                });
            }
        });

        // Apply sorting
        $sortBy = $filters['sortBy'] ?? 'latest';
        switch ($sortBy) {
            case 'budget_high_low':
                $query->orderBy('budget', 'desc');
                break;
            case 'budget_low_high':
                $query->orderBy('budget', 'asc');
                break;
            case 'deadline':
                $query->orderBy('deadline', 'asc');
                break;
            case 'oldest':
                $query->oldest();
                break;
            default: // 'latest'
                $query->latest();
                break;
        }

        return $query;
    }

    /**
     * Relationship for Direct Hire target producer.
     */
    public function targetProducer()
    {
        return $this->belongsTo(User::class, 'target_producer_id');
    }

    /**
     * Check if the project type is Standard.
     */
    public function isStandard(): bool
    {
        return $this->workflow_type === self::WORKFLOW_TYPE_STANDARD;
    }

    /**
     * Check if the project type is Contest.
     */
    public function isContest(): bool
    {
        return $this->workflow_type === self::WORKFLOW_TYPE_CONTEST;
    }

    /**
     * Check if the project type is Direct Hire.
     */
    public function isDirectHire(): bool
    {
        return $this->workflow_type === self::WORKFLOW_TYPE_DIRECT_HIRE;
    }

    /**
     * Check if the project type is Client Management.
     */
    public function isClientManagement(): bool
    {
        return $this->workflow_type === self::WORKFLOW_TYPE_CLIENT_MANAGEMENT;
    }

    /**
     * Get the contest result relationship
     */
    public function contestResult()
    {
        return $this->hasOne(ContestResult::class);
    }

    /**
     * Get the contest prizes relationship
     */
    public function contestPrizes()
    {
        return $this->hasMany(ContestPrize::class);
    }

    /**
     * Get the total cash prize budget for this contest
     */
    public function getTotalPrizeBudget(): float
    {
        return (float) $this->contestPrizes()
            ->where('prize_type', ContestPrize::TYPE_CASH)
            ->sum('cash_amount');
    }

    /**
     * Get the total estimated value of all prizes (cash + estimated values of other prizes)
     */
    public function getTotalPrizeValue(): float
    {
        $cashTotal = $this->getTotalPrizeBudget();
        $otherTotal = (float) $this->contestPrizes()
            ->where('prize_type', ContestPrize::TYPE_OTHER)
            ->sum('prize_value_estimate');

        return $cashTotal + $otherTotal;
    }

    /**
     * Get prize for a specific placement
     */
    public function getPrizeForPlacement(string $placement): ?ContestPrize
    {
        return $this->contestPrizes()
            ->where('placement', $placement)
            ->first();
    }

    /**
     * Check if the contest has any prizes configured
     */
    public function hasPrizes(): bool
    {
        return $this->contestPrizes()->exists();
    }

    /**
     * Check if the contest has cash prizes
     */
    public function hasCashPrizes(): bool
    {
        return $this->contestPrizes()
            ->where('prize_type', ContestPrize::TYPE_CASH)
            ->exists();
    }

    /**
     * Get count of each prize type
     */
    public function getPrizeTypeCounts(): array
    {
        $prizes = $this->contestPrizes()->get();

        return [
            'total' => $prizes->count(),
            'cash' => $prizes->where('prize_type', ContestPrize::TYPE_CASH)->count(),
            'other' => $prizes->where('prize_type', ContestPrize::TYPE_OTHER)->count(),
        ];
    }

    /**
     * Get a summary of all prizes for display
     */
    public function getPrizeSummary(): array
    {
        $prizes = $this->contestPrizes()->orderByRaw("
            CASE placement 
                WHEN '1st' THEN 1 
                WHEN '2nd' THEN 2 
                WHEN '3rd' THEN 3 
                WHEN 'runner_up' THEN 4 
                ELSE 5 
            END
        ")->get();

        $summary = [];
        foreach ($prizes as $prize) {
            $summary[] = [
                'placement' => $prize->getPlacementDisplayName(),
                'placement_key' => $prize->placement,
                'type' => $prize->prize_type,
                'display_value' => $prize->getDisplayValue(),
                'cash_value' => $prize->getCashValue(),
                'estimated_value' => $prize->getEstimatedValue(),
                'emoji' => $prize->getPlacementEmoji(),
                'title' => $prize->prize_title,
                'description' => $prize->prize_description,
            ];
        }

        return $summary;
    }

    /**
     * Check if contest judging has been finalized
     */
    public function isJudgingFinalized(): bool
    {
        return ! is_null($this->judging_finalized_at);
    }

    /**
     * Check if contest judging can be finalized
     */
    public function canFinalizeJudging(): bool
    {
        return $this->isContest() &&
               $this->isSubmissionPeriodClosed() &&
               ! $this->isJudgingFinalized();
    }

    /**
     * Check if contest submissions are closed (either by deadline or early closure)
     */
    public function isSubmissionPeriodClosed(): bool
    {
        if (! $this->isContest()) {
            return false;
        }

        // Check if closed early
        if ($this->submissions_closed_early_at) {
            return true;
        }

        // Check if deadline has passed
        return $this->submission_deadline && $this->submission_deadline->isPast();
    }

    /**
     * Check if contest was closed early
     */
    public function wasClosedEarly(): bool
    {
        return ! is_null($this->submissions_closed_early_at);
    }

    /**
     * Check if contest can be closed early
     */
    public function canCloseEarly(): bool
    {
        return $this->isContest() &&
               $this->is_published &&
               ! $this->isSubmissionPeriodClosed() &&
               ! $this->isJudgingFinalized() &&
               $this->getContestEntries()->isNotEmpty();
    }

    /**
     * Get the effective submission deadline (early closure or original deadline)
     */
    public function getEffectiveSubmissionDeadline(): ?\Carbon\Carbon
    {
        if ($this->submissions_closed_early_at) {
            return $this->submissions_closed_early_at;
        }

        return $this->submission_deadline;
    }

    /**
     * Get relationship to user who closed submissions early
     */
    public function submissionsClosedEarlyBy()
    {
        return $this->belongsTo(User::class, 'submissions_closed_early_by');
    }

    /**
     * Get all contest entries for this project
     */
    public function getContestEntries()
    {
        return $this->pitches()
            ->whereIn('status', [
                Pitch::STATUS_CONTEST_ENTRY,
                Pitch::STATUS_CONTEST_WINNER,
                Pitch::STATUS_CONTEST_RUNNER_UP,
                Pitch::STATUS_CONTEST_NOT_SELECTED,
            ])
            ->with(['user', 'currentSnapshot'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get an array of all project types.
     */
    public static function getWorkflowTypes(): array
    {
        return [
            self::WORKFLOW_TYPE_STANDARD,
            self::WORKFLOW_TYPE_CONTEST,
            self::WORKFLOW_TYPE_DIRECT_HIRE,
            self::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        ];
    }

    /**
     * Get human-readable project type name.
     */
    public function getReadableWorkflowTypeAttribute(): string
    {
        $types = [
            self::WORKFLOW_TYPE_STANDARD => 'Standard Project',
            self::WORKFLOW_TYPE_CONTEST => 'Contest',
            self::WORKFLOW_TYPE_DIRECT_HIRE => 'Direct Hire',
            self::WORKFLOW_TYPE_CLIENT_MANAGEMENT => 'Client Management',
        ];

        return $types[$this->workflow_type] ?? 'Unknown Type';
    }

    /**
     * Get human-readable workflow type name for a given workflow type.
     */
    public static function getReadableWorkflowType(string $workflowType): string
    {
        $types = [
            self::WORKFLOW_TYPE_STANDARD => 'Standard Project',
            self::WORKFLOW_TYPE_CONTEST => 'Contest',
            self::WORKFLOW_TYPE_DIRECT_HIRE => 'Direct Hire',
            self::WORKFLOW_TYPE_CLIENT_MANAGEMENT => 'Client Management',
        ];

        return $types[$workflowType] ?? 'Unknown Type';
    }

    /**
     * Get the CSS color class for the current status
     */
    public function getStatusColorClass(): string
    {
        switch ($this->status) {
            case self::STATUS_UNPUBLISHED:
                return 'text-gray-600 bg-gray-100';
            case self::STATUS_OPEN:
                return 'text-green-700 bg-green-100';
            case self::STATUS_IN_PROGRESS:
                return 'text-blue-700 bg-blue-100';
            case self::STATUS_COMPLETED:
                return 'text-purple-700 bg-purple-100';
            default:
                return 'text-gray-700 bg-gray-100';
        }
    }

    // ========== PRIVACY METHODS ==========

    /**
     * Check if the project is private
     */
    public function isPrivate(): bool
    {
        return $this->is_private || $this->visibility_level === self::VISIBILITY_PRIVATE;
    }

    /**
     * Check if the project is public
     */
    public function isPublic(): bool
    {
        return $this->visibility_level === self::VISIBILITY_PUBLIC && ! $this->is_private;
    }

    /**
     * Check if the project is unlisted
     */
    public function isUnlisted(): bool
    {
        return $this->visibility_level === self::VISIBILITY_UNLISTED;
    }

    /**
     * Check if the project is invite-only
     */
    public function isInviteOnly(): bool
    {
        return $this->visibility_level === self::VISIBILITY_INVITE_ONLY;
    }

    /**
     * Make the project private
     */
    public function makePrivate(User $user, array $settings = []): bool
    {
        // Check if user can create private projects
        if (! $this->canUserCreatePrivateProject($user)) {
            return false;
        }

        $this->update([
            'is_private' => true,
            'visibility_level' => self::VISIBILITY_PRIVATE,
            'privacy_set_at' => now(),
            'privacy_month_year' => now()->format('Y-m'),
            'privacy_settings' => array_merge([
                'created_by' => $user->id,
                'reason' => 'user_requested',
                'allow_direct_access' => false,
            ], $settings),
            'access_code' => $this->generateAccessCode(),
        ]);

        // Update monthly limit tracking
        $this->incrementUserPrivateProjectCount($user);

        return true;
    }

    /**
     * Make the project public
     */
    public function makePublic(): void
    {
        $this->update([
            'is_private' => false,
            'visibility_level' => self::VISIBILITY_PUBLIC,
            'privacy_set_at' => null,
            'privacy_settings' => null,
            'access_code' => null,
            'privacy_month_year' => null,
        ]);
    }

    /**
     * Set visibility level
     */
    public function setVisibilityLevel(string $level, User $user): bool
    {
        if (! in_array($level, [
            self::VISIBILITY_PUBLIC,
            self::VISIBILITY_UNLISTED,
            self::VISIBILITY_PRIVATE,
            self::VISIBILITY_INVITE_ONLY,
        ])) {
            return false;
        }

        // For private/invite-only, check subscription limits
        if (in_array($level, [self::VISIBILITY_PRIVATE, self::VISIBILITY_INVITE_ONLY])) {
            if (! $this->canUserCreatePrivateProject($user)) {
                return false;
            }

            $this->update([
                'visibility_level' => $level,
                'is_private' => true,
                'privacy_set_at' => now(),
                'privacy_month_year' => now()->format('Y-m'),
                'access_code' => $this->generateAccessCode(),
            ]);

            $this->incrementUserPrivateProjectCount($user);
        } else {
            $this->update([
                'visibility_level' => $level,
                'is_private' => false,
                'privacy_set_at' => null,
                'privacy_month_year' => null,
                'access_code' => null,
            ]);
        }

        return true;
    }

    /**
     * Check if user can create/modify private projects
     */
    private function canUserCreatePrivateProject(User $user): bool
    {
        // If this project is already private, allow modification
        if ($this->exists && $this->isPrivate()) {
            return true;
        }

        $monthlyLimit = $user->getMaxPrivateProjectsMonthly();

        // Unlimited for Pro Engineer
        if ($monthlyLimit === null) {
            return true;
        }

        // Not allowed for free users
        if ($monthlyLimit === 0) {
            return false;
        }

        // Check monthly usage
        $currentMonth = now()->format('Y-m');
        $usedThisMonth = self::where('user_id', $user->id)
            ->where('is_private', true)
            ->where('privacy_month_year', $currentMonth)
            ->count();

        return $usedThisMonth < $monthlyLimit;
    }

    /**
     * Increment user's private project count for the month
     */
    private function incrementUserPrivateProjectCount(User $user): void
    {
        $currentMonth = now()->format('Y-m');

        $monthlyLimit = UserMonthlyLimit::updateOrCreate(
            ['user_id' => $user->id, 'month_year' => $currentMonth],
            ['last_reset_at' => now()]
        );

        $monthlyLimit->increment('private_projects_created');
    }

    /**
     * Generate a secure access code for private projects
     */
    private function generateAccessCode(): string
    {
        return Str::random(16);
    }

    /**
     * Check if user can view this project
     */
    public function canUserView(?User $user = null, ?string $accessCode = null): bool
    {
        // Owner can always view
        if ($user && $this->isOwnedByUser($user)) {
            return true;
        }

        // Public projects are viewable by everyone
        if ($this->isPublic()) {
            return true;
        }

        // Unlisted projects are viewable by anyone with the link
        if ($this->isUnlisted()) {
            return true;
        }

        // Private/Invite-only projects need special access
        if ($this->isPrivate() || $this->isInviteOnly()) {
            // Check access code
            if ($accessCode && $this->access_code === $accessCode) {
                return true;
            }

            // Check if user is invited (could implement invitation system later)
            // For now, only owner can view
            return false;
        }

        return false;
    }

    /**
     * Get privacy status label
     */
    public function getPrivacyStatusLabel(): string
    {
        if ($this->isPrivate()) {
            return 'Private';
        }

        return match ($this->visibility_level) {
            self::VISIBILITY_PUBLIC => 'Public',
            self::VISIBILITY_UNLISTED => 'Unlisted',
            self::VISIBILITY_INVITE_ONLY => 'Invite Only',
            default => 'Public',
        };
    }

    /**
     * Get privacy icon class
     */
    public function getPrivacyIconClass(): string
    {
        if ($this->isPrivate()) {
            return 'fas fa-lock text-red-500';
        }

        return match ($this->visibility_level) {
            self::VISIBILITY_PUBLIC => 'fas fa-globe text-green-500',
            self::VISIBILITY_UNLISTED => 'fas fa-eye-slash text-yellow-500',
            self::VISIBILITY_INVITE_ONLY => 'fas fa-user-friends text-blue-500',
            default => 'fas fa-globe text-green-500',
        };
    }

    // ========== QUERY SCOPES ==========

    /**
     * Scope to only public projects
     */
    public function scopePublic($query)
    {
        return $query->where('visibility_level', self::VISIBILITY_PUBLIC)
            ->where('is_private', false);
    }

    /**
     * Scope to only private projects
     */
    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    /**
     * Scope to projects viewable by a specific user
     */
    public function scopeViewableBy($query, ?User $user = null)
    {
        if (! $user) {
            // Anonymous users can only see public projects
            return $query->public();
        }

        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id) // Own projects
                ->orWhere(function ($subQuery) {
                    $subQuery->where('visibility_level', self::VISIBILITY_PUBLIC)
                        ->where('is_private', false);
                })
                ->orWhere('visibility_level', self::VISIBILITY_UNLISTED); // Unlisted are viewable with link
        });
    }

    /**
     * Scope to exclude private projects from public listings
     */
    public function scopePubliclyViewable($query)
    {
        return $query->whereIn('visibility_level', [
            self::VISIBILITY_PUBLIC,
            self::VISIBILITY_UNLISTED,
        ])->where('is_private', false);
    }

    /**
     * Scope by visibility level
     */
    public function scopeByVisibility($query, string $level)
    {
        return $query->where('visibility_level', $level);
    }

    /**
     * Scope to user's private projects for current month
     */
    public function scopeUserPrivateCurrentMonth($query, User $user)
    {
        $currentMonth = now()->format('Y-m');

        return $query->where('user_id', $user->id)
            ->where('is_private', true)
            ->where('privacy_month_year', $currentMonth);
    }

    /**
     * Static method to get available visibility levels for a user
     */
    public static function getAvailableVisibilityLevels(User $user): array
    {
        $levels = [
            self::VISIBILITY_PUBLIC => 'Public - Anyone can find and view',
            self::VISIBILITY_UNLISTED => 'Unlisted - Only people with the link can view',
        ];

        // Add private options for eligible users
        $monthlyLimit = $user->getMaxPrivateProjectsMonthly();
        if ($monthlyLimit === null || $monthlyLimit > 0) {
            $levels[self::VISIBILITY_PRIVATE] = 'Private - Only you can view';
            $levels[self::VISIBILITY_INVITE_ONLY] = 'Invite Only - Only invited users can view';
        }

        return $levels;
    }

    /**
     * Get user's remaining private project quota for current month
     */
    public static function getRemainingPrivateQuota(User $user): ?int
    {
        $monthlyLimit = $user->getMaxPrivateProjectsMonthly();

        if ($monthlyLimit === null) {
            return null; // Unlimited
        }

        $currentMonth = now()->format('Y-m');
        $usedThisMonth = self::where('user_id', $user->id)
            ->where('is_private', true)
            ->where('privacy_month_year', $currentMonth)
            ->count();

        return max(0, $monthlyLimit - $usedThisMonth);
    }

    // ========== REDDIT INTEGRATION ==========

    /**
     * Check if this project has been posted to Reddit
     */
    public function hasBeenPostedToReddit(): bool
    {
        return ! is_null($this->reddit_post_id);
    }

    /**
     * Get the Reddit post URL
     */
    public function getRedditUrl(): ?string
    {
        return $this->reddit_permalink;
    }

    /**
     * Get comprehensive contest payment status information
     */
    public function getContestPaymentStatus(): array
    {
        if (! $this->isContest() || ! $this->isJudgingFinalized()) {
            return [
                'has_cash_prizes' => false,
                'total_prize_amount' => 0,
                'payment_status' => 'not_applicable',
                'prizes_paid' => 0,
                'prizes_pending' => 0,
                'winners_with_status' => [],
                'summary' => 'Contest not finalized',
            ];
        }

        $cashPrizes = $this->contestPrizes()
            ->where('prize_type', 'cash')
            ->where('cash_amount', '>', 0)
            ->get();

        if ($cashPrizes->isEmpty()) {
            return [
                'has_cash_prizes' => false,
                'total_prize_amount' => 0,
                'payment_status' => 'no_cash_prizes',
                'prizes_paid' => 0,
                'prizes_pending' => 0,
                'winners_with_status' => [],
                'summary' => 'No cash prizes to pay',
            ];
        }

        $contestResult = $this->contestResult;
        $totalPrizeAmount = $cashPrizes->sum('cash_amount');
        $winnersWithStatus = [];
        $prizesPaid = 0;
        $prizesPending = 0;

        foreach ($cashPrizes as $prize) {
            $winnerPitch = $contestResult ? $contestResult->getWinnerForPlacement($prize->placement) : null;

            if ($winnerPitch) {
                $isPaid = $winnerPitch->payment_status === 'paid';
                $user = $winnerPitch->user;

                $winnersWithStatus[] = [
                    'prize' => $prize,
                    'pitch' => $winnerPitch,
                    'user' => $user,
                    'is_paid' => $isPaid,
                    'payment_date' => $winnerPitch->payment_completed_at,
                    'stripe_ready' => $user->stripe_account_id && $user->hasValidStripeConnectAccount(),
                    'payment_amount' => $prize->cash_amount,
                ];

                if ($isPaid) {
                    $prizesPaid++;
                } else {
                    $prizesPending++;
                }
            }
        }

        // Determine overall payment status
        $paymentStatus = 'pending';
        $summary = '';

        if ($prizesPaid === 0 && $prizesPending === 0) {
            $paymentStatus = 'no_winners';
            $summary = 'No winners selected for cash prizes';
        } elseif ($prizesPaid === count($winnersWithStatus)) {
            $paymentStatus = 'all_paid';
            $summary = 'All prizes paid';
        } elseif ($prizesPaid > 0) {
            $paymentStatus = 'partially_paid';
            $summary = "{$prizesPaid} of ".count($winnersWithStatus).' prizes paid';
        } else {
            $paymentStatus = 'none_paid';
            $summary = 'No prizes paid yet';
        }

        return [
            'has_cash_prizes' => true,
            'total_prize_amount' => $totalPrizeAmount,
            'payment_status' => $paymentStatus,
            'prizes_paid' => $prizesPaid,
            'prizes_pending' => $prizesPending,
            'winners_with_status' => $winnersWithStatus,
            'summary' => $summary,
        ];
    }
}
