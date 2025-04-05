<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Pitch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Exceptions\Pitch\PitchCreationException;
use App\Exceptions\Pitch\UnauthorizedActionException; // General purpose
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService; // Assumed dependency
use Illuminate\Support\Facades\Auth; // Added for Auth::id()
use App\Models\PitchSnapshot;
use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Exceptions\Pitch\SnapshotException;
use App\Exceptions\Pitch\SubmissionValidationException;

class PitchWorkflowService
{
    protected $notificationService;

    // Inject other services like NotificationService if needed
    // Note: Assumes an App\\Services\\NotificationService exists, responsible for
    // queuing and dispatching application notifications via appropriate channels
    // (e.g., mail, database) based on user preferences and event types.
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new pitch for a project.
     *
     * @param Project $project
     * @param User $user The user creating the pitch (producer).
     * @param array $validatedData (Potentially empty for initial creation, or could include title/desc if added)
     * @return Pitch
     * @throws PitchCreationException|UnauthorizedActionException
     */
    public function createPitch(Project $project, User $user, array $validatedData): Pitch
    {
        // Perform checks previously in controller/model (can user pitch? project open? user already pitched?)
        // Use policies or dedicated model methods for checks.
        // Note: isOpenForPitches() needs to be implemented in Project model.
        if (!$project->isOpenForPitches()) {
             throw new PitchCreationException('This project is not currently open for pitches.');
        }
        // Note: userPitch() needs to be implemented in Project model.
        if ($project->userPitch($user->id)) {
            throw new PitchCreationException('You have already submitted a pitch for this project.');
        }
        // Add policy check if not handled by Form Request
        // if ($user->cannot('createPitch', $project)) {
        //      throw new UnauthorizedActionException('You are not authorized to create a pitch for this project.');
        // }

        try {
            return DB::transaction(function () use ($project, $user, $validatedData) {
                $pitch = new Pitch();
                $pitch->project_id = $project->id;
                $pitch->user_id = $user->id;
                $pitch->status = Pitch::STATUS_PENDING; // Default initial status
                $pitch->fill($validatedData); // If title/desc are captured at creation

                // Slug generation is handled by the Sluggable trait on saving
                $pitch->save();

                // Create initial event (Consider moving to an Observer: PitchObserver::created)
                $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => 'Pitch created and pending project owner approval.',
                    'status' => $pitch->status,
                    // Ensure Auth::id() is available or use $user->id appropriately
                    'created_by' => Auth::check() ? Auth::id() : $user->id,
                ]);

                // Notify project owner (queue this notification)
                // Note: notifyPitchSubmitted() needs to be implemented in NotificationService
                $this->notificationService->notifyPitchSubmitted($pitch);

                return $pitch;
            });
        } catch (\Exception $e) {
            // Don't expose raw DB errors
            throw new PitchCreationException('An error occurred while creating your pitch. Please try again.');
        }
    }

    /**
     * Approve an initial pitch application (Pending -> In Progress).
     *
     * @param Pitch $pitch
     * @param User $approvingUser (Project Owner)
     * @return Pitch
     * @throws InvalidStatusTransitionException|UnauthorizedActionException
     */
    public function approveInitialPitch(Pitch $pitch, User $approvingUser): Pitch
    {
        // Authorization check (is user the project owner?)
        if ($pitch->project->user_id !== $approvingUser->id) {
            throw new UnauthorizedActionException('approve initial pitch');
        }
        // Policy check: if ($approvingUser->cannot('approveInitial', $pitch)) { throw new UnauthorizedActionException('approve initial pitch'); }

        if ($pitch->status !== Pitch::STATUS_PENDING) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_IN_PROGRESS, 'Pitch must be pending for initial approval.');
        }

        try {
            return DB::transaction(function () use ($pitch, $approvingUser) { // Pass $approvingUser
                $pitch->status = Pitch::STATUS_IN_PROGRESS;
                $pitch->save();

                // Create event
                $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => 'Pitch application approved by project owner.',
                    'status' => $pitch->status,
                    'created_by' => $approvingUser->id, // Use the approver's ID
                ]);

                // Notify pitch creator
                // Note: notifyPitchApproved() needs implementation in NotificationService
                $this->notificationService->notifyPitchApproved($pitch);

                return $pitch;
            });
        } catch (\Exception $e) {
            Log::error('Error approving initial pitch', ['pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_IN_PROGRESS, 'Failed to approve pitch.');
        }
    }

    /**
     * Approve a submitted snapshot/pitch (Ready For Review -> Approved).
     *
     * @param Pitch $pitch
     * @param int $snapshotId The ID of the snapshot being approved.
     * @param User $approvingUser (Project Owner)
     * @return Pitch
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function approveSubmittedPitch(Pitch $pitch, int $snapshotId, User $approvingUser): Pitch
    {
        // Authorization
        Log::debug('Approve Action - Authorization Check', [
            'pitch_id' => $pitch->id,
            'project_id' => $pitch->project_id,
            'project_owner_id' => $pitch->project->user_id,
            'requesting_user_id' => $approvingUser->id,
            'is_match' => $pitch->project->user_id === $approvingUser->id
        ]);
        
        if ($pitch->project->user_id !== $approvingUser->id) {
            throw new UnauthorizedActionException('approve submitted pitch');
        }
        // Policy check: if ($approvingUser->cannot('approveSubmission', $pitch)) { throw new UnauthorizedActionException('approve submitted pitch'); }

        // Check for completed/paid status FIRST
        if ($this->isPitchPaidAndCompleted($pitch)) {
             throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Paid & completed pitch cannot be modified.');
        }

        // Validate status and snapshot state
        $snapshot = $pitch->snapshots()->find($snapshotId);
        if (!$snapshot || !$snapshot->isPending()) {
            // Fix SnapshotException call by passing snapshot ID as the first parameter
            throw new SnapshotException($snapshotId, 'Snapshot not found or not pending review');
        }
        if ($pitch->status !== Pitch::STATUS_READY_FOR_REVIEW || $pitch->current_snapshot_id !== $snapshotId) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Pitch must be ready for review with the specified snapshot.');
        }

        try {
             return DB::transaction(function() use ($pitch, $snapshot, $approvingUser) { // Pass snapshot and approvingUser
                Log::debug('Inside approveSubmittedPitch transaction - Before save', [
                    'pitch_id' => $pitch->id, 'pitch_status' => $pitch->status, 'target_pitch_status' => Pitch::STATUS_APPROVED,
                    'snapshot_id' => $snapshot->id, 'snapshot_status' => $snapshot->status, 'target_snapshot_status' => PitchSnapshot::STATUS_ACCEPTED
                ]);
                
                // Update Pitch Status
                $pitch->status = Pitch::STATUS_APPROVED;
                $pitchSaveResult = $pitch->save();

                // Update Snapshot Status
                $snapshot->status = PitchSnapshot::STATUS_ACCEPTED;
                $snapshotSaveResult = $snapshot->save();

                Log::debug('Inside approveSubmittedPitch transaction - After save', [
                    'pitch_id' => $pitch->id, 'pitch_status_after_save' => $pitch->status,
                    'snapshot_id' => $snapshot->id, 'snapshot_status_after_save' => $snapshot->status,
                    'pitch_save_result' => $pitchSaveResult,
                    'snapshot_save_result' => $snapshotSaveResult
                ]);
                
                // Create event
                $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => 'Pitch submission approved.',
                    'status' => $pitch->status,
                    'snapshot_id' => $snapshot->id,
                    'created_by' => $approvingUser->id, // Use approver's ID
                ]);

                // Notify pitch creator
                // Note: notifyPitchSubmissionApproved() needs implementation in NotificationService
                $this->notificationService->notifyPitchSubmissionApproved($pitch, $snapshot);

                return $pitch;
             });
        } catch (\Exception $e) {
            Log::error('Error approving submitted pitch', ['pitch_id' => $pitch->id, 'snapshot_id' => $snapshotId, 'error' => $e->getMessage()]);
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Failed to approve pitch submission.');
        }
    }

     /**
     * Deny a submitted snapshot/pitch (Ready For Review -> Denied).
     *
     * @param Pitch $pitch
     * @param int $snapshotId
     * @param User $denyingUser (Project Owner)
     * @param string|null $reason
     * @return Pitch
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function denySubmittedPitch(Pitch $pitch, int $snapshotId, User $denyingUser, ?string $reason = null): Pitch
    {
        // Authorization
        Log::debug('Deny Action - Authorization Check', [
            'pitch_id' => $pitch->id,
            'project_id' => $pitch->project_id,
            'project_owner_id' => $pitch->project->user_id,
            'requesting_user_id' => $denyingUser->id,
            'is_match' => $pitch->project->user_id === $denyingUser->id
        ]);
        
        if ($pitch->project->user_id !== $denyingUser->id) {
            throw new UnauthorizedActionException('deny submitted pitch');
        }
        // Policy check: if ($denyingUser->cannot('denySubmission', $pitch)) { throw new UnauthorizedActionException('deny submitted pitch'); }

        // Check for completed/paid status FIRST
        if ($this->isPitchPaidAndCompleted($pitch)) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_DENIED, 'Paid & completed pitch cannot be modified.');
        }

        // Validation
        $snapshot = $pitch->snapshots()->find($snapshotId);
        if (!$snapshot || !$snapshot->isPending()) {
            // Fix SnapshotException call by passing snapshot ID as the first parameter
            throw new SnapshotException($snapshotId, 'Snapshot not found or not pending review');
        }
        if ($pitch->status !== Pitch::STATUS_READY_FOR_REVIEW || $pitch->current_snapshot_id !== $snapshotId) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_DENIED, 'Pitch must be ready for review with the specified snapshot to deny.');
        }

        try {
            return DB::transaction(function() use ($pitch, $snapshot, $reason, $denyingUser) { // Pass snapshot and denyingUser
                Log::debug('Inside denySubmittedPitch transaction - Before save', [
                    'pitch_id' => $pitch->id, 'pitch_status' => $pitch->status, 'target_pitch_status' => Pitch::STATUS_DENIED,
                    'snapshot_id' => $snapshot->id, 'snapshot_status' => $snapshot->status, 'target_snapshot_status' => PitchSnapshot::STATUS_DENIED
                ]);
                
                $pitch->status = Pitch::STATUS_DENIED;
                $pitchSaveResult = $pitch->save();

                $snapshot->status = PitchSnapshot::STATUS_DENIED;
                $snapshotSaveResult = $snapshot->save();

                Log::debug('Inside denySubmittedPitch transaction - After save', [
                    'pitch_id' => $pitch->id, 'pitch_status_after_save' => $pitch->status,
                    'snapshot_id' => $snapshot->id, 'snapshot_status_after_save' => $snapshot->status,
                    'pitch_save_result' => $pitchSaveResult,
                    'snapshot_save_result' => $snapshotSaveResult
                ]);
                
                $comment = 'Pitch submission denied.';
                if ($reason) $comment .= " Reason: {$reason}";
                $event = $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => $comment,
                    'status' => $pitch->status,
                    'snapshot_id' => $snapshot->id,
                    'created_by' => $denyingUser->id, // Use denier's ID
                ]);

                // Note: notifyPitchSubmissionDenied() needs implementation in NotificationService
                $this->notificationService->notifyPitchSubmissionDenied($pitch, $snapshot, $reason);

                return $pitch;
             });
        } catch (\Exception $e) {
            Log::error('Error denying submitted pitch', ['pitch_id' => $pitch->id, 'snapshot_id' => $snapshot->id ?? $snapshotId, 'error' => $e->getMessage()]); // Use snapshot id if available
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_DENIED, 'Failed to deny pitch submission.');
        }
    }

    /**
     * Request revisions for a submitted snapshot/pitch (Ready For Review -> Revisions Requested).
     *
     * @param Pitch $pitch
     * @param int $snapshotId
     * @param User $requestingUser (Project Owner)
     * @param string $feedback
     * @return Pitch
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException|\InvalidArgumentException
     */
    public function requestPitchRevisions(Pitch $pitch, int $snapshotId, User $requestingUser, string $feedback): Pitch
    {
         // Authorization
         if ($pitch->project->user_id !== $requestingUser->id) {
             throw new UnauthorizedActionException('request revisions for this pitch');
         }
         // Policy check: if ($requestingUser->cannot('requestRevisions', $pitch)) { throw new UnauthorizedActionException('request revisions for this pitch'); }

         // Check for completed/paid status FIRST
         if ($this->isPitchPaidAndCompleted($pitch)) {
              throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_REVISIONS_REQUESTED, 'Paid & completed pitch cannot be modified.');
         }

         // Validation
         $snapshot = $pitch->snapshots()->find($snapshotId);
         if (!$snapshot || !$snapshot->isPending()) {
             // Fix SnapshotException call by passing snapshot ID as the first parameter
             throw new SnapshotException($snapshotId, 'Snapshot not found or not pending review');
         }
         if ($pitch->status !== Pitch::STATUS_READY_FOR_REVIEW || $pitch->current_snapshot_id !== $snapshotId) {
             throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_REVISIONS_REQUESTED, 'Pitch must be ready for review with the specified snapshot to request revisions.');
         }
         if (empty($feedback)) {
             throw new \InvalidArgumentException('Revision feedback cannot be empty.');
         }

        try {
             return DB::transaction(function() use ($pitch, $snapshot, $feedback, $requestingUser) { // Pass snapshot, feedback, requestingUser
                $pitch->status = Pitch::STATUS_REVISIONS_REQUESTED;
                $pitch->save();

                $snapshot->status = PitchSnapshot::STATUS_REVISIONS_REQUESTED;
                $snapshot->save();

                $pitch->events()->create([
                    'event_type' => 'revision_request', // Use specific event type
                    'comment' => "Revisions requested. Feedback: {$feedback}",
                    'status' => $pitch->status,
                    'snapshot_id' => $snapshot->id,
                    'created_by' => $requestingUser->id, // Use requester's ID
                    'metadata' => ['feedback' => $feedback], // Store feedback in metadata
                ]);

                // Note: notifyPitchRevisionsRequested() needs implementation in NotificationService
                $this->notificationService->notifyPitchRevisionsRequested($pitch, $snapshot, $feedback);

                return $pitch;
            });
        } catch (\Exception $e) {
            Log::error('Error requesting pitch revisions', ['pitch_id' => $pitch->id, 'snapshot_id' => $snapshot->id ?? $snapshotId, 'error' => $e->getMessage()]);
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_REVISIONS_REQUESTED, 'Failed to request revisions.');
        }
    }

     /**
     * Cancel a pitch submission (Ready For Review -> In Progress).
     * Action performed by the pitch creator.
     *
     * @param Pitch $pitch
     * @param User $cancellingUser (Pitch Creator)
     * @return Pitch
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function cancelPitchSubmission(Pitch $pitch, User $cancellingUser): Pitch
    {
        // Authorization
        if ($pitch->user_id !== $cancellingUser->id) {
            throw new UnauthorizedActionException('cancel pitch submission');
        }
        // Policy check: if ($cancellingUser->cannot('cancelSubmission', $pitch)) { throw new UnauthorizedActionException('cancel pitch submission'); }

        // Validation
        $snapshot = $pitch->currentSnapshot; // Use the relationship
        if ($pitch->status !== Pitch::STATUS_READY_FOR_REVIEW) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_IN_PROGRESS, 'Pitch must be ready for review to cancel submission.');
        }
        if (!$snapshot || !$snapshot->isPending()) {
            // Fix SnapshotException call by passing snapshot ID if available
            $snapshotId = $snapshot ? $snapshot->id : null;
            throw new SnapshotException($snapshotId, 'Cannot cancel submission; the current snapshot is not pending review');
        }

        try {
            return DB::transaction(function () use ($pitch, $snapshot, $cancellingUser) { // Pass snapshot and cancellingUser
                $pitch->status = Pitch::STATUS_IN_PROGRESS;
                $pitch->current_snapshot_id = null; // Reset current snapshot ID
                $pitch->save();

                // Mark the snapshot as cancelled to preserve history.
                $snapshot->status = PitchSnapshot::STATUS_CANCELLED; // Assuming STATUS_CANCELLED constant exists
                $snapshot->save();

                $pitch->events()->create([
                    'event_type' => 'status_change', // Or 'submission_cancelled'
                    'comment' => 'Pitch submission cancelled by creator.',
                    'status' => $pitch->status,
                    'snapshot_id' => $snapshot->id,
                    'created_by' => $cancellingUser->id, // Use canceller's ID
                ]);

                // Notify project owner? Maybe not necessary.

                return $pitch;
            });
        } catch (\Exception $e) {
            Log::error('Error cancelling pitch submission', ['pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_IN_PROGRESS, 'Failed to cancel submission.');
        }
    }

    /**
     * Submit a pitch for review.
     *
     * @param Pitch $pitch
     * @param User $submitter (Pitch Owner)
     * @param string|null $responseToFeedback Optional message when resubmitting after revisions.
     * @return Pitch
     * @throws SubmissionValidationException|InvalidStatusTransitionException|UnauthorizedActionException
     */
    public function submitPitchForReview(Pitch $pitch, User $submitter, ?string $responseToFeedback = null): Pitch
    {
        // Authorization
        if ($pitch->user_id !== $submitter->id) {
            throw new UnauthorizedActionException('submit this pitch for review');
        }
        // Policy: if ($submitter->cannot('submitForReview', $pitch)) { throw new UnauthorizedActionException('You are not authorized to submit this pitch.'); }

        // Validation
        // Add STATUS_DENIED if denied pitches can be resubmitted
        if (!in_array($pitch->status, [Pitch::STATUS_IN_PROGRESS, Pitch::STATUS_REVISIONS_REQUESTED])) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_READY_FOR_REVIEW, 'Pitch cannot be submitted from its current status.');
        }
        if ($pitch->files()->count() === 0) {
            throw new SubmissionValidationException('Cannot submit pitch for review with no files attached.');
        }
        // Optional: Require feedback response when resubmitting after revisions requested.
        // if ($pitch->status === Pitch::STATUS_REVISIONS_REQUESTED && empty($responseToFeedback)) {
        //     throw new SubmissionValidationException('Please provide a response to the revision feedback.');
        // }

        try {
            return DB::transaction(function() use ($pitch, $responseToFeedback, $submitter) {
                $previousSnapshot = $pitch->currentSnapshot;
                // Safely get version from snapshot_data or default to 0
                $previousVersion = $previousSnapshot ? ($previousSnapshot->snapshot_data['version'] ?? 0) : 0;
                $newVersion = $previousVersion + 1;

                // Create new Snapshot
                $snapshotData = [
                    'version' => $newVersion,
                    'file_ids' => $pitch->files->pluck('id')->toArray(),
                    'response_to_feedback' => $responseToFeedback,
                    'previous_snapshot_id' => $previousSnapshot?->id,
                ];

                $newSnapshot = $pitch->snapshots()->create([
                    'project_id' => $pitch->project_id,
                    'user_id' => $submitter->id,
                    'snapshot_data' => $snapshotData,
                    'status' => PitchSnapshot::STATUS_PENDING, // Initially pending review
                ]);

                // Update Pitch
                $originalStatus = $pitch->getOriginal('status'); // Get status before potential changes
                $pitch->status = Pitch::STATUS_READY_FOR_REVIEW;
                $pitch->current_snapshot_id = $newSnapshot->id;
                $pitch->save();

                // Update previous snapshot status if applicable
                if ($previousSnapshot && $originalStatus === Pitch::STATUS_REVISIONS_REQUESTED) {
                    // Mark the snapshot that *received* the revisions feedback as addressed
                    $previousSnapshot->status = PitchSnapshot::STATUS_REVISION_ADDRESSED;
                    $previousSnapshot->save();
                }

                // Create Event
                $comment = 'Pitch submitted for review (Version ' . $newVersion . ').';
                if ($responseToFeedback) $comment .= " Response: {$responseToFeedback}";
                $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => $comment,
                    'status' => $pitch->status,
                    'snapshot_id' => $newSnapshot->id,
                    'created_by' => $submitter->id, // Use submitter's ID
                ]);

                // Notify Project Owner
                // Note: notifyPitchReadyForReview() needs implementation in NotificationService
                $this->notificationService->notifyPitchReadyForReview($pitch, $newSnapshot);

                return $pitch;
            });
        } catch (\Exception $e) {
            Log::error('Error submitting pitch for review', ['pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
            // Consider re-throwing a more specific exception if needed
            throw new \RuntimeException('Failed to submit pitch for review.', 0, $e);
        }
    }

    /**
     * Returns a pitch (and its current snapshot) back to the 'Ready for Review' status.
     * Intended to be called by the project owner to undo an approval, denial, or revision request.
     *
     * @param Pitch $pitch The pitch to revert.
     * @param User $revertingUser The user performing the action (project owner).
     * @return Pitch The updated pitch.
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function returnPitchToReview(Pitch $pitch, User $revertingUser): Pitch
    {
        // Authorization
        if ($pitch->project->user_id !== $revertingUser->id) {
            throw new UnauthorizedActionException('return this pitch to review');
        }
        // Policy: if ($revertingUser->cannot('returnToReview', $pitch)) { ... } // Consider adding this policy ability

        // Validation: Ensure pitch is in a revertible status
        $revertibleStatuses = [
            Pitch::STATUS_APPROVED,
            Pitch::STATUS_REVISIONS_REQUESTED,
            Pitch::STATUS_DENIED,
        ];
        if (!in_array($pitch->status, $revertibleStatuses)) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_READY_FOR_REVIEW, 'Pitch cannot be returned to review from its current status.');
        }

        // Ensure there's a current snapshot to revert
        $snapshot = $pitch->currentSnapshot;
        if (!$snapshot) {
            throw new SnapshotException('Cannot return to review; no current snapshot found.');
        }

        // Prevent action if pitch is completed and paid/processing
        if ($this->isPitchPaidAndCompleted($pitch)) {
             throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_READY_FOR_REVIEW, 'Paid & completed pitch status cannot be reverted.');
        }

        try {
            return DB::transaction(function () use ($pitch, $snapshot) {
                // --- Get original status BEFORE changing it ---
                $originalStatus = $pitch->getOriginal('status');

                // Update Pitch Status
                $pitch->status = Pitch::STATUS_READY_FOR_REVIEW;
                $pitch->save();

                // Update Snapshot Status back to Pending
                $snapshot->status = PitchSnapshot::STATUS_PENDING;
                $snapshot->save();

                // --- Delete the previous Deny/Revision event ---
                if (in_array($originalStatus, [Pitch::STATUS_DENIED, Pitch::STATUS_REVISIONS_REQUESTED])) {
                    $eventToDelete = $pitch->events()
                        ->where('status', $originalStatus) // Find event matching the status we reverted FROM
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($eventToDelete) {
                        Log::info('Deleting historical pitch event upon status revert', [
                            'pitch_id' => $pitch->id,
                            'event_id' => $eventToDelete->id,
                            'reverted_from_status' => $originalStatus
                        ]);
                        $eventToDelete->delete();
                    } else {
                        Log::warning('Could not find historical pitch event to delete upon status revert', [
                             'pitch_id' => $pitch->id,
                             'reverted_from_status' => $originalStatus
                        ]);
                    }
                }
                // --- End Deletion ---

                // Create event for the *new* status
                $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => 'Pitch status returned to Ready for Review by project owner.',
                    'status' => $pitch->status,
                    'snapshot_id' => $snapshot->id,
                    'created_by' => Auth::id(),
                ]);

                // Notify pitch creator? Optional.
                // $this->notificationService->notifyPitchReturnedToReview($pitch, $snapshot);

                return $pitch;
            });
        } catch (\Exception $e) {
            Log::error('Error returning pitch to review', ['pitch_id' => $pitch->id, 'snapshot_id' => $snapshot->id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to return pitch to review.', 0, $e); // Use a generic exception or a specific one
        }
    }

    /**
     * Helper function to check if pitch is paid and completed.
     * @param Pitch $pitch
     * @return bool
     */
    private function isPitchPaidAndCompleted(Pitch $pitch): bool
    {
        return $pitch->status === Pitch::STATUS_COMPLETED &&
               in_array($pitch->payment_status, [Pitch::PAYMENT_STATUS_PAID, Pitch::PAYMENT_STATUS_PROCESSING]);
    }

    /**
     * Mark a pitch as paid.
     * Called after successful payment processing (e.g., from webhook or controller).
     *
     * @param Pitch $pitch
     * @param string $stripeInvoiceId The Stripe Invoice ID associated with the payment.
     * @param string|null $stripeChargeId Optional Stripe Charge ID or Payment Intent ID.
     * @return Pitch
     * @throws \Exception If saving fails.
     */
    public function markPitchAsPaid(Pitch $pitch, string $stripeInvoiceId, ?string $stripeChargeId = null): Pitch
    {
        // Validate: Only completed pitches should be marked as paid.
        if ($pitch->status !== Pitch::STATUS_COMPLETED) {
            Log::warning('Attempted to mark non-completed pitch as paid.', [
                'pitch_id' => $pitch->id,
                'current_status' => $pitch->status
            ]);
            // Optionally throw an exception or return early depending on desired strictness
             throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_COMPLETED, 'Pitch must be completed to be marked as paid.');
           // return $pitch;
        }

        // Idempotency: If already paid, do nothing and return.
        if ($pitch->payment_status === Pitch::PAYMENT_STATUS_PAID) {
             Log::info('Attempted to mark already paid pitch as paid again.', ['pitch_id' => $pitch->id]);
            return $pitch;
        }

        try {
            $pitch->payment_status = Pitch::PAYMENT_STATUS_PAID;
            $pitch->final_invoice_id = $stripeInvoiceId;
            $pitch->payment_completed_at = now();
            // Set the payment amount based on the project budget
            $pitch->payment_amount = $pitch->project->budget;
            // Optionally store the charge ID if needed for refunds etc.
            // $pitch->stripe_charge_id = $stripeChargeId;
            $pitch->save();

            // Create event
            $comment = 'Payment completed successfully.';
             if ($stripeInvoiceId) $comment .= " Stripe Invoice ID: {$stripeInvoiceId}";
             if ($stripeChargeId) $comment .= " Stripe Charge ID: {$stripeChargeId}"; // Or PaymentIntent ID
            $pitch->events()->create([
                'event_type' => 'payment_status_change',
                'comment' => $comment,
                'status' => $pitch->status, // Keep the pitch status
                'payment_status' => $pitch->payment_status, // Add payment status to event
                'created_by' => $pitch->user_id, // Use the pitch owner's ID for system-initiated events
            ]);

            // Notify user/owner?
             // Note: notifyPaymentProcessed() needs implementation in NotificationService
            $this->notificationService->notifyPaymentProcessed($pitch, $pitch->project->budget, $stripeInvoiceId);

            Log::info('Pitch marked as paid successfully.', [
                'pitch_id' => $pitch->id, 
                'invoice_id' => $stripeInvoiceId,
                'payment_amount' => $pitch->payment_amount
            ]);
            return $pitch;

        } catch (\Exception $e) {
            Log::error('Failed to mark pitch as paid in service', [
                'pitch_id' => $pitch->id,
                'invoice_id' => $stripeInvoiceId,
                'error' => $e->getMessage()
            ]);
            // Re-throw to allow calling code (controller/webhook handler) to manage response
            throw $e;
        }
    }

     /**
     * Mark a pitch payment as failed.
     * Called after failed payment attempt or failed webhook notification.
     *
     * @param Pitch $pitch
     * @param string|null $stripeInvoiceId Optional Stripe Invoice ID associated with the attempt.
     * @param string $failureReason Reason for failure (e.g., from Stripe exception or webhook).
     * @return Pitch
      * @throws \Exception If saving fails.
     */
    public function markPitchPaymentFailed(Pitch $pitch, ?string $stripeInvoiceId = null, string $failureReason = 'Unknown reason'): Pitch
    {
        // Validate: Only completed pitches with pending payment should be marked failed.
        // Allows marking failed even if status somehow changed, but logs warning.
        if ($pitch->status !== Pitch::STATUS_COMPLETED || $pitch->payment_status !== Pitch::PAYMENT_STATUS_PENDING) {
             Log::warning('Attempted to mark payment as failed for pitch not in COMPLETED/PENDING_PAYMENT status.', [
                'pitch_id' => $pitch->id,
                'current_status' => $pitch->status,
                'current_payment_status' => $pitch->payment_status,
            ]);
             // Optionally throw an exception if strict state is required.
             // throw new InvalidStateException(...)
        }

        // Idempotency: If already failed, do nothing.
        if ($pitch->payment_status === Pitch::PAYMENT_STATUS_FAILED) {
            return $pitch;
        }

        try {
            $pitch->payment_status = Pitch::PAYMENT_STATUS_FAILED;
            $pitch->final_invoice_id = $stripeInvoiceId; // Store invoice ID even on failure if available
            $pitch->save();

            // Create event
            $comment = "Payment attempt failed. Reason: {$failureReason}";
            if ($stripeInvoiceId) $comment .= " Stripe Invoice ID: {$stripeInvoiceId}";
            $pitch->events()->create([
                'event_type' => 'payment_status_change',
                'comment' => $comment,
                'status' => $pitch->status,
                'payment_status' => $pitch->payment_status,
                'created_by' => $pitch->user_id, // Use the pitch owner's ID for system-initiated events
            ]);

            // Notify user/owner
            // Note: notifyPaymentFailed() needs implementation in NotificationService
            $this->notificationService->notifyPaymentFailed($pitch, $failureReason);

             Log::info('Pitch payment marked as failed.', ['pitch_id' => $pitch->id, 'invoice_id' => $stripeInvoiceId, 'reason' => $failureReason]);
            return $pitch;

        } catch (\Exception $e) {
             Log::error('Failed to mark pitch payment as failed in service', [
                'pitch_id' => $pitch->id,
                'invoice_id' => $stripeInvoiceId,
                'error' => $e->getMessage()
            ]);
             throw $e;
        }
    }

    /**
     * Return a completed pitch back to the Approved status.
     * This also reopens the associated project if it was completed.
     *
     * @param Pitch $pitch The pitch to revert.
     * @param User $revertingUser The user performing the action (Project Owner).
     * @return Pitch
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|\RuntimeException
     */
    public function returnPitchToApproved(Pitch $pitch, User $revertingUser): Pitch
    {
        // Authorization (Use specific policy method)
        // Policy check: if ($revertingUser->cannot('returnToApproved', $pitch)) { throw new UnauthorizedActionException('return this pitch to approved'); }
        // Simplified check for now:
        if ($pitch->project->user_id !== $revertingUser->id) {
            throw new UnauthorizedActionException('return this pitch to approved');
        }

        // Validation: Pitch must be completed, payment must be pending or failed.
        if ($pitch->status !== Pitch::STATUS_COMPLETED) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Pitch must be completed to return it to approved status.');
        }
        if (!in_array($pitch->payment_status, [Pitch::PAYMENT_STATUS_PENDING, Pitch::PAYMENT_STATUS_FAILED])) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Pitch cannot be returned to approved status because payment is ' . $pitch->payment_status . '.');
        }

        try {
            return DB::transaction(function() use ($pitch, $revertingUser) {
                Log::info('Returning pitch to approved status', ['pitch_id' => $pitch->id, 'user_id' => $revertingUser->id]);

                // 1. Revert Pitch Status
                $originalStatus = $pitch->status;
                $pitch->status = Pitch::STATUS_APPROVED;
                $pitch->completed_at = null; // Clear completion timestamp
                $pitch->payment_status = null; // Clear payment status as it's no longer relevant
                $pitch->final_invoice_id = null;
                $pitch->payment_completed_at = null;
                $pitch->save();

                // 2. Reopen Project (Inject and use ProjectManagementService)
                $projectManagementService = app(\App\Services\Project\ProjectManagementService::class);
                $projectManagementService->reopenProject($pitch->project);

                // 3. Create Event
                $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => 'Pitch returned to Approved status by project owner.',
                    'status' => $pitch->status,
                    'previous_status' => $originalStatus,
                    'created_by' => $revertingUser->id,
                ]);

                // 4. Notifications (Optional)
                // $this->notificationService->notifyPitchReturnedToApproved($pitch);

                Log::info('Pitch returned to approved successfully', ['pitch_id' => $pitch->id]);

                return $pitch->refresh();
            });
        } catch (\Exception $e) {
            Log::error('Error returning pitch to approved', [
                'pitch_id' => $pitch->id,
                'user_id' => $revertingUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to return pitch to approved status.', 0, $e);
        }
    }

    // Add updatePitchDetails method later if needed for title/description edits
} 