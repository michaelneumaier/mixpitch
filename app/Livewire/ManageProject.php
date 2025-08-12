<?php

namespace App\Livewire;

use App\Exceptions\File\FileDeletionException;
use App\Exceptions\File\FileUploadException;
use App\Jobs\PostProjectToReddit;
// use App\Http\Controllers\ProjectController; // Remove direct controller usage
use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use App\Models\ProjectFile; // Ensure the Facade is imported if needed
use App\Services\FileManagementService; // Add Auth facade
// Added for refactoring
use App\Services\NotificationService;
use App\Services\Project\ProjectImageService; // <-- Import FileManagementService
use App\Services\Project\ProjectManagementService; // <-- Import ProjectImageService
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Masmerise\Toaster\Toaster;

class ManageProject extends Component
{
    use WithFileUploads;

    public Project $project;

    public ProjectForm $form;

    public bool $autoAllowAccess;

    public $hasPreviewTrack = false;

    public $audioUrl;

    // Storage tracking
    public $storageUsedPercentage = 0;

    public $storageLimitMessage = '';

    public $storageRemaining = 0;

    public bool $showDeleteModal = false;

    public $fileToDelete = null;

    public bool $isDeleting = false;

    public $showDeleteConfirmation = false;

    // Project deletion properties
    public bool $showProjectDeleteModal = false;

    // Project image management properties
    public bool $showImageUploadModal = false;

    public $newProjectImage;

    public $uploadingImage = false;

    public $imagePreviewUrl = null;

    // Reddit posting state
    public bool $isPostingToReddit = false;

    public $redditPostingStartedAt = null;

    // Browser timezone for datetime-local conversion
    public $browserTimezone;

    // Contest deadline properties (for editing contest projects)
    public $submission_deadline = null;

    public $judging_deadline = null;

    // Add listener for the new file uploader component
    protected $listeners = [
        'filesUploaded' => 'refreshProjectData',
        'checkRedditStatus' => 'checkRedditStatus',
        // Keep existing listeners if any
    ];

    public function mount(Project $project)
    {
        // Redirect client management projects to dedicated page
        if ($project->isClientManagement()) {
            return redirect()->route('projects.manage-client', $project);
        }

        try {
            $this->authorize('update', $project);
        } catch (AuthorizationException $e) {
            abort(403, 'You are not authorized to manage this project.');
        }

        $this->project = $project;
        $this->autoAllowAccess = $this->project->auto_allow_access;

        // Eager load relationships needed based on workflow type
        if ($this->project->isDirectHire()) {
            $this->project->load('targetProducer'); // Load the target producer
            // Load the single associated pitch for Direct Hire
            $this->project->load(['pitches' => function ($query) {
                $query->with(['user', 'files', 'events']); // Load pitch details
            }]);
        } else {
            // Load standard pitches/applicants for other workflow types
            $this->project->load('pitches.user'); // Load standard pitches
        }

        // Initialize the form object
        $this->form = new ProjectForm($this, 'form');
        // Use the fill method to populate the form from the model
        $this->form->fill($this->project);

        // Initialize timezone service for datetime conversions
        $timezoneService = app(\App\Services\TimezoneService::class);

        // Handle standard project deadline - parse raw database value as UTC
        if ($this->project->deadline && $this->project->deadline instanceof \Carbon\Carbon) {
            $rawDeadline = $this->project->getRawOriginal('deadline');
            if ($rawDeadline) {
                $utcTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $rawDeadline, 'UTC');
                $this->form->deadline = $timezoneService->convertToUserTimezone($utcTime, auth()->user())->format('Y-m-d\TH:i');
            } else {
                $this->form->deadline = null;
            }

            \Log::info('ManageProject: CORRECT standard project deadline', [
                'project_id' => $this->project->id,
                'raw_database' => $rawDeadline,
                'parsed_as_utc' => $utcTime->format('Y-m-d H:i:s T'),
                'deadline_converted' => $this->form->deadline,
                'user_timezone' => auth()->user()->getTimezone(),
            ]);
        } elseif (is_string($this->project->deadline)) {
            // If the model has a string, assume it's correctly formatted and ensure the form has it
            // fill() might have already handled this, but this makes it explicit
            $this->form->deadline = $this->project->deadline;
        } else {
            $this->form->deadline = null; // Default to null if model deadline isn't set or Carbon
        }

        // Handle contest deadlines if this is a contest project
        if ($this->project->isContest()) {
            \Log::info('ManageProject: Converting contest deadlines for editing', [
                'project_id' => $this->project->id,
                'submission_deadline_utc' => $this->project->submission_deadline,
                'judging_deadline_utc' => $this->project->judging_deadline,
                'user_timezone' => auth()->user()->getTimezone(),
            ]);

            // Convert UTC times to user's timezone for datetime-local inputs - parse raw as UTC
            if ($this->project->submission_deadline) {
                $rawSubmissionDeadline = $this->project->getRawOriginal('submission_deadline');
                if ($rawSubmissionDeadline) {
                    $utcTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $rawSubmissionDeadline, 'UTC');
                    $this->submission_deadline = $timezoneService->convertToUserTimezone($utcTime, auth()->user())->format('Y-m-d\TH:i');
                } else {
                    $this->submission_deadline = null;
                }
            } else {
                $this->submission_deadline = null;
            }

            if ($this->project->judging_deadline) {
                $rawJudgingDeadline = $this->project->getRawOriginal('judging_deadline');
                if ($rawJudgingDeadline) {
                    $utcTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $rawJudgingDeadline, 'UTC');
                    $this->judging_deadline = $timezoneService->convertToUserTimezone($utcTime, auth()->user())->format('Y-m-d\TH:i');
                } else {
                    $this->judging_deadline = null;
                }
            } else {
                $this->judging_deadline = null;
            }

            \Log::info('ManageProject: CORRECT contest deadlines', [
                'submission_raw' => $this->project->getRawOriginal('submission_deadline'),
                'judging_raw' => $this->project->getRawOriginal('judging_deadline'),
                'submission_deadline_converted' => $this->submission_deadline,
                'judging_deadline_converted' => $this->judging_deadline,
            ]);
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
                'error' => $e->getMessage(),
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
        if (empty($types)) {
            return;
        }
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
        // Use user-based storage instead of project-based storage
        $user = $this->project->user;
        $userStorageService = app(\App\Services\UserStorageService::class);

        // Use caching for expensive calculations
        $cacheKey = "user_{$user->id}_storage_info";
        $cacheTTL = 120; // Cache for 2 minutes

        $storageInfo = cache()->remember($cacheKey, $cacheTTL, function () use ($user, $userStorageService) {
            return [
                'percentage' => $userStorageService->getUserStoragePercentage($user),
                'message' => $userStorageService->getStorageLimitMessage($user),
                'remaining' => $userStorageService->getUserStorageRemaining($user),
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
        $user = $this->project->user;
        $cacheKey = "user_{$user->id}_storage_info";
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
                'error' => $e->getMessage(),
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
        Log::debug('[ManageProject] Publish: Start - Project ID: '.$this->project->id.', Status: '.$this->project->status.', Auth User: '.(auth()->check() ? auth()->id() : 'None'));

        $this->authorize('publish', $this->project);
        Log::debug('[ManageProject] Publish: Authorized');

        // Use the model's publish method instead of directly setting properties
        $this->project->publish();
        Log::debug('[ManageProject] Publish: After save - Status: '.$this->project->status.', is_published: '.($this->project->is_published ? 'true' : 'false'));

        // Refresh the project to ensure we have the latest data
        $this->project->refresh();
        Log::debug('[ManageProject] Publish: After refresh - Status: '.$this->project->status.', is_published: '.($this->project->is_published ? 'true' : 'false'));

        Toaster::success('Project published successfully.');
        $this->dispatch('project-updated');

        Log::debug('[ManageProject] Publish: End');
    }

    /**
     * Unpublish the project.
     */
    public function unpublish(): void
    {
        Log::debug('[ManageProject] Unpublish: Start - Project ID: '.$this->project->id.', Status: '.$this->project->status.', Auth User: '.(auth()->check() ? auth()->id() : 'None'));

        $this->authorize('unpublish', $this->project);
        Log::debug('[ManageProject] Unpublish: Authorized');

        // Use the model's unpublish method instead of directly setting properties
        $this->project->unpublish();
        Log::debug('[ManageProject] Unpublish: After save - Status: '.$this->project->status.', is_published: '.($this->project->is_published ? 'true' : 'false'));

        // Refresh the project to ensure we have the latest data
        $this->project->refresh();
        Log::debug('[ManageProject] Unpublish: After refresh - Status: '.$this->project->status.', is_published: '.($this->project->is_published ? 'true' : 'false'));

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
                        'file_id' => $file->id,
                    ]);
                    Toaster::error('Could not set preview track. Please try again.');
                }
            }

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to change the preview track.');
        } catch (\Exception $e) {
            Log::error('Error toggling project preview track via Livewire', ['project_id' => $this->project->id, 'file_id' => $file->id, 'error' => $e->getMessage()]);
            Toaster::error('Could not update preview track: '.$e->getMessage());
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
            Toaster::error('Could not clear preview track: '.$e->getMessage());
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
        $this->isDeleting = true;

        $idToDelete = $fileId ?? $this->fileToDelete;

        Log::debug('Starting file deletion process', [
            'file_id' => $idToDelete,
            'is_file_id_null' => is_null($fileId),
            'is_file_to_delete_null' => is_null($this->fileToDelete),
        ]);

        if (! $idToDelete) {
            $this->isDeleting = false;
            Toaster::error('No file selected for deletion.');

            return;
        }

        try {
            $projectFile = ProjectFile::findOrFail($idToDelete);
            Log::debug('Found file to delete', [
                'file_id' => $projectFile->id,
                'file_name' => $projectFile->file_name,
            ]);

            // Authorization: Use Policy
            $this->authorize('delete', $projectFile);
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
                'user_total_storage_used' => $this->project->user->total_storage_used,
            ]);

            // Clear the storage cache
            $this->clearStorageCache();
            Log::debug('Storage cache cleared');

            // Force a direct recalculation of storage info without caching
            $user = $this->project->user;
            $userStorageService = app(\App\Services\UserStorageService::class);
            $forcedStorageInfo = [
                'percentage' => $userStorageService->getUserStoragePercentage($user),
                'message' => $userStorageService->getStorageLimitMessage($user),
                'remaining' => $userStorageService->getUserStorageRemaining($user),
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
                'trace' => $e->getTraceAsString(),
            ]);
            Toaster::error('An unexpected error occurred while deleting the file: '.$e->getMessage());
        } finally {
            $this->showDeleteModal = false;
            $this->fileToDelete = null;
            $this->isDeleting = false;
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
            Toaster::error('Could not generate download link: '.$e->getMessage());
        }
    }

    /**
     * Resend the client invitation email with a new signed URL.
     */
    public function resendClientInvite(NotificationService $notificationService)
    {
        // Authorization: Ensure user is the project owner and it's a client mgmt project
        if (auth()->id() !== $this->project->user_id || ! $this->project->isClientManagement()) {
            Toaster::error('Unauthorized action.');

            return;
        }

        if (empty($this->project->client_email)) {
            Toaster::error('Client email is not set for this project.');

            return;
        }

        try {
            // Regenerate Signed URL
            $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'client.portal.view',
                now()->addDays(config('mixpitch.client_portal_link_expiry_days', 7)),
                ['project' => $this->project->id]
            );

            // Log the signed URL directly for admin access
            \Illuminate\Support\Facades\Log::info('Client invite URL generated for resend (Livewire)', [
                'project_id' => $this->project->id,
                'client_email' => $this->project->client_email,
                'signed_url' => $signedUrl,
            ]);

            // Trigger notification
            $notificationService->notifyClientProjectInvite($this->project, $signedUrl);

            // Feedback to producer
            Toaster::success('Client invitation resent successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to resend client invite', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to resend invitation. Please try again later.');
        }
    }

    /**
     * Check if the current user can upload files to this project.
     */
    public function getCanUploadFilesProperty(): bool
    {
        return Gate::allows('uploadFile', $this->project);
    }

    public function render()
    {
        // Eager load relationships to avoid N+1 queries
        // Load the entire relationship graph needed for the view in one query
        $this->project->load([
            'pitches.user',
            'pitches.snapshots',
            'files',
        ]);

        // Pre-calculate expensive operations
        $approvedPitches = $this->project->pitches->filter(function ($pitch) {
            return in_array($pitch->status, [
                \App\Models\Pitch::STATUS_APPROVED,
                \App\Models\Pitch::STATUS_COMPLETED,
            ]);
        })->sortByDesc(function ($pitch) {
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
            'newlyUploadedFileIds' => $newlyUploadedFileIds,
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
        return $this->project->pitches->filter(function ($pitch) {
            return in_array($pitch->status, [
                \App\Models\Pitch::STATUS_APPROVED,
                \App\Models\Pitch::STATUS_COMPLETED,
            ]);
        })->sortByDesc(function ($pitch) {
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

        // Add contest deadline validation if this is a contest project
        if ($this->project->isContest()) {
            $contestValidation = $this->validate([
                'submission_deadline' => 'nullable|date|after:now',
                'judging_deadline' => 'nullable|date|after:submission_deadline',
            ]);

            // Merge contest deadline validation with form validation
            $validatedData = array_merge($validatedData, $contestValidation);
        }

        // Log the validated data
        Log::debug('ManageProject: After validate()', ['validated_data' => $validatedData]);

        // Transform collaboration types and format data for service
        $collaborationTypes = [];
        if ($this->form->collaborationTypeMixing) {
            $collaborationTypes[] = 'Mixing';
        }
        if ($this->form->collaborationTypeMastering) {
            $collaborationTypes[] = 'Mastering';
        }
        if ($this->form->collaborationTypeProduction) {
            $collaborationTypes[] = 'Production';
        }
        if ($this->form->collaborationTypeSongwriting) {
            $collaborationTypes[] = 'Songwriting';
        }
        if ($this->form->collaborationTypeVocalTuning) {
            $collaborationTypes[] = 'Vocal Tuning';
        }

        // Remove collaboration type booleans and add the array
        $validatedData['collaboration_type'] = $collaborationTypes;
        unset(
            $validatedData['collaborationTypeMixing'],
            $validatedData['collaborationTypeMastering'],
            $validatedData['collaborationTypeProduction'],
            $validatedData['collaborationTypeSongwriting'],
            $validatedData['collaborationTypeVocalTuning']
        );

        // Handle budget based on budgetType
        if (isset($validatedData['budgetType'])) {
            // If budget type is 'paid', use the value from form->budget
            // Otherwise set it to 0 for 'free'
            $validatedData['budget'] = ($validatedData['budgetType'] === 'paid') ?
                (int) $this->form->budget : 0;
            unset($validatedData['budgetType']);
        } else {
            // If budgetType isn't set, we still need to ensure budget is included
            $validatedData['budget'] = (int) $this->form->budget;
        }

        // Set proper project_type from the form
        $validatedData['project_type'] = $this->form->projectType;
        // Remove the projectType key to avoid confusion
        unset($validatedData['projectType']);

        // Extract image if present
        $imageFile = $validatedData['projectImage'] ?? null;
        unset($validatedData['projectImage']);

        // Convert deadline to UTC if provided
        if (isset($validatedData['deadline']) && $validatedData['deadline']) {
            $validatedData['deadline'] = $this->convertDateTimeToUtc($validatedData['deadline']);
        }

        // Convert contest deadline fields to UTC if provided
        if (isset($validatedData['submission_deadline']) && $validatedData['submission_deadline']) {
            $validatedData['submission_deadline'] = $this->convertDateTimeToUtc($validatedData['submission_deadline']);
        }

        if (isset($validatedData['judging_deadline']) && $validatedData['judging_deadline']) {
            $validatedData['judging_deadline'] = $this->convertDateTimeToUtc($validatedData['judging_deadline']);
        }

        Log::debug('ManageProject: Before calling service->updateProject', [
            'project_id' => $this->project->id,
            'validated_data' => $validatedData,
            'budget' => $validatedData['budget'] ?? null,
            'project_type' => $validatedData['project_type'] ?? null,
            'form_projectType' => $this->form->projectType,
        ]);

        try {
            $project = $projectService->updateProject(
                $this->project,
                $validatedData,
                $imageFile
            );

            Log::debug('ManageProject: After calling service->updateProject', [
                'project_id' => $this->project->id,
                'updated_project_type' => $project->project_type,
                'updated_budget' => $project->budget,
            ]);

            // Update the component's project reference with the updated model
            $this->project = $project;

            Toaster::success('Project details updated successfully!');

            // Optional: redirect or refresh component
            // return redirect()->route('projects.manage', $project);

            $this->dispatch('project-details-updated'); // Event for JS handling
        } catch (\Exception $e) {
            Log::error('Error in ManageProject::updateProjectDetails', ['error' => $e->getMessage(), 'project_id' => $this->project->id]);
            Toaster::error('Error updating project: '.$e->getMessage());
        }
    }

    /**
     * Special test helper to simplify testing image uploads.
     * This method is only usable in testing environments.
     */
    public function forceImageUpdate()
    {
        if (! app()->environment('testing')) {
            throw new \Exception('This method can only be used in the testing environment.');
        }

        if (! $this->form->projectImage) {
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
     * @param  int  $bytes
     * @param  int  $precision
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

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Check and set preview track status.
     */
    private function checkPreviewTrackStatus()
    {
        $this->hasPreviewTrack = $this->project->hasPreviewTrack();

        if ($this->hasPreviewTrack) {
            $previewFile = $this->project->files()->where('id', $this->project->preview_track)->first();
            if ($previewFile) {
                $this->audioUrl = $previewFile->getSignedUrl();
            }
        }
    }

    /**
     * Confirm project deletion
     */
    public function confirmDeleteProject()
    {
        $this->authorize('delete', $this->project);
        $this->showProjectDeleteModal = true;
    }

    /**
     * Cancel project deletion
     */
    public function cancelDeleteProject()
    {
        $this->showProjectDeleteModal = false;
    }

    /**
     * Delete the project and all associated data
     */
    public function deleteProject()
    {
        $this->authorize('delete', $this->project);

        try {
            $projectTitle = $this->project->title;
            $this->project->delete(); // This will cascade delete pitches and files via observer

            Toaster::success("Project '{$projectTitle}' deleted successfully.");

            // Redirect to projects list
            return redirect()->route('projects.index');

        } catch (\Exception $e) {
            Log::error('Error deleting project', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to delete project. Please try again.');
        }
    }

    /**
     * Show the image upload modal
     */
    public function showImageUpload()
    {
        $this->showImageUploadModal = true;
        $this->newProjectImage = null;
        $this->imagePreviewUrl = null;
    }

    /**
     * Hide the image upload modal
     */
    public function hideImageUpload()
    {
        $this->showImageUploadModal = false;
        $this->newProjectImage = null;
        $this->imagePreviewUrl = null;
        $this->uploadingImage = false;
    }

    /**
     * Handle image file selection and show preview
     */
    public function updatedNewProjectImage()
    {
        if ($this->newProjectImage) {
            try {
                // Validate file on frontend
                $this->validate([
                    'newProjectImage' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
                ]);

                // Generate preview URL
                $this->imagePreviewUrl = $this->newProjectImage->temporaryUrl();
            } catch (\Exception $e) {
                $this->imagePreviewUrl = null;
                Toaster::error('Invalid image file. Please select a valid image (JPG, PNG, GIF, or WebP) under 5MB.');
                $this->newProjectImage = null;
            }
        }
    }

    /**
     * Upload the new project image
     */
    public function uploadProjectImage(ProjectImageService $imageService)
    {
        if (! $this->newProjectImage) {
            Toaster::error('Please select an image to upload.');

            return;
        }

        try {
            $this->authorize('update', $this->project);

            $this->uploadingImage = true;

            // Validate image file
            $this->validate([
                'newProjectImage' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
            ]);

            // Use the ProjectImageService to handle upload
            if ($this->project->image_path) {
                // Update existing image
                $path = $imageService->updateProjectImage($this->project, $this->newProjectImage);
                $message = 'Project image updated successfully!';
            } else {
                // Upload new image
                $path = $imageService->uploadProjectImage($this->project, $this->newProjectImage);
                $message = 'Project image added successfully!';
            }

            // Refresh project to get new image
            $this->project->refresh();

            // Close modal and show success
            $this->hideImageUpload();
            Toaster::success($message);

            // Dispatch event for any listening components
            $this->dispatch('project-image-updated');

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
        } catch (FileUploadException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error uploading project image in ManageProject', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            Toaster::error('An error occurred while uploading the image. Please try again.');
        } finally {
            $this->uploadingImage = false;
        }
    }

    /**
     * Remove the project image
     */
    public function removeProjectImage(ProjectImageService $imageService)
    {
        try {
            $this->authorize('update', $this->project);

            // Use the ProjectImageService to handle deletion
            $success = $imageService->deleteProjectImage($this->project);

            if ($success) {
                // Refresh project to remove image reference
                $this->project->refresh();

                Toaster::success('Project image removed successfully!');

                // Dispatch event for any listening components
                $this->dispatch('project-image-updated');
            } else {
                Toaster::error('Failed to remove project image. Please try again.');
            }

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
        } catch (\Exception $e) {
            Log::error('Error removing project image in ManageProject', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            Toaster::error('An error occurred while removing the image. Please try again.');
        }
    }

    /**
     * Post project to Reddit
     */
    public function postToReddit()
    {
        try {
            $this->authorize('update', $this->project);

            // Prevent multiple simultaneous submissions
            if ($this->isPostingToReddit) {
                Toaster::warning('Reddit posting is already in progress. Please wait...');

                return;
            }

            // Validate project requirements
            if (! $this->project->is_published) {
                Toaster::error('Project must be published before posting to Reddit.');

                return;
            }

            if (empty($this->project->title) || empty($this->project->description)) {
                Toaster::error('Project must have a title and description to post to Reddit.');

                return;
            }

            if ($this->project->hasBeenPostedToReddit()) {
                Toaster::warning('This project has already been posted to Reddit.');

                return;
            }

            // Rate limiting check - 3 posts per hour per user
            $recentPosts = auth()->user()->projects()
                ->whereNotNull('reddit_posted_at')
                ->where('reddit_posted_at', '>', now()->subHour())
                ->count();

            if ($recentPosts >= 3) {
                Toaster::error('You can only post 3 projects per hour to Reddit. Please try again later.');

                return;
            }

            // Set posting state
            $this->isPostingToReddit = true;
            $this->redditPostingStartedAt = now();

            // Dispatch the job
            PostProjectToReddit::dispatch($this->project);

            Toaster::success('Your project is being posted to r/MixPitch! This may take a few moments...');

            Log::info('Reddit post job dispatched', [
                'project_id' => $this->project->id,
                'user_id' => auth()->id(),
            ]);

            // Start polling for the result
            $this->dispatch('start-reddit-polling');

        } catch (AuthorizationException $e) {
            $this->isPostingToReddit = false;
            $this->redditPostingStartedAt = null;
            Toaster::error('You are not authorized to post this project.');
        } catch (\Exception $e) {
            $this->isPostingToReddit = false;
            $this->redditPostingStartedAt = null;
            Log::error('Error posting project to Reddit', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            Toaster::error('An error occurred while posting to Reddit. Please try again.');
        }
    }

    /**
     * Check Reddit posting status (called by polling)
     */
    public function checkRedditStatus()
    {
        // Refresh the project to get latest data
        $this->project->refresh();

        // Check if the Reddit post has been completed
        if ($this->project->hasBeenPostedToReddit()) {
            $this->isPostingToReddit = false;
            $this->redditPostingStartedAt = null;

            Toaster::success('Successfully posted to r/MixPitch! You can now view your post on Reddit.');

            // Stop polling
            $this->dispatch('stop-reddit-polling');

            return;
        }

        // Check for timeout (5 minutes)
        if ($this->redditPostingStartedAt && now()->diffInMinutes($this->redditPostingStartedAt) > 5) {
            $this->isPostingToReddit = false;
            $this->redditPostingStartedAt = null;

            Toaster::warning('Reddit posting is taking longer than expected. Please check back in a few minutes or try again.');

            // Stop polling
            $this->dispatch('stop-reddit-polling');
        }
    }

    /**
     * Reset Reddit posting state (for debugging/recovery)
     */
    public function resetRedditPostingState()
    {
        $this->isPostingToReddit = false;
        $this->redditPostingStartedAt = null;
    }

    /**
     * Convert datetime-local input to UTC for database storage
     * This method treats datetime-local inputs as being in the user's timezone
     */
    private function convertDateTimeToUtc(string $dateTime): Carbon
    {
        $userTimezone = auth()->user()->getTimezone();

        Log::debug('ManageProject convertDateTimeToUtc called', [
            'input' => $dateTime,
            'user_timezone' => $userTimezone,
            'input_type' => gettype($dateTime),
        ]);

        // Handle datetime-local format: "2025-06-29T13:00"
        if (str_contains($dateTime, 'T')) {
            // Convert T to space and add seconds if needed
            $formattedDateTime = str_replace('T', ' ', $dateTime);
            if (substr_count($formattedDateTime, ':') === 1) {
                $formattedDateTime .= ':00'; // Add seconds
            }

            // Create Carbon instance in user's timezone and convert to UTC
            $result = Carbon::createFromFormat('Y-m-d H:i:s', $formattedDateTime, $userTimezone)->utc();

            Log::debug('ManageProject: Datetime-local conversion', [
                'input' => $dateTime,
                'formatted' => $formattedDateTime,
                'user_timezone' => $userTimezone,
                'output_utc' => $result->toDateTimeString(),
            ]);

            return $result;
        }

        // Fallback: assume it's already in UTC or parse as-is
        $result = Carbon::parse($dateTime)->utc();
        Log::debug('ManageProject: Fallback conversion', [
            'input' => $dateTime,
            'output' => $result->toDateTimeString(),
        ]);

        return $result;
    }

    public function updatedAutoAllowAccess(bool $value)
    {
        $this->project->update(['auto_allow_access' => $value]);
        $this->project->refresh();

        $message = $value ? 'Automatic access enabled.' : 'Automatic access disabled.';
        Toaster::success($message);
    }
}
