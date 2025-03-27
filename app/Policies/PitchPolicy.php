<?php

namespace App\Policies;

use App\Models\Pitch;
use App\Models\User;
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
}
