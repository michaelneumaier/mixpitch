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

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
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
     * Called when new files are selected
     * This method accumulates files instead of replacing them
     */
    public function updatedNewUploadedFiles()
    {
        $this->newlyAddedFileKeys = []; // Reset the tracking array

        $startIndex = count($this->tempUploadedFiles);

        foreach ($this->newUploadedFiles as $file) {
            $this->tempUploadedFiles[] = $file;
            // Calculate and store file size
            $this->fileSizes[] = $this->formatFileSize($file->getSize());
            // Track the index of this newly added file
            $this->newlyAddedFileKeys[] = $startIndex;
            $startIndex++;
        }

        // Reset the new files input to allow for more files to be added
        $this->newUploadedFiles = [];

        // We'll use JavaScript setTimeout in the blade file instead
        $this->dispatch('new-files-added');
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
     * Upload multiple files
     */
    public function uploadFiles()
    {
        // Check if the pitch status allows file uploads
        if (!in_array($this->pitch->status, ['in_progress', 'pending_review'])) {
            Toaster::warning('You can only upload files when the pitch is in progress or pending review.');
            return;
        }

        $this->validate([
            'tempUploadedFiles.*' => 'required|file|max:102400', // 100MB max
        ]);

        $this->newlyUploadedFileIds = []; // Reset the tracking array

        foreach ($this->tempUploadedFiles as $file) {
            $filePath = $file->store('pitch_files', 'public');

            $pitchFile = $this->pitch->files()->create([
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'user_id' => Auth::id(),
                'size' => $file->getSize(), // Store the file size
            ]);

            if ($pitchFile) {
                $this->newlyUploadedFileIds[] = $pitchFile->id;

                // Notify project owner about new file upload
                // Only if the uploader is not the project owner
                if (Auth::id() !== $this->pitch->project->user_id) {
                    app(\App\Services\NotificationService::class)->notifyFileUploadedToProject($this->pitch, $pitchFile);
                }
            }
        }

        $this->tempUploadedFiles = []; // Clear the files after upload
        $this->fileSizes = []; // Clear file sizes
        $this->pitch->refresh(); // Refresh pitch files relation

        $comment = count($this->newlyUploadedFileIds) . (count($this->newlyUploadedFileIds) > 1 ? ' files ' : ' file ') . 'have been uploaded.';
        $this->pitch->addComment($comment);

        Toaster::success('Files uploaded successfully.');
        $this->dispatch('new-uploads-completed');
    }

    /**
     * Remove a file from the upload queue
     */
    public function removeUploadedFile($key)
    {
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
        $file = PitchFile::findOrFail($fileId);

        // Ensure the file belongs to the pitch
        if ($file->pitch_id !== $this->pitch->id) {
            return;
        }

        // Delete the file from storage
        Storage::disk('public')->delete($file->file_path);

        // Delete the file record from the database
        $file->delete();
        Toaster::success('File deleted successfully.');
    }

    /**
     * Download a file
     */
    public function downloadFile($fileId)
    {
        $file = PitchFile::findOrFail($fileId);

        // Ensure the file belongs to the pitch
        if ($file->pitch_id !== $this->pitch->id) {
            Toaster::error('You do not have permission to download this file.');
            return;
        }

        // Check if the file exists in storage
        if (!Storage::disk('public')->exists($file->file_path)) {
            Toaster::error('File not found in storage.');
            return;
        }

        // Generate a download response
        return response()->download(
            storage_path('app/public/' . $file->file_path),
            $file->file_name ?? basename($file->file_path)
        );
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
            return redirect()->route('pitches.show', $this->pitch);
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
            return redirect()->route('pitches.show', $this->pitch);
        } catch (UnauthorizedActionException $e) {
            Log::error('Unauthorized attempt to cancel pitch submission', [
                'pitch_id' => $this->pitch->id,
                'user_id' => auth()->id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->route('pitches.show', $this->pitch);
        } catch (InvalidStatusTransitionException $e) {
            Toaster::error($e->getMessage());
            return redirect()->route('pitches.show', $this->pitch);
        } catch (\Exception $e) {
            Log::error('Error cancelling pitch submission', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Toaster::error('An error occurred while cancelling your submission. Please try again or contact support.');
            return redirect()->route('pitches.show', $this->pitch);
        }
    }
}
