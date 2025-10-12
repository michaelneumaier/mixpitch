<?php

namespace App\Policies;

use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PitchFilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the pitch file.
     * Generally, project owner and pitch owner can view.
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
     * @param  \App\Models\Pitch  $pitch  The pitch to upload to.
     */
    public function uploadFile(User $user, Pitch $pitch): bool
    {
        // Only the pitch owner can upload
        if ($user->id !== $pitch->user_id) {
            return false;
        }

        // Terminal states - no uploads allowed
        $terminalStates = [
            Pitch::STATUS_COMPLETED,
            Pitch::STATUS_CLOSED,
            Pitch::STATUS_DENIED,
            Pitch::STATUS_CONTEST_WINNER,
            Pitch::STATUS_CONTEST_RUNNER_UP,
            Pitch::STATUS_CONTEST_NOT_SELECTED,
        ];

        if (in_array($pitch->status, $terminalStates)) {
            return false;
        }

        // Payment-protected states
        if ($pitch->isAcceptedCompletedAndPaid()) {
            return false;
        }

        // Workflow-specific logic
        return match ($pitch->project->workflow_type) {
            \App\Models\Project::WORKFLOW_TYPE_CONTEST => $this->allowContestUploads($pitch),
            \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT => $this->allowClientManagementUploads($pitch),
            default => $this->allowStandardUploads($pitch),
        };
    }

    /**
     * Determine whether the user can delete the pitch file.
     * Typically, only the pitch owner can delete, and only in specific statuses.
     */
    public function deleteFile(User $user, PitchFile $pitchFile): bool
    {
        $pitch = $pitchFile->pitch;

        // Only the pitch owner can delete
        if ($user->id !== $pitch->user_id) {
            return false;
        }

        // Use same logic as upload - if you can upload, you can delete
        // Plus allow deletion in DENIED state for cleanup
        return $this->uploadFile($user, $pitch) ||
               $pitch->status === Pitch::STATUS_DENIED;
    }

    /**
     * Determine whether the user can download the pitch file.
     * Project owners can only download original files if pitch is accepted, completed, and paid.
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
     */
    public function accessOriginalFile(User $user, PitchFile $pitchFile): bool
    {
        return $pitchFile->canAccessOriginalFile($user);
    }

    /**
     * Determine whether the user can stream the pitch file.
     * This is used for audio players and preview purposes.
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
     */
    public function receivesWatermarked(User $user, PitchFile $pitchFile): bool
    {
        return $pitchFile->shouldServeWatermarked($user);
    }

    /**
     * Check if contest uploads are allowed.
     */
    private function allowContestUploads(Pitch $pitch): bool
    {
        // Only allow during submission period and in contest entry status
        return $pitch->status === Pitch::STATUS_CONTEST_ENTRY &&
               ! $pitch->project->isSubmissionPeriodClosed();
    }

    /**
     * Check if client management workflow uploads are allowed.
     */
    private function allowClientManagementUploads(Pitch $pitch): bool
    {
        $allowedStatuses = [
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
            Pitch::STATUS_READY_FOR_REVIEW, // Allow uploads when ready for review to support recall functionality
        ];

        return in_array($pitch->status, $allowedStatuses);
    }

    /**
     * Check if standard workflow uploads are allowed.
     */
    private function allowStandardUploads(Pitch $pitch): bool
    {
        return in_array($pitch->status, [
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_REVISIONS_REQUESTED,
        ]);
    }

    /**
     * Determine whether the user can upload a new version of this file.
     * Only file owner can upload new versions, and only when pitch is in editable state.
     */
    public function uploadVersion(User $user, PitchFile $file): bool
    {
        // Only file owner can upload new versions
        if ($user->id !== $file->user_id) {
            return false;
        }

        // Check if uploads are allowed for the pitch (reuse existing logic)
        return $this->uploadFile($user, $file->pitch);
    }

    /**
     * Determine whether the user can delete a specific version.
     * Can delete version if:
     * - User owns the file
     * - File is not the root file (can't delete root without deleting all)
     * - Pitch is in editable state
     */
    public function deleteVersion(User $user, PitchFile $file): bool
    {
        // User must own the file
        if ($user->id !== $file->user_id) {
            return false;
        }

        // Cannot delete root file using deleteVersion (use deleteFile instead)
        if ($file->parent_file_id === null) {
            return false;
        }

        // Check if file operations are allowed for the pitch
        return $this->deleteFile($user, $file);
    }

    /**
     * Determine whether the user can switch between versions of a file.
     * Can switch versions if:
     * - User owns the pitch (is the producer)
     * - Pitch is in a state where file management is allowed
     */
    public function switchVersion(User $user, PitchFile $file): bool
    {
        $pitch = $file->pitch;

        // Only pitch owner can switch versions
        if ($user->id !== $pitch->user_id) {
            return false;
        }

        // Use same logic as uploadFile - if you can manage files, you can switch versions
        return $this->uploadFile($user, $pitch);
    }
}
