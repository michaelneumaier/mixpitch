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
                   Pitch::STATUS_REVISIONS_REQUESTED,
                   Pitch::STATUS_CONTEST_ENTRY, // Allow contest entries to upload files
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
                   Pitch::STATUS_DENIED,
                   Pitch::STATUS_CONTEST_ENTRY, // Allow contest entries to delete files
               ]);
    }

    /**
     * Determine whether the user can download the pitch file.
     * Project owners can only download original files if pitch is accepted, completed, and paid.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PitchFile  $pitchFile
     * @return bool
     */
    public function downloadFile(User $user, PitchFile $pitchFile): bool
    {
        $pitch = $pitchFile->pitch;
        
        // Pitch owner can always download their files
        if ($user->id === $pitch->user_id) {
            return true;
        }
        
        // Project owner can only download original files if pitch is accepted, completed, and paid
        if ($user->id === $pitch->project->user_id) {
            return $pitch->isAcceptedCompletedAndPaid();
        }
        
        return false;
    }

    /**
     * Determine whether the user can access the original (non-watermarked) file.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PitchFile  $pitchFile
     * @return bool
     */
    public function accessOriginalFile(User $user, PitchFile $pitchFile): bool
    {
        return $pitchFile->canAccessOriginalFile($user);
    }

    /**
     * Determine whether the user can stream the pitch file.
     * This is used for audio players and preview purposes.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PitchFile  $pitchFile
     * @return bool
     */
    public function streamFile(User $user, PitchFile $pitchFile): bool
    {
        // Same logic as view - project owner and pitch owner can stream
        // The PitchFile model will determine which version to serve
        return $this->view($user, $pitchFile);
    }

    /**
     * Determine whether the user should receive watermarked version.
     * This is used to show appropriate UI indicators.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PitchFile  $pitchFile
     * @return bool
     */
    public function receivesWatermarked(User $user, PitchFile $pitchFile): bool
    {
        return $pitchFile->shouldServeWatermarked($user);
    }
} 