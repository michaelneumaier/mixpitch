<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;
use App\Models\ProjectFile;
// use App\Http\Controllers\ProjectController; // Remove direct controller usage
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Masmerise\Toaster\Toaster; // Ensure the Facade is imported if needed
use Illuminate\Support\Facades\Auth; // Add Auth facade

// Added for refactoring
use App\Services\Project\ProjectManagementService;
use App\Services\FileManagementService; // <-- Import FileManagementService
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use App\Livewire\Forms\ProjectForm;
use App\Exceptions\File\FileUploadException;
use App\Exceptions\File\StorageLimitException;
use App\Exceptions\File\FileDeletionException;

class ManageProject extends Component
{
    use WithFileUploads;
    public Project $project;
    public ProjectForm $form;

    public $hasPreviewTrack = false;
    public $audioUrl;
    public $isUploading = false;
    public $tempUploadedFiles = []; // Array of Livewire\TemporaryUploadedFile objects for the queue
    public $fileSizes = []; // Store display file sizes for the queue
    public $newlyAddedFileKeys = []; // Track which files were just added to the queue visually
    public $newlyUploadedFileIds = []; // Track DB IDs of newly persisted files for UI feedback

    // Sequential upload properties
    public $isProcessingQueue = false;
    public $uploadingFileKey = null;
    public $uploadProgress = 0;
    public $uploadProgressMessage = '';

    // Storage tracking
    public $storageUsedPercentage = 0;
    public $storageLimitMessage = '';
    public $storageRemaining = 0;

    public bool $showDeleteModal = false;

    public function mount(Project $project)
    {
        try {
            $this->authorize('update', $project);
        } catch (AuthorizationException $e) {
            abort(403, 'You are not authorized to manage this project.');
        }

        $this->project = $project;

        // Initialize the form object
        $this->form = new ProjectForm($this, 'form');
        // Use the fill method to populate the form from the model
        $this->form->fill($this->project);

        // Ensure the form's deadline is a 'Y-m-d' string
        if ($this->project->deadline && $this->project->deadline instanceof \Carbon\Carbon) {
            // If the model has a Carbon instance, format it for the form
            $this->form->deadline = $this->project->deadline->format('Y-m-d');
        } elseif (is_string($this->project->deadline)) {
            // If the model has a string, assume it's correctly formatted and ensure the form has it
            // fill() might have already handled this, but this makes it explicit
            $this->form->deadline = $this->project->deadline;
        } else {
            $this->form->deadline = null; // Default to null if model deadline isn't set or Carbon
        }

        // Handle mapping collaboration types (assuming ProjectForm has boolean properties)
        $this->mapCollaborationTypesToForm($this->project->collaboration_type);

        // Handle budget type (assuming ProjectForm has budgetType property)
        $this->form->budgetType = $this->project->budget > 0 ? 'paid' : 'free';

        if ($this->project->hasPreviewTrack()) {
            $this->audioUrl = $this->project->previewTrackPath();
            $this->hasPreviewTrack = true;
        }
        $this->updateStorageInfo();
    }

    /**
     * Helper to map project collaboration types to form boolean properties.
     * This might need adjustment based on ProjectForm properties.
     */
    private function mapCollaborationTypesToForm(?array $types): void
    {
        if (empty($types)) return;
        // Assuming ProjectForm has these boolean properties
        $this->form->collaborationTypeMixing = in_array('Mixing', $types);
        $this->form->collaborationTypeMastering = in_array('Mastering', $types);
        $this->form->collaborationTypeProduction = in_array('Production', $types);
        $this->form->collaborationTypeSongwriting = in_array('Songwriting', $types);
        $this->form->collaborationTypeVocalTuning = in_array('Vocal Tuning', $types);
    }

    /**
     * Update displayed storage information.
     */
    protected function updateStorageInfo()
    {
        $this->project->refresh(); // Ensure we have the latest storage usage
        $this->storageUsedPercentage = $this->project->getStorageUsedPercentage();
        $this->storageLimitMessage = $this->project->getStorageLimitMessage();
        $this->storageRemaining = $this->project->getRemainingStorageBytes();
    }

    // TODO: Refactor in Step 5 (File Management)
    /**
     * Called when new files are selected via the file input.
     * Accumulates files into the temporary queue.
     */
    public function updatedTempUploadedFiles()
    {
        $this->newlyAddedFileKeys = []; // Reset visual tracking
        // Validate incoming files before adding to queue?
        // Note: Validation should ideally happen *before* upload attempt in processQueuedFiles

        // The $this->tempUploadedFiles property now holds the Livewire\TemporaryUploadedFile objects
        // We just need to update UI cues if necessary
        $this->dispatch('new-files-added');
    }

    // TODO: Refactor in Step 5 (File Management)
    /**
     * Clear the highlight for newly added files in the queue.
     */
    public function clearHighlights()
    {
        $this->newlyAddedFileKeys = [];
    }

    // TODO: Refactor in Step 5 (File Management) - Use Str:: helper if available or keep
    /**
     * Format file size in human-readable format.
     */
    protected function formatFileSize($bytes)
    {
        return \Illuminate\Support\Str::formatBytes($bytes);
    }

    /**
     * Publish the project.
     */
    public function publish(): void
    {
        Log::debug('[ManageProject] Publish: Start - Project ID: ' . $this->project->id . ', Status: ' . $this->project->status . ', Auth User: ' . (auth()->check() ? auth()->id() : 'None'));

        $this->authorize('publish', $this->project);
        Log::debug('[ManageProject] Publish: Authorized');

        // Use the model's publish method instead of directly setting properties
        $this->project->publish();
        Log::debug('[ManageProject] Publish: After save - Status: ' . $this->project->status . ', is_published: ' . ($this->project->is_published ? 'true' : 'false'));

        // Refresh the project to ensure we have the latest data
        $this->project->refresh();
        Log::debug('[ManageProject] Publish: After refresh - Status: ' . $this->project->status . ', is_published: ' . ($this->project->is_published ? 'true' : 'false'));

        Toaster::success('Project published successfully.');
        $this->dispatch('project-updated');

        Log::debug('[ManageProject] Publish: End');
    }

    /**
     * Unpublish the project.
     */
    public function unpublish(): void
    {
        Log::debug('[ManageProject] Unpublish: Start - Project ID: ' . $this->project->id . ', Status: ' . $this->project->status . ', Auth User: ' . (auth()->check() ? auth()->id() : 'None'));

        $this->authorize('unpublish', $this->project);
        Log::debug('[ManageProject] Unpublish: Authorized');

        // Use the model's unpublish method instead of directly setting properties
        $this->project->unpublish();
        Log::debug('[ManageProject] Unpublish: After save - Status: ' . $this->project->status . ', is_published: ' . ($this->project->is_published ? 'true' : 'false'));

        // Refresh the project to ensure we have the latest data
        $this->project->refresh();
        Log::debug('[ManageProject] Unpublish: After refresh - Status: ' . $this->project->status . ', is_published: ' . ($this->project->is_published ? 'true' : 'false'));

        Toaster::success('Project unpublished successfully.');
        $this->dispatch('project-updated');

        Log::debug('[ManageProject] Unpublish: End');
    }

    /**
     * Toggle the preview track for the project.
     */
    public function togglePreviewTrack(ProjectFile $file, FileManagementService $fileManagementService)
    {
        try {
            // Authorization check: Can the current user update this project?
            // We'll also assume a specific policy for setting the preview track exists.
            $this->authorize('update', $this->project);
            // $this->authorize('setPreviewTrack', $this->project); // More specific policy if desired

            // Check if the clicked file is the current preview track
            if ($this->hasPreviewTrack && $this->project->preview_track == $file->id) {
                // If it is, clear the preview track
                $fileManagementService->clearProjectPreviewTrack($this->project);
                $this->hasPreviewTrack = false;
                $this->audioUrl = null;
                $this->dispatch('audioUrlUpdated', null);
                Toaster::success('Preview track cleared successfully.');
            } else {
                // If it's not, set the new preview track
                $fileManagementService->setProjectPreviewTrack($this->project, $file);
                $this->audioUrl = $file->previewTrackPath(); // Assume this helper still exists or adjust
                $this->hasPreviewTrack = true;
                $this->dispatch('audioUrlUpdated', $this->audioUrl);
                Toaster::success('Preview track updated successfully.');
            }
             // Refresh the project model to reflect the change in preview_track ID
            $this->project->refresh();

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to change the preview track.');
        } catch (\Exception $e) {
            Log::error('Error toggling project preview track via Livewire', ['project_id' => $this->project->id, 'file_id' => $file->id, 'error' => $e->getMessage()]);
            Toaster::error('Could not update preview track: ' . $e->getMessage());
        }
    }

    /**
     * Clear the current preview track.
     */
    public function clearPreviewTrack(FileManagementService $fileManagementService)
    {
        try {
             // Authorization check
            $this->authorize('update', $this->project);
            // $this->authorize('clearPreviewTrack', $this->project); // More specific policy if desired

            $fileManagementService->clearProjectPreviewTrack($this->project);
            $this->hasPreviewTrack = false;
            $this->audioUrl = null;
            $this->dispatch('audioUrlUpdated', null);
            Toaster::success('Preview track cleared successfully.');

             // Refresh the project model
            $this->project->refresh();

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to clear the preview track.');
        } catch (\Exception $e) {
             Log::error('Error clearing project preview track via Livewire', ['project_id' => $this->project->id, 'error' => $e->getMessage()]);
            Toaster::error('Could not clear preview track: ' . $e->getMessage());
        }
    }


    // --- File Upload Queue Logic (Marked for Refactor in Step 5) --- START --- //

    /**
     * Starts the processing of the file upload queue.
     */
    public function queueFilesForUpload(FileManagementService $fileManagementService)
    {
        if (empty($this->tempUploadedFiles)) {
            Toaster::warning('No files selected for upload.');
            return;
        }

        // Authorization check: Ensure user can upload to this project
        // Note: Policy checks *could* happen here, but also within the loop for each file might be safer
        // depending on granular permissions. For now, assume upload permission applies to the project.
        try {
            $this->authorize('uploadFile', $this->project);
        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to upload files to this project.');
            return;
        }

        $this->isProcessingQueue = true;
        $this->uploadingFileKey = 0; // Start with the first file index
        $this->newlyUploadedFileIds = []; // Reset list of successful uploads for this batch
        $this->processNextFileInQueue($fileManagementService); // Pass the service instance
    }

    /**
     * Processes the next file in the upload queue recursively or iteratively.
     */
    protected function processNextFileInQueue(FileManagementService $fileManagementService)
    {
        $queueKeys = array_keys($this->tempUploadedFiles);
        $currentKeyIndex = $this->uploadingFileKey ?? 0;

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
            // Perform the actual upload using the service
            $projectFile = $fileManagementService->uploadProjectFile($this->project, $file, Auth::user());

            // Success for this file
            $this->newlyUploadedFileIds[] = $projectFile->id;
            Log::info('Project file uploaded successfully via Livewire', ['project_id' => $this->project->id, 'file_id' => $projectFile->id, 'filename' => $fileName]);

            // Move to the next file
            $this->uploadingFileKey = $currentKeyIndex + 1;
            // Use Livewire's dispatchSelf for potential UI updates/recursion without full re-render
            $this->dispatchSelf('processNextFileInQueue'); // Trigger next step

        } catch (FileUploadException | StorageLimitException $e) {
            // Handle specific upload errors (size, storage limit, status)
            Log::warning('Project file upload failed (validation) via Livewire', ['project_id' => $this->project->id, 'filename' => $fileName, 'error' => $e->getMessage()]);
            Toaster::error("Upload failed for {$fileName}: " . $e->getMessage());
            // Remove the failed file from the queue and continue
            unset($this->tempUploadedFiles[$currentKey]);
            $this->uploadingFileKey = $currentKeyIndex; // Stay on the same *index* for the *next* iteration (which will now point to the next item)
            $this->dispatchSelf('processNextFileInQueue'); // Continue with the next file

        } catch (AuthorizationException $e) {
            // Should ideally be caught before the loop, but handle just in case
            Log::error('Unauthorized file upload attempt caught mid-queue', ['project_id' => $this->project->id, 'filename' => $fileName]);
            Toaster::error('Authorization failed during upload queue.');
            $this->finishUploadProcess(false); // Stop processing

        } catch (\Exception $e) {
            // Handle generic upload errors
            Log::error('Error uploading project file via Livewire', ['project_id' => $this->project->id, 'filename' => $fileName, 'error' => $e->getMessage()]);
            Toaster::error("An unexpected error occurred uploading {$fileName}.");
            // Remove the failed file from the queue and continue
            unset($this->tempUploadedFiles[$currentKey]);
            $this->uploadingFileKey = $currentKeyIndex;
            $this->dispatchSelf('processNextFileInQueue'); // Continue with the next file
        }
    }

    /**
     * Finalize the upload process, update UI.
     */
    protected function finishUploadProcess($success = true)
    {
        $this->isProcessingQueue = false;
        $this->uploadingFileKey = null;
        $this->uploadProgress = $success ? 100 : $this->uploadProgress; // Show 100% on success
        $this->uploadProgressMessage = $success ? 'Upload complete.' : 'Upload finished with errors.';
        $this->tempUploadedFiles = []; // Clear the temporary queue
        $this->fileSizes = [];
        $this->newlyAddedFileKeys = [];

        // Refresh project data and storage info
        $this->updateStorageInfo();
        $this->dispatch('upload-complete'); // Notify other parts of the UI
    }

    /**
     * Remove a file from the temporary upload queue before processing.
     */
    public function removeUploadedFile($key)
    {
        if (isset($this->tempUploadedFiles[$key])) {
            // Remove file and its size entry
            unset($this->tempUploadedFiles[$key]);
            unset($this->fileSizes[$key]);
            // Re-index array keys if needed for subsequent processing
            $this->tempUploadedFiles = array_values($this->tempUploadedFiles);
            $this->fileSizes = array_values($this->fileSizes);
        }
    }

    // --- File Upload Queue Logic --- END --- //

    /**
     * Delete a persisted Project File.
     */
    public function deleteFile($fileId)
    {
        try {
            $projectFile = ProjectFile::findOrFail($fileId);
            
            // Authorization: Use Policy
            $this->authorize('deleteFile', $projectFile); // Assuming ProjectFilePolicy with deleteFile method exists
            
            // Call the service
            $this->fileManagementService->deleteProjectFile($projectFile, Auth::user());
            
            Toaster::success("File '{$projectFile->file_name}' deleted successfully.");
            $this->updateStorageInfo(); // Refresh storage display
            $this->dispatch('file-deleted'); // Notify UI to refresh file list
            // Refresh the project model in the component IF the files relationship is used directly in render()
            $this->project->refresh(); 
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Toaster::error('File not found.');
        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to delete this file.');
        } catch (FileDeletionException $e) {
            Log::warning('Project file deletion failed via Livewire', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error deleting project file via Livewire', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while deleting the file.');
        }
    }

    /**
     * Generate and dispatch a temporary download URL for a file.
     */
    public function getDownloadUrl($fileId, FileManagementService $fileManagementService) // Inject service here
    {
        try {
            $projectFile = ProjectFile::findOrFail($fileId);
            
            // Authorization: Use Policy
            $this->authorize('download', $projectFile); // Assuming ProjectFilePolicy with download method exists

            // Get URL from service
            $url = $fileManagementService->getTemporaryDownloadUrl($projectFile, Auth::user());

            // Dispatch event for JavaScript to handle opening the URL
            $this->dispatch('openUrl', url: $url);
            Toaster::info('Your download will begin shortly...'); // Optional feedback

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Toaster::error('File not found.');
        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to download this file.');
        } catch (\Exception $e) {
            Log::error('Error getting project file download URL via Livewire', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            Toaster::error('Could not generate download link: ' . $e->getMessage());
        }
    }

    /**
     * Clear the highlight for newly uploaded files in the main list.
     */
    public function clearUploadHighlights()
    {
        $this->newlyUploadedFileIds = [];
    }

    // Method to get approved/completed pitches seems fine
    private function getApprovedAndCompletedPitches()
    {
        return $this->project->pitches()
            ->whereIn('status', [\App\Models\Pitch::STATUS_APPROVED, \App\Models\Pitch::STATUS_COMPLETED])
            ->with('user') // Eager load user
            ->orderBy('status', 'desc') // Show completed first
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    // Method to check for multiple approved pitches seems fine
    private function hasMultipleApprovedPitches()
    {
        return $this->project->pitches()->where('status', \App\Models\Pitch::STATUS_APPROVED)->count() > 1;
    }

    // Method to count approved pitches seems fine
    private function getApprovedPitchesCount()
    {
        return $this->project->pitches()->where('status', \App\Models\Pitch::STATUS_APPROVED)->count();
    }

    /**
     * Update the project details using the service.
     */
    public function updateProjectDetails(ProjectManagementService $projectService)
    {
        Log::debug('ManageProject: Entered updateProjectDetails', ['project_id' => $this->project->id]); // Log entry
        try {
            $this->authorize('update', $this->project);

            // --- Revert DEBUG: Validate the entire form --- START ---
            Log::debug('ManageProject: Before validate()', ['form_state' => $this->form->all()]); // Log form state before validation
            $validatedData = $this->form->validate(); // Restore original validation
            Log::debug('ManageProject: After validate()', ['validated_data' => $validatedData]); // Log validated data
            // Remove manually constructed data array
            // --- Revert DEBUG: Validate the entire form --- END ---

            // Transform collaboration types
            $collaborationTypes = [];
            if ($this->form->collaborationTypeMixing) $collaborationTypes[] = 'Mixing';
            if ($this->form->collaborationTypeMastering) $collaborationTypes[] = 'Mastering';
            if ($this->form->collaborationTypeProduction) $collaborationTypes[] = 'Production';
            if ($this->form->collaborationTypeSongwriting) $collaborationTypes[] = 'Songwriting';
            if ($this->form->collaborationTypeVocalTuning) $collaborationTypes[] = 'Vocal Tuning';
            // Apply transformation to $validatedData
            $validatedData['collaboration_type'] = $collaborationTypes;
             unset(
                $validatedData['collaborationTypeMixing'],
                $validatedData['collaborationTypeMastering'],
                $validatedData['collaborationTypeProduction'],
                $validatedData['collaborationTypeSongwriting'],
                $validatedData['collaborationTypeVocalTuning'],
                $validatedData['budgetType']
            );

            // Separate image file
            $imageFile = $this->form->projectImage ?? null;
            
            // Check if image file is valid
            if ($this->form->projectImage && !($imageFile instanceof \Illuminate\Http\UploadedFile && $imageFile->isValid())) {
                throw new \RuntimeException('Image file received from form is not a valid UploadedFile.');
            }

            // Unset image from validated data if it exists
            if ($imageFile) {
                unset($validatedData['projectImage']);
            }

            Log::debug('ManageProject: Before calling service->updateProject', ['project_id' => $this->project->id]); // Log before service call
            // Call the service to update the project
            $this->project = $projectService->updateProject(
                $this->project,
                $validatedData, // Use validated data again
                $imageFile // Service handles old image deletion if new one provided
            );

            Log::debug('ManageProject: After calling service->updateProject', ['project_id' => $this->project->id]); // Log after service call

            $this->project->refresh(); // Refresh the model state locally
            Toaster::success('Project details updated successfully.');

            // Optionally dispatch event or handle redirect
            // $this->dispatch('projectUpdated');

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
            if (app()->environment('testing')) throw $e;
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are automatically handled by Livewire
            Log::info('Validation failed updating project details', ['errors' => $e->errors()]);
        } catch (\App\Exceptions\Project\ProjectUpdateException $e) {
            Toaster::error($e->getMessage());
            if (app()->environment('testing')) throw $e;
        } catch (\Exception $e) {
            Log::error('Error updating project details', ['project_id' => $this->project->id, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while updating the project.');
            if (app()->environment('testing')) throw $e;
        }
    }

    public function render()
    {
        $approvedPitches = $this->getApprovedAndCompletedPitches();
        return view('livewire.project.page.manage-project', [
            'approvedPitches' => $approvedPitches,
            'hasCompletedPitch' => $this->project->pitches()->where('status', \App\Models\Pitch::STATUS_COMPLETED)->exists(),
            'hasMultipleApprovedPitches' => $this->hasMultipleApprovedPitches(),
            'approvedPitchesCount' => $this->getApprovedPitchesCount()
        ]);
    }
}
