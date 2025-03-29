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

class ManagePitch extends Component
{
    use WithFileUploads;
    use WithPagination;

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

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
        
        // Initialize storage information
        $this->updateStorageInfo();
        
        // Check if file management is allowed for this pitch status
        $this->canManageFiles = $this->pitch->canManageFiles();
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
     * Validate if file management operations are allowed for this pitch
     * 
     * @return bool Whether file management is allowed
     */
    protected function validateFileOperation()
    {
        if (!$this->canManageFiles) {
            Toaster::error('File management is not available for pending pitches');
            return false;
        }
        return true;
    }

    /**
     * Queue files for upload when selected
     */
    public function queueFilesForUpload()
    {
        if (!$this->validateFileOperation()) {
            return;
        }
        // This is now handled by JavaScript that directly sets tempUploadedFiles and fileSizes
        $this->newlyAddedFileKeys = array_keys($this->tempUploadedFiles);
        $this->dispatch('new-files-added');
    }

    /**
     * Process the queued files one by one
     */
    public function processQueuedFiles()
    {
        if (!$this->validateFileOperation()) {
            return;
        }
        // Check if the pitch status allows file uploads
        if (!in_array($this->pitch->status, ['in_progress', 'pending_review'])) {
            Toaster::warning('You can only upload files when the pitch is in progress or pending review.');
            return;
        }
        
        if (empty($this->tempUploadedFiles)) {
            Toaster::warning('No files selected for upload.');
            return;
        }
        
        // Check file sizes against limits before starting uploads
        $totalSizeToUpload = 0;
        $tooLargeFiles = [];
        
        foreach ($this->tempUploadedFiles as $key => $file) {
            $fileSize = $file['size'] ?? 0;
            $totalSizeToUpload += $fileSize;
            
            // Check individual file size limit
            if (!Pitch::isFileSizeAllowed($fileSize)) {
                $tooLargeFiles[] = [
                    'name' => $file['name'],
                    'size' => Pitch::formatBytes($fileSize),
                    'limit' => Pitch::formatBytes(Pitch::MAX_FILE_SIZE_BYTES)
                ];
            }
        }
        
        // Check pitch storage capacity
        if (!$this->pitch->hasStorageCapacity($totalSizeToUpload)) {
            $this->pitch->refresh();
            $this->updateStorageInfo();
            
            Toaster::error(
                'Pitch storage limit exceeded. Available space: ' . 
                Pitch::formatBytes($this->pitch->getRemainingStorageBytes()) . 
                '. Required: ' . 
                Pitch::formatBytes($totalSizeToUpload)
            );
            return;
        }
        
        // Handle files that are too large
        if (!empty($tooLargeFiles)) {
            $message = count($tooLargeFiles) === 1 
                ? 'One file exceeds the maximum allowed size of ' . Pitch::formatBytes(Pitch::MAX_FILE_SIZE_BYTES)
                : count($tooLargeFiles) . ' files exceed the maximum allowed size of ' . Pitch::formatBytes(Pitch::MAX_FILE_SIZE_BYTES);
                
            foreach ($tooLargeFiles as $file) {
                $message .= "\n• {$file['name']} ({$file['size']})";
            }
            
            Toaster::error($message);
            return;
        }
        
        // Reset tracking variables
        $this->isProcessingQueue = true;
        $this->newlyUploadedFileIds = []; 
        $this->uploadProgress = 0;
        $this->uploadProgressMessage = 'Preparing to upload files...';
        
        $totalFiles = count($this->tempUploadedFiles);
        
        // Process the first file
        $this->processNextFile(0, $totalFiles);
    }
    
    /**
     * Process the next file in the queue
     */
    public function processNextFile($currentIndex, $totalFiles)
    {
        if ($currentIndex >= count($this->tempUploadedFiles)) {
            // All files processed
            $this->finishUploadProcess();
            return;
        }
        
        $this->uploadingFileKey = $currentIndex;
        $this->uploadProgress = round(($currentIndex / $totalFiles) * 100);
        $this->uploadProgressMessage = "Uploading file " . ($currentIndex + 1) . " of " . $totalFiles;
        
        \Log::info('Processing next file', [
            'index' => $currentIndex,
            'total' => $totalFiles,
            'pitch_id' => $this->pitch->id
        ]);
        
        // Fix the event dispatch to ensure proper data format
        $this->dispatch('uploadNextFile', index: $currentIndex, total: $totalFiles);
    }
    
    /**
     * Upload a single file (called from JavaScript)
     */
    public function uploadSingleFile($index)
    {
        if (!$this->validateFileOperation()) {
            return;
        }
        if (!isset($this->tempUploadedFiles[$index])) {
            $this->processNextFile($index + 1, count($this->tempUploadedFiles));
            return;
        }
        
        $this->uploadingFileKey = $index;
        $this->isUploading = true;
        
        try {
            $file = $this->tempUploadedFiles[$index];
            $fileName = $file['name'];
            
            // You'll need to implement file handling here
            // This might involve JavaScript to get the actual file from the input
            // and upload it via AJAX or a separate route
            
            // For now, we'll just simulate a successful upload
            $pitchFile = $this->pitch->files()->create([
                'file_path' => 'pitch_files/' . $this->pitch->id . '/' . $fileName,
                'file_name' => $fileName,
                'user_id' => Auth::id(),
                'size' => $file['size']
            ]);
            
            if ($pitchFile) {
                $this->newlyUploadedFileIds[] = $pitchFile->id;
            }
            
            // Process the next file
            $this->isUploading = false;
            $this->processNextFile($index + 1, count($this->tempUploadedFiles));
        } catch (\Exception $e) {
            Log::error('Error uploading pitch file', [
                'error' => $e->getMessage(),
                'pitch_id' => $this->pitch->id,
                'file_index' => $index
            ]);
            
            // Skip this file and continue with the next one
            $this->isUploading = false;
            $this->processNextFile($index + 1, count($this->tempUploadedFiles));
        }
    }
    
    /**
     * Finish the upload process and clean up
     */
    protected function finishUploadProcess()
    {
        $this->isProcessingQueue = false;
        $this->uploadingFileKey = null;
        $this->uploadProgress = 100;
        $this->uploadProgressMessage = 'Upload complete!';
        
        // Add a comment about the uploads
        $uploadCount = count($this->newlyUploadedFileIds);
        if ($uploadCount > 0) {
            $comment = $uploadCount . ($uploadCount > 1 ? ' files ' : ' file ') . 'have been uploaded.';
            $this->pitch->addComment($comment);
        }
        
        // Clear the queue
        $this->tempUploadedFiles = [];
        $this->fileSizes = [];
        
        // Refresh the pitch to update files
        $this->pitch->refresh();
        
        Toaster::success('Files uploaded successfully.');
        $this->dispatch('new-uploads-completed');
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
     * Legacy upload method - no longer used
     */
    public function uploadFiles()
    {
        // This method is kept for compatibility but no longer used
        // The sequential upload process via uploadSingleFile is used instead
        Toaster::info('Please use the new upload process instead.');
        return;
    }

    /**
     * Remove a file from the upload queue
     */
    public function removeUploadedFile($key)
    {
        if (!$this->validateFileOperation()) {
            return;
        }
        if (isset($this->tempUploadedFiles[$key])) {
            unset($this->tempUploadedFiles[$key]);
            unset($this->fileSizes[$key]);

            // Re-index arrays
            $this->tempUploadedFiles = array_values($this->tempUploadedFiles);
            $this->fileSizes = array_values($this->fileSizes);

            // Clear the newly added keys since indexes have changed
            $this->newlyAddedFileKeys = [];
        }
    }

    public function deleteFile($fileId)
    {
        if (!$this->validateFileOperation()) {
            return;
        }
        try {
            $file = PitchFile::findOrFail($fileId);

            // Ensure the file belongs to the pitch
            if ($file->pitch_id !== $this->pitch->id) {
                Toaster::error('You do not have permission to delete this file.');
                return;
            }

            // Capture file size before deletion
            $fileSize = $file->size;

            // Delete the file from S3 storage
            if (Storage::disk('s3')->exists($file->file_path)) {
                Storage::disk('s3')->delete($file->file_path);
                
                Log::info('File deleted from S3 via Livewire', [
                    'file_path' => $file->file_path,
                    'pitch_id' => $this->pitch->id
                ]);
            } else {
                Log::warning('File not found in S3 during deletion via Livewire', [
                    'file_path' => $file->file_path,
                    'pitch_id' => $this->pitch->id
                ]);
            }

            // Delete the file record from the database
            $file->delete();
            
            // Update the pitch's storage usage
            $this->pitch->decrement('total_storage_used', $fileSize);
            
            Toaster::success('File deleted successfully.');
            
            // Refresh the pitch to update the files list and storage info
            $this->pitch->refresh();
            $this->updateStorageInfo();
        } catch (\Exception $e) {
            Log::error('Error deleting file from S3 via Livewire', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            Toaster::error('Error deleting file: ' . $e->getMessage());
        }
    }

    /**
     * Download a file
     */
    public function downloadFile($fileId)
    {
        if (!$this->validateFileOperation()) {
            return;
        }
        try {
            $file = PitchFile::findOrFail($fileId);

            // Ensure the file belongs to the pitch
            if ($file->pitch_id !== $this->pitch->id) {
                Toaster::error('You do not have permission to download this file.');
                return;
            }

            // For S3 files, we'll redirect to the signed URL
            if (Storage::disk('s3')->exists($file->file_path)) {
                // Create a temporary signed URL that expires after a short time
                $signedUrl = Storage::disk('s3')->temporaryUrl(
                    $file->file_path,
                    now()->addMinutes(5),
                    [
                        'ResponseContentDisposition' => 'attachment; filename="' . $file->file_name . '"'
                    ]
                );
                
                Log::info('Generated signed URL for file download', [
                    'file_id' => $fileId,
                    'pitch_id' => $this->pitch->id
                ]);
                
                // Redirect to the signed URL
                return redirect()->away($signedUrl);
            }
            
            // File not found in S3
            Toaster::error('File not found in storage.');
            Log::warning('File not found in S3 during download attempt', [
                'file_id' => $fileId,
                'file_path' => $file->file_path
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error generating download URL', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            Toaster::error('Error downloading file: ' . $e->getMessage());
            return null;
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

    public function submitForReview()
    {
        $this->validate([
            'acceptedTerms' => 'accepted',
        ]);

        try {
            // Validate that the pitch can be submitted for review
            $this->pitch->canSubmitForReview();

            // Check if this is a resubmission after denial or revisions requested
            $isResubmission = in_array($this->pitch->status, [
                Pitch::STATUS_DENIED,
                Pitch::STATUS_REVISIONS_REQUESTED
            ]);

            // Create a new snapshot
            $snapshot = $this->pitch->createSnapshot();

            // Change the status to ready for review
            $filesCount = $this->pitch->files()->count();
            $this->pitch->status = Pitch::STATUS_READY_FOR_REVIEW;
            $this->pitch->current_snapshot_id = $snapshot->id;
            $this->pitch->save();

            // Create an event for this status change with appropriate message
            $eventComment = $isResubmission
                ? "Pitch resubmitted for review with {$filesCount} files in snapshot {$snapshot->snapshot_data['version']}"
                : "Pitch submitted for review with {$filesCount} files in snapshot {$snapshot->snapshot_data['version']}";

            $this->pitch->events()->create([
                'event_type' => 'status_change',
                'comment' => $eventComment,
                'status' => Pitch::STATUS_READY_FOR_REVIEW,
                'created_by' => auth()->id(),
            ]);

            // Notify the project owner
            try {
                $notificationService = new NotificationService();

                // Use the appropriate notification method
                if ($isResubmission) {
                    // For revisions resubmission
                    Log::info('Sending resubmission notification for pitch', [
                        'pitch_id' => $this->pitch->id,
                        'previous_status' => $this->pitch->status,
                        'new_status' => Pitch::STATUS_READY_FOR_REVIEW
                    ]);
                    $notificationService->notifyNewSubmission($this->pitch);
                } else {
                    // For first submission
                    $notificationService->notifyNewSubmission($this->pitch);
                }
            } catch (\Exception $e) {
                // Log notification error but don't fail the submission
                Log::error('Failed to send notification: ' . $e->getMessage());
            }

            $successMessage = $isResubmission
                ? 'Your pitch has been resubmitted for review!'
                : 'Your pitch has been submitted for review!';

            Toaster::success($successMessage);
            return redirect()->route('projects.pitches.show', ['project' => $this->pitch->project->slug, 'pitch' => $this->pitch->slug]);
        } catch (InvalidStatusTransitionException $e) {
            Toaster::error($e->getMessage());
            return;
        } catch (\Exception $e) {
            Log::error('Error submitting pitch for review', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Toaster::error('An error occurred while submitting your pitch. Please try again or contact support.');
            return;
        }
    }

    public function deleteSnapshot($snapshotId)
    {
        if ($this->pitch->deleteSnapshot($snapshotId)) {
            Toaster::success('Pitch deleted successfully');
        } else {
            Toaster::warning('Pitch deleted unsuccessfully');
        }
    }

    public function cancelPitchSubmission()
    {
        try {
            // Check if user is authorized to cancel the submission
            if (auth()->id() !== $this->pitch->user_id) {
                throw new UnauthorizedActionException(
                    'cancel submission',
                    'Only the pitch owner can cancel a submission'
                );
            }

            // Validate that the pitch can be cancelled
            $this->pitch->canCancelSubmission();

            // Find the latest pending snapshot
            $latestPendingSnapshot = $this->pitch->snapshots()
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();

            // Get the previous snapshot to update current_snapshot_id
            $previousSnapshot = null;
            if ($latestPendingSnapshot) {
                $previousSnapshot = $this->pitch->snapshots()
                    ->where('id', '!=', $latestPendingSnapshot->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Delete the pending snapshot
                $latestPendingSnapshot->delete();

                Log::info('Deleted pending snapshot during cancellation', [
                    'pitch_id' => $this->pitch->id,
                    'snapshot_id' => $latestPendingSnapshot->id
                ]);
            } else {
                Log::warning('No pending snapshot found when cancelling pitch', [
                    'pitch_id' => $this->pitch->id,
                    'user_id' => auth()->id()
                ]);
            }

            // Change the status back to in_progress
            $this->pitch->status = Pitch::STATUS_IN_PROGRESS;
            $this->pitch->current_snapshot_id = $previousSnapshot ? $previousSnapshot->id : null;
            $this->pitch->save();

            // Create an event for this status change
            $this->pitch->events()->create([
                'event_type' => 'status_change',
                'comment' => "Pitch submission cancelled and returned to in progress",
                'status' => Pitch::STATUS_IN_PROGRESS,
                'created_by' => auth()->id(),
                'snapshot_id' => $previousSnapshot ? $previousSnapshot->id : null
            ]);

            // Send notification
            try {
                $notificationService = new NotificationService();
                $notificationService->notifyPitchCancellation($this->pitch);
            } catch (\Exception $e) {
                // Log notification error but don't fail the overall operation
                Log::error('Failed to send cancellation notification: ' . $e->getMessage());
            }

            Toaster::success('Submission cancelled successfully. Your pitch has been returned to "In Progress" status.');
            return redirect()->route('projects.pitches.show', ['project' => $this->pitch->project->slug, 'pitch' => $this->pitch->slug]);
        } catch (UnauthorizedActionException $e) {
            Log::error('Unauthorized attempt to cancel pitch submission', [
                'pitch_id' => $this->pitch->id,
                'user_id' => auth()->id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->route('projects.pitches.show', ['project' => $this->pitch->project->slug, 'pitch' => $this->pitch->slug]);
        } catch (InvalidStatusTransitionException $e) {
            Toaster::error($e->getMessage());
            return redirect()->route('projects.pitches.show', ['project' => $this->pitch->project->slug, 'pitch' => $this->pitch->slug]);
        } catch (\Exception $e) {
            Log::error('Error cancelling pitch submission', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Toaster::error('An error occurred while cancelling your submission. Please try again or contact support.');
            return redirect()->route('projects.pitches.show', ['project' => $this->pitch->project->slug, 'pitch' => $this->pitch->slug]);
        }
    }

    /**
     * Handle successful file upload
     */
    public function uploadSuccess($index, $filePath, $fileId)
    {
        if (!$this->validateFileOperation()) {
            return;
        }
        if (!isset($this->tempUploadedFiles[$index])) {
            Log::error('File index not found in tempUploadedFiles', [
                'index' => $index,
                'pitch_id' => $this->pitch->id
            ]);
            return;
        }
        
        // Add to newly uploaded files
        $this->newlyUploadedFileIds[] = $fileId;
        
        // Update progress
        $totalFiles = count($this->tempUploadedFiles);
        $this->uploadProgress = round((($index + 1) / $totalFiles) * 100);
        
        // Refresh the pitch and update storage info
        $this->pitch->refresh();
        $this->updateStorageInfo();
        
        // Process the next file
        $this->processNextFile($index + 1, $totalFiles);
    }
    
    /**
     * Handle failed file upload
     */
    public function uploadFailed($index, $errorMessage)
    {
        if (!$this->validateFileOperation()) {
            return;
        }
        Log::error('File upload failed', [
            'pitch_id' => $this->pitch->id,
            'file_index' => $index,
            'error' => $errorMessage
        ]);
        
        Toaster::error("Failed to upload file: " . $errorMessage);
        
        // Process the next file, skipping this one
        $totalFiles = count($this->tempUploadedFiles);
        $this->processNextFile(($index !== null ? $index : 0) + 1, $totalFiles);
    }

    /**
     * Update storage information for the view
     */
    protected function updateStorageInfo()
    {
        $this->storageUsedPercentage = $this->pitch->getStorageUsedPercentage();
        $this->storageLimitMessage = $this->pitch->getStorageLimitMessage();
        $this->storageRemaining = Pitch::formatBytes($this->pitch->getRemainingStorageBytes());
    }
}
