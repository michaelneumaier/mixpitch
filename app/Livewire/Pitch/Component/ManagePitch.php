<?php

// app/Livewire/Pitch/Component/ManagePitch.php

namespace App\Livewire\Pitch\Component;

use App\Exceptions\File\FileDeletionException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\SnapshotException;
use App\Exceptions\SubmissionValidationException;
use App\Exceptions\UnauthorizedActionException;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Services\FileManagementService;
use App\Services\PitchWorkflowService;
use Illuminate\Database\Eloquent\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class ManagePitch extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public Pitch $pitch;

    public $comment;

    public $rating;

    public $acceptedTerms = false;

    public $budgetFlexibility = 'strict';

    public $licensingAgreement = 'exclusive';

    // Storage tracking
    public $storageUsedPercentage = 0;

    public $storageLimitMessage = '';

    public $storageRemaining = 0;

    // File management access flag
    public $canManageFiles = false;

    // Property to capture optional response to feedback when resubmitting
    public $responseToFeedback = '';

    // Property to hold the latest feedback message (revision or denial)
    public $statusFeedbackMessage = null;

    public $project;

    public $currentSnapshot = null;

    public $latestSnapshot = null;

    public $snapshots = [];

    public $events = [];

    // Modal State
    public $showDeleteModal = false;

    public $fileIdToDelete = null;

    public $internalNotes = null;

    protected $rules = [
        'responseToFeedback' => 'nullable|string|max:5000',
        'internalNotes' => 'nullable|string|max:10000', // Add validation for internal notes
    ];

    protected $listeners = [
        'refreshPitchData' => 'mount',
        'filesUploaded' => 'refreshPitchData',
        'fileDeleted' => '$refresh', // Refresh when files are deleted
    ];

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;

        // Initialize storage information
        $this->updateStorageInfo();

        $this->pitch->refresh(); // Ensure latest data is loaded

        // Reload relationships to ensure data is fresh
        $this->pitch->load(['project', 'user', 'snapshots', 'events.user', 'files']);
        $this->project = $this->pitch->project;
        $this->snapshots = $this->pitch->snapshots()->orderBy('created_at', 'desc')->get();
        $this->events = $this->pitch->events()->orderBy('created_at', 'desc')->get();
        $this->currentSnapshot = $this->pitch->currentSnapshot;
        $this->latestSnapshot = $this->snapshots->first(); // Get the most recent one
        $this->responseToFeedback = ''; // Reset form
        $this->fileIdToDelete = null;
        $this->showDeleteModal = false;

        // Reset revision feedback message
        $this->statusFeedbackMessage = null;
        Log::debug('ManagePitch Mount: Checking pitch status.', ['pitch_id' => $this->pitch->id, 'status' => $this->pitch->status]);

        // Fetch feedback if status is revisions_requested OR denied
        if ($this->pitch->status === Pitch::STATUS_REVISIONS_REQUESTED || $this->pitch->status === Pitch::STATUS_DENIED) {
            Log::debug('ManagePitch Mount: Status is REVISIONS_REQUESTED or DENIED. Fetching feedback.', ['pitch_id' => $this->pitch->id, 'status' => $this->pitch->status]);
            $this->statusFeedbackMessage = $this->getLatestStatusFeedback();
            Log::debug('ManagePitch Mount: Feedback fetched.', ['pitch_id' => $this->pitch->id, 'feedback_message' => $this->statusFeedbackMessage]);
        } else {
            Log::debug('ManagePitch Mount: Status is not REVISIONS_REQUESTED or DENIED. Skipping feedback fetch.', ['pitch_id' => $this->pitch->id]);
        }

        // Initialize internal notes
        $this->internalNotes = $this->pitch->internal_notes;
    }

    public function render()
    {
        $existingFiles = PitchFile::where('pitch_id', $this->pitch->id)->paginate(10);
        $events = $this->pitch->events()->latest()->paginate(5);
        $snapshots = $this->pitch->snapshots()->orderBy('created_at', 'desc')->get();

        return view('livewire.pitch.component.manage-pitch')->with([
            'existingFiles' => $existingFiles,
            'events' => $events,
            'snapshots' => $snapshots,
        ]);
    }

    /**
     * Refresh component data after file uploads.
     */
    public function refreshPitchData()
    {
        $this->pitch->refresh(); // Refresh the pitch model relation
        $this->updateStorageInfo(); // Update storage display
        // Reload files specifically if needed, though refresh() might cover it
        // $this->pitch->load('files');
        // You might need to refresh other derived properties if necessary
        // $this->dispatch('pitch-updated'); // Optional: If other components need notifying
    }

    /**
     * Delete a persisted Pitch File.
     */
    public function deleteFile($fileId, FileManagementService $fileManagementService)
    {
        // Find the file model first
        $pitchFile = PitchFile::find($fileId);
        if (! $pitchFile || $pitchFile->pitch_id !== $this->pitch->id) {
            Toaster::error('File not found.');

            return;
        }

        // Authorize the deletion
        try {
            $this->authorize('deleteFile', $pitchFile); // Use PitchFilePolicy
        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to delete this file.');

            return;
        }

        try {
            $deleted = $fileManagementService->deletePitchFile($pitchFile, Auth::user());

            Toaster::success("File '{$pitchFile->file_name}' deleted successfully.");
            $this->updateStorageInfo();
            $this->dispatch('file-deleted');

        } catch (FileDeletionException $e) {
            Log::warning('Pitch file deletion failed via Livewire', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error deleting pitch file via Livewire', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while deleting the file.');
        }
    }

    /**
     * Set the file ID to be deleted and show the confirmation modal, after authorization.
     */
    public function confirmDeleteFile($fileId)
    {
        $pitchFile = PitchFile::where('id', $fileId)->where('pitch_id', $this->pitch->id)->first();
        if (! $pitchFile) {
            Toaster::error('File not found or does not belong to this pitch.');

            return;
        }

        // Check authorization using the policy
        if (Auth::user()->can('deleteFile', $pitchFile)) {
            $this->fileIdToDelete = $fileId;
            $this->showDeleteModal = true;
        } else {
            // Explain *why* they can't delete (status or ownership)
            $pitch = $pitchFile->pitch;
            if (Auth::id() !== $pitch->user_id) {
                Toaster::warning('You are not the owner of this pitch.');
            } else {
                Toaster::warning('Files can only be deleted when the pitch is In Progress, Revisions Requested, or Denied.');
            }
        }
    }

    /**
     * Hide the delete confirmation modal.
     */
    public function cancelDeleteFile()
    {
        $this->showDeleteModal = false;
        $this->fileIdToDelete = null;
    }

    /**
     * Delete the selected file.
     * Called by the modal confirmation button.
     */
    public function deleteSelectedFile(FileManagementService $fileManagementService)
    {
        if (! $this->fileIdToDelete) {
            Toaster::error('No file selected for deletion.');
            $this->cancelDeleteFile();

            return;
        }

        $pitchFile = PitchFile::find($this->fileIdToDelete);
        if (! $pitchFile || $pitchFile->pitch_id !== $this->pitch->id) {
            Toaster::error('File not found or invalid.');
            $this->cancelDeleteFile();

            return;
        }

        // Re-authorize just in case, but show Toaster on failure
        try {
            $this->authorize('deleteFile', $pitchFile);
        } catch (AuthorizationException $e) {
            // This catch block might be redundant now if confirmDeleteFile works correctly,
            // but provides defense-in-depth.
            Log::warning('Authorization failed unexpectedly during file delete confirmation', ['user_id' => Auth::id(), 'file_id' => $this->fileIdToDelete]);
            Toaster::error('Authorization failed. You may not be allowed to delete this file in the current pitch status.');
            $this->cancelDeleteFile();

            return;
        }

        try {
            $fileName = $pitchFile->file_name;
            $deleted = $fileManagementService->deletePitchFile($pitchFile, Auth::user());

            if ($deleted) {
                Toaster::success("File '{$fileName}' deleted successfully.");
                $this->updateStorageInfo();
                $this->dispatch('file-deleted');
                $this->pitch->refresh();
            } else {
                Toaster::error("Failed to delete file '{$fileName}'.");
            }

        } catch (FileDeletionException $e) {
            Log::warning('Pitch file deletion failed via Livewire', ['file_id' => $this->fileIdToDelete, 'error' => $e->getMessage()]);
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error deleting pitch file via Livewire', ['file_id' => $this->fileIdToDelete, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while deleting the file.');
        }

        $this->cancelDeleteFile();
    }

    /**
     * Initiate file download by generating a temporary URL.
     */
    public function downloadFile($fileId, FileManagementService $fileManagementService)
    {
        // Find the file model first
        $pitchFile = PitchFile::find($fileId);
        if (! $pitchFile || $pitchFile->pitch_id !== $this->pitch->id) {
            Toaster::error('File not found.');

            return;
        }

        // Authorize the download
        try {
            $this->authorize('downloadFile', $pitchFile); // Use PitchFilePolicy
        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to download this file.');

            return;
        }

        try {
            $url = $fileManagementService->getTemporaryDownloadUrl($pitchFile, Auth::user());

            // Dispatch event for JavaScript to handle opening the URL
            $this->dispatch('openUrl', url: $url);
            Toaster::info('Your download will begin shortly...');

        } catch (\Exception $e) {
            Log::error('Error getting pitch file download URL via Livewire', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            Toaster::error('Could not generate download link: '.$e->getMessage());
        }
    }

    public function submitComment()
    {
        $this->validate([
            'comment' => 'required|string|max:255',
        ]);

        $this->pitch->addComment($this->comment);

        $this->comment = '';
        Toaster::success('Comment added successfully.');
    }

    public function deleteComment($commentId)
    {
        try {
            $this->pitch->deleteComment($commentId);
            Toaster::success('Comment deleted successfully.');
        } catch (\Exception $e) {
            Toaster::warning('You are not authorized to delete this comment');
        }
    }

    public function submitRating()
    {
        $this->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $this->pitch->addRating($this->rating);

        $this->rating = '';
        Toaster::success(sprintf('Rating added successfully.'));
    }

    public function saveNote($fileId, $note)
    {
        $pitchFile = PitchFile::findOrFail($fileId);

        // Update the note
        $pitchFile->update([
            'note' => $note,
        ]);

        if ($note == '') {
            $this->pitch->addComment('Note removed from file.');
        } else {
            $this->pitch->addComment("Note added to file ({$pitchFile->file_name}): {$note}");
        }

        // Optional: Provide feedback to the user
        Toaster::success('Note saved successfully.');
    }

    /**
     * Submit the pitch for review.
     * Delegates logic to PitchWorkflowService.
     */
    public function submitForReview(PitchWorkflowService $pitchWorkflowService)
    {
        // --- DEBUGGING ---
        Log::debug('ManagePitch::submitForReview called in test.', [
            'auth_user_id' => Auth::id(),
            'pitch_id' => $this->pitch->id,
            'pitch_user_id' => $this->pitch->user_id,
        ]);
        // --- END DEBUGGING ---

        $this->authorize('submitForReview', $this->pitch);
        $this->validateOnly('responseToFeedback');

        try {
            $pitchWorkflowService->submitPitchForReview($this->pitch, Auth::user(), $this->responseToFeedback);

            Toaster::success('Pitch submitted for review successfully.');
            $this->responseToFeedback = ''; // Clear the textarea
            $this->pitch->refresh(); // Refresh pitch data
            $this->mount($this->pitch); // Remount to refresh all data including feedback

        } catch (SubmissionValidationException $e) {
            Log::warning('Pitch submission validation failed', ['pitch_id' => $this->pitch->id, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
            Toaster::error($e->getMessage()); // Show specific validation error
        } catch (InvalidStatusTransitionException $e) {
            Log::warning('Invalid status transition on pitch submission', ['pitch_id' => $this->pitch->id, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
            Toaster::error($e->getMessage());
        } catch (UnauthorizedActionException $e) { // Catch service-level exception
            Log::warning('Unauthorized action attempt: submitForReview', [
                'pitch_id' => $this->pitch->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            // Toaster::error('You are not authorized to perform this action.'); // Keep toaster for UI, but still fail request
            throw $e; // Re-throw the exception
        } catch (AuthorizationException $e) { // Catch policy-level exception
            Log::warning('Authorization failed via policy: submitForReview', [
                'pitch_id' => $this->pitch->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            // Toaster::error('You do not have permission to submit this pitch.'); // Keep toaster for UI, but still fail request
            throw $e; // Re-throw the exception
        } catch (SnapshotException $e) {
            Log::error('Snapshot error during pitch submission', ['pitch_id' => $this->pitch->id, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
            Toaster::error('Could not create a snapshot for the submission. Please try again.');
        } catch (\Exception $e) {
            Log::error('Unexpected error submitting pitch for review', ['pitch_id' => $this->pitch->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            Toaster::error('An unexpected error occurred while submitting the pitch.');
        }
    }

    /**
     * Cancels the current pitch submission (Producer action).
     * Called directly or via confirmation dialog.
     *
     * @return void
     */
    public function cancelPitchSubmission(PitchWorkflowService $pitchWorkflowService)
    {
        $this->authorize('cancelSubmission', $this->pitch);

        try {
            $pitchWorkflowService->cancelPitchSubmission($this->pitch, Auth::user());

            Toaster::success('Pitch submission cancelled successfully.');
            $this->pitch->refresh(); // Refresh pitch data
            $this->mount($this->pitch); // Remount to refresh component state

        } catch (InvalidStatusTransitionException $e) {
            Log::warning('Invalid status transition on pitch cancellation', ['pitch_id' => $this->pitch->id, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
            Toaster::error($e->getMessage());
        } catch (UnauthorizedActionException|AuthorizationException $e) {
            Log::warning('Unauthorized pitch cancellation attempt', ['pitch_id' => $this->pitch->id, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
            Toaster::error('You are not authorized to cancel this pitch submission.');
        } catch (\Exception $e) {
            Log::error('Unexpected error cancelling pitch submission', ['pitch_id' => $this->pitch->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            Toaster::error('An unexpected error occurred while cancelling the submission.');
        }
    }

    /**
     * Update storage information for the view
     */
    protected function updateStorageInfo()
    {
        // Use user-based storage instead of pitch-based storage
        $user = $this->pitch->user;
        $userStorageService = app(\App\Services\UserStorageService::class);

        $this->storageUsedPercentage = $userStorageService->getUserStoragePercentage($user);
        $this->storageLimitMessage = $userStorageService->getStorageLimitMessage($user);
        $this->storageRemaining = $userStorageService->getUserStorageRemaining($user);
    }

    /**
     * Fetch the latest feedback message (revision or denial) from events.
     */
    protected function getLatestStatusFeedback(): ?string
    {
        Log::debug('ManagePitch: getLatestStatusFeedback called.', ['pitch_id' => $this->pitch->id]);

        // Query for the latest relevant event (revision request OR denial)
        $feedbackEvent = $this->pitch->events()
            ->where(function ($query) {
                // Look for revision requests
                $query->where('event_type', 'revision_request');
                // OR look for denial events (type = status_change, status = denied)
                $query->orWhere(function ($q) {
                    $q->where('event_type', 'status_change')
                        ->where('status', Pitch::STATUS_DENIED);
                });
            })
            ->orderBy('created_at', 'desc') // Get the most recent one
            ->first();

        if (! $feedbackEvent) {
            Log::debug('ManagePitch: No relevant feedback event found.', ['pitch_id' => $this->pitch->id]);

            return null;
        }

        Log::debug('ManagePitch: Found relevant feedback event.', [
            'pitch_id' => $this->pitch->id,
            'event_id' => $feedbackEvent->id,
            'event_type' => $feedbackEvent->event_type,
            'event_status' => $feedbackEvent->status,
            'event_comment' => $feedbackEvent->comment,
            'event_metadata' => $feedbackEvent->metadata,
        ]);

        $message = null;

        // Extract message based on event type/status
        if ($feedbackEvent->event_type === 'revision_request') {
            // Prioritize metadata for revisions
            $message = $feedbackEvent->metadata['feedback'] ?? null;
            if (empty($message)) {
                // Fallback to parsing comment
                $message = preg_replace('/^Revisions requested\.\s*(Feedback:\s*)?/i', '', $feedbackEvent->comment);
            }
        } elseif ($feedbackEvent->event_type === 'status_change' && $feedbackEvent->status === Pitch::STATUS_DENIED) {
            // Use the robust parsing logic similar to FeedbackConversation
            $commentLower = strtolower($feedbackEvent->comment);
            $prefix = 'pitch submission denied.';
            $reasonPrefix = 'reason:';
            $reasonPos = strpos($commentLower, $reasonPrefix, strlen($prefix));

            if ($reasonPos !== false) {
                $message = trim(substr($feedbackEvent->comment, $reasonPos + strlen($reasonPrefix)));
            } else {
                $potentialReason = trim(substr($feedbackEvent->comment, strlen($prefix)));
                if (! empty($potentialReason)) {
                    // Option: Show generic message if prefix missing
                    $message = '[Pitch Denied - No explicit reason provided]';
                    // Option: Show remaining text as reason
                    // $message = $potentialReason;
                } else {
                    $message = '[Pitch Denied]'; // Only the prefix was present
                }
            }
        }

        $message = trim($message ?? '');
        Log::debug('ManagePitch: Extracted feedback message.', ['pitch_id' => $this->pitch->id, 'message' => $message]);

        return ! empty($message) ? $message : null;
    }

    /**
     * Helper method to format file sizes, delegates to the model.
     */
    public function formatFileSize(int $bytes, int $precision = 2): string
    {
        return Pitch::formatBytes($bytes, $precision);
    }

    /**
     * Save the internal notes for the pitch.
     */
    public function saveInternalNotes()
    {
        // Authorize: Only the pitch creator should be able to update internal notes
        $this->authorize('update', $this->pitch);

        // Validate the notes field
        $this->validate([
            'internalNotes' => 'nullable|string|max:10000',
        ]);

        // Update the pitch model
        $this->pitch->internal_notes = $this->internalNotes;
        $this->pitch->save();

        // Show success message using Toaster directly
        Toaster::success('Internal notes saved successfully.');

        // Log success
        Log::info('Internal notes saved', [
            'pitch_id' => $this->pitch->id,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Check if the current user can upload files to this pitch.
     */
    public function getCanUploadFilesProperty(): bool
    {
        return Gate::allows('uploadFile', $this->pitch);
    }
}
