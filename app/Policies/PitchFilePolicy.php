<?php

namespace App\Policies;

use App\Models\PitchFile;
use App\Models\User;
use App\Models\Pitch;
use Illuminate\Auth\Access\HandlesAuthorization;

class PitchFilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the pitch file.
     * Generally, project owner and pitch owner can view.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PitchFile  $pitchFile
     * @return bool
     */
    public function view(User $user, PitchFile $pitchFile): bool
    {
        $pitch = $pitchFile->pitch;
        return $user->id === $pitch->user_id || $user->id === $pitch->project->user_id;
    }

    /**
     * Determine whether the user can upload files to the associated pitch.
     * Typically, only the pitch owner can upload, and only in specific statuses.
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

    /**
     * Determine whether the user can delete the pitch file.
     * Typically, only the pitch owner can delete, and only in specific statuses.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PitchFile  $pitchFile
     * @return bool
     */
    public function deleteFile(User $user, PitchFile $pitchFile): bool
    {
        $pitch = $pitchFile->pitch;
        // Only the pitch owner can delete
        // Pitch must be in a status that allows file modification
        return $user->id === $pitch->user_id &&
               in_array($pitch->status, [
                   Pitch::STATUS_IN_PROGRESS,
                   Pitch::STATUS_REVISIONS_REQUESTED,
                   Pitch::STATUS_DENIED
               ]);
    }

    /**
     * Determine whether the user can download the pitch file.
     * Similar logic to viewing.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PitchFile  $pitchFile
     * @return bool
     */
    public function downloadFile(User $user, PitchFile $pitchFile): bool
    {
        // Same logic as view for now
        $pitch = $pitchFile->pitch;
        return $user->id === $pitch->user_id || $user->id === $pitch->project->user_id;
    }
} 