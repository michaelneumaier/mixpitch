<?php

namespace App\Policies;

use App\Models\Pitch;
use App\Models\User;
use App\Models\PitchSnapshot;
use Illuminate\Auth\Access\HandlesAuthorization;

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
        // Allow both the pitch owner and the project owner to view the pitch
        return $user->id === $pitch->user_id || $user->id === $pitch->project->user_id;
    }

    /**
     * Determine whether the user can create pitches for the project.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function createPitch(User $user, \App\Models\Project $project): bool
    {
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
        // Only the pitch owner can update the pitch
        // Additional logic for status-based permissions
        if ($user->id === $pitch->user_id) {
            // Allow editing if the pitch is in these statuses
            return in_array($pitch->status, [
                Pitch::STATUS_IN_PROGRESS,
                Pitch::STATUS_DENIED,
                Pitch::STATUS_REVISIONS_REQUESTED,
                Pitch::STATUS_PENDING_REVIEW
            ]);
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
        // Only the pitch owner can delete the pitch and only in certain statuses
        return $user->id === $pitch->user_id && in_array($pitch->status, [
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_DENIED,
            Pitch::STATUS_REVISIONS_REQUESTED
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
        // Only the project owner can approve initial pitch
        // Pitch must be in 'pending' status
        return $user->id === $pitch->project->user_id && $pitch->status === Pitch::STATUS_PENDING;
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
        // Only the project owner can approve submissions
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
        // Only the pitch owner (creator) can cancel
        // Pitch must be 'ready_for_review'
        // Snapshot must be 'pending'
        $currentSnapshotIsPending = $pitch->currentSnapshot && $pitch->currentSnapshot->status === PitchSnapshot::STATUS_PENDING;

        return $user->id === $pitch->user_id &&
               $pitch->status === Pitch::STATUS_READY_FOR_REVIEW &&
               $currentSnapshotIsPending;
    }

    /**
     * Determine whether the pitch creator can submit the pitch for review.
     *
     * @param  \App\Models\User  $user (Pitch Creator)
     * @param  \App\Models\Pitch  $pitch
     * @return bool
     */
    public function submitForReview(User $user, Pitch $pitch): bool
    {
        // Only the pitch owner (creator) can submit
        // Pitch must be in 'in_progress' or 'revisions_requested' status
        // Add Pitch::STATUS_DENIED here if denied pitches should be resubmittable
        return $user->id === $pitch->user_id &&
               in_array($pitch->status, [
                   Pitch::STATUS_IN_PROGRESS,
                   Pitch::STATUS_REVISIONS_REQUESTED
                ]);
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
        // Only the project owner can complete the pitch
        // Pitch must be in 'approved' status
        // Cannot complete if already paid or processing payment
        $isPaidOrProcessing = in_array($pitch->payment_status, [
            Pitch::PAYMENT_STATUS_PAID,
            Pitch::PAYMENT_STATUS_PROCESSING
        ]);

        return $user->id === $pitch->project->user_id &&
               $pitch->status === Pitch::STATUS_APPROVED &&
               !$isPaidOrProcessing;
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
        // Only the pitch owner can upload
        // Pitch must be in a status that allows uploads
        return $user->id === $pitch->user_id &&
               in_array($pitch->status, [
                   Pitch::STATUS_IN_PROGRESS,
                   Pitch::STATUS_REVISIONS_REQUESTED
                   // Add other statuses if needed
               ]);
    }

    // Add other policy methods as needed, e.g., for completion, file management
}
