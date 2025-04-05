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

    // Storage tracking
    public $storageUsedPercentage = 0;
    public $storageLimitMessage = '';
    public $storageRemaining = 0;

    public bool $showDeleteModal = false;
    public $fileToDelete;

    // Add listener for the new file uploader component
    protected $listeners = [
        'filesUploaded' => 'refreshProjectData', 
        // Keep existing listeners if any
    ];

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

        // Preview track logic
        $this->checkPreviewTrackStatus();
        
        // Use try-catch to prevent potential hangs from storage methods
        try {
        $this->updateStorageInfo();
        } catch (\Exception $e) {
            // Log error but don't fail the component initialization
            Log::error('Error updating storage info in ManageProject mount', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage()
            ]);
            
            // Set default values to prevent UI issues
            $this->storageUsedPercentage = 0;
            $this->storageLimitMessage = '100% available';
            $this->storageRemaining = 104857600; // 100MB default
        }
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
        // Use caching for expensive calculations
        $cacheKey = "project_{$this->project->id}_storage_info";
        $cacheTTL = 120; // Cache for 2 minutes
        
        $storageInfo = cache()->remember($cacheKey, $cacheTTL, function () {
            return [
                'percentage' => $this->project->getStorageUsedPercentage(),
                'message' => $this->project->getStorageLimitMessage(),
                'remaining' => $this->project->getRemainingStorageBytes()
            ];
        });
        
        $this->storageUsedPercentage = $storageInfo['percentage'];
        $this->storageLimitMessage = $storageInfo['message'];
        $this->storageRemaining = $storageInfo['remaining'];
    }
    
    /**
     * Clear the storage info cache when files change
     */
    protected function clearStorageCache()
    {
        $cacheKey = "project_{$this->project->id}_storage_info";
        cache()->forget($cacheKey);
    }

    /**
     * Refresh component data after file uploads.
     */
    public function refreshProjectData()
    {
        $this->project->refresh(); // Refresh the project model
        $this->clearStorageCache(); // Clear cache before updating
        
        // Use try-catch to prevent potential hangs from storage methods
        try {
            $this->updateStorageInfo(); // Update storage display
        } catch (\Exception $e) {
            // Log error but don't fail
            Log::error('Error updating storage info in refreshProjectData', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage()
            ]);
            
            // Set default values
            $this->storageUsedPercentage = 0;
            $this->storageLimitMessage = '100% available';
            $this->storageRemaining = 104857600; // 100MB default
        }
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
            $this->authorize('update', $this->project);

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
                
                // Refresh project data
                $this->project->refresh();
                
                // Directly use Project model's method
                if ($this->project->hasPreviewTrack()) {
                    $this->hasPreviewTrack = true;
                    // No need to store URL in a property - it will be generated directly in the view
                    // Just dispatch an event to refresh the UI
                    $this->dispatch('preview-track-updated');
                    Toaster::success('Preview track updated successfully.');
                } else {
                    Log::warning('Preview track set but hasPreviewTrack() returned false', [
                        'project_id' => $this->project->id,
                        'file_id' => $file->id
                    ]);
                    Toaster::error('Could not set preview track. Please try again.');
                }
            }
            
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

            $fileManagementService->clearProjectPreviewTrack($this->project);
            $this->hasPreviewTrack = false;
            $this->dispatch('preview-track-updated');
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

    /**
     * Set delete modal state
     */
    public function confirmDeleteFile($fileId)
    {
        $this->showDeleteModal = true;
        $this->fileToDelete = $fileId;
    }
    
    /**
     * Cancel file deletion
     */
    public function cancelDeleteFile()
    {
        $this->showDeleteModal = false;
        $this->fileToDelete = null;
    }
    
    /**
     * Get the file management service
     * 
     * @return FileManagementService
     */
    protected function getFileService(): FileManagementService
    {
        return app(FileManagementService::class);
    }
    
    /**
     * Delete a persisted Project File.
     */
    public function deleteFile($fileId = null)
    {
        $idToDelete = $fileId ?? $this->fileToDelete;
        
        Log::debug('Starting file deletion process', [
            'file_id' => $idToDelete,
            'is_file_id_null' => is_null($fileId),
            'is_file_to_delete_null' => is_null($this->fileToDelete)
        ]);
        
        if (!$idToDelete) {
            Toaster::error('No file selected for deletion.');
            return;
        }
        
        try {
            $projectFile = ProjectFile::findOrFail($idToDelete);
            Log::debug('Found file to delete', [
                'file_id' => $projectFile->id,
                'file_name' => $projectFile->file_name
            ]);
            
            // Authorization: Use Policy
            $this->authorize('deleteFile', $projectFile);
            Log::debug('Authorization passed');
            
            // Get service via protected method
            $fileManager = $this->getFileService();
            Log::debug('File service resolved');
            
            // Store the file size for logging
            $fileSize = $projectFile->size;
            Log::debug('File to be deleted size', ['size' => $fileSize]);
            
            // Delete the file
            $fileManager->deleteProjectFile($projectFile);
            Log::debug('File deleted successfully');
            
            // Important: Refresh the project model first to get the latest data
            $this->project->refresh();
            Log::debug('Project model refreshed', [
                'total_storage_used' => $this->project->total_storage_used,
                'storage_used_percentage' => $this->project->getStorageUsedPercentage()
            ]);
            
            // Clear the storage cache
            $this->clearStorageCache();
            Log::debug('Storage cache cleared');
            
            // Force a direct recalculation of storage info without caching
            $forcedStorageInfo = [
                'percentage' => $this->project->getStorageUsedPercentage(),
                'message' => $this->project->getStorageLimitMessage(),
                'remaining' => $this->project->getRemainingStorageBytes()
            ];
            
            Log::debug('Forced storage recalculation', $forcedStorageInfo);
            
            // Manually set the properties with the forced values
            $this->storageUsedPercentage = $forcedStorageInfo['percentage'];
            $this->storageLimitMessage = $forcedStorageInfo['message'];
            $this->storageRemaining = $forcedStorageInfo['remaining'];
            
            // Update the info through normal method too
            $this->updateStorageInfo();
            
            Toaster::success("File '{$projectFile->file_name}' deleted successfully.");
            $this->dispatch('file-deleted'); // Notify UI to refresh file list
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('File not found for deletion', ['file_id' => $idToDelete]);
            Toaster::error('File not found.');
        } catch (AuthorizationException $e) {
            Log::error('Authorization failed for file deletion', ['file_id' => $idToDelete, 'user_id' => auth()->id()]);
            Toaster::error('You are not authorized to delete this file.');
        } catch (FileDeletionException $e) {
            Log::warning('Project file deletion failed via Livewire', ['file_id' => $idToDelete, 'error' => $e->getMessage()]);
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error deleting project file via Livewire', [
                'file_id' => $idToDelete, 
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Toaster::error('An unexpected error occurred while deleting the file: ' . $e->getMessage());
        } finally {
            $this->showDeleteModal = false;
            $this->fileToDelete = null;
        }
    }

    /**
     * Generate and dispatch a temporary download URL for a file.
     */
    public function getDownloadUrl($fileId)
    {
        try {
            $projectFile = ProjectFile::findOrFail($fileId);
            
            // Authorization: Use Policy
            $this->authorize('download', $projectFile);

            // Get service from helper method
            $fileManagementService = $this->getFileService();

            // Get URL from service (force download by default)
            $url = $fileManagementService->getTemporaryDownloadUrl($projectFile);
            $filename = $projectFile->original_file_name ?: $projectFile->file_name;

            // Dispatch event for JavaScript to handle opening the URL
            $this->dispatch('open-url', url: $url, filename: $filename);
            Toaster::info('Your download will begin shortly...');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Toaster::error('File not found.');
        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to download this file.');
        } catch (\Exception $e) {
            Log::error('Error getting project file download URL via Livewire', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            Toaster::error('Could not generate download link: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Eager load relationships to avoid N+1 queries
        // Load the entire relationship graph needed for the view in one query
        $this->project->load([
            'pitches.user', 
            'pitches.snapshots',
            'files'
        ]);
        
        // Pre-calculate expensive operations
        $approvedPitches = $this->project->pitches->filter(function($pitch) {
            return in_array($pitch->status, [
                \App\Models\Pitch::STATUS_APPROVED, 
                \App\Models\Pitch::STATUS_COMPLETED
            ]);
        })->sortByDesc(function($pitch) {
            return $pitch->status === \App\Models\Pitch::STATUS_COMPLETED ? 1 : 0;
        });
        
        // Use the loaded relationship instead of new queries
        $hasCompletedPitch = $this->project->pitches->contains('status', \App\Models\Pitch::STATUS_COMPLETED);
        
        // Count from the collection instead of running a separate query
        $approvedPitchesCount = $this->project->pitches->where('status', \App\Models\Pitch::STATUS_APPROVED)->count();
        $hasMultipleApprovedPitches = $approvedPitchesCount > 1;
        
        // Set new property for any newly uploaded files
        $newlyUploadedFileIds = session('newly_uploaded_file_ids', []);
        
        return view('livewire.project.page.manage-project', [
            'approvedPitches' => $approvedPitches,
            'hasCompletedPitch' => $hasCompletedPitch,
            'hasMultipleApprovedPitches' => $hasMultipleApprovedPitches, 
            'approvedPitchesCount' => $approvedPitchesCount,
            'newlyUploadedFileIds' => $newlyUploadedFileIds
        ]);
    }
    
    /**
     * Helper methods below are now optimized to use the already loaded relationships
     * keeping them for backward compatibility
     */
    
    // Method to get approved/completed pitches using the loaded relationship
    private function getApprovedAndCompletedPitches()
    {
        // Use the already loaded relationship
        return $this->project->pitches->filter(function($pitch) {
            return in_array($pitch->status, [
                \App\Models\Pitch::STATUS_APPROVED, 
                \App\Models\Pitch::STATUS_COMPLETED
            ]);
        })->sortByDesc(function($pitch) {
            return $pitch->status === \App\Models\Pitch::STATUS_COMPLETED ? 1 : 0;
        });
    }

    // Method to check for multiple approved pitches using the loaded relationship
    private function hasMultipleApprovedPitches()
    {
        return $this->project->pitches->where('status', \App\Models\Pitch::STATUS_APPROVED)->count() > 1;
    }

    // Method to count approved pitches using the loaded relationship
    private function getApprovedPitchesCount()
    {
        return $this->project->pitches->where('status', \App\Models\Pitch::STATUS_APPROVED)->count();
    }

    /**
     * Update the project details using the service.
     */
    public function updateProjectDetails(ProjectManagementService $projectService)
    {
        Log::debug('ManageProject: Entered updateProjectDetails', ['project_id' => $this->project->id]);
        
        $this->authorize('update', $this->project);
        
        // Log the form state before validation
        Log::debug('ManageProject: Before validate()', ['form_state' => json_decode(json_encode($this->form), true)]);
        
        $validatedData = $this->form->validate();
        
        // Log the validated data
        Log::debug('ManageProject: After validate()', ['validated_data' => $validatedData]);

        // Transform collaboration types and format data for service
        $collaborationTypes = [];
        if ($this->form->collaborationTypeMixing) $collaborationTypes[] = 'Mixing';
        if ($this->form->collaborationTypeMastering) $collaborationTypes[] = 'Mastering';
        if ($this->form->collaborationTypeProduction) $collaborationTypes[] = 'Production';
        if ($this->form->collaborationTypeSongwriting) $collaborationTypes[] = 'Songwriting';
        if ($this->form->collaborationTypeVocalTuning) $collaborationTypes[] = 'Vocal Tuning';

        // Remove collaboration type booleans and add the array
        $validatedData['collaboration_type'] = $collaborationTypes;
        unset(
            $validatedData['collaborationTypeMixing'],
            $validatedData['collaborationTypeMastering'],
            $validatedData['collaborationTypeProduction'],
            $validatedData['collaborationTypeSongwriting'],
            $validatedData['collaborationTypeVocalTuning']
        );
        
        // Unset budgetType if present
        if (isset($validatedData['budgetType'])) {
            unset($validatedData['budgetType']);
        }

        // Ensure project_type is correctly set if it's coming from projectType
        if (isset($validatedData['projectType'])) {
            $validatedData['project_type'] = $validatedData['projectType'];
            unset($validatedData['projectType']);
        }

        // Extract image if present
        $imageFile = $validatedData['projectImage'] ?? null;
        unset($validatedData['projectImage']);
        
        Log::debug('ManageProject: Before calling service->updateProject', ['project_id' => $this->project->id]);

        try {
            $project = $projectService->updateProject(
                $this->project,
                $validatedData,
                $imageFile
            );

            Log::debug('ManageProject: After calling service->updateProject', ['project_id' => $this->project->id]);
            
            // Update the component's project reference with the updated model
            $this->project = $project;
           
            Toaster::success('Project details updated successfully!');
            
            // Optional: redirect or refresh component
            // return redirect()->route('projects.manage', $project);
            
            $this->dispatch('project-details-updated'); // Event for JS handling
        } catch (\Exception $e) {
            Log::error('Error in ManageProject::updateProjectDetails', ['error' => $e->getMessage(), 'project_id' => $this->project->id]);
            Toaster::error('Error updating project: ' . $e->getMessage());
        }
    }

    /**
     * Special test helper to simplify testing image uploads.
     * This method is only usable in testing environments.
     */
    public function forceImageUpdate()
    {
        if (!app()->environment('testing')) {
            throw new \Exception('This method can only be used in the testing environment.');
        }

        if (!$this->form->projectImage) {
            throw new \Exception('No project image has been uploaded to update.');
        }

        $imageFile = $this->form->projectImage;
        
        // Generate a unique filename for the test
        $timestamp = time();
        $randomStr = substr(md5(rand()), 0, 10);
        $filename = "test_forced_{$timestamp}_{$randomStr}.jpg";
        
        // Force a new image path to ensure it's different
        $uniqueImagePath = $imageFile->storeAs(
            'project_images',
            $filename,
            's3'
        );

        // Update the project directly
        $oldImagePath = $this->project->image_path;
        $this->project->image_path = $uniqueImagePath;
        $this->project->save();

        // Delete old image if it exists
        if ($oldImagePath) {
            Storage::disk('s3')->delete($oldImagePath);
        }

        return $this->project;
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatFileSize($bytes, $precision = 2)
    {
        if ($bytes === null || $bytes <= 0) {
            return '0 bytes';
        }

        $units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Check and set preview track status.
     */
    private function checkPreviewTrackStatus()
    {
        $this->hasPreviewTrack = $this->project->hasPreviewTrack();
        
        // No need to store the URL - it will be generated directly in the view
        // This prevents URL expiration issues between page loads
        
        Log::debug('Checked preview track status', [
            'project_id' => $this->project->id,
            'has_preview_track' => $this->hasPreviewTrack
        ]);
    }
}
