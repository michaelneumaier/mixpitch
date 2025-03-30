<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PitchSnapshot extends Model
{
    use HasFactory;

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DENIED = 'denied';
    const STATUS_REVISIONS_REQUESTED = 'revisions_requested';
    const STATUS_CANCELLED = 'cancelled'; // Added for cancellation feature
    // Removed unused statuses based on current implementation, can be added back if needed
    const STATUS_REVISION_ADDRESSED = 'revision_addressed'; 
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'pitch_id',
        'project_id',
        'user_id',
        'snapshot_data',
        'status',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'status' => 'string', // Ensure status is treated as a string
    ];

    public function pitch()
    {
        return $this->belongsTo(Pitch::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
            self::STATUS_ACCEPTED,
            self::STATUS_DENIED,
            self::STATUS_REVISIONS_REQUESTED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabelAttribute()
    {
        // Use a static map for labels
        $statusMapping = self::getStatusLabels();
        return $statusMapping[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get a map of status constants to human-readable labels.
     *
     * @return array
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_DENIED => 'Denied',
            self::STATUS_REVISIONS_REQUESTED => 'Revisions Requested',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Check if the snapshot has changes requested
     */
    public function hasChangesRequested(): bool
    {
        return $this->status === self::STATUS_REVISIONS_REQUESTED;
    }

    /**
     * Check if the snapshot is approved (accepted)
     *
     * @return bool
     */
    public function isAccepted(): bool // Renamed from isApproved for clarity
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if the snapshot is denied
     *
     * @return bool
     */
    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    /**
     * Check if the snapshot is pending review
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the snapshot is cancelled
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Change the status of the snapshot
     *
     * @param string $status
     * @return bool
     */
    public function changeStatus(string $status): bool
    {
        $allowedStatuses = self::getStatuses();

        if (!in_array($status, $allowedStatuses)) {
            throw new \InvalidArgumentException("Invalid snapshot status: {$status}");
        }

        $this->status = $status;
        return $this->save();
    }
}
