<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

use Illuminate\Support\Facades\Storage;
use Sebdesign\SM\StateMachine\StateMachine;
use Sebdesign\SM\StateMachine\StateMachineInterface;
use Illuminate\Support\Number;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
        'slug',
        'submission_deadline',
        'judging_deadline',
        'judging_finalized_at',
        'results_announced_at',
        'results_announced_by',
        'judging_notes',
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
        'payment_amount',
        'completed_at',
    ];

    protected $casts = [
        'collaboration_type' => 'array',
        'is_published' => 'boolean',
        'completed_at' => 'datetime',
        'deadline' => 'datetime',
        'target_producer_id' => 'integer',
        'project_type_id' => 'integer',
        'prize_amount' => 'decimal:2',
        'submission_deadline' => 'datetime',
        'judging_deadline' => 'datetime',
        'judging_finalized_at' => 'datetime',
        'results_announced_at' => 'datetime',
        'show_submissions_publicly' => 'boolean',
    ];

    protected $attributes = [
        'workflow_type' => self::WORKFLOW_TYPE_STANDARD,
        'status' => self::STATUS_UNPUBLISHED,
        'is_published' => false
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
                return Storage::disk('s3')->temporaryUrl(
                    $track->file_path,
                    now()->addMinutes(15)
                );
            } catch (Exception $e) {
                \Log::error('Error getting signed preview track path', [
                    'track_id' => $this->preview_track,
                    'error' => $e->getMessage()
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

    public function pitches()
    {
        return $this->hasMany(Pitch::class);
    }

    public function userPitch($userId)
    {
        return $this->pitches()->where('user_id', $userId)->with('project')->first();
    }

    /**
     * Check if the project is open and accepting new pitches.
     *
     * @return bool
     */
    public function isOpenForPitches(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function mixes()
    {
        return $this->hasMany(Mix::class);
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    /**
     * Get the full URL for the project image
     *
     * @return string|null
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }
        
        try {
            return Storage::disk('s3')->temporaryUrl(
                $this->image_path,
                now()->addHours(1) // Longer expiration for images since they're used in UI
            );
        } catch (Exception $e) {
            \Log::error('Error getting signed project image URL', [
                'project_id' => $this->id,
                'image_path' => $this->image_path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if the project has available storage capacity
     * 
     * @param int $additionalBytes Additional bytes to check if they would fit
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
        if (!$this->relationLoaded('user')) {
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
     * @param int $fileSize File size in bytes
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
            'total_storage_used' => DB::raw("GREATEST(0, total_storage_used - $bytes)")
        ]);
    }

    /**
     * Get storage used by project files (client uploads) in bytes
     */
    public function getProjectFilesStorageUsed(): int
    {
        return $this->files()->sum('file_size') ?? 0;
    }

    /**
     * Get storage used by pitch files (producer uploads) in bytes
     */
    public function getPitchFilesStorageUsed(): int
    {
        return $this->pitches()->with('files')->get()
            ->flatMap(fn($pitch) => $pitch->files)
            ->sum('file_size') ?? 0;
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
        if (!$this->isClientManagement()) {
            return [
                'total' => $this->total_storage_used ?? 0,
                'project_files' => 0,
                'pitch_files' => 0
            ];
        }

        $projectFiles = $this->getProjectFilesStorageUsed();
        $pitchFiles = $this->getPitchFilesStorageUsed();

        return [
            'total' => $projectFiles + $pitchFiles,
            'project_files' => $projectFiles,
            'pitch_files' => $pitchFiles
        ];
    }

    /**
     * Scope a query to apply filters and sorting.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
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
                $sq->where('name', 'like', '%' . $search . '%')
                   ->orWhere('description', 'like', '%' . $search . '%');
            });
        });

        $minBudget = $filters['min_budget'] ?? null;
        $maxBudget = $filters['max_budget'] ?? null;
        $query->when($minBudget && $maxBudget, function ($q) use ($minBudget, $maxBudget) {
            $q->whereBetween('budget', [(int)$minBudget, (int)$maxBudget]);
        })->when($minBudget && !$maxBudget, function ($q) use ($minBudget) {
            $q->where('budget', '>=', (int)$minBudget);
        })->when(!$minBudget && $maxBudget, function ($q) use ($maxBudget) {
            $q->where('budget', '<=', (int)$maxBudget);
        });

        $deadlineStart = $filters['deadline_start'] ?? null;
        $deadlineEnd = $filters['deadline_end'] ?? null;

        // Try using Carbon objects directly for comparison
        $start = $deadlineStart ? \Carbon\Carbon::parse($deadlineStart)->startOfDay() : null;
        $end = $deadlineEnd ? \Carbon\Carbon::parse($deadlineEnd)->endOfDay() : null;

        $query->when($start && $end, function ($q) use ($start, $end) {
            $q->whereBetween('deadline', [$start, $end]);
        })->when($start && !$end, function ($q) use ($start) {
            $q->where('deadline', '>=', $start); // where() should handle Carbon comparison
        })->when(!$start && $end, function ($q) use ($end) {
            $q->where('deadline', '<=', $end); // where() should handle Carbon comparison
        });

        // Collaboration Type Filtering (JSON array)
        $query->when($filters['selected_collaboration_types'] ?? null, function ($q, $types) {
            // Ensure $types is an array
            if (!is_array($types) || empty($types)) {
                return;
            }
            
            // Check if we're using SQLite
            $isSqlite = \DB::connection()->getDriverName() === 'sqlite';
            
            if ($isSqlite) {
                // SQLite-compatible alternative approach
                // This is a fallback that works in SQLite but might be less efficient
                $q->where(function (Builder $subQuery) use ($types) {
                    foreach ($types as $type) {
                        // Use LIKE for SQLite as a simple workaround
                        // This is less precise but allows filtering to work
                        $subQuery->orWhere('collaboration_type', 'LIKE', '%"' . $type . '"%');
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
     *
     * @return bool
     */
    public function isStandard(): bool
    {
        return $this->workflow_type === self::WORKFLOW_TYPE_STANDARD;
    }

    /**
     * Check if the project type is Contest.
     *
     * @return bool
     */
    public function isContest(): bool
    {
        return $this->workflow_type === self::WORKFLOW_TYPE_CONTEST;
    }

    /**
     * Check if the project type is Direct Hire.
     *
     * @return bool
     */
    public function isDirectHire(): bool
    {
        return $this->workflow_type === self::WORKFLOW_TYPE_DIRECT_HIRE;
    }

    /**
     * Check if the project type is Client Management.
     *
     * @return bool
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
                'description' => $prize->prize_description
            ];
        }

        return $summary;
    }

    /**
     * Check if contest judging has been finalized
     */
    public function isJudgingFinalized(): bool
    {
        return !is_null($this->judging_finalized_at);
    }

    /**
     * Check if contest judging can be finalized
     */
    public function canFinalizeJudging(): bool
    {
        return $this->isContest() && 
               $this->submission_deadline && 
               $this->submission_deadline->isPast() && 
               !$this->isJudgingFinalized();
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
                Pitch::STATUS_CONTEST_NOT_SELECTED
            ])
            ->with(['user', 'currentSnapshot'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get an array of all project types.
     *
     * @return array
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
     *
     * @return string
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
     * Get the CSS color class for the current status
     * 
     * @return string
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
}
