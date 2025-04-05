<?php
// app/Livewire/Pitch/Component/ManagePitch.php

namespace App\Livewire\Pitch\Component;

use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\PitchSnapshot;
use App\Services\NotificationService;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\SnapshotException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;
use App\Exceptions\UnauthorizedActionException;
use App\Jobs\GenerateAudioWaveform;
use App\Services\PitchWorkflowService;
use App\Services\FileManagementService;
use App\Exceptions\File\FileUploadException;
use App\Exceptions\File\StorageLimitException;
use App\Exceptions\File\FileDeletionException;
use Illuminate\Database\Eloquent\AuthorizationException;
use App\Exceptions\SubmissionValidationException;
use App\Models\Project;
use App\Helpers\RouteHelpers;
use Illuminate\Support\Str;

class ManagePitch extends Component
{
    use WithFileUploads;
    use WithPagination;
    use AuthorizesRequests;

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
    
    public $fileToDelete = null; // Added property to hold the file object for deletion
    public $showDeleteModal = false; // Added property to control delete modal visibility
    public $showDeletePitchModal = false; // Property for deleting the entire pitch

    protected $listeners = [
        'refreshPitchData' => 'mount',
        'filesUploaded' => 'refreshPitchData',
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
        $this->fileToDelete = null;
        $this->showDeleteModal = false;
        $this->showDeletePitchModal = false;
        
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
        if (!$pitchFile || $pitchFile->pitch_id !== $this->pitch->id) {
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
     * Set the file to be deleted and show the confirmation modal.
     * Called by AlpineJS when delete icon is clicked.
     */
    public function setFileToDelete($fileId)
    {
        // Fetch the file model to ensure it exists and belongs to the pitch
        $file = PitchFile::where('id', $fileId)->where('pitch_id', $this->pitch->id)->first();
        if ($file) {
            $this->fileToDelete = $file; // Store the file model
            // We don't need $this->showDeleteModal = true; as Alpine handles the modal visibility
        } else {
             Toaster::error('File not found or invalid.');
             $this->fileToDelete = null;
        }
    }

    /**
     * Delete the selected file.
     * Called by AlpineJS when the modal confirmation button is clicked.
     */
    public function deleteSelectedFile(FileManagementService $fileManagementService)
    {
        if (!$this->fileToDelete) {
            Toaster::error('No file selected for deletion.');
            return;
        }

        // Authorize the deletion
        try {
            $this->authorize('deleteFile', $this->fileToDelete); // Use PitchFilePolicy
        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to delete this file.');
            $this->fileToDelete = null; // Clear selection
            return;
        }
        
        try {
            $fileName = $this->fileToDelete->file_name; // Get name before deleting
            $deleted = $fileManagementService->deletePitchFile($this->fileToDelete, Auth::user());

            if ($deleted) {
                Toaster::success("File '{$fileName}' deleted successfully.");
                $this->updateStorageInfo();
                $this->dispatch('file-deleted');
                $this->pitch->refresh(); // Refresh the pitch relationship
            } else {
                // This case might not be reachable if service throws exceptions
                Toaster::error("Failed to delete file '{$fileName}'.");
            }

        } catch (FileDeletionException $e) {
            Log::warning('Pitch file deletion failed via Livewire', ['file_id' => $this->fileToDelete->id ?? null, 'error' => $e->getMessage()]);
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error deleting pitch file via Livewire', ['file_id' => $this->fileToDelete->id ?? null, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while deleting the file.');
        }
        
        $this->fileToDelete = null; // Clear the selection after attempt
    }

    /**
     * Initiate file download by generating a temporary URL.
     */
    public function downloadFile($fileId, FileManagementService $fileManagementService)
    {
        // Find the file model first
        $pitchFile = PitchFile::find($fileId);
        if (!$pitchFile || $pitchFile->pitch_id !== $this->pitch->id) {
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
            Toaster::error('Could not generate download link: ' . $e->getMessage());
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

        if ($note == "") {
            $this->pitch->addComment("Note removed from file.");
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
        try {
            // Authorization check using PitchPolicy
            $this->authorize('submitForReview', $this->pitch);

            // Call the service method
            $pitch = $pitchWorkflowService->submitPitchForReview(
                $this->pitch,
                auth()->user(),
                $this->responseToFeedback ?: null // Pass null if empty string
            );

            // Success feedback
            Toaster::success('Pitch submitted successfully for review!');

            // Dispatch event to notify other components if needed
            $this->dispatch('pitchStatusUpdated');

            // Redirect to the pitch show page
            return redirect()->route('projects.pitches.show', [
                'project' => $pitch->project->slug, // Use the returned pitch object
                'pitch' => $pitch->slug
            ]);

        } catch (AuthorizationException | UnauthorizedActionException $e) {
            Log::warning('Unauthorized pitch submission attempt', ['pitch_id' => $this->pitch->id, 'user_id' => auth()->id()]);
            Toaster::error('You are not authorized to submit this pitch for review.');
        } catch (SubmissionValidationException | InvalidStatusTransitionException $e) {
            // Handle specific validation/logic errors from the service
            Log::warning('Pitch submission failed validation', ['pitch_id' => $this->pitch->id, 'error' => $e->getMessage()]);
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            // Handle unexpected errors
            Log::error('Error submitting pitch for review via Livewire', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Optional: include trace for detailed debugging
            ]);
            Toaster::error('An unexpected error occurred while submitting your pitch. Please try again.');
        }
    }

    /**
     * Cancels the current pitch submission (Producer action).
     * Called directly or via confirmation dialog.
     *
     * @param PitchWorkflowService $pitchWorkflowService
     * @return void
     */
    public function cancelPitchSubmission(PitchWorkflowService $pitchWorkflowService)
    {
        try {
            // Authorize using Policy (ensure 'cancelSubmission' ability exists)
            $this->authorize('cancelSubmission', $this->pitch);

            $pitchWorkflowService->cancelPitchSubmission(
                $this->pitch,
                auth()->user()
            );

            Toaster::success('Pitch submission cancelled successfully.');
            $this->dispatch('pitchStatusUpdated');
            $this->dispatch('snapshot-status-updated'); // Ensure snapshot list updates if shown
            $this->pitch->refresh(); // Refresh pitch data
            $this->updateStorageInfo(); // Update storage info
            $this->canManageFiles = $this->pitch->canManageFiles(); // Update file management capability

            // Potentially reset upload state if needed
            // $this->resetUploadState(); 

        } catch (UnauthorizedActionException $e) {
            Toaster::error('You are not authorized to cancel this submission.');
        } catch (InvalidStatusTransitionException | SnapshotException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error cancelling submission via Livewire ManagePitch', ['pitch_id' => $this->pitch->id, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while cancelling the submission.');
        }
    }

    /**
     * Update storage information for the view
     */
    protected function updateStorageInfo()
    {
        $this->pitch->refresh(); // Ensure we have the latest storage usage
        $this->storageUsedPercentage = $this->pitch->getStorageUsedPercentage();
        $this->storageLimitMessage = $this->pitch->getStorageLimitMessage();
        $this->storageRemaining = $this->pitch->getRemainingStorageBytes();
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
                $query->orWhere(function($q) {
                    $q->where('event_type', 'status_change')
                      ->where('status', Pitch::STATUS_DENIED);
                });
            })
            ->orderBy('created_at', 'desc') // Get the most recent one
            ->first();

        if (!$feedbackEvent) {
            Log::debug('ManagePitch: No relevant feedback event found.', ['pitch_id' => $this->pitch->id]);
            return null;
        }

        Log::debug('ManagePitch: Found relevant feedback event.', [
            'pitch_id' => $this->pitch->id,
            'event_id' => $feedbackEvent->id,
            'event_type' => $feedbackEvent->event_type,
            'event_status' => $feedbackEvent->status,
            'event_comment' => $feedbackEvent->comment,
            'event_metadata' => $feedbackEvent->metadata
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
                 if (!empty($potentialReason)) {
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

        return !empty($message) ? $message : null; 
    }
}
