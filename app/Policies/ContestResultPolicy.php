<?php

namespace App\Policies;

use App\Models\ContestResult;
use App\Models\User;

class ContestResultPolicy
{
    /**
     * Determine whether the user can view any contest results.
     */
    public function viewAny(User $user): bool
    {
        // Anyone can view contest results if they're public
        return true;
    }

    /**
     * Determine whether the user can view the contest result.
     */
    public function view(User $user, ContestResult $contestResult): bool
    {
        $project = $contestResult->project;

        // Contest runner can always view results
        if ($user->id === $project->user_id) {
            return true;
        }

        // Contest participants can view results if judging is finalized
        if ($contestResult->isFinalized()) {
            $hasEntry = $project->pitches()
                ->where('user_id', $user->id)
                ->where('status', 'like', '%contest%')
                ->exists();

            if ($hasEntry) {
                return true;
            }
        }

        // Public can view results if contest allows public viewing and is finalized
        if ($contestResult->isFinalized() && $project->show_submissions_publicly) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create contest results.
     */
    public function create(User $user): bool
    {
        // Contest results are created automatically by the system
        // Only allow programmatic creation through the service layer
        return false;
    }

    /**
     * Determine whether the user can update the contest result.
     */
    public function update(User $user, ContestResult $contestResult): bool
    {
        // Only the contest runner can update results, and only if not finalized
        return $user->id === $contestResult->project->user_id &&
               ! $contestResult->isFinalized();
    }

    /**
     * Determine whether the user can delete the contest result.
     */
    public function delete(User $user, ContestResult $contestResult): bool
    {
        // Only the contest runner can delete results, and only if not finalized
        return $user->id === $contestResult->project->user_id &&
               ! $contestResult->isFinalized();
    }

    /**
     * Determine whether the user can restore the contest result.
     */
    public function restore(User $user, ContestResult $contestResult): bool
    {
        // Only the contest runner can restore results
        return $user->id === $contestResult->project->user_id;
    }

    /**
     * Determine whether the user can permanently delete the contest result.
     */
    public function forceDelete(User $user, ContestResult $contestResult): bool
    {
        // Only the contest runner can permanently delete results
        // This might be restricted to admins in the future
        return $user->id === $contestResult->project->user_id;
    }

    /**
     * Determine whether the user can finalize the contest result.
     */
    public function finalize(User $user, ContestResult $contestResult): bool
    {
        // Only the contest runner can finalize results
        return $user->id === $contestResult->project->user_id &&
               ! $contestResult->isFinalized() &&
               $contestResult->project->canFinalizeJudging();
    }

    /**
     * Determine whether the user can reopen the contest result.
     */
    public function reopen(User $user, ContestResult $contestResult): bool
    {
        // Only the contest runner can reopen results, and only if finalized
        // This might be restricted to admins in the future
        return $user->id === $contestResult->project->user_id &&
               $contestResult->isFinalized();
    }

    /**
     * Determine whether the user can modify contest placements.
     */
    public function modifyPlacements(User $user, ContestResult $contestResult): bool
    {
        // Only the contest runner can modify placements, and only if not finalized
        return $user->id === $contestResult->project->user_id &&
               ! $contestResult->isFinalized();
    }

    /**
     * Determine whether the user can view detailed contest analytics.
     */
    public function viewAnalytics(User $user, ContestResult $contestResult): bool
    {
        // Only the contest runner can view detailed analytics
        return $user->id === $contestResult->project->user_id;
    }

    /**
     * Determine whether the user can export contest results.
     */
    public function export(User $user, ContestResult $contestResult): bool
    {
        // Contest runner can always export
        if ($user->id === $contestResult->project->user_id) {
            return true;
        }

        // Participants can export if results are public and finalized
        if ($contestResult->isFinalized() && $contestResult->project->show_submissions_publicly) {
            $hasEntry = $contestResult->project->pitches()
                ->where('user_id', $user->id)
                ->where('status', 'like', '%contest%')
                ->exists();

            return $hasEntry;
        }

        return false;
    }
}
