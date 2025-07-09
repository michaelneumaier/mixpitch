<?php

namespace App\Models;

use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Exceptions\Pitch\SnapshotException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Pitch extends Model implements HasMedia
{
    use HasFactory, Sluggable, SoftDeletes, InteractsWithMedia;
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_READY_FOR_REVIEW = 'ready_for_review';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_DENIED = 'denied';
    const STATUS_REVISIONS_REQUESTED = 'revisions_requested';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CLOSED = 'closed';
    const STATUS_INPROGRESS = 'inprogress';

    // Contest Statuses
    const STATUS_CONTEST_ENTRY = 'contest_entry';
    const STATUS_CONTEST_WINNER = 'contest_winner';
    const STATUS_CONTEST_RUNNER_UP = 'contest_runner_up';
    const STATUS_CONTEST_NOT_SELECTED = 'contest_not_selected';

    // Direct Hire Status (Optional - for explicit acceptance flow)
    const STATUS_AWAITING_ACCEPTANCE = 'awaiting_acceptance';

    // Client Management Status (Optional - for explicit client feedback loop)
    const STATUS_CLIENT_REVISIONS_REQUESTED = 'client_revisions_requested';

    // Payment status constants
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PROCESSING = 'processing';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_NOT_REQUIRED = 'payment_not_required';
    const PAYMENT_STATUS_REFUNDED = 'refunded';

    // Contest rank constants
    const RANK_FIRST = '1st';
    const RANK_SECOND = '2nd';
    const RANK_THIRD = '3rd';
    const RANK_RUNNER_UP = 'runner-up';

    /**
     * The maximum storage allowed per pitch in bytes (1GB)
     */
    const MAX_STORAGE_BYTES = 1073741824; // 1GB in bytes
    
    /**
     * The maximum file size allowed per upload in bytes (200MB)
     */
    const MAX_FILE_SIZE_BYTES = 209715200; // 200MB in bytes

    protected $fillable = [
        'project_id',
        'user_id',
        'title',
        'description',
        'status',
        'rank',
        'current_snapshot_id',
        'completed_at',
        'payment_status',
        'final_invoice_id',
        'payment_amount',
        'payment_completed_at',
        'completion_feedback',
        'completion_date',
        'slug',
        'terms_agreed',
        'message',
        'is_active',
        'has_files',
        'status_message',
        'internal_notes',
        'price',
        'paid_at',
        'denied_at',
        'approved_at',
        'revisions_requested_at',
        'submitted_at',
        'closed_at',
        'rank', // Add rank for contests (optional)
        'amount',
        'currency',
        'contest_ranking', // e.g., 1 for winner, 2 for runner-up
        'judging_notes',
        'placement_finalized_at',
        // Client management specific
        'client_approved_at',
        'client_revision_requested_at',
        'client_submitted_at',
        // Audio processing fields
        'audio_processed',
        'audio_processed_at',
        'audio_processing_results',
        'audio_processing_error',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'completed_at',
        'payment_completed_at',
        'approved_at',
        'rejected_at',
        'client_approved_at',
        'client_revision_requested_at',
        'client_submitted_at',
    ];

    protected $attributes = [
        'max_files' => 25,
        'is_inactive' => false,
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'revision_requested_at' => 'datetime',
        'submitted_at' => 'datetime',
        'client_approved_at' => 'datetime',
        'client_revision_requested_at' => 'datetime',
        'client_submitted_at' => 'datetime',
        'payment_completed_at' => 'datetime',
        'amount' => 'decimal:2',
        'audio_processed' => 'boolean',
        'audio_processed_at' => 'datetime',
        'audio_processing_results' => 'json',
    ];

    public static $transitions = [
        'forward' => [
            self::STATUS_PENDING => self::STATUS_IN_PROGRESS,
            self::STATUS_IN_PROGRESS => self::STATUS_READY_FOR_REVIEW,
            self::STATUS_PENDING_REVIEW => self::STATUS_READY_FOR_REVIEW,
            self::STATUS_READY_FOR_REVIEW => [
                self::STATUS_APPROVED,
                self::STATUS_DENIED,
                self::STATUS_REVISIONS_REQUESTED,
            ],
            self::STATUS_APPROVED => self::STATUS_COMPLETED,
            self::STATUS_REVISIONS_REQUESTED => self::STATUS_READY_FOR_REVIEW,
            // No forward transitions from closed status
        ],
        'backward' => [
            self::STATUS_IN_PROGRESS => self::STATUS_PENDING,
            self::STATUS_APPROVED => self::STATUS_READY_FOR_REVIEW,
            self::STATUS_DENIED => self::STATUS_READY_FOR_REVIEW,
            self::STATUS_REVISIONS_REQUESTED => self::STATUS_IN_PROGRESS,
            self::STATUS_PENDING_REVIEW => [
                self::STATUS_PENDING,
                self::STATUS_IN_PROGRESS,
            ],
            self::STATUS_READY_FOR_REVIEW => [
                self::STATUS_IN_PROGRESS,
                self::STATUS_DENIED,
                self::STATUS_REVISIONS_REQUESTED,
                self::STATUS_PENDING_REVIEW,
            ],
            self::STATUS_COMPLETED => self::STATUS_APPROVED,
            // No backward transitions from closed status
        ],
    ];

    protected static function booted()
    {
        parent::booted();

        // Ensure slug is generated on creation
        static::creating(function ($pitch) {
            if (empty($pitch->slug)) {
                $pitch->slug = $pitch->generateSlug();
            }
        });

        // Handle events for notification triggers
        static::updated(function ($pitch) {
            $notificationService = app(\App\Services\NotificationService::class);

            // Handle status changes
            if ($pitch->isDirty('status')) {
                $oldStatus = $pitch->getOriginal('status');
                $newStatus = $pitch->status;

                // Log status transition for debugging
                \Log::info('Pitch status changed', [
                    'pitch_id' => $pitch->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);

                // Don't create a generic status change notification if we're going to create a more specific one
                // in the component actions (e.g., approval, denial, changes requested)
                $skipGenericNotification =
                    ($newStatus === self::STATUS_APPROVED) ||
                    ($newStatus === self::STATUS_DENIED) ||
                    ($newStatus === self::STATUS_REVISIONS_REQUESTED) ||
                    ($newStatus === self::STATUS_COMPLETED);

                // If we're transitioning to/from a review status, this is a significant change for the pitch creator
                $isSignificantTransition =
                    ($oldStatus === self::STATUS_READY_FOR_REVIEW && $newStatus === self::STATUS_PENDING_REVIEW) ||
                    ($oldStatus === self::STATUS_PENDING_REVIEW && in_array($newStatus, [
                        self::STATUS_APPROVED,
                        self::STATUS_DENIED,
                        self::STATUS_REVISIONS_REQUESTED
                    ]));

                // Remove the generic notification entirely - it's redundant with the toast messages
                // that are already shown in the controllers and components
                /*
                if ($isSignificantTransition && !$skipGenericNotification) {
                    // Only notify the pitch owner about significant status transitions
                    // that aren't handled by more specific notifications
                    $notificationService->notifyPitchStatusChange($pitch, $newStatus);
                }
                */
            }
        });
    }

    public function getReadableStatusAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'Pending';
            case self::STATUS_IN_PROGRESS:
                return 'In Progress';
            case self::STATUS_READY_FOR_REVIEW:
                return 'Ready for Review';
            case self::STATUS_PENDING_REVIEW:
                return 'Pending Review';
            case self::STATUS_APPROVED:
                return 'Approved';
            case self::STATUS_DENIED:
                return 'Denied';
            case self::STATUS_REVISIONS_REQUESTED:
                return 'Revisions Requested';
            case self::STATUS_COMPLETED:
                return 'Completed';
            case self::STATUS_CLOSED:
                return 'Closed';
            case self::STATUS_CONTEST_ENTRY:
                return 'Contest Entry';
            case self::STATUS_CONTEST_WINNER:
                return 'Contest Winner';
            case self::STATUS_CONTEST_RUNNER_UP:
                return 'Contest Runner-Up';
            case self::STATUS_CONTEST_NOT_SELECTED:
                return 'Not Selected';
            case self::STATUS_AWAITING_ACCEPTANCE:
                return 'Awaiting Producer Acceptance';
            case self::STATUS_CLIENT_REVISIONS_REQUESTED:
                return 'Client Revisions Requested';
            default:
                return ucfirst($this->status);
        }
    }

    /**
     * Get a detailed description of the pitch status
     *
     * @return string
     */
    public function getStatusDescriptionAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'Your pitch is waiting for project owner approval.';
            case self::STATUS_IN_PROGRESS:
                return 'You\'re actively working on this pitch. Upload files and submit for review when ready.';
            case self::STATUS_READY_FOR_REVIEW:
                return 'Your pitch has been submitted and is awaiting review by the project owner.';
            case self::STATUS_PENDING_REVIEW:
                return 'Your response has been submitted and is awaiting review.';
            case self::STATUS_APPROVED:
                return 'The project owner has approved your pitch! You can now proceed with the project.';
            case self::STATUS_DENIED:
                return 'The project owner has declined this pitch. You may review feedback and resubmit if appropriate.';
            case self::STATUS_REVISIONS_REQUESTED:
                return 'The project owner has requested changes. Please review feedback and make the necessary revisions.';
            case self::STATUS_COMPLETED:
                return 'Congratulations! This pitch has been successfully completed.';
            case self::STATUS_CLOSED:
                return 'This pitch has been closed and is no longer active.';
            case self::STATUS_CONTEST_ENTRY:
                return 'This pitch is an entry in a contest.';
            case self::STATUS_CONTEST_WINNER:
                return 'This entry has been selected as the contest winner!';
            case self::STATUS_CONTEST_RUNNER_UP:
                return 'This entry was selected as a runner-up in the contest.';
            case self::STATUS_CONTEST_NOT_SELECTED:
                return 'This contest entry was not selected as a winner or runner-up.';
            case self::STATUS_AWAITING_ACCEPTANCE:
                return 'The project owner has offered you this direct hire project. Please accept or reject.';
            case self::STATUS_CLIENT_REVISIONS_REQUESTED:
                return 'The client has requested revisions. Please review their feedback and submit an update.';
            default:
                return 'This pitch is in the ' . strtolower(str_replace('_', ' ', $this->status)) . ' status.';
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner($user)
    {
        if (!$user) {
            return false;
        }
        return $this->user_id == $user->id;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function files()
    {
        return $this->hasMany(PitchFile::class);
    }

    public function events()
    {
        return $this->hasMany(PitchEvent::class);
    }

    /**
     * Get the completion rating for this pitch, if it exists.
     *
     * @return int|null
     */
    public function getCompletionRating(): ?int
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return null;
        }

        // Find the event that marked the pitch as completed
        $completionEvent = $this->events()
                                ->where('event_type', 'status_change')
                                ->where('status', self::STATUS_COMPLETED)
                                ->orderBy('created_at', 'desc')
                                ->first();

        return $completionEvent?->rating;
    }

    public function snapshots()
    {
        return $this->hasMany(PitchSnapshot::class);
    }

    public function currentSnapshot()
    {
        return $this->belongsTo(PitchSnapshot::class, 'current_snapshot_id');
    }

    /**
     * Check if the provided file size is allowed based on the defined limit.
     *
     * @param int $fileSize The file size in bytes
     * @return bool Whether the file size is allowed
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
        $used = self::formatBytes($this->total_storage_used);
        $total = self::formatBytes(self::MAX_STORAGE_BYTES);
        $remaining = self::formatBytes($this->getRemainingStorageBytes());
        
        return "Using $used of $total ($remaining available)";
    }

    /**
     * Get total storage used by this pitch
     *
     * @return int
     */
    public function getTotalStorageUsed()
    {
        return $this->total_storage_used;
    }

    /**
     * Check if the pitch has enough storage for the given file size
     *
     * @param int $fileSize The file size in bytes
     * @return bool Whether there is enough storage
     */
    public function hasEnoughStorageFor($fileSize)
    {
        return $this->getRemainingStorageBytes() >= $fileSize;
    }

    /**
     * Check if payment is finalized (paid, processing, or not required)
     * 
     * @return bool
     */
    public function isPaymentFinalized()
    {
        return in_array($this->payment_status, [
            self::PAYMENT_STATUS_PAID,
            self::PAYMENT_STATUS_PROCESSING, // Once processing begins, it should be considered finalized
            self::PAYMENT_STATUS_NOT_REQUIRED
        ]);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
    
    /**
     * Generate a unique slug for this pitch
     *
     * @return string
     */
    public function generateSlug(): string
    {
        // If we have a title, use it as base
        if (!empty($this->title)) {
            $baseSlug = \Illuminate\Support\Str::slug($this->title);
        } else {
            // Use pitch-{id} or pitch-{uniqid} as fallback
            $baseSlug = $this->id ? 'pitch-' . $this->id : 'pitch-' . uniqid();
        }
        
        // Ensure uniqueness
        $slug = $baseSlug;
        $counter = 1;
        
        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['title', 'id'],
                'method' => function ($string, $separator) {
                    return $this->generateSlug();
                },
                'unique' => true,
                'includeTrashed' => false,
            ]
        ];
    }

    /**
     * Check if the pitch has available storage capacity
     * 
     * @param int $additionalBytes Additional bytes to check if they would fit
     * @return bool
     */
    public function hasStorageCapacity($additionalBytes = 0)
    {
        // Use contest-specific storage limit for contest entries
        if ($this->status === self::STATUS_CONTEST_ENTRY) {
            // Load project if not loaded
            if (!$this->relationLoaded('project')) {
                $this->load('project');
            }
            
            if ($this->project && $this->project->isContest()) {
                $storageLimit = 100 * 1024 * 1024; // 100MB for contest entries
            } else {
                $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
            }
        } else {
            // Use the limit set in the database if it exists, otherwise fall back to constant
            $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
        }
        
        return ($this->total_storage_used + $additionalBytes) <= $storageLimit;
    }
    
    /**
     * Get remaining storage capacity in bytes
     * 
     * @return int
     */
    public function getRemainingStorageBytes()
    {
        // Use contest-specific storage limit for contest entries
        if ($this->status === self::STATUS_CONTEST_ENTRY) {
            // Load project if not loaded
            if (!$this->relationLoaded('project')) {
                $this->load('project');
            }
            
            if ($this->project && $this->project->isContest()) {
                $storageLimit = 100 * 1024 * 1024; // 100MB for contest entries
            } else {
                $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
            }
        } else {
            $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
        }
        
        $remaining = $storageLimit - $this->total_storage_used;
        return max(0, $remaining);
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes The size in bytes
     * @param int $precision The precision for decimal places
     * @return string The formatted size
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Get the storage used percentage
     *
     * @return float The percentage of storage used (0-100)
     */
    public function getStorageUsedPercentage()
    {
        // Use contest-specific storage limit for contest entries
        if ($this->status === self::STATUS_CONTEST_ENTRY) {
            // Load project if not loaded
            if (!$this->relationLoaded('project')) {
                $this->load('project');
            }
            
            if ($this->project && $this->project->isContest()) {
                $storageLimit = 100 * 1024 * 1024; // 100MB for contest entries
            } else {
                $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
            }
        } else {
            $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
        }
        
        return round(($this->total_storage_used / $storageLimit) * 100, 2);
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
        // Ensure storage doesn't go below zero
        $bytesToDecrement = min($bytes, $this->total_storage_used);
        return $this->decrement('total_storage_used', $bytesToDecrement);
    }

    /**
     * Get a readable version of the pitch status
     */
    public function isInactive(): bool
    {
        return $this->is_inactive;
    }

    /**
     * Get all defined status constants.
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        // Use reflection to get all constants starting with STATUS_
        $reflection = new \ReflectionClass(static::class);
        $constants = $reflection->getConstants();
        $statuses = [];
        foreach ($constants as $name => $value) {
            if (strpos($name, 'STATUS_') === 0) {
                $statuses[$value] = $value; // Use value as both key and value, or generate readable name
            }
        }
        return $statuses;
    }

    /**
     * Get human-readable status labels.
     *
     * @return array
     */

    // Add status helper methods here

    /**
     * Check if the pitch is a contest entry.
     *
     * @return bool
     */
    public function isContestEntry(): bool
    {
        return $this->status === self::STATUS_CONTEST_ENTRY;
    }

    /**
     * Check if the pitch is a contest winner.
     *
     * @return bool
     */
    public function isContestWinner(): bool
    {
        return $this->status === self::STATUS_CONTEST_WINNER;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('pitch_files');
        // ->useDisk('s3'); // Configure disk if needed
    }

    /**
     * Get the appropriate Tailwind CSS background and text color class based on pitch status.
     *
     * @return string
     */
    public function getStatusColorClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING, self::STATUS_AWAITING_ACCEPTANCE => 'bg-gray-100 text-gray-800',
            self::STATUS_IN_PROGRESS, self::STATUS_CONTEST_ENTRY => 'bg-blue-100 text-blue-800',
            self::STATUS_READY_FOR_REVIEW => 'bg-yellow-100 text-yellow-800',
            self::STATUS_REVISIONS_REQUESTED, self::STATUS_CLIENT_REVISIONS_REQUESTED => 'bg-orange-100 text-orange-800',
            self::STATUS_APPROVED => 'bg-sky-100 text-sky-800', // Using sky for approved to differentiate from completed green
            self::STATUS_COMPLETED, self::STATUS_CONTEST_WINNER => 'bg-green-100 text-green-800',
            self::STATUS_CONTEST_RUNNER_UP => 'bg-teal-100 text-teal-800', // Teal for runner-up
            self::STATUS_DENIED, self::STATUS_CLOSED, self::STATUS_CONTEST_NOT_SELECTED => 'bg-red-100 text-red-800',
            default => 'bg-gray-200 text-gray-600', // Default fallback
        };
    }

    /**
     * Check if the pitch has been placed in the contest
     */
    public function isPlaced(): bool
    {
        return !is_null($this->rank);
    }

    /**
     * Get the placement label for display
     */
    public function getPlacementLabel(): ?string
    {
        return match($this->rank) {
            self::RANK_FIRST => '1st Place',
            self::RANK_SECOND => '2nd Place', 
            self::RANK_THIRD => '3rd Place',
            self::RANK_RUNNER_UP => 'Runner-up',
            default => null
        };
    }

    /**
     * Check if the pitch placement has been finalized
     */
    public function isPlacementFinalized(): bool
    {
        return !is_null($this->placement_finalized_at);
    }
}
