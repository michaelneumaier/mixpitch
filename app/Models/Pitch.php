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

class Pitch extends Model
{
    use HasFactory;
    use Sluggable;
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

    // Payment status constants
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PROCESSING = 'processing';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_NOT_REQUIRED = 'payment_not_required';
    const PAYMENT_STATUS_REFUNDED = 'refunded';

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
        'closed_at'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'completed_at',
        'payment_completed_at',
    ];

    protected $attributes = [
        'max_files' => 25,
        'is_inactive' => false,
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
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'id',
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
        // Use the limit set in the database if it exists, otherwise fall back to constant
        $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
        
        return ($this->total_storage_used + $additionalBytes) <= $storageLimit;
    }
    
    /**
     * Get remaining storage capacity in bytes
     * 
     * @return int
     */
    public function getRemainingStorageBytes()
    {
        $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
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
        $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
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
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_READY_FOR_REVIEW,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_APPROVED,
            self::STATUS_DENIED,
            self::STATUS_REVISIONS_REQUESTED,
            self::STATUS_COMPLETED,
            self::STATUS_CLOSED,
        ];
    }

    /**
     * Get human-readable status labels.
     *
     * @return array
     */
}
