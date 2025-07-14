<?php

namespace App\Policies;

use App\Models\Pitch;
use App\Models\User;
use App\Models\PitchSnapshot;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Project;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;

class PitchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the pitch.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pitch  $pitch
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Pitch $pitch)
    {
        // Allow both the pitch owner (producer for DH/CM) and the project owner to view the pitch
        return $user->id === $pitch->user_id || $user->id === $pitch->project->user_id;
    }

    /**
     * Determine whether the user can create pitches for the project.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function createPitch(User $user, Project $project): bool
    {
        // Check subscription limits first
        if (!$user->canCreatePitch()) {
            return false;
        }

        // Deny public creation for Direct Hire / Client Management workflows
        if ($project->isDirectHire() || $project->isClientManagement()) {
            return false;
        }

        // User must be authenticated (implied by User type hint)
        // User cannot be the project owner
        // Project must be open for pitches
        // User must not already have a pitch for this project
        return $user->id !== $project->user_id &&
               $project->isOpenForPitches() &&
               !$project->userPitch($user->id); // Check if user already has a pitch
    }

    /**
     * Determine whether the user can update the pitch.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pitch  $pitch
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Pitch $pitch)
    {
        // Only the pitch owner (producer for DH/CM) can update the pitch details
        if ($user->id === $pitch->user_id) {
            // Allow editing if the pitch is in these statuses
            $allowedStatuses = [
                Pitch::STATUS_IN_PROGRESS,
                Pitch::STATUS_REVISIONS_REQUESTED,
                Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, // Added for Client Mgmt
                Pitch::STATUS_CONTEST_ENTRY, // Allow contest entries to be updated/managed
                Pitch::STATUS_DENIED, // Allow denied pitches to be editable
                Pitch::STATUS_PENDING_REVIEW, // Allow review-pending to be editable
            ];
            
            // For Client Management projects, also allow editing when READY_FOR_REVIEW (for recall functionality)
            if ($pitch->project->isClientManagement() && $pitch->status === Pitch::STATUS_READY_FOR_REVIEW) {
                $allowedStatuses[] = Pitch::STATUS_READY_FOR_REVIEW;
            }
            
            return in_array($pitch->status, $allowedStatuses);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the pitch.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pitch  $pitch
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Pitch $pitch)
    {
        // Only the pitch owner (producer for DH/CM) can delete the pitch and only in certain statuses
        return $user->id === $pitch->user_id && in_array($pitch->status, [
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_REVISIONS_REQUESTED,
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, // Added
            Pitch::STATUS_AWAITING_ACCEPTANCE, // Added for explicit DH
            Pitch::STATUS_CONTEST_ENTRY, // Allow contest entries to be deleted
            Pitch::STATUS_DENIED, // Allow denied pitches to be deleted
        ]);
    }

    /**
     * Determine whether the project owner can approve an initial pitch application.
     *
     * @param  \App\Models\User  $user (Project Owner)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function approveInitial(User $user, Pitch $pitch): bool
    {
        // Only project owner can approve, and only for Standard workflow projects
        return $user->id === $pitch->project->user_id &&
               $pitch->project->isStandard() && // Ensure it's standard
               $pitch->status === Pitch::STATUS_PENDING;
    }

    /**
     * Determine whether the project owner can approve a submitted snapshot.
     *
     * @param  \App\Models\User  $user (Project Owner)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function approveSubmission(User $user, Pitch $pitch): bool
    {
        // Block for Contests and Client Management (Client approves via portal)
        if ($pitch->project->isContest() || $pitch->project->isClientManagement()) {
            return false;
        }

        // Only project owner can approve submissions
        // Pitch must be 'ready_for_review'
        // Cannot modify paid & completed pitches
        $isPaidAndCompleted = $pitch->status === Pitch::STATUS_COMPLETED ||
                              in_array($pitch->payment_status, [Pitch::PAYMENT_STATUS_PAID, Pitch::PAYMENT_STATUS_PROCESSING]);

        return $user->id === $pitch->project->user_id &&
               $pitch->status === Pitch::STATUS_READY_FOR_REVIEW &&
               !$isPaidAndCompleted;
    }

    /**
     * Determine whether the project owner can deny a submitted snapshot.
     *
     * @param  \App\Models\User  $user (Project Owner)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function denySubmission(User $user, Pitch $pitch): bool
    {
        // Block for Contests and Client Management
        if ($pitch->project->isContest() || $pitch->project->isClientManagement()) {
            return false;
        }

        // Same logic as approveSubmission for who can deny and when
        $isPaidAndCompleted = $pitch->status === Pitch::STATUS_COMPLETED ||
                              in_array($pitch->payment_status, [Pitch::PAYMENT_STATUS_PAID, Pitch::PAYMENT_STATUS_PROCESSING]);

        return $user->id === $pitch->project->user_id &&
               $pitch->status === Pitch::STATUS_READY_FOR_REVIEW &&
               !$isPaidAndCompleted;
    }

    /**
     * Determine whether the project owner can request revisions for a submitted snapshot.
     *
     * @param  \App\Models\User  $user (Project Owner)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function requestRevisions(User $user, Pitch $pitch): bool
    {
        // Block for Contests and Client Management (Client requests via portal)
        if ($pitch->project->isContest() || $pitch->project->isClientManagement()) {
            return false;
        }

        // Same logic as approveSubmission/denySubmission
        $isPaidAndCompleted = $pitch->status === Pitch::STATUS_COMPLETED ||
                              in_array($pitch->payment_status, [Pitch::PAYMENT_STATUS_PAID, Pitch::PAYMENT_STATUS_PROCESSING]);

        return $user->id === $pitch->project->user_id &&
               $pitch->status === Pitch::STATUS_READY_FOR_REVIEW &&
               !$isPaidAndCompleted;
    }

    /**
     * Determine whether the pitch creator can cancel their submission.
     *
     * @param  \App\Models\User  $user (Pitch Creator)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function cancelSubmission(User $user, Pitch $pitch): bool
    {
        // Block for Contests
        if ($pitch->project->isContest()) {
            return false;
        }

        // Only the pitch owner (producer for DH/CM) can cancel
        // Pitch must be 'ready_for_review'
        // Snapshot must be 'pending'
        $currentSnapshotIsPending = $pitch->currentSnapshot && $pitch->currentSnapshot->status === PitchSnapshot::STATUS_PENDING;

        // Note: Client Management uses a different review flow (no snapshots)
        if ($pitch->project->isClientManagement()) {
            return false; // Cancellation might need different logic/policy for Client Mgmt
        }

        return $user->id === $pitch->user_id &&
               $pitch->status === Pitch::STATUS_READY_FOR_REVIEW &&
               $currentSnapshotIsPending;
    }

    /**
     * Determine whether the pitch creator can recall their submission (Client Management specific).
     *
     * @param  \App\Models\User  $user (Pitch Creator)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function recallSubmission(User $user, Pitch $pitch): bool
    {
        // Only for Client Management projects
        if (!$pitch->project->isClientManagement()) {
            return false;
        }

        // Only the pitch owner can recall
        // Pitch must be 'ready_for_review'
        return $user->id === $pitch->user_id &&
               $pitch->status === Pitch::STATUS_READY_FOR_REVIEW;
    }

    /**
     * Determine whether the pitch creator can submit the pitch for review.
     *
     * @param  \App\Models\User  $user (Pitch Creator)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function submitForReview(User $user, Pitch $pitch): bool
    {
        \Illuminate\Support\Facades\Log::debug('PitchPolicy::submitForReview called in test.', [
            'user_id' => $user->id,
            'pitch_id' => $pitch->id,
            'pitch_user_id' => $pitch->user_id,
            'is_contest' => $pitch->project->isContest(),
            'would_return' => $user->id === $pitch->user_id && !$pitch->project->isContest()
        ]);
        
        // Block for Contests
        if ($pitch->project->isContest()) {
            throw new AuthorizationException('Contest pitches cannot be submitted directly.');
        }

        // Only the pitch owner (producer for DH/CM) can submit
        // Pitch must be in an active state allowing submission
        $isTargetProducer = $user->id === $pitch->user_id;
        if (!$isTargetProducer) {
            throw new AuthorizationException('Only the pitch owner can submit it for review.');
        }
        
        $isCorrectStatus = in_array($pitch->status, [
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_REVISIONS_REQUESTED,
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED // Added for Client Mgmt
        ]);

        if (!$isCorrectStatus) {
            throw new AuthorizationException('Pitch cannot be submitted in its current status.');
        }
        
        \Illuminate\Support\Facades\Log::debug('PitchPolicy::submitForReview results.', [
            'isTargetProducer' => $isTargetProducer,
            'isCorrectStatus' => $isCorrectStatus,
            'pitch_status' => $pitch->status,
            'would_return' => $isTargetProducer && $isCorrectStatus
        ]);
        
        return true; // If we reached here, all checks passed
    }

    /**
     * Determine whether the project owner can mark the pitch as complete.
     *
     * @param  \App\Models\User  $user (Project Owner)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function complete(User $user, Pitch $pitch): bool
    {
        // Block completion for Contests (use selectWinner)
        // For Client Management, only the producer (pitch owner) should complete?
        if ($pitch->project->isContest()) {
            return false;
        }

        // Determine who can complete based on workflow
        $canComplete = false;
        if ($pitch->project->isStandard() || $pitch->project->isDirectHire()) {
            // Only project owner can complete standard/direct hire
            $canComplete = $user->id === $pitch->project->user_id;
        } elseif ($pitch->project->isClientManagement()) {
            // Only the producer (pitch owner) can complete client mgmt projects
            $canComplete = $user->id === $pitch->user_id;
        }

        // Pitch must be approved
        $isCorrectStatus = $pitch->status === Pitch::STATUS_APPROVED;

        return $canComplete && $isCorrectStatus;
    }

    /**
     * Determine whether the user can return a completed pitch to approved status.
     *
     * @param  \App\Models\User  $user (Project Owner)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function returnToApproved(User $user, Pitch $pitch): bool
    {
        // Block for Contests and Client Management
        if ($pitch->project->isContest() || $pitch->project->isClientManagement()) {
            return false;
        }
        // Only the project owner can perform this action
        return $user->id === $pitch->project->user_id &&
               $pitch->status === Pitch::STATUS_COMPLETED; // Must be completed
    }

    /**
     * Determine whether the user can upload files to the pitch.
     * Added from PitchFilePolicy as the check relates to the Pitch status/ownership.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pitch  $pitch The pitch to upload to.
     * @return bool
     */
    public function uploadFile(User $user, Pitch $pitch): bool
    {
        // Generally, only the pitch owner (producer) should upload files during active phases.
        // Project owner might upload reference files if logic allows, but primary uploads are by producer.

        $canUpload = $user->id === $pitch->user_id;

        // Define allowed statuses for upload
        $allowedStatuses = [
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_REVISIONS_REQUESTED,
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, // Allow producer upload after client request
            Pitch::STATUS_CONTEST_ENTRY // Allow contest entry upload
            // Maybe Pitch::STATUS_PENDING if initial files are allowed before approval?
        ];

        return $canUpload && in_array($pitch->status, $allowedStatuses);
    }

    /**
     * Determine whether the user can manage access for a pitch (grant/revoke access)
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function manageAccess(User $user, Pitch $pitch): bool
    {
        // Example: Only project owner can manage access
        return $user->id === $pitch->project->user_id;
    }

    /**
     * Determine whether the user can manage review status of a pitch
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function manageReview(User $user, Pitch $pitch): bool
    {
        // Only project owner can manage reviews
        // Check if pitch status allows review actions
        $isOwner = $user->id === $pitch->project->user_id;
        $isCorrectStatus = $pitch->status === Pitch::STATUS_READY_FOR_REVIEW;

        // Deny if contest or client management (different review flows)
        if ($pitch->project->isContest() || $pitch->project->isClientManagement()) {
            return false;
        }

        return $isOwner && $isCorrectStatus;
    }

    // <<< PHASE 3: CONTEST POLICIES >>>

    /**
     * Determine whether the project owner can select a contest winner.
     *
     * @param  \App\Models\User  $user (Project Owner)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function selectWinner(User $user, Pitch $pitch): bool
    {
        // Only project owner can select winner for their contest pitch
        return $user->id === $pitch->project->user_id &&
               $pitch->project->isContest() &&
               $pitch->status === Pitch::STATUS_CONTEST_ENTRY; // Can only select from entries
    }

    /**
     * Determine whether the project owner can select a contest runner-up.
     *
     * @param  \App\Models\User  $user (Project Owner)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function selectRunnerUp(User $user, Pitch $pitch): bool
    {
        // Only project owner can select runner-up for their contest pitch
        return $user->id === $pitch->project->user_id &&
               $pitch->project->isContest() &&
               $pitch->status === Pitch::STATUS_CONTEST_ENTRY;
    }

    /**
     * Determine whether the user can accept a Direct Hire offer.
     *
     * @param User $user
     * @param Pitch $pitch
     * @return boolean
     */
    public function acceptDirectHire(User $user, Pitch $pitch): bool
    {
        // Only the assigned producer can accept, and only if awaiting acceptance
        return $user->id === $pitch->user_id &&
               $pitch->project->isDirectHire() &&
               $pitch->status === Pitch::STATUS_AWAITING_ACCEPTANCE;
    }

    /**
     * Determine whether the user can reject a Direct Hire offer.
     *
     * @param User $user
     * @param Pitch $pitch
     * @return boolean
     */
    public function rejectDirectHire(User $user, Pitch $pitch): bool
    {
        // Only the assigned producer can reject, and only if awaiting acceptance
        return $user->id === $pitch->user_id &&
               $pitch->project->isDirectHire() &&
               $pitch->status === Pitch::STATUS_AWAITING_ACCEPTANCE;
    }

    // <<< END PHASE 3: CONTEST POLICIES >>>

    // <<< PHASE 5: CONTEST JUDGING POLICIES >>>

    /**
     * Determine whether the user can set contest placement for a pitch.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function setContestPlacement(User $user, Pitch $pitch): bool
    {
        // Only the contest runner can set placements
        return $user->id === $pitch->project->user_id &&
               $pitch->project->isContest() &&
               !$pitch->project->isJudgingFinalized() &&
               in_array($pitch->status, [Pitch::STATUS_CONTEST_ENTRY, Pitch::STATUS_CONTEST_WINNER, Pitch::STATUS_CONTEST_RUNNER_UP]);
    }

    /**
     * Determine whether the user can view contest entry snapshots.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function viewContestEntry(User $user, Pitch $pitch): bool
    {
        // Contest runner can always view entries
        if ($user->id === $pitch->project->user_id && $pitch->project->isContest()) {
            return true;
        }

        // Entry owner can view their own entry
        if ($user->id === $pitch->user_id && $pitch->project->isContest()) {
            return true;
        }

        // Other participants can view if submissions are public or judging is finalized
        if ($pitch->project->isContest()) {
            $hasEntry = $pitch->project->pitches()
                                      ->where('user_id', $user->id)
                                      ->where('status', 'like', '%contest%')
                                      ->exists();
            
            if ($hasEntry && ($pitch->project->show_submissions_publicly || $pitch->project->isJudgingFinalized())) {
                return true;
            }
        }

        // Public can view if contest allows public viewing
        if ($pitch->project->isContest() && $pitch->project->show_submissions_publicly) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can judge this specific contest entry.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function judgeContestEntry(User $user, Pitch $pitch): bool
    {
        // Only the contest runner can judge entries, and only if judging isn't finalized
        return $user->id === $pitch->project->user_id &&
               $pitch->project->isContest() &&
               !$pitch->project->isJudgingFinalized() &&
               in_array($pitch->status, [Pitch::STATUS_CONTEST_ENTRY, Pitch::STATUS_CONTEST_WINNER, Pitch::STATUS_CONTEST_RUNNER_UP]);
    }

    /**
     * Determine whether the user can access contest judging features for this pitch.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function accessContestJudging(User $user, Pitch $pitch): bool
    {
        // Only the contest runner can access judging features
        return $user->id === $pitch->project->user_id && $pitch->project->isContest();
    }

    // <<< END PHASE 5: CONTEST JUDGING POLICIES >>>

    // Add other policy methods as needed, e.g., for completion, file management
}
