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
     * Get the files associated with this snapshot.
     * Files are determined by the file_ids in snapshot_data.
     */
    public function getFilesAttribute()
    {
        $fileIds = $this->snapshot_data['file_ids'] ?? [];
        if (empty($fileIds)) {
            return collect();
        }

        return PitchFile::whereIn('id', $fileIds)->orderBy('created_at')->get();
    }

    /**
     * Get the version number for this snapshot.
     */
    public function getVersionAttribute()
    {
        return $this->snapshot_data['version'] ?? 1;
    }

    /**
     * Get the response to feedback for this snapshot.
     */
    public function getResponseToFeedbackAttribute()
    {
        return $this->snapshot_data['response_to_feedback'] ?? null;
    }

    /**
     * Check if this snapshot has files.
     */
    public function hasFiles(): bool
    {
        $fileIds = $this->snapshot_data['file_ids'] ?? [];

        return ! empty($fileIds);
    }

    /**
     * Get the count of files in this snapshot.
     */
    public function getFileCountAttribute(): int
    {
        return count($this->snapshot_data['file_ids'] ?? []);
    }

    /**
     * Get all defined status constants.
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
     */
    public function isAccepted(): bool // Renamed from isApproved for clarity
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if the snapshot is denied
     */
    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    /**
     * Check if the snapshot is pending review
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the snapshot is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Change the status of the snapshot
     */
    public function changeStatus(string $status): bool
    {
        $allowedStatuses = self::getStatuses();

        if (! in_array($status, $allowedStatuses)) {
            throw new \InvalidArgumentException("Invalid snapshot status: {$status}");
        }

        $this->status = $status;

        return $this->save();
    }
}
