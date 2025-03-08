<?php

namespace App\Models;

use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Exceptions\Pitch\SnapshotException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Pitch extends Model
{
    use HasFactory;
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_READY_FOR_REVIEW = 'ready_for_review';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_DENIED = 'denied';
    const STATUS_REVISIONS_REQUESTED = 'revisions_requested';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'project_id',
        'completion_date',
        'completion_feedback',
        'current_snapshot_id',
        'status',
        'is_inactive'
    ];

    protected $attributes = [
        'max_files' => 25,
        'is_inactive' => false,
    ];

    protected static $transitions = [
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

                if ($isSignificantTransition && !$skipGenericNotification) {
                    // Only notify the pitch owner about significant status transitions
                    // that aren't handled by more specific notifications
                    $notificationService->notifyPitchStatusChange($pitch, $newStatus);
                }
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOwnedByUser(User $user)
    {
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

    public function createSnapshot()
    {
        // Retrieve the last snapshot for this pitch
        $lastSnapshot = $this->snapshots()->orderBy('created_at', 'desc')->first();

        // Determine the version number
        $version = $lastSnapshot ? ($lastSnapshot->snapshot_data['version'] + 1) : 1;

        // Prepare the snapshot data
        $snapshotData = [
            'version' => $version,
            'file_ids' => $this->files->pluck('id')->toArray(),
        ];

        // Create the new snapshot
        $snapshot = $this->snapshots()->create([
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'snapshot_data' => $snapshotData,
            'status' => 'pending',
        ]);

        // Set as the current snapshot
        $this->current_snapshot_id = $snapshot->id;
        $this->save();

        return $snapshot;
    }

    public function deleteSnapshot($snapshotId)
    {
        // Find the snapshot by ID
        $snapshot = $this->snapshots()->find($snapshotId);

        // Check if the snapshot exists and the authenticated user is the pitch creator
        if ($snapshot && $this->user_id === Auth::id()) {
            // If this is the current snapshot, clear that reference
            if ($this->current_snapshot_id == $snapshotId) {
                $this->current_snapshot_id = null;
                $this->save();
            }

            // Check if this is a pending snapshot for a pitch in ready_for_review status
            $isPendingSnapshot = $snapshot->status === 'pending';
            $isReadyForReview = $this->status === self::STATUS_READY_FOR_REVIEW;

            // Delete the snapshot
            $snapshot->delete();

            // If we're deleting a pending snapshot for a pitch that's ready for review,
            // automatically revert the pitch status to in_progress
            if ($isPendingSnapshot && $isReadyForReview) {
                // Check if there are any other pending snapshots
                $hasPendingSnapshots = $this->snapshots()->where('status', 'pending')->exists();

                if (!$hasPendingSnapshots) {
                    // No more pending snapshots, so revert to in_progress
                    $this->changeStatus(
                        'backward',
                        self::STATUS_IN_PROGRESS,
                        'Pitch reverted to in progress because the pending snapshot was deleted'
                    );
                }
            }

            // Log the deletion
            Log::info('Snapshot deleted', [
                'pitch_id' => $this->id,
                'snapshot_id' => $snapshotId,
                'user_id' => Auth::id()
            ]);

            return true;
        }

        return false;
    }

    public function changeSnapshotStatus($snapshotId, $status)
    {
        // Find the snapshot by ID
        $snapshot = $this->snapshots()->find($snapshotId);

        // Check if the snapshot exists and the authenticated user is the pitch creator
        if ($snapshot && $this->user_id === Auth::id()) {
            return $snapshot->changeStatus($status);
        }

        return false;
    }

    /**
     * Validate a status transition
     *
     * @param string $direction 'forward' or 'backward'
     * @param string|null $newStatus Optional target status
     * @return array [bool $isValid, string $errorMessage]
     */
    protected function validateStatusTransition($direction, $newStatus)
    {
        $currentStatus = $this->status;

        if ($direction === 'forward' && is_array(self::$transitions[$direction][$currentStatus])) {
            if (!in_array($newStatus, self::$transitions[$direction][$currentStatus])) {
                return [false, "Invalid status transition from $currentStatus to $newStatus"];
            }
        } else if ($direction === 'backward' && is_array(self::$transitions[$direction][$currentStatus])) {
            if (!in_array($newStatus, self::$transitions[$direction][$currentStatus])) {
                return [false, "Invalid status transition from $currentStatus to $newStatus"];
            }
        } else {
            if (!isset(self::$transitions[$direction][$currentStatus])) {
                return [false, "Cannot change status in the $direction direction from $currentStatus"];
            }
        }

        return [true, ''];
    }

    /**
     * Change the status of a pitch
     *
     * @param string $direction 'forward' or 'backward'
     * @param string|null $newStatus Optional target status
     * @param string|null $comment Optional comment about the status change
     * @return bool Whether the status was changed successfully
     * @throws \Exception If the status transition is invalid
     */
    public function changeStatus($direction, $newStatus = null, $comment = null)
    {
        if (!in_array($direction, ['forward', 'backward'])) {
            throw new \InvalidArgumentException("Invalid direction.");
        }

        $currentStatus = $this->status;
        $oldStatus = $this->status;

        // Validate the transition based on the requested direction and new status
        $validationResult = $this->validateStatusTransition($direction, $newStatus);
        if (!$validationResult[0]) {
            throw new \Exception($validationResult[1]);
        }

        if ($direction === 'forward' && is_array(self::$transitions[$direction][$currentStatus])) {
            if (!in_array($newStatus, self::$transitions[$direction][$currentStatus])) {
                throw new \Exception("Invalid status transition.");
            }
            $this->status = $newStatus;
        } else if ($direction === 'backward' && is_array(self::$transitions[$direction][$currentStatus])) {
            if (!in_array($newStatus, self::$transitions[$direction][$currentStatus])) {
                throw new \Exception("Invalid status transition.");
            }
            $this->status = $newStatus;
        } else {
            if (isset(self::$transitions[$direction][$currentStatus])) {
                $this->status = self::$transitions[$direction][$currentStatus];
            } else {
                throw new \Exception("Cannot change status in the $direction direction.");
            }
        }

        // Log the status change with detailed information
        Log::info('Pitch status changing', [
            'pitch_id' => $this->id,
            'from_status' => $oldStatus,
            'to_status' => $this->status,
            'direction' => $direction,
            'user_id' => auth()->id() ?? 'system'
        ]);

        $this->save();

        // Generate a default comment if none provided
        if (empty($comment)) {
            $comment = $this->generateStatusChangeComment($oldStatus, $this->status);
        }

        // Create an event record for the status change
        $this->events()->create([
            'event_type' => 'status_change',
            'comment' => $comment,
            'status' => $this->status,
            'created_by' => auth()->id() ?? null,
        ]);

        // Handle snapshot status updates when pitch status changes
        if (($oldStatus === self::STATUS_APPROVED || $oldStatus === self::STATUS_DENIED) &&
            $this->status === self::STATUS_READY_FOR_REVIEW
        ) {

            Log::info('Attempting to update snapshot status', [
                'pitch_id' => $this->id,
                'old_status' => $oldStatus,
                'new_status' => $this->status
            ]);

            // Find the latest snapshot that was either accepted or denied
            $latestSnapshot = null;

            if ($oldStatus === self::STATUS_APPROVED) {
                $latestSnapshot = $this->snapshots()
                    ->where('status', 'accepted')
                    ->orderBy('created_at', 'desc')
                    ->first();
            } else if ($oldStatus === self::STATUS_DENIED) {
                $latestSnapshot = $this->snapshots()
                    ->where('status', 'denied')
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            if ($latestSnapshot) {
                Log::info('Found snapshot to update', [
                    'snapshot_id' => $latestSnapshot->id,
                    'old_snapshot_status' => $latestSnapshot->status
                ]);

                // Update the snapshot status to pending for review
                $latestSnapshot->status = 'pending';
                $latestSnapshot->save();

                // Set as current snapshot
                $this->current_snapshot_id = $latestSnapshot->id;
                $this->save();

                Log::info('Updated snapshot status to pending', [
                    'snapshot_id' => $latestSnapshot->id,
                    'new_snapshot_status' => $latestSnapshot->status
                ]);
            } else {
                Log::warning('No snapshot found to update', [
                    'pitch_id' => $this->id,
                    'snapshots_count' => $this->snapshots()->count(),
                    'old_status' => $oldStatus
                ]);

                // Dump all snapshots for debugging
                $allSnapshots = $this->snapshots()->get();
                Log::info('All snapshots for this pitch:', [
                    'snapshots' => $allSnapshots->map(function ($s) {
                        return ['id' => $s->id, 'status' => $s->status, 'created_at' => $s->created_at];
                    })
                ]);
            }
        }

        return true;
    }

    /**
     * Generate a descriptive comment for status changes
     *
     * @param string $oldStatus
     * @param string $newStatus
     * @return string
     */
    protected function generateStatusChangeComment($oldStatus, $newStatus)
    {
        $statusMap = [
            self::STATUS_PENDING => 'Pending Access',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_READY_FOR_REVIEW => 'Ready for Review',
            self::STATUS_PENDING_REVIEW => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DENIED => 'Denied',
            self::STATUS_REVISIONS_REQUESTED => 'Revisions Requested',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CLOSED => 'Closed'
        ];

        $oldStatusName = $statusMap[$oldStatus] ?? $oldStatus;
        $newStatusName = $statusMap[$newStatus] ?? $newStatus;

        return "Status changed from {$oldStatusName} to {$newStatusName}";
    }

    public function addComment($comment)
    {
        $event = $this->events()->create([
            'event_type' => 'comment',
            'comment' => $comment,
            'created_by' => auth()->id(),
        ]);

        // Don't notify if the comment is from the pitch owner
        if (auth()->id() !== $this->user_id) {
            // Notify the pitch owner about the new comment
            app(\App\Services\NotificationService::class)->notifyPitchComment(
                $this,
                $comment,
                auth()->id()
            );
        }

        return $event;
    }

    /**
     * Complete a pitch and mark the project as completed
     * 
     * @param string $feedback Final feedback for the pitch
     * @return bool
     */
    public function completePitch($feedback = null)
    {
        // Verify that the pitch is in the approved status
        if ($this->status !== self::STATUS_APPROVED) {
            throw new \Exception('Only approved pitches can be marked as completed.');
        }

        // Update the pitch status to completed
        $this->status = self::STATUS_COMPLETED;
        $this->completion_date = now();
        $this->completion_feedback = $feedback;
        $this->save();

        // Create an event for the status change
        $this->events()->create([
            'event_type' => 'status_change',
            'comment' => 'Pitch completed with feedback: ' . ($feedback ?: 'No feedback provided'),
            'status' => self::STATUS_COMPLETED,
            'created_by' => auth()->id(),
            'snapshot_id' => $this->current_snapshot_id
        ]);

        // Mark all other pitches for this project as inactive
        $this->project->pitches()
            ->where('id', '!=', $this->id)
            ->where('status', '!=', self::STATUS_CLOSED)
            ->update(['is_inactive' => true]);

        // Add an event to all inactive pitches
        foreach ($this->project->pitches()->where('id', '!=', $this->id)->where('is_inactive', true)->get() as $pitch) {
            $pitch->events()->create([
                'event_type' => 'status_change',
                'comment' => 'This pitch has been marked as inactive because another pitch for this project has been completed.',
                'status' => $pitch->status,
                'created_by' => auth()->id(),
            ]);
        }

        // If there's a current snapshot, make sure it stays as the completed snapshot
        if ($this->current_snapshot_id) {
            $currentSnapshot = $this->snapshots()->find($this->current_snapshot_id);
            if ($currentSnapshot && $currentSnapshot->status === 'accepted') {
                $currentSnapshot->status = 'completed';
                $currentSnapshot->save();

                Log::info('Snapshot marked as completed', [
                    'pitch_id' => $this->id,
                    'snapshot_id' => $this->current_snapshot_id
                ]);

                // Create an event for this snapshot status change
                $this->events()->create([
                    'event_type' => 'snapshot_status_change',
                    'comment' => 'Snapshot marked as completed',
                    'status' => 'completed',
                    'created_by' => auth()->id(),
                    'snapshot_id' => $currentSnapshot->id
                ]);
            }
        }

        return true;
    }

    public function deleteComment($commentId)
    {
        $comment = $this->events()->where('id', $commentId)->where('event_type', 'comment')->firstOrFail();

        if ($comment->created_by == auth()->id()) {
            $comment->delete();
        } else {
            throw new \Exception('Unauthorized action.');
        }
    }

    public function addRating($rating)
    {
        $this->events()->create([
            'event_type' => 'rating',
            'rating' => $rating,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Validate if the pitch can be submitted for review
     * 
     * @throws InvalidStatusTransitionException
     * @return bool
     */
    public function canSubmitForReview()
    {
        // Check if the pitch has files
        if ($this->files()->count() === 0) {
            throw new InvalidStatusTransitionException(
                $this->status,
                self::STATUS_READY_FOR_REVIEW,
                'You must upload at least one file before submitting for review.'
            );
        }

        // Check if the pitch is in the correct status
        // Allow transition from in_progress, denied, approved, or revisions_requested
        if (!in_array($this->status, [
            self::STATUS_IN_PROGRESS,
            self::STATUS_DENIED,
            self::STATUS_APPROVED,
            self::STATUS_REVISIONS_REQUESTED
        ])) {
            throw new InvalidStatusTransitionException(
                $this->status,
                self::STATUS_READY_FOR_REVIEW,
                'The pitch must be in progress, approved, denied, or have revisions requested before it can be submitted for review.'
            );
        }

        return true;
    }

    /**
     * Validate if the pitch can be cancelled
     * 
     * @throws InvalidStatusTransitionException
     * @throws UnauthorizedActionException
     * @return bool
     */
    public function canCancelSubmission()
    {
        // Check if the authenticated user is the pitch owner
        if (Auth::id() !== $this->user_id) {
            throw new UnauthorizedActionException(
                'cancel submission',
                'Only the pitch owner can cancel a submission'
            );
        }

        // Check if the pitch is in the correct status
        if ($this->status !== self::STATUS_READY_FOR_REVIEW) {
            throw new InvalidStatusTransitionException(
                $this->status,
                self::STATUS_IN_PROGRESS,
                'Only pitches that are ready for review can be cancelled.'
            );
        }

        // Check if there's a current snapshot
        if (!$this->current_snapshot_id) {
            throw new SnapshotException(
                null,
                'No current snapshot found. Cannot cancel submission.'
            );
        }

        // Check the current snapshot status
        $currentSnapshot = $this->snapshots()->find($this->current_snapshot_id);
        if (!$currentSnapshot) {
            throw new SnapshotException(
                $this->current_snapshot_id,
                'Current snapshot not found. Cannot cancel submission.'
            );
        }

        if ($currentSnapshot->status !== 'pending') {
            throw new SnapshotException(
                $this->current_snapshot_id,
                'Current snapshot is not pending. Cannot cancel a submission that is already being reviewed.'
            );
        }

        return true;
    }

    /**
     * Validate if the pitch can be approved
     * 
     * @param int $snapshotId
     * @throws InvalidStatusTransitionException
     * @throws SnapshotException
     * @return bool
     */
    public function canApprove($snapshotId)
    {
        // Check if the pitch is in the correct status
        if ($this->status !== self::STATUS_READY_FOR_REVIEW) {
            throw new InvalidStatusTransitionException(
                $this->status,
                self::STATUS_APPROVED,
                'Only pitches that are ready for review can be approved.'
            );
        }

        // Check if the snapshot exists and is pending
        $snapshot = $this->snapshots()->find($snapshotId);
        if (!$snapshot) {
            throw new SnapshotException(
                $snapshotId,
                'The specified snapshot does not exist.'
            );
        }

        if ($snapshot->status !== 'pending') {
            throw new SnapshotException(
                $snapshotId,
                'Only pending snapshots can be approved.'
            );
        }

        return true;
    }

    /**
     * Validate if the pitch can be denied
     * 
     * @param int $snapshotId
     * @throws InvalidStatusTransitionException
     * @throws SnapshotException
     * @return bool
     */
    public function canDeny($snapshotId)
    {
        // Check if the pitch is in the correct status
        if ($this->status !== self::STATUS_READY_FOR_REVIEW) {
            throw new InvalidStatusTransitionException(
                $this->status,
                self::STATUS_DENIED,
                'Only pitches that are ready for review can be denied.'
            );
        }

        // Check if the snapshot exists
        $snapshot = $this->snapshots()->find($snapshotId);
        if (!$snapshot) {
            throw new SnapshotException(
                $snapshotId,
                'The specified snapshot does not exist.'
            );
        }

        // Allow denying any snapshot when the pitch is ready for review, not just pending ones
        // This is more flexible and prevents pitches from getting stuck

        return true;
    }

    /**
     * Validate if the pitch can be completed
     * 
     * @throws InvalidStatusTransitionException
     * @return bool
     */
    public function canComplete()
    {
        // Check if the pitch is in the correct status
        if ($this->status !== self::STATUS_APPROVED) {
            throw new InvalidStatusTransitionException(
                $this->status,
                self::STATUS_COMPLETED,
                'Only approved pitches can be marked as completed.'
            );
        }

        // Check if there's an accepted snapshot
        $hasAcceptedSnapshot = $this->snapshots()->where('status', 'accepted')->exists();
        if (!$hasAcceptedSnapshot) {
            throw new SnapshotException(
                null,
                'No approved snapshot found for this pitch.'
            );
        }

        return true;
    }

    /**
     * Validate if revisions can be requested for a pitch snapshot
     * 
     * @param int $snapshotId
     * @throws InvalidStatusTransitionException
     * @throws SnapshotException
     * @return bool
     */
    public function canRequestRevisions($snapshotId)
    {
        // Check if the pitch is in the correct status for requesting revisions
        if ($this->status !== self::STATUS_READY_FOR_REVIEW) {
            throw new InvalidStatusTransitionException(
                $this->status,
                self::STATUS_REVISIONS_REQUESTED,
                'Only pitches that are ready for review can have revisions requested.'
            );
        }

        // Check if the snapshot exists
        $snapshot = $this->snapshots()->find($snapshotId);
        if (!$snapshot) {
            throw new SnapshotException(
                $snapshotId,
                'The specified snapshot does not exist.'
            );
        }

        // Only pending snapshots can have revisions requested
        if ($snapshot->status !== 'pending') {
            throw new SnapshotException(
                $snapshotId,
                'Only pending snapshots can have revisions requested.'
            );
        }

        return true;
    }

    /**
     * Validate if the pitch can be returned from completed to approved status
     * 
     * @throws InvalidStatusTransitionException
     * @return bool
     */
    public function canReturnToApproved()
    {
        // Check if the pitch is in the correct status
        if ($this->status !== self::STATUS_COMPLETED) {
            throw new InvalidStatusTransitionException(
                $this->status,
                self::STATUS_APPROVED,
                'Only completed pitches can be returned to approved status.'
            );
        }

        // Check if there's an accepted snapshot
        $hasAcceptedSnapshot = $this->snapshots()->where('status', 'accepted')->exists();
        if (!$hasAcceptedSnapshot) {
            throw new SnapshotException(
                null,
                'No approved snapshot found for this pitch.'
            );
        }

        return true;
    }

    /**
     * Validate if the pitch can be allowed access (transition from pending to in_progress)
     * 
     * @throws InvalidStatusTransitionException
     * @return bool
     */
    public function canAllowAccess()
    {
        // Check if the pitch is in the correct status
        if ($this->status !== self::STATUS_PENDING) {
            throw new InvalidStatusTransitionException(
                $this->status,
                self::STATUS_IN_PROGRESS,
                'Only pending pitches can be allowed access.'
            );
        }

        return true;
    }

    /**
     * Validate if the pitch can have access removed (transition from in_progress to pending)
     * 
     * @throws InvalidStatusTransitionException
     * @return bool
     */
    public function canRemoveAccess()
    {
        // Check if the pitch is in the correct status
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            throw new InvalidStatusTransitionException(
                $this->status,
                self::STATUS_PENDING,
                'Only in-progress pitches can have access removed.'
            );
        }

        return true;
    }
}
