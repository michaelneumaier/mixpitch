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
use App\Services\PitchService;
use App\Helpers\RouteHelpers;

class ManagePitch extends Component
{
    use WithFileUploads;
    use WithPagination;
    use AuthorizesRequests;

    public Pitch $pitch;
    public $files = [];
    public $comment;
    public $rating;

    public $acceptedTerms = false;

    public $budgetFlexibility = 'strict';
    public $licensingAgreement = 'exclusive';

    // New properties for file upload
    public $tempUploadedFiles = []; // Renamed from uploadedFiles to avoid conflict
    public $newUploadedFiles = []; // For accumulating new files
    public $fileSizes = []; // Store file sizes
    public $newlyAddedFileKeys = []; // Track which files were just added
    public $newlyUploadedFileIds = []; // Track IDs of newly uploaded files
    
    // Sequential upload properties
    public $isProcessingQueue = false;
    public $uploadingFileKey = null;
    public $uploadProgress = 0;
    public $uploadProgressMessage = '';
    public $singleFileUpload = null;
    public $isUploading = false;
    
    // Storage tracking
    public $storageUsedPercentage = 0;
    public $storageLimitMessage = '';
    public $storageRemaining = 0;
    
    // File management access flag
    public $canManageFiles = false;

    // Property to capture optional response to feedback when resubmitting
    public $responseToFeedback = '';

    public $project;
    public $currentSnapshot = null;
    public $latestSnapshot = null;
    public $snapshots = [];
    public $events = [];

    protected $listeners = [
        'refreshPitchData' => 'mount',
        'processNextFileInQueue' => 'processNextFileInQueue',
        'clearHighlights' => 'clearHighlights',
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
        $this->tempUploadedFiles = []; // Clear queue
        $this->fileSizes = [];
        $this->newlyAddedFileKeys = [];
        $this->newlyUploadedFileIds = [];
        $this->isProcessingQueue = false;
        $this->uploadingFileKey = null;
        $this->uploadProgress = 0;
        $this->uploadProgressMessage = '';
        $this->fileToDelete = null;
        $this->showDeleteModal = false;
        $this->showDeletePitchModal = false;
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
     * Called when new files are selected/dropped.
     * Livewire automatically populates $tempUploadedFiles.
     * We can add validation here if needed.
     */
    public function updatedTempUploadedFiles()
    {
        // Optional: Validate individual file types/sizes immediately
        // $this->validate('tempUploadedFiles.*'); 
        $this->newlyAddedFileKeys = array_keys($this->tempUploadedFiles);
        $this->dispatch('new-files-added'); // Notify frontend if needed
    }

    /**
     * Upload all files currently in the temporary queue.
     */
    public function uploadQueuedFiles(FileManagementService $fileManagementService)
    {
        if (empty($this->tempUploadedFiles)) {
            Toaster::warning('No files selected for upload.');
            return;
        }

        // Authorize the action for the pitch
        try {
            $this->authorize('uploadFile', $this->pitch); // Assuming PitchPolicy exists
        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to upload files to this pitch.');
            return;
        }

        $this->newlyUploadedFileIds = []; // Reset successful uploads for this batch
        $totalFiles = count($this->tempUploadedFiles);
        $filesProcessed = 0;
        $uploadErrors = 0;

        foreach ($this->tempUploadedFiles as $key => $file) {
            $fileName = $file->getClientOriginalName();
            try {
                // Upload using the service
                $pitchFile = $fileManagementService->uploadPitchFile($this->pitch, $file, Auth::user());

                // Success for this file
                $this->newlyUploadedFileIds[] = $pitchFile->id;
                Log::info('Pitch file uploaded successfully via Livewire', ['pitch_id' => $this->pitch->id, 'file_id' => $pitchFile->id, 'filename' => $fileName]);
                 // Optionally provide per-file success toast
                 // Toaster::success("Uploaded {$fileName}"); 

            } catch (FileUploadException | StorageLimitException $e) {
                // Handle specific upload errors (size, storage limit, status)
                Log::warning('Pitch file upload failed (validation) via Livewire', ['pitch_id' => $this->pitch->id, 'filename' => $fileName, 'error' => $e->getMessage()]);
                Toaster::error("Upload failed for {$fileName}: " . $e->getMessage());
                $uploadErrors++;
            } catch (AuthorizationException $e) {
                 // Should have been caught above, but as safety
                Log::error('Unauthorized file upload attempt caught mid-queue', ['pitch_id' => $this->pitch->id, 'filename' => $fileName]);
                Toaster::error('Authorization failed during upload.');
                $uploadErrors++;
                break; // Stop processing if authorization fails mid-way
            } catch (\Exception $e) {
                // Handle generic upload errors
                Log::error('Error uploading pitch file via Livewire', ['pitch_id' => $this->pitch->id, 'filename' => $fileName, 'error' => $e->getMessage()]);
                Toaster::error("An unexpected error occurred uploading {$fileName}.");
                $uploadErrors++;
            }
            $filesProcessed++;
        }

        $this->finishUploadProcess($uploadErrors === 0, $totalFiles, $uploadErrors);
    }
    
    /**
     * Finalize the upload process after the loop completes.
     */
    protected function finishUploadProcess(bool $allSucceeded, int $totalFiles, int $errorCount)
    {
        $this->tempUploadedFiles = []; // Clear the temporary queue
        $this->fileSizes = [];
        $this->newlyAddedFileKeys = [];

        // Refresh pitch data and storage info
        $this->updateStorageInfo(); 
        $this->dispatch('upload-complete'); // Notify other parts of the UI (e.g., file list)

        // Provide summary feedback
        if ($totalFiles > 0) {
             if ($allSucceeded) {
                 Toaster::success("Successfully uploaded {$totalFiles} file(s).");
             } elseif ($errorCount < $totalFiles) {
                 Toaster::warning("Completed upload: " . ($totalFiles - $errorCount) . " succeeded, {$errorCount} failed.");
             } else {
                 Toaster::error("All {$totalFiles} file uploads failed.");
             }
        }
    }

    /**
     * Clear the highlight for newly added files
     */
    public function clearHighlights()
    {
        $this->newlyAddedFileKeys = [];
    }

    /**
     * Clear the highlight for newly uploaded files
     */
    public function clearUploadHighlights()
    {
        $this->newlyUploadedFileIds = [];
    }

    /**
     * Format file size in human-readable format
     */
    protected function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Remove a file from the upload queue
     */
    public function removeUploadedFile($key)
    {
        if (isset($this->tempUploadedFiles[$key])) {
            // Remove file from Livewire's temp array
            unset($this->tempUploadedFiles[$key]);
            // Remove associated size if tracked
            if(isset($this->fileSizes[$key])) {
                 unset($this->fileSizes[$key]);
            }
            // Re-index array keys if needed for display consistency
            $this->tempUploadedFiles = array_values($this->tempUploadedFiles);
             $this->fileSizes = array_values($this->fileSizes); // Keep sizes in sync if used
        } else {
            Log::warning('Attempted to remove non-existent key from upload queue', ['key' => $key, 'pitch_id' => $this->pitch->id]);
        }
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

    public function deleteSelectedFile(FileManagementService $fileManagementService)
    {
        if (!$this->fileToDelete) return;

        try {
            $this->authorize('deleteFile', $this->pitch);
            
            $fileManagementService->deletePitchFile($this->fileToDelete, Auth::user());
            Toaster::success('File deleted successfully.');
            $this->fileToDelete = null;
            $this->showDeleteModal = false;
            $this->updateStorageInfo();
            $this->dispatch('refreshPitchData'); // Refresh the file list

        } catch (AuthorizationException $e) {
            Log::error('Authorization failed for pitch file deletion', ['pitch_id' => $this->pitch->id, 'user_id' => auth()->id(), 'file_id' => $this->fileToDelete->id]);
            Toaster::error('You are not authorized to delete this file.');
            $this->showDeleteModal = false;
        } catch (FileDeletionException | \Exception $e) {
            Log::error('Error deleting pitch file via Livewire', [
                'pitch_id' => $this->pitch->id, 'user_id' => auth()->id(), 'file_id' => $this->fileToDelete->id, 'error' => $e->getMessage()
            ]);
            Toaster::error('Error deleting file: ' . $e->getMessage());
            $this->showDeleteModal = false;
        }
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
     * Start processing the queued files sequentially.
     */
    public function processQueuedFiles(FileManagementService $fileManagementService)
    {
        if (empty($this->tempUploadedFiles) || $this->isProcessingQueue) {
            return;
        }

        $this->isProcessingQueue = true;
        $this->uploadingFileKey = 0; // Start with the first file
        $this->newlyUploadedFileIds = []; // Reset for this batch
        $this->dispatch('processNextFileInQueue');
    }

    /**
     * Process the next file in the upload queue.
     * This is triggered by dispatchSelf after each successful upload.
     */
    public function processNextFileInQueue(FileManagementService $fileManagementService)
    {
        if (!$this->isProcessingQueue || $this->uploadingFileKey === null) {
            return; // Should not happen if called correctly
        }

        $queueKeys = array_keys($this->tempUploadedFiles);
        $currentKeyIndex = $this->uploadingFileKey;

        if (!isset($queueKeys[$currentKeyIndex])) {
            // No more files in the queue
            $this->finishUploadProcess(true);
            return;
        }

        $currentKey = $queueKeys[$currentKeyIndex];
        $file = $this->tempUploadedFiles[$currentKey];
        $fileName = $file->getClientOriginalName();
        $totalFiles = count($this->tempUploadedFiles);

        $this->uploadProgress = round((($currentKeyIndex + 1) / $totalFiles) * 100);
        $this->uploadProgressMessage = "Uploading {$fileName} (" . ($currentKeyIndex + 1) . " of " . $totalFiles . ")...";

        try {
            // Authorize the upload action
            $this->authorize('uploadFile', $this->pitch);
            
            // Perform the actual upload using the service
            $pitchFile = $fileManagementService->uploadPitchFile($this->pitch, $file, auth()->user());

            // Success for this file
            $this->newlyUploadedFileIds[] = $pitchFile->id;
            Log::info('Pitch file uploaded successfully via Livewire', ['pitch_id' => $this->pitch->id, 'file_id' => $pitchFile->id, 'filename' => $fileName]);

            // Move to the next file
            $this->uploadingFileKey = $currentKeyIndex + 1;
            $this->dispatch('processNextFileInQueue'); // Trigger next step

        } catch (AuthorizationException $e) {
            Log::error('Authorization failed for pitch file upload', ['pitch_id' => $this->pitch->id, 'user_id' => auth()->id(), 'filename' => $fileName]);
            Toaster::error('Error uploading ' . $fileName . ': You are not authorized.');
            $this->finishUploadProcess(false, 'Authorization failed.');
        } catch (StorageLimitException $e) {
            Log::warning('Storage limit exceeded during pitch file upload', ['pitch_id' => $this->pitch->id, 'user_id' => auth()->id(), 'filename' => $fileName]);
            Toaster::error('Error uploading ' . $fileName . ': ' . $e->getMessage());
            $this->finishUploadProcess(false, 'Storage limit exceeded.');
        } catch (FileUploadException | \Exception $e) {
            Log::error('Error uploading pitch file via Livewire', [
                'pitch_id' => $this->pitch->id, 'user_id' => auth()->id(), 'filename' => $fileName, 'error' => $e->getMessage()
            ]);
            Toaster::error('Error uploading ' . $fileName . ': ' . $e->getMessage());
            // Decide whether to stop queue or skip file
            // For now, stop the queue on any error
            $this->finishUploadProcess(false, 'Upload failed for ' . $fileName);
        }
    }

    public function confirmDeleteFile(PitchFile $file)
    {
        $this->fileToDelete = $file;
        $this->showDeleteModal = true;
    }

    /**
     * Show modal to confirm pitch deletion.
     */
    public function confirmDeletePitch()
    {
        $this->showDeletePitchModal = true;
    }

    /**
     * Delete the entire pitch.
     */
    public function deletePitch(PitchService $pitchService)
    {
        try {
            $this->authorize('delete', $this->pitch);
            $project = $this->pitch->project; // Get project before deleting pitch
            $pitchService->deletePitch($this->pitch, auth()->user());

            Toaster::success('Pitch deleted successfully.');
            // Redirect to the project page
            return redirect()->route('projects.show', $project);

        } catch (AuthorizationException $e) {
            Log::warning('Unauthorized attempt to delete pitch via Livewire', ['pitch_id' => $this->pitch->id, 'user_id' => auth()->id()]);
            Toaster::error($e->getMessage());
            $this->showDeletePitchModal = false;
        } catch (\Exception $e) {
            Log::error('Error deleting pitch via Livewire', [
                'pitch_id' => $this->pitch->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            Toaster::error('Failed to delete pitch. Please try again.');
            $this->showDeletePitchModal = false;
        }
    }
}
