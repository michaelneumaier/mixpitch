<?php

namespace App\Services;

use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Exceptions\Pitch\PitchCreationException;
use App\Exceptions\Pitch\SnapshotException;
use App\Exceptions\Pitch\SubmissionValidationException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Models\LicenseSignature;
use App\Models\Pitch; // General purpose
use App\Models\PitchSnapshot;
// Assumed dependency
use App\Models\Project; // Added for Auth::id()
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PitchWorkflowService
{
    protected $notificationService;

    protected $emailService;

    // Inject other services like NotificationService if needed
    // Note: Assumes an App\\Services\\NotificationService exists, responsible for
    // queuing and dispatching application notifications via appropriate channels
    // (e.g., mail, database) based on user preferences and event types.
    public function __construct(NotificationService $notificationService, EmailService $emailService)
    {
        $this->notificationService = $notificationService;
        $this->emailService = $emailService;
    }

    /**
     * Create a new pitch for a project.
     *
     * @param  User  $user  The user creating the pitch (producer).
     * @param  array  $validatedData  (Potentially empty for initial creation, or could include title/desc if added)
     *
     * @throws PitchCreationException|UnauthorizedActionException
     */
    public function createPitch(Project $project, User $user, array $validatedData): Pitch
    {
        // Perform checks previously in controller/model (can user pitch? project open? user already pitched?)
        // Use policies or dedicated model methods for checks.
        // Note: isOpenForPitches() needs to be implemented in Project model.

        // <<< PHASE 1 GUARD >>>
        // Block public pitch creation for Direct Hire and Client Management projects.
        // Contests will use this method initially but with different logic later.
        if ($project->isDirectHire() || $project->isClientManagement()) {
            throw new PitchCreationException('Pitches cannot be publicly submitted for this workflow type.');
        }
        // <<< END PHASE 1 GUARD >>>

        // Check for contest deadline - PHASE 3 (updated for early closure)
        if ($project->isContest() && $project->isSubmissionPeriodClosed()) {
            throw new PitchCreationException('Contest submissions are closed.');
        }

        if (! $project->isOpenForPitches()) {
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
                $pitch = new Pitch;
                $pitch->project_id = $project->id;
                $pitch->user_id = $user->id;

                // Set initial status based on project workflow type - PHASE 3
                if ($project->isContest()) {
                    $pitch->status = Pitch::STATUS_CONTEST_ENTRY;
                    $initialComment = 'Contest entry submitted.';
                } else {
                    $pitch->status = Pitch::STATUS_PENDING; // Default initial status
                    $initialComment = 'Pitch created and pending project owner approval.';
                }

                $pitch->fill($validatedData); // If title/desc are captured at creation

                // Slug generation is handled by the Sluggable trait on saving
                $pitch->save();

                // Create license signature if project requires agreement
                if ($project->requiresLicenseAgreement() && isset($validatedData['agree_license'])) {
                    $this->createLicenseSignature($project, $user);
                }

                // Create initial event (Consider moving to an Observer: PitchObserver::created)
                $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => $initialComment,
                    'status' => $pitch->status,
                    // Ensure Auth::id() is available or use $user->id appropriately
                    'created_by' => Auth::check() ? Auth::id() : $user->id,
                ]);

                // Notify project owner (queue this notification)
                // Note: notifyPitchSubmitted() needs to be implemented in NotificationService
                $this->notificationService->notifyPitchSubmitted($pitch);

                // Automatically approve the pitch if the project owner has enabled it
                if ($project->auto_allow_access && $project->isStandard()) {
                    $this->_approveInitialPitch($pitch, $project->user, true);
                }

                return $pitch;
            });
        } catch (\Exception $e) {
            // Don't expose raw DB errors
            throw new PitchCreationException('An error occurred while creating your pitch. Please try again.');
        }
    }

    /**
     * Create a license signature for a user on a project
     */
    private function createLicenseSignature(Project $project, User $user): LicenseSignature
    {
        // Check if signature already exists (prevent duplicates)
        $existingSignature = LicenseSignature::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingSignature) {
            return $existingSignature;
        }

        return LicenseSignature::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'license_template_id' => $project->license_template_id,
            'signature_text' => $user->name, // Auto-sign with user's name
            'signature_method' => 'electronic',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'agreement_hash' => hash('sha256', $project->getLicenseContent()),
            'status' => 'active',
            'metadata' => [
                'signed_during_pitch_creation' => true,
                'signed_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Approve an initial pitch application (Pending -> In Progress).
     *
     * @param  User  $approvingUser  (Project Owner)
     *
     * @throws InvalidStatusTransitionException|UnauthorizedActionException
     */
    public function approveInitialPitch(Pitch $pitch, User $approvingUser): Pitch
    {
        // <<< PHASE 1/5 GUARD >>>
        // Only Standard projects require initial pitch approval.
        if (! $pitch->project->isStandard()) {
            throw new UnauthorizedActionException('Initial pitch approval is not applicable for this workflow type.');
        }
        // <<< END PHASE 1/5 GUARD >>>

        // Authorization check (is user the project owner?)
        if ($pitch->project->user_id !== $approvingUser->id) {
            throw new UnauthorizedActionException('approve initial pitch');
        }
        // Policy check: if ($approvingUser->cannot('approveInitial', $pitch)) { throw new UnauthorizedActionException('approve initial pitch'); }

        if ($pitch->status !== Pitch::STATUS_PENDING) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_IN_PROGRESS, 'Pitch must be pending for initial approval.');
        }

        return $this->_approveInitialPitch($pitch, $approvingUser);
    }

    private function _approveInitialPitch(Pitch $pitch, User $approvingUser, $autoApproved = false)
    {
        try {
            return DB::transaction(function () use ($pitch, $approvingUser, $autoApproved) { // Pass $approvingUser
                $pitch->status = Pitch::STATUS_IN_PROGRESS;
                $pitch->save();

                $comment = $autoApproved
                    ? 'Pitch application auto-approved.'
                    : 'Pitch application approved by project owner.';

                // Create event
                $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => $comment,
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
     * @param  int  $snapshotId  The ID of the snapshot being approved.
     * @param  User  $approvingUser  (Project Owner)
     *
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function approveSubmittedPitch(Pitch $pitch, int $snapshotId, User $approvingUser): Pitch
    {
        Log::debug('Entering approveSubmittedPitch', [
            'pitch_id' => $pitch->id,
            'initial_pitch_status' => $pitch->status, // Log status on entry
            'snapshot_id' => $snapshotId,
            'approving_user_id' => $approvingUser->id,
        ]);

        // <<< PHASE 3 GUARD >>>
        if ($pitch->project->isContest()) {
            throw new UnauthorizedActionException('Standard pitch approval is not applicable for contests.');
        }
        // <<< END PHASE 3 GUARD >>>

        // Authorization
        Log::debug('Approve Action - Authorization Check', [
            'pitch_id' => $pitch->id,
            'project_id' => $pitch->project_id,
            'project_owner_id' => $pitch->project->user_id,
            'requesting_user_id' => $approvingUser->id,
            'is_match' => $pitch->project->user_id === $approvingUser->id,
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
        if (! $snapshot || ! $snapshot->isPending()) {
            // Fix SnapshotException call by passing snapshot ID as the first parameter
            throw new SnapshotException($snapshotId, 'Snapshot not found or not pending review');
        }
        if ($pitch->status !== Pitch::STATUS_READY_FOR_REVIEW || $pitch->current_snapshot_id !== $snapshotId) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Pitch must be ready for review with the specified snapshot.');
        }

        try {
            return DB::transaction(function () use ($pitch, $snapshot, $approvingUser) { // Pass snapshot and approvingUser
                Log::debug('Inside approveSubmittedPitch transaction - Before save', [
                    'pitch_id' => $pitch->id, 'pitch_status' => $pitch->status, 'target_pitch_status' => Pitch::STATUS_APPROVED,
                    'snapshot_id' => $snapshot->id, 'snapshot_status' => $snapshot->status, 'target_snapshot_status' => PitchSnapshot::STATUS_ACCEPTED,
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
                    'snapshot_save_result' => $snapshotSaveResult,
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
     * @param  User  $denyingUser  (Project Owner)
     *
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function denySubmittedPitch(Pitch $pitch, int $snapshotId, User $denyingUser, ?string $reason = null): Pitch
    {
        // <<< PHASE 3 GUARD >>>
        if ($pitch->project->isContest()) {
            throw new UnauthorizedActionException('Standard pitch denial is not applicable for contests.');
        }
        // <<< END PHASE 3 GUARD >>>

        // Authorization
        Log::debug('Deny Action - Authorization Check', [
            'pitch_id' => $pitch->id,
            'project_id' => $pitch->project_id,
            'project_owner_id' => $pitch->project->user_id,
            'requesting_user_id' => $denyingUser->id,
            'is_match' => $pitch->project->user_id === $denyingUser->id,
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
        if (! $snapshot || ! $snapshot->isPending()) {
            // Fix SnapshotException call by passing snapshot ID as the first parameter
            throw new SnapshotException($snapshotId, 'Snapshot not found or not pending review');
        }
        if ($pitch->status !== Pitch::STATUS_READY_FOR_REVIEW || $pitch->current_snapshot_id !== $snapshotId) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_DENIED, 'Pitch must be ready for review with the specified snapshot to deny.');
        }

        try {
            return DB::transaction(function () use ($pitch, $snapshot, $reason, $denyingUser) { // Pass snapshot and denyingUser
                Log::debug('Inside denySubmittedPitch transaction - Before save', [
                    'pitch_id' => $pitch->id, 'pitch_status' => $pitch->status, 'target_pitch_status' => Pitch::STATUS_DENIED,
                    'snapshot_id' => $snapshot->id, 'snapshot_status' => $snapshot->status, 'target_snapshot_status' => PitchSnapshot::STATUS_DENIED,
                ]);

                $pitch->status = Pitch::STATUS_DENIED;
                $pitchSaveResult = $pitch->save();

                $snapshot->status = PitchSnapshot::STATUS_DENIED;
                $snapshotSaveResult = $snapshot->save();

                Log::debug('Inside denySubmittedPitch transaction - After save', [
                    'pitch_id' => $pitch->id, 'pitch_status_after_save' => $pitch->status,
                    'snapshot_id' => $snapshot->id, 'snapshot_status_after_save' => $snapshot->status,
                    'pitch_save_result' => $pitchSaveResult,
                    'snapshot_save_result' => $snapshotSaveResult,
                ]);

                $comment = 'Pitch submission denied.';
                if ($reason) {
                    $comment .= " Reason: {$reason}";
                }
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
     * @param  User  $requestingUser  (Project Owner)
     *
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException|\InvalidArgumentException
     */
    public function requestPitchRevisions(Pitch $pitch, int $snapshotId, User $requestingUser, string $feedback): Pitch
    {
        // <<< PHASE 3 GUARD >>>
        if ($pitch->project->isContest()) {
            throw new UnauthorizedActionException('Revisions cannot be requested for contest entries.');
        }
        // <<< END PHASE 3 GUARD >>>

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
        if (! $snapshot || ! $snapshot->isPending()) {
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
            return DB::transaction(function () use ($pitch, $snapshot, $feedback, $requestingUser) { // Pass snapshot, feedback, requestingUser
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

                // Use the snapshot-specific notification method
                $this->notificationService->notifySnapshotRevisionsRequested($snapshot, $feedback);

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
     * @param  User  $cancellingUser  (Pitch Creator)
     *
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function cancelPitchSubmission(Pitch $pitch, User $cancellingUser): Pitch
    {
        // <<< PHASE 3 GUARD >>>
        if ($pitch->project->isContest()) {
            throw new UnauthorizedActionException('Standard pitch cancellation is not applicable for contests.');
        }
        // <<< END PHASE 3 GUARD >>>

        // Authorization: Only pitch creator can cancel
        if ($pitch->user_id !== $cancellingUser->id) {
            throw new UnauthorizedActionException('cancel pitch submission');
        }
        // Policy check: if ($cancellingUser->cannot('cancelSubmission', $pitch)) { throw new UnauthorizedActionException('cancel pitch submission'); }

        // Validation
        $snapshot = $pitch->currentSnapshot; // Use the relationship
        if ($pitch->status !== Pitch::STATUS_READY_FOR_REVIEW) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_IN_PROGRESS, 'Pitch must be ready for review to cancel submission.');
        }
        if (! $snapshot || ! $snapshot->isPending()) {
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
     * @param  User  $submitter  (Pitch Owner)
     * @param  string|null  $responseToFeedback  Optional message when resubmitting after revisions.
     *
     * @throws SubmissionValidationException|InvalidStatusTransitionException|UnauthorizedActionException
     */
    public function submitPitchForReview(Pitch $pitch, User $submitter, ?string $responseToFeedback = null): Pitch
    {
        // <<< PHASE 3 GUARD >>>
        if ($pitch->project->isContest()) {
            throw new UnauthorizedActionException('Standard review submission is not applicable for contests.');
        }
        // <<< END PHASE 3 GUARD >>>

        // Authorization check (Is the submitter the pitch owner?)
        if ((int) $pitch->user_id !== (int) $submitter->id) {
            throw new UnauthorizedActionException('submit this pitch for review');
        }
        // Policy: if ($submitter->cannot('submitForReview', $pitch)) { throw new UnauthorizedActionException('You are not authorized to submit this pitch.'); }

        // Validation
        // Add STATUS_DENIED if denied pitches can be resubmitted
        if (! in_array($pitch->status, [Pitch::STATUS_IN_PROGRESS, Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_CLIENT_REVISIONS_REQUESTED])) {
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
            $debugOriginalStatus = 'NOT_SET'; // Initialize debug variable

            return DB::transaction(function () use ($pitch, $submitter, $responseToFeedback, &$debugOriginalStatus) { // Pass by reference

                // --- Snapshot Creation Logic ---
                $previousSnapshot = $pitch->currentSnapshot;
                $previousVersion = $previousSnapshot ? ($previousSnapshot->snapshot_data['version'] ?? 0) : 0;
                $newVersion = $previousVersion + 1;

                $snapshotData = [
                    'version' => $newVersion,
                    'file_ids' => $pitch->files->pluck('id')->toArray(), // Ensure files are loaded
                    'response_to_feedback' => $responseToFeedback,
                    'previous_snapshot_id' => $previousSnapshot?->id,
                ];

                $snapshot = $pitch->snapshots()->create([
                    'user_id' => $submitter->id,
                    'project_id' => $pitch->project_id,
                    'snapshot_data' => $snapshotData,
                    'status' => PitchSnapshot::STATUS_PENDING,
                ]);

                // Explicitly ensure the pitch_id is set (Workaround for potential Eloquent issue)
                if (! $snapshot->pitch_id) {
                    $snapshot->pitch_id = $pitch->id;
                    $snapshot->save(); // Save the change
                    Log::warning('Explicitly set pitch_id on newly created snapshot.', [
                        'pitch_id' => $pitch->id,
                        'snapshot_id' => $snapshot->id,
                    ]);
                }

                Log::info('New snapshot created for pitch submission.', [
                    'pitch_id' => $pitch->id,
                    'new_snapshot_id' => $snapshot->id, // Log the ID (using $snapshot)
                    'new_version' => $newVersion,
                    'new_snapshot_pitch_id' => $snapshot->pitch_id, // Explicitly log the pitch_id (using $snapshot)
                ]);

                // Link snapshot to pending revision milestone (if exists)
                // This happens when producer uploads revision after client requested paid revision
                if ($pitch->project->isClientManagement()) {
                    $revisionRound = $newVersion - 1; // V2 = round 1, V3 = round 2, etc.
                    $pendingMilestone = $pitch->milestones()
                        ->where('is_revision_milestone', true)
                        ->where('revision_round_number', $revisionRound)
                        ->whereNull('pitch_snapshot_id')
                        ->first();

                    if ($pendingMilestone) {
                        $pendingMilestone->pitch_snapshot_id = $snapshot->id;
                        $pendingMilestone->save();

                        Log::info('Linked revision milestone to new snapshot', [
                            'pitch_id' => $pitch->id,
                            'milestone_id' => $pendingMilestone->id,
                            'snapshot_id' => $snapshot->id,
                            'revision_round' => $revisionRound,
                        ]);
                    }
                }

                // Update Pitch Status
                $pitch->status = Pitch::STATUS_READY_FOR_REVIEW;
                $pitch->current_snapshot_id = $snapshot->id; // Link the new snapshot
                $pitch->save();

                // For Client Management projects, also update project status to OPEN
                // This ensures the project becomes "visible" in the system when first submitted
                if ($pitch->project->isClientManagement() && $pitch->project->status === \App\Models\Project::STATUS_UNPUBLISHED) {
                    $pitch->project->status = \App\Models\Project::STATUS_OPEN;
                    $pitch->project->save();

                    Log::info('Client Management project published upon first submission.', [
                        'project_id' => $pitch->project->id,
                        'pitch_id' => $pitch->id,
                    ]);
                }

                // Update previous snapshot status if applicable
                Log::debug('Checking previous snapshot status update condition.', [
                    'pitch_id' => $pitch->id,
                    'previous_snapshot_exists' => ! is_null($previousSnapshot),
                    'previous_snapshot_status' => $previousSnapshot?->status,
                    'expected_previous_snapshot_status' => PitchSnapshot::STATUS_REVISIONS_REQUESTED,
                    'condition_met' => ($previousSnapshot && $previousSnapshot->status === PitchSnapshot::STATUS_REVISIONS_REQUESTED),
                ]);
                if ($previousSnapshot && $previousSnapshot->status === PitchSnapshot::STATUS_REVISIONS_REQUESTED) {
                    $previousSnapshot->status = PitchSnapshot::STATUS_REVISION_ADDRESSED;
                    $previousSnapshot->saveOrFail();
                    Log::info('Updated previous snapshot status to revision_addressed.', ['previous_snapshot_id' => $previousSnapshot->id]);
                } else {
                    Log::debug('Skipped updating previous snapshot status.', [
                        'previous_snapshot_id' => $previousSnapshot?->id,
                        'reason' => ! $previousSnapshot ? 'No previous snapshot' : 'Previous snapshot status was not revisions_requested',
                    ]);
                }

                // Create event
                $eventComment = 'Pitch submitted for review (Version '.$newVersion.').';
                if ($responseToFeedback) {
                    $eventComment .= " Response: {$responseToFeedback}";
                }
                $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => $eventComment,
                    'status' => $pitch->status,
                    'snapshot_id' => $snapshot->id,
                    'created_by' => $submitter->id,
                ]);

                // Notify appropriate party
                if ($pitch->project->isClientManagement()) {
                    // Generate signed URL for client
                    $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                        'client.portal.view',
                        now()->addDays(config('mixpitch.client_portal_link_expiry_days', 7)),
                        ['project' => $pitch->project_id]
                    );
                    // Trigger client notification
                    try {
                        $this->notificationService->notifyClientReviewReady($pitch, $signedUrl);
                        Log::info('Client review ready notification sent.', ['pitch_id' => $pitch->id, 'client_email' => $pitch->project->client_email]);
                    } catch (\Exception $notificationException) {
                        Log::warning('Failed to send client review ready notification', [
                            'pitch_id' => $pitch->id,
                            'error' => $notificationException->getMessage(),
                        ]);
                        // Continue execution - notification failure shouldn't fail the whole operation
                    }

                    // Send producer resubmission email if this is a resubmission (not first submission)
                    if ($newVersion > 1) {
                        try {
                            $fileCount = $pitch->files()->count();
                            $this->emailService->sendClientProducerResubmitted(
                                $pitch->project->client_email,
                                $pitch->project->client_name,
                                $pitch->project,
                                $pitch,
                                $signedUrl,
                                $fileCount,
                                $responseToFeedback
                            );
                            Log::info('Sent client producer resubmitted email', [
                                'pitch_id' => $pitch->id,
                                'client_email' => $pitch->project->client_email,
                                'version' => $newVersion,
                            ]);
                        } catch (\Exception $e) {
                            // Log but don't fail the workflow
                            Log::error('Failed to send client producer resubmitted email', [
                                'pitch_id' => $pitch->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                } else {
                    // Standard notification to project owner
                    try {
                        $this->notificationService->notifyPitchReadyForReview($pitch, $snapshot); // Use $snapshot
                        Log::info('Project owner pitch ready for review notification sent.', ['pitch_id' => $pitch->id, 'project_owner_id' => $pitch->project->user_id]);
                    } catch (\Exception $notificationException) {
                        Log::warning('Failed to send pitch ready for review notification', [
                            'pitch_id' => $pitch->id,
                            'error' => $notificationException->getMessage(),
                        ]);
                        // Continue execution - notification failure shouldn't fail the whole operation
                    }
                }

                // Process audio files for workflows that require watermarking
                try {
                    // Get all audio files for this pitch
                    $audioFiles = $pitch->files()
                        ->where(function ($query) {
                            $query->whereRaw("LOWER(file_path) LIKE '%.mp3'")
                                ->orWhereRaw("LOWER(file_path) LIKE '%.wav'")
                                ->orWhereRaw("LOWER(file_path) LIKE '%.ogg'")
                                ->orWhereRaw("LOWER(file_path) LIKE '%.aac'")
                                ->orWhereRaw("LOWER(file_path) LIKE '%.m4a'")
                                ->orWhereRaw("LOWER(file_path) LIKE '%.flac'");
                        })
                        ->get();

                    // Filter files that need watermarking (workflow-agnostic)
                    $filesToProcess = $audioFiles->filter(function ($file) {
                        return $file->shouldBeWatermarked() && ! $file->audio_processed;
                    });

                    if ($filesToProcess->isNotEmpty()) {
                        Log::info('Dispatching audio processing jobs for submission', [
                            'pitch_id' => $pitch->id,
                            'workflow_type' => $pitch->project->workflow_type,
                            'audio_files_count' => $audioFiles->count(),
                            'files_to_process' => $filesToProcess->count(),
                            'snapshot_id' => $snapshot->id,
                            'watermarking_enabled' => $pitch->watermarking_enabled ?? false,
                        ]);

                        // Dispatch individual jobs for each audio file that needs processing
                        foreach ($filesToProcess as $audioFile) {
                            dispatch(new \App\Jobs\ProcessAudioForSubmission($audioFile));
                        }
                    } else {
                        Log::info('No audio files require processing', [
                            'pitch_id' => $pitch->id,
                            'workflow_type' => $pitch->project->workflow_type,
                            'audio_files_count' => $audioFiles->count(),
                            'snapshot_id' => $snapshot->id,
                            'watermarking_enabled' => $pitch->watermarking_enabled ?? false,
                        ]);
                    }
                } catch (\Exception $audioProcessingException) {
                    Log::warning('Failed to dispatch audio processing job', [
                        'pitch_id' => $pitch->id,
                        'workflow_type' => $pitch->project->workflow_type,
                        'error' => $audioProcessingException->getMessage(),
                    ]);
                    // Continue execution - audio processing failure shouldn't fail the whole operation
                }

                return $pitch->fresh(['currentSnapshot']); // Reload with snapshot relationship
            });

        } catch (SnapshotException $e) { // Catch specific snapshot errors if defined
            Log::error('Error creating snapshot for pitch submission', ['pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error submitting pitch for review', ['pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to submit pitch for review.', 0, $e);
        }
    }

    /**
     * Returns a pitch (and its current snapshot) back to the 'Ready for Review' status.
     * Intended to be called by the project owner to undo an approval, denial, or revision request.
     *
     * @param  Pitch  $pitch  The pitch to revert.
     * @param  User  $revertingUser  The user performing the action (project owner).
     * @return Pitch The updated pitch.
     *
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function returnPitchToReview(Pitch $pitch, User $revertingUser): Pitch
    {
        // <<< PHASE 3 GUARD >>>
        if ($pitch->project->isContest()) {
            throw new UnauthorizedActionException('Standard pitch status changes are not applicable for contests.');
        }
        // <<< END PHASE 3 GUARD >>>

        // Authorization check
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
        if (! in_array($pitch->status, $revertibleStatuses)) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_READY_FOR_REVIEW, 'Pitch cannot be returned to review from its current status.');
        }

        // Ensure there's a current snapshot to revert
        $snapshot = $pitch->currentSnapshot;
        if (! $snapshot) {
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
                            'reverted_from_status' => $originalStatus,
                        ]);
                        $eventToDelete->delete();
                    } else {
                        Log::warning('Could not find historical pitch event to delete upon status revert', [
                            'pitch_id' => $pitch->id,
                            'reverted_from_status' => $originalStatus,
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
     * @param  string  $stripeInvoiceId  The Stripe Invoice ID associated with the payment.
     * @param  string|null  $stripeChargeId  Optional Stripe Charge ID or Payment Intent ID.
     *
     * @throws \Exception If saving fails.
     */
    public function markPitchAsPaid(Pitch $pitch, string $stripeInvoiceId, ?string $stripeChargeId = null): Pitch
    {
        // Validate: Only completed pitches should be marked as paid.
        if ($pitch->status !== Pitch::STATUS_COMPLETED) {
            Log::warning('Attempted to mark non-completed pitch as paid.', [
                'pitch_id' => $pitch->id,
                'current_status' => $pitch->status,
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
            if ($stripeInvoiceId) {
                $comment .= " Stripe Invoice ID: {$stripeInvoiceId}";
            }
            if ($stripeChargeId) {
                $comment .= " Stripe Charge ID: {$stripeChargeId}";
            } // Or PaymentIntent ID
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

            // Schedule payout for the producer
            $payoutService = app(\App\Services\PayoutProcessingService::class);
            $payoutSchedule = $payoutService->schedulePayoutForPitch($pitch, $stripeInvoiceId);

            Log::info('Pitch marked as paid successfully.', [
                'pitch_id' => $pitch->id,
                'invoice_id' => $stripeInvoiceId,
                'payment_amount' => $pitch->payment_amount,
                'payout_schedule_id' => $payoutSchedule->id,
            ]);

            return $pitch;

        } catch (\Exception $e) {
            Log::error('Failed to mark pitch as paid in service', [
                'pitch_id' => $pitch->id,
                'invoice_id' => $stripeInvoiceId,
                'error' => $e->getMessage(),
            ]);
            // Re-throw to allow calling code (controller/webhook handler) to manage response
            throw $e;
        }
    }

    /**
     * Mark a pitch payment as failed.
     * Called after failed payment attempt or failed webhook notification.
     *
     * @param  string|null  $stripeInvoiceId  Optional Stripe Invoice ID associated with the attempt.
     * @param  string  $failureReason  Reason for failure (e.g., from Stripe exception or webhook).
     *
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
            if ($stripeInvoiceId) {
                $comment .= " Stripe Invoice ID: {$stripeInvoiceId}";
            }
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
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Return a completed pitch back to the Approved status.
     * This also reopens the associated project if it was completed.
     *
     * @param  Pitch  $pitch  The pitch to revert.
     * @param  User  $revertingUser  The user performing the action (Project Owner).
     *
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|\RuntimeException
     */
    public function returnPitchToApproved(Pitch $pitch, User $revertingUser): Pitch
    {
        // <<< PHASE 3 GUARD >>>
        if ($pitch->project->isContest()) {
            throw new UnauthorizedActionException('Standard pitch status changes are not applicable for contests.');
        }
        // <<< END PHASE 3 GUARD >>>

        // Authorization check
        if ($pitch->project->user_id !== $revertingUser->id) {
            throw new UnauthorizedActionException('return this pitch to approved');
        }
        // Policy: if ($revertingUser->cannot('returnToApproved', $pitch)) { throw new UnauthorizedActionException('return this pitch to approved'); }

        // Validation: Pitch must be completed, payment must be pending or failed.
        if ($pitch->status !== Pitch::STATUS_COMPLETED) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Pitch must be completed to return it to approved status.');
        }
        if (! in_array($pitch->payment_status, [Pitch::PAYMENT_STATUS_PENDING, Pitch::PAYMENT_STATUS_FAILED])) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Pitch cannot be returned to approved status because payment is '.$pitch->payment_status.'.');
        }

        try {
            return DB::transaction(function () use ($pitch, $revertingUser) {
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
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Failed to return pitch to approved status.', 0, $e);
        }
    }

    // Add updatePitchDetails method later if needed for title/description edits

    // <<< PHASE 3: CONTEST METHODS >>>

    /**
     * Select a winner for a contest pitch
     *
     * @param  Pitch  $pitch  The pitch to select as winner
     * @param  User  $projectOwner  The project owner performing the action
     * @return Pitch The updated pitch
     */
    public function selectContestWinner(Pitch $pitch, User $projectOwner): Pitch
    {
        // Verify project owner
        if ($pitch->project->user_id !== $projectOwner->id) {
            throw new UnauthorizedActionException('Only the project owner can select a winner');
        }

        // Verify this is a contest entry
        if ($pitch->status !== Pitch::STATUS_CONTEST_ENTRY) {
            throw new InvalidStatusTransitionException(
                $pitch->status,
                Pitch::STATUS_CONTEST_WINNER,
                'Only contest entries can be selected as winners'
            );
        }

        try {
            return DB::transaction(function () use ($pitch, $projectOwner) {
                // Set as winner
                $pitch->status = Pitch::STATUS_CONTEST_WINNER;
                $pitch->rank = 1; // Winner is always rank 1
                $pitch->approved_at = now();

                $isNoPrize = $pitch->project->prize_amount <= 0;
                $eventType = $isNoPrize ? 'contest_winner_selected_no_prize' : 'contest_winner_selected';
                $notificationMethod = $isNoPrize ? 'notifyContestWinnerSelectedNoPrize' : 'notifyContestWinnerSelected'; // Use different methods

                // Handle prize payment if applicable
                if (! $isNoPrize) {
                    $pitch->payment_amount = $pitch->project->prize_amount;
                    $pitch->payment_status = Pitch::PAYMENT_STATUS_PROCESSING;

                    // Create invoice if a prize amount is set
                    $invoiceService = app(InvoiceService::class);
                    $invoice = $invoiceService->createInvoiceForContestPrize(
                        $pitch->project,
                        $pitch->user, // Correct: Winning Producer
                        $pitch->project->prize_amount,
                        $pitch->project->prize_currency ?? 'USD'
                    );

                    $pitch->final_invoice_id = $invoice->id;
                } else {
                    // No prize money
                    $pitch->payment_amount = 0;
                    $pitch->payment_status = Pitch::PAYMENT_STATUS_NOT_REQUIRED;
                }

                $pitch->save();

                // Create event with correct type
                $pitch->events()->create([
                    'event_type' => $eventType, // Use dynamic event type
                    'status' => Pitch::STATUS_CONTEST_WINNER,
                    'comment' => 'Selected as contest winner.',
                    'created_by' => $projectOwner->id,
                ]);

                // Notify the winner (using dynamic method name)
                // Ensure NotificationService has both methods or handles the distinction
                $this->notificationService->{$notificationMethod}($pitch);

                // Notify the owner (Add this notification)
                $ownerNotificationMethod = $isNoPrize ? 'notifyContestWinnerSelectedOwnerNoPrize' : 'notifyContestWinnerSelectedOwner';
                $this->notificationService->{$ownerNotificationMethod}($pitch);

                // Close other entries
                $this->closeOtherContestEntries($pitch); // This already notifies non-winners

                return $pitch;
            });
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to select contest winner', [
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw specific exceptions
            if ($e instanceof UnauthorizedActionException || $e instanceof InvalidStatusTransitionException) {
                throw $e;
            }

            // For other exceptions, throw a generic error
            throw new \Exception('Failed to select contest winner: '.$e->getMessage());
        }
    }

    /**
     * Select a runner-up for a contest
     *
     * Select a pitch as a contest runner-up (optional).
     *
     * @param  Pitch  $pitch  The runner-up pitch entry.
     * @param  User  $projectOwner  The user selecting the runner-up.
     * @param  int  $rank  The rank to assign (must be > 1).
     * @return Pitch The updated runner-up pitch.
     *
     * @throws UnauthorizedActionException|InvalidStatusTransitionException|\InvalidArgumentException|\Exception
     */
    public function selectContestRunnerUp(Pitch $pitch, User $projectOwner, int $rank): Pitch
    {
        if ($rank <= 1) {
            throw new \InvalidArgumentException('Runner-up rank must be greater than 1.');
        }
        $project = $pitch->project;
        // Authorization & Validation
        if ($projectOwner->id !== $project->user_id || ! $project->isContest()) {
            throw new UnauthorizedActionException('select contest runner-up');
        }
        if ($pitch->status !== Pitch::STATUS_CONTEST_ENTRY) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_CONTEST_RUNNER_UP, 'Only contest entries can be selected as runner-ups.');
        }

        return DB::transaction(function () use ($pitch, $projectOwner, $rank) {
            $pitch->status = Pitch::STATUS_CONTEST_RUNNER_UP;
            $pitch->rank = $rank;
            // Runner-ups typically don't get payment via this flow, but could be added
            $pitch->save();

            // Create event
            $pitch->events()->create([
                'event_type' => 'contest_runner_up_selected',
                'comment' => "Selected as contest runner-up (Rank: {$rank}).",
                'status' => $pitch->status,
                'created_by' => $projectOwner->id,
            ]);

            // Notify runner-up
            // Note: notifyContestRunnerUpSelected needs implementation in NotificationService
            $this->notificationService->notifyContestRunnerUpSelected($pitch);

            return $pitch;
        });
    }

    /**
     * Close all other contest entries when a winner is selected
     *
     * @param  Pitch  $winningPitch  The pitch that was selected as winner
     */
    protected function closeOtherContestEntries(Pitch $winningPitch): void
    {
        // Find all other contest entries for the same project
        $otherEntries = Pitch::where('project_id', $winningPitch->project_id)
            ->where('id', '!=', $winningPitch->id)
            ->where('status', Pitch::STATUS_CONTEST_ENTRY)
            ->get();

        foreach ($otherEntries as $entry) {
            // Update status and set closed timestamp
            $entry->status = Pitch::STATUS_CONTEST_NOT_SELECTED;
            $entry->closed_at = now();
            $entry->save();

            // Create event
            $entry->events()->create([
                'event_type' => 'contest_entry_not_selected',
                'status' => Pitch::STATUS_CONTEST_NOT_SELECTED,
                'comment' => 'Entry was not selected as winner.',
                'created_by' => $winningPitch->project->user_id, // Project owner made the selection
            ]);

            // Notify the pitch owner
            $this->notificationService->notifyContestEntryNotSelected($entry);
        }
    }

    // <<< END PHASE 3: CONTEST METHODS >>>

    // --- Client Management Specific Methods ---

    /**
     * Approve a pitch submission via the Client Portal.
     * Called by the webhook (handleCheckoutSessionCompleted) after successful payment,
     * or directly by ClientPortalController if no payment is required.
     *
     * For client management projects, this automatically completes the project.
     *
     * @param  string  $clientIdentifier  (Usually the client email)
     *
     * @throws InvalidStatusTransitionException|UnauthorizedActionException
     */
    public function clientApprovePitch(Pitch $pitch, string $clientIdentifier): Pitch
    {
        // Validation: Ensure it's a client management project
        if (! $pitch->project->isClientManagement()) {
            throw new UnauthorizedActionException('Client approval is only applicable for client management projects.');
        }

        // Idempotency Check 1: If already completed, just return the pitch.
        if ($pitch->status === Pitch::STATUS_COMPLETED) {
            Log::info('clientApprovePitch called but pitch is already completed.', ['pitch_id' => $pitch->id]);

            return $pitch;
        }

        // Validation: Ensure correct status for approval
        // Can be approved from Ready for Review OR (less common) from Revisions Requested if webhook retried?
        if (! in_array($pitch->status, [Pitch::STATUS_READY_FOR_REVIEW, Pitch::STATUS_CLIENT_REVISIONS_REQUESTED])) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_COMPLETED, 'Pitch must be ready for review (or pending revision processing) for client approval.');
        }

        // Optional: Verify $clientIdentifier matches $pitch->project->client_email if needed?
        // Not strictly necessary if called internally/by webhook only, but could add robustness.

        try {
            return DB::transaction(function () use ($pitch, $clientIdentifier) {
                // Idempotency Check 2 (within transaction): Re-check status before update
                $pitch->refresh(); // Get latest state
                if ($pitch->status === Pitch::STATUS_COMPLETED) {
                    Log::info('Pitch became completed during transaction. Skipping update.', ['pitch_id' => $pitch->id]);

                    return $pitch;
                }

                // Check if milestones exist and require payment
                $hasMilestones = $pitch->milestones()->exists();
                $allMilestonesPaid = $hasMilestones &&
                    $pitch->milestones()->where('payment_status', '!=', Pitch::PAYMENT_STATUS_PAID)->count() === 0;

                if ($hasMilestones && ! $allMilestonesPaid) {
                    // Milestones exist with pending payments - set to APPROVED, not COMPLETED
                    // Client can approve the work, but completion requires payment
                    $pitch->status = Pitch::STATUS_APPROVED;
                    $pitch->approved_at = now();
                    Log::info('Client approved pitch with unpaid milestones. Status set to APPROVED.', [
                        'pitch_id' => $pitch->id,
                        'unpaid_milestones' => $pitch->milestones()->where('payment_status', '!=', Pitch::PAYMENT_STATUS_PAID)->count(),
                    ]);
                } else {
                    // No milestones OR all milestones paid OR no payment required - proceed to COMPLETED
                    $pitch->status = Pitch::STATUS_COMPLETED;
                    $pitch->approved_at = now();
                    $pitch->completed_at = now();
                    Log::info('Client approved pitch. No payment required or all milestones paid. Status set to COMPLETED.', [
                        'pitch_id' => $pitch->id,
                        'has_milestones' => $hasMilestones,
                    ]);
                }
                $pitch->save();

                // Update current snapshot status to ACCEPTED
                $currentSnapshot = $pitch->currentSnapshot;
                if ($currentSnapshot) {
                    $currentSnapshot->status = PitchSnapshot::STATUS_ACCEPTED;
                    $currentSnapshot->save();
                    Log::info('Updated snapshot status to ACCEPTED after client approval.', [
                        'pitch_id' => $pitch->id,
                        'snapshot_id' => $currentSnapshot->id,
                    ]);
                }

                // Only complete the project if status is COMPLETED
                if ($pitch->status === Pitch::STATUS_COMPLETED) {
                    $this->completeClientManagementProject($pitch, $clientIdentifier);

                    // Create completion event
                    $pitch->events()->create([
                        'event_type' => 'client_completed',
                        'comment' => 'Project automatically completed after client approval.',
                        'status' => $pitch->status,
                        'created_by' => null, // System action
                        'metadata' => ['client_email' => $clientIdentifier],
                    ]);

                    // Notify producer of approval AND completion
                    $this->notificationService->notifyProducerClientApprovedAndCompleted($pitch);
                    Log::info('Producer notified of client approval and completion.', ['pitch_id' => $pitch->id]);
                } else {
                    // Status is APPROVED - awaiting payment
                    // Just notify about approval, not completion
                    $this->notificationService->notifyProducerOfClientApproval($pitch);
                    Log::info('Producer notified of client approval. Awaiting milestone payments.', ['pitch_id' => $pitch->id]);
                }

                // Always create approval event
                $pitch->events()->create([
                    'event_type' => 'client_approved',
                    'comment' => 'Client approved the submission.',
                    'status' => Pitch::STATUS_APPROVED, // Log the approval step
                    'created_by' => null, // Client action (via system/webhook)
                    'metadata' => ['client_email' => $clientIdentifier],
                ]);

                return $pitch;
            });
        } catch (\Exception $e) {
            Log::error('Error during clientApprovePitch transaction', [
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Rethrow as a generic exception to signal failure
            throw new \RuntimeException('Could not approve pitch due to an internal error.', 0, $e);
        }
    }

    /**
     * Complete a client management project after client approval.
     * This handles project status updates and payout scheduling.
     */
    private function completeClientManagementProject(Pitch $pitch, string $clientIdentifier): void
    {
        $project = $pitch->project;

        // Update project status to completed
        $project->status = Project::STATUS_COMPLETED;
        $project->save();

        Log::info('Client management project completed', [
            'project_id' => $project->id,
            'pitch_id' => $pitch->id,
            'client_email' => $clientIdentifier,
        ]);

        // Schedule payout if payment was made
        if ($pitch->payment_status === Pitch::PAYMENT_STATUS_PAID && $pitch->payment_amount > 0) {
            $this->scheduleProducerPayout($pitch);
        }
    }

    /**
     * Schedule a payout for the producer after successful client payment.
     */
    private function scheduleProducerPayout(Pitch $pitch): void
    {
        try {
            $producer = $pitch->user;
            $project = $pitch->project;

            // Calculate payout amount (after platform commission)
            $grossAmount = $pitch->payment_amount;
            $commissionRate = $producer->getPlatformCommissionRate();
            $commissionAmount = $grossAmount * ($commissionRate / 100);
            $netAmount = $grossAmount - $commissionAmount;

            // Create payout schedule
            $payoutSchedule = \App\Models\PayoutSchedule::create([
                'producer_user_id' => $producer->id,
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'gross_amount' => $grossAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
                'status' => \App\Models\PayoutSchedule::STATUS_SCHEDULED,
                'hold_release_date' => app(\App\Services\PayoutHoldService::class)->calculateHoldReleaseDate('client_management'),
                'metadata' => [
                    'type' => 'client_management_completion',
                    'client_email' => $project->client_email,
                    'project_title' => $project->title,
                ],
            ]);

            Log::info('Producer payout scheduled for client management project', [
                'payout_schedule_id' => $payoutSchedule->id,
                'producer_id' => $producer->id,
                'pitch_id' => $pitch->id,
                'net_amount' => $netAmount,
            ]);

            // Notify producer about payout
            $this->notificationService->notifyProducerPayoutScheduled($producer, $netAmount, $payoutSchedule);

        } catch (\Exception $e) {
            Log::error('Failed to schedule producer payout', [
                'pitch_id' => $pitch->id,
                'producer_id' => $pitch->user_id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - payout can be handled manually if needed
        }
    }

    /**
     * Request revisions via the Client Portal.
     *
     * @param  string  $feedback  The client's feedback.
     * @param  string  $clientIdentifier  Email or other identifier for logging/verification
     *
     * @throws InvalidStatusTransitionException|UnauthorizedActionException
     */
    public function clientRequestRevisions(Pitch $pitch, string $feedback, string $clientIdentifier): Pitch
    {
        // Validation
        if (! $pitch->project->isClientManagement()) {
            throw new UnauthorizedActionException('Client revisions are only applicable for client management projects.');
        }

        // Allow revisions from READY_FOR_REVIEW or COMPLETED status
        // COMPLETED allows clients to change their mind after approval
        $allowedStatuses = [Pitch::STATUS_READY_FOR_REVIEW, Pitch::STATUS_COMPLETED];
        if (! in_array($pitch->status, $allowedStatuses)) {
            throw new InvalidStatusTransitionException(
                $pitch->status,
                Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                'Pitch must be ready for review or completed to request client revisions.'
            );
        }

        return DB::transaction(function () use ($pitch, $feedback, $clientIdentifier) {
            // Check if this exceeds included revisions
            $revisionIsFree = $this->isRevisionFree($pitch);

            // Increment revision counter FIRST (before creating milestone)
            $pitch->revisions_used = ($pitch->revisions_used ?? 0) + 1;

            if (! $revisionIsFree) {
                // Create a paid revision milestone (after incrementing)
                // This ensures the milestone uses the correct revision round number
                $this->createRevisionMilestone($pitch, $feedback);
            }

            $pitch->status = Pitch::STATUS_CLIENT_REVISIONS_REQUESTED; // Use the new status
            // Note: Does not use standard snapshots/revision cycles. Status change drives the flow.
            $pitch->revisions_requested_at = now(); // Use standard field
            $pitch->save();

            // Update current snapshot status to REVISIONS_REQUESTED
            $currentSnapshot = $pitch->currentSnapshot;
            if ($currentSnapshot) {
                $currentSnapshot->status = PitchSnapshot::STATUS_REVISIONS_REQUESTED;
                $currentSnapshot->save();
                Log::info('Updated snapshot status to REVISIONS_REQUESTED after client feedback.', [
                    'pitch_id' => $pitch->id,
                    'snapshot_id' => $currentSnapshot->id,
                    'revision_number' => $pitch->revisions_used,
                ]);
            }

            // Create event with feedback
            $pitch->events()->create([
                'event_type' => 'client_revisions_requested',
                'comment' => $feedback, // Store client feedback here
                'status' => $pitch->status,
                'created_by' => null, // Client action
                'metadata' => [
                    'client_email' => $clientIdentifier,
                    'revision_number' => $pitch->revisions_used,
                    'is_paid_revision' => ! $revisionIsFree,
                ],
            ]);

            // Notify producer
            $this->notificationService->notifyProducerClientRevisionsRequested($pitch, $feedback); // Requires implementation

            // Send client confirmation email
            try {
                $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'client.portal.view',
                    now()->addDays(config('mixpitch.client_portal_link_expiry_days', 7)),
                    ['project' => $pitch->project_id]
                );
                $this->emailService->sendClientRevisionRequestConfirmation(
                    $pitch->project->client_email,
                    $pitch->project->client_name,
                    $pitch->project,
                    $pitch,
                    $feedback,
                    $signedUrl
                );
                Log::info('Sent client revision request confirmation email', [
                    'pitch_id' => $pitch->id,
                    'client_email' => $pitch->project->client_email,
                ]);
            } catch (\Exception $e) {
                // Log but don't fail the workflow
                Log::error('Failed to send client revision request confirmation email', [
                    'pitch_id' => $pitch->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $pitch;
        });
    }

    /**
     * Check if the upcoming revision will be free (covered by included revisions).
     * This should be called BEFORE incrementing revisions_used.
     */
    private function isRevisionFree(Pitch $pitch): bool
    {
        $revisionsUsed = $pitch->revisions_used ?? 0;
        $includedRevisions = $pitch->included_revisions ?? 0;

        // Check if the NEXT revision (after incrementing) will be within included revisions
        // Example: 1 included revision means revisions 1 is free
        // revisions_used=0 → next=1 → 1 <= 1 = true (free)
        // revisions_used=1 → next=2 → 2 <= 1 = false (paid)
        return ($revisionsUsed + 1) <= $includedRevisions;
    }

    /**
     * Create a paid revision milestone.
     *
     * Note: The milestone is created when the client requests a paid revision,
     * but it is NOT linked to a snapshot at this time. The snapshot link
     * will be established when the producer uploads the revision files.
     */
    private function createRevisionMilestone(Pitch $pitch, string $feedback): void
    {
        // Guard: Don't create milestone if additional_revision_price is $0 or not set
        $additionalRevisionPrice = $pitch->additional_revision_price ?? 0;
        if ($additionalRevisionPrice == 0) {
            Log::info('Skipping revision milestone creation - additional_revision_price is $0', [
                'pitch_id' => $pitch->id,
                'revision_round' => ($pitch->revisions_used ?? 0) + 1,
            ]);

            return;
        }

        // Mark all previous files as superseded
        $this->markFilesAsSuperseded($pitch);

        // Revision round = revisions_used (which was just incremented)
        // This represents the round number for the UPCOMING revision
        $revisionRound = $pitch->revisions_used ?? 1;

        // Get the highest sort order to append this milestone at the end
        $maxSortOrder = $pitch->milestones()->max('sort_order') ?? 0;

        $milestone = $pitch->milestones()->create([
            'name' => "Revision Round {$revisionRound}",
            'description' => 'Additional revision requested beyond included revisions',
            'amount' => $pitch->additional_revision_price ?? 0,
            'sort_order' => $maxSortOrder + 1,
            'payment_status' => \App\Models\Pitch::PAYMENT_STATUS_PENDING,
            'is_revision_milestone' => true,
            'revision_round_number' => $revisionRound,
            'revision_request_details' => $feedback,
            // DO NOT link snapshot here - it will be linked when producer uploads the revision
            'pitch_snapshot_id' => null,
        ]);

        Log::info('Created revision milestone', [
            'pitch_id' => $pitch->id,
            'milestone_id' => $milestone->id,
            'revision_round' => $revisionRound,
            'amount' => $milestone->amount,
            'note' => 'Snapshot will be linked when producer uploads revision',
        ]);
    }

    /**
     * Mark all current pitch files as superseded by the upcoming revision
     */
    private function markFilesAsSuperseded(Pitch $pitch): void
    {
        $pitch->files()
            ->where('superseded_by_revision', false)
            ->update(['superseded_by_revision' => true]);

        Log::info('Marked pitch files as superseded', [
            'pitch_id' => $pitch->id,
        ]);
    }

    /**
     * Associate new files with the current revision round
     */
    public function associateFilesWithRevision(Pitch $pitch, array $fileIds): void
    {
        $currentRevisionRound = $pitch->revisions_used ?? 1;

        $pitch->files()
            ->whereIn('id', $fileIds)
            ->update(['revision_round' => $currentRevisionRound]);

        Log::info('Associated files with revision round', [
            'pitch_id' => $pitch->id,
            'revision_round' => $currentRevisionRound,
            'file_count' => count($fileIds),
        ]);
    }

    /**
     * Deny an initial pitch application (Pending -> Denied).
     *
     * @param  User  $denyingUser  (Project Owner)
     * @param  string|null  $reason  Optional reason for denial.
     *
     * @throws InvalidStatusTransitionException|UnauthorizedActionException
     */
    public function denyInitialPitch(Pitch $pitch, User $denyingUser, ?string $reason = null): Pitch
    {
        // <<< PHASE 1/5 GUARD >>>
        if (! $pitch->project->isStandard()) {
            throw new UnauthorizedActionException('Initial pitch denial is not applicable for this workflow type.');
        }
        // <<< END PHASE 1/5 GUARD >>>

        // Authorization check
        if ($pitch->project->user_id !== $denyingUser->id) {
            throw new UnauthorizedActionException('deny initial pitch');
        }
        // Policy check: if ($denyingUser->cannot('denyInitial', $pitch)) { throw new UnauthorizedActionException(...); }

        if ($pitch->status !== Pitch::STATUS_PENDING) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_DENIED, 'Pitch must be pending for initial denial.');
        }

        $savedPitch = null;
        try {
            $savedPitch = DB::transaction(function () use ($pitch, $denyingUser, $reason) {
                $pitch->status = Pitch::STATUS_DENIED;
                // Removed denied_at assignment since the column doesn't exist in test DB
                $pitch->save();

                // Create event
                $comment = 'Pitch application denied by project owner.';
                if ($reason) {
                    $comment .= " Reason: {$reason}";
                }
                $pitch->events()->create([
                    'event_type' => 'status_change', // Or a specific 'initial_denial' type?
                    'comment' => $comment,
                    'status' => $pitch->status,
                    'created_by' => $denyingUser->id,
                ]);

                return $pitch; // Return the updated pitch from transaction
            });

            // Notify AFTER transaction succeeds
            if ($savedPitch) {
                $this->notificationService->notifyInitialPitchDenied($savedPitch, $reason);
            }

        } catch (\Exception $e) {
            // Log the actual error from the transaction or notification
            Log::error('Error denying initial pitch or sending notification', [
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Include trace
            ]);
            // Rethrow a more specific exception or handle as needed
            throw new \RuntimeException('Failed to deny initial pitch application.', 0, $e);
        }

        return $savedPitch ?? $pitch; // Return the saved pitch if possible
    }
}
