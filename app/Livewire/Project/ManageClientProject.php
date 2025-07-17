<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\Pitch;
use App\Services\FileManagementService;
use App\Services\PitchWorkflowService;
use App\Services\NotificationService;
use App\Exceptions\File\FileDeletionException;
use App\Exceptions\UnauthorizedActionException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class ManageClientProject extends Component
{
    use AuthorizesRequests;

    public Project $project;
    public Pitch $pitch;
    
    // Storage tracking
    public $storageUsedPercentage = 0;
    public $storageLimitMessage = '';
    public $storageRemaining = 0;
    
    // File management
    public $showDeleteModal = false;
    public $fileIdToDelete = null;
    
    // Client file management
    public $showDeleteClientFileModal = false;
    public $clientFileIdToDelete = null;
    public $clientFileNameToDelete = '';
    
    // Project management
    public $showProjectDeleteModal = false;
    
    // Workflow management
    public $responseToFeedback = '';
    public $statusFeedbackMessage = null;
    public $canResubmit = false; // Track if files have changed since last submission
    
    // Communication features
    public $newComment = '';
    public $showCommunicationTimeline = true;

    protected $listeners = [
        'filesUploaded' => 'refreshData',
        'fileDeleted' => '$refresh',
    ];

    protected $rules = [
        'responseToFeedback' => 'nullable|string|max:5000',
        'newComment' => 'required|string|max:2000',
    ];

    public function mount(Project $project)
    {
        // Verify this is a client management project
        if (!$project->isClientManagement()) {
            abort(404, 'This page is only available for client management projects.');
        }

        // Authorization check
        try {
            $this->authorize('update', $project);
        } catch (\Exception $e) {
            abort(403, 'You are not authorized to manage this project.');
        }

        $this->project = $project;
        
        // Load the associated pitch (should be exactly one for client management)
        $this->pitch = $project->pitches()
            ->where('user_id', $project->user_id)
            ->with(['files', 'events.user'])
            ->firstOrFail();

        $this->updateStorageInfo();
        $this->loadStatusFeedback();
        $this->checkResubmissionEligibility();
    }

    public function render()
    {
        return view('livewire.project.manage-client-project')
            ->layout('components.layouts.app');
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
     * Load status feedback message if applicable
     */
    protected function loadStatusFeedback()
    {
        if (in_array($this->pitch->status, [Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_DENIED])) {
            $this->statusFeedbackMessage = $this->getLatestStatusFeedback();
        }
    }

    /**
     * Check if the producer can resubmit based on file changes
     */
    protected function checkResubmissionEligibility()
    {
        // If pitch is in READY_FOR_REVIEW, check if files have been modified since submission
        if ($this->pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW) {
            $lastSubmissionTime = $this->pitch->events()
                ->where('event_type', 'status_change')
                ->where('status', \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
                ->latest()
                ->first()?->created_at;

            if ($lastSubmissionTime) {
                // Check if any files were added/modified after submission
                $filesModifiedAfterSubmission = $this->pitch->files()
                    ->where('created_at', '>', $lastSubmissionTime)
                    ->exists();

                $this->canResubmit = $filesModifiedAfterSubmission;
            }
        }
    }

    /**
     * Get the latest feedback message from events
     */
    protected function getLatestStatusFeedback(): ?string
    {
        $latestEvent = $this->pitch->events()
            ->whereIn('event_type', ['revisions_requested', 'pitch_denied'])
            ->latest()
            ->first();

        return $latestEvent ? $latestEvent->comment : null;
    }

    /**
     * Refresh component data after file uploads
     */
    public function refreshData()
    {
        $this->pitch->refresh();
        $this->updateStorageInfo();
        $this->checkResubmissionEligibility(); // Check if new files enable resubmission
    }

    /**
     * Recall submission and return to in progress status
     */
    public function recallSubmission()
    {
        // Authorization check
        $this->authorize('recallSubmission', $this->pitch);

        // Validation: Can only recall from READY_FOR_REVIEW status
        if ($this->pitch->status !== \App\Models\Pitch::STATUS_READY_FOR_REVIEW) {
            Toaster::error('Can only recall submissions that are ready for review.');
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                // Update pitch status
                $this->pitch->status = \App\Models\Pitch::STATUS_IN_PROGRESS;
                $this->pitch->save();

                // Create event to track the recall
                $this->pitch->events()->create([
                    'event_type' => 'submission_recalled',
                    'comment' => 'Producer recalled submission to make changes.',
                    'status' => $this->pitch->status,
                    'created_by' => auth()->id(),
                ]);
            });

            $this->pitch->refresh();
            $this->checkResubmissionEligibility();
            
            Toaster::success('Submission recalled successfully. You can now make changes and resubmit.');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error recalling submission', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage()
            ]);
            Toaster::error('Failed to recall submission. Please try again.');
        }
    }

    /**
     * Submit pitch for client review
     */
    public function submitForReview(PitchWorkflowService $pitchWorkflowService)
    {
        $this->authorize('submitForReview', $this->pitch);
        $this->validateOnly('responseToFeedback');

        try {
            $pitchWorkflowService->submitPitchForReview($this->pitch, Auth::user(), $this->responseToFeedback);
            
            Toaster::success('Pitch submitted for client review successfully.');
            $this->responseToFeedback = '';
            $this->pitch->refresh();
            $this->loadStatusFeedback();

        } catch (\Exception $e) {
            Log::warning('Pitch submission failed', [
                'pitch_id' => $this->pitch->id, 
                'user_id' => Auth::id(), 
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
        }
    }

    /**
     * Download a producer file (pitch file)
     */
    public function downloadFile($fileId, FileManagementService $fileManagementService)
    {
        $file = $this->pitch->files()->findOrFail($fileId);
        
        try {
            $this->authorize('download', $file);
            $downloadUrl = $fileManagementService->getTemporaryDownloadUrl($file);
            $this->redirect($downloadUrl);
        } catch (\Exception $e) {
            Toaster::error('Unable to download file.');
        }
    }

    /**
     * Download a client file (project file)
     */
    public function downloadClientFile($fileId, FileManagementService $fileManagementService)
    {
        $file = $this->project->files()->findOrFail($fileId);
        
        try {
            $this->authorize('download', $file);
            $downloadUrl = $fileManagementService->getTemporaryDownloadUrl($file);
            $this->redirect($downloadUrl);
        } catch (\Exception $e) {
            Toaster::error('Unable to download client file.');
        }
    }

    /**
     * Confirm file deletion
     */
    public function confirmDeleteFile($fileId)
    {
        $file = $this->pitch->files()->findOrFail($fileId);
        
        try {
            $this->authorize('deleteFile', $file);
            $this->fileIdToDelete = $fileId;
            $this->showDeleteModal = true;
        } catch (\Exception $e) {
            Toaster::error('You are not authorized to delete this file.');
        }
    }

    /**
     * Cancel file deletion
     */
    public function cancelDeleteFile()
    {
        $this->showDeleteModal = false;
        $this->fileIdToDelete = null;
    }

    /**
     * Delete a file
     */
    public function deleteFile(FileManagementService $fileManagementService)
    {
        if (!$this->fileIdToDelete) {
            return;
        }

        $file = $this->pitch->files()->findOrFail($this->fileIdToDelete);
        
        try {
            $this->authorize('deleteFile', $file);
            $fileManagementService->deletePitchFile($file, Auth::user());
            
            Toaster::success("File '{$file->file_name}' deleted successfully.");
            $this->updateStorageInfo();
            $this->cancelDeleteFile();
            
        } catch (FileDeletionException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error deleting file', ['file_id' => $this->fileIdToDelete, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while deleting the file.');
        }
    }

    /**
     * Resend client invite
     */
    public function resendClientInvite(NotificationService $notificationService)
    {
        try {
            $this->authorize('update', $this->project);
            
            // Generate new signed URL
            $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'client.portal.view',
                now()->addDays(config('mixpitch.client_portal_link_expiry_days', 7)),
                ['project' => $this->project->id]
            );

            // Resend notification
            $notificationService->notifyClientProjectInvite($this->project, $signedUrl);
            
            Toaster::success('Client invite resent successfully.');
            
        } catch (\Exception $e) {
            Log::error('Error resending client invite', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage()
            ]);
            Toaster::error('Failed to resend client invite.');
        }
    }

    /**
     * Preview client portal (for testing purposes)
     */
    public function previewClientPortal()
    {
        try {
            $this->authorize('update', $this->project);
            
            // Generate signed URL (same as what gets sent to clients)
            $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'client.portal.view',
                now()->addDays(config('mixpitch.client_portal_link_expiry_days', 7)),
                ['project' => $this->project->id]
            );

            // Log the URL for development
            Log::info('🔗 CLIENT PORTAL PREVIEW', [
                'project_id' => $this->project->id,
                'portal_url' => $signedUrl,
                'expires_at' => now()->addDays(config('mixpitch.client_portal_link_expiry_days', 7))->toDateTimeString(),
                'accessed_by' => auth()->user()->name . ' (Project Owner)'
            ]);

            // Redirect to the client portal
            return redirect($signedUrl);
            
        } catch (\Exception $e) {
            Log::error('Error generating client portal preview', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage()
            ]);
            Toaster::error('Failed to generate client portal preview.');
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
                'error' => $e->getMessage()
            ]);
            Toaster::error('Failed to delete project. Please try again.');
        }
    }

    /**
     * Get conversation items for communication timeline
     */
    public function getConversationItemsProperty()
    {
        $items = collect();
        
        // Get all relevant events for client management communication
        $events = $this->pitch->events()
            ->whereIn('event_type', [
                'client_comment', 
                'producer_comment', 
                'status_change', 
                'client_approved', 
                'client_revisions_requested',
                'submission_recalled',
                'file_uploaded'
            ])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        
        foreach ($events as $event) {
            $items->push([
                'type' => $this->getEventDisplayType($event),
                'content' => $this->getEventDisplayContent($event),
                'date' => $event->created_at,
                'user' => $event->user,
                'metadata' => $event->metadata,
                'event_type' => $event->event_type,
                'event' => $event
            ]);
        }
        
        return $items;
    }

    /**
     * Add a producer comment visible to the client
     */
    public function addProducerComment()
    {
        $this->validate(['newComment' => 'required|string|max:2000']);
        
        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                // Create producer comment event
                $this->pitch->events()->create([
                    'event_type' => 'producer_comment',
                    'comment' => $this->newComment,
                    'status' => $this->pitch->status,
                    'created_by' => auth()->id(),
                    'metadata' => [
                        'visible_to_client' => true,
                        'comment_type' => 'producer_update'
                    ]
                ]);
                
                // Notify client if project has client email
                if ($this->project->client_email) {
                    app(NotificationService::class)->notifyClientProducerCommented(
                        $this->pitch, 
                        $this->newComment
                    );
                }
            });
            
            $this->newComment = '';
            $this->pitch->refresh();
            
            Toaster::success('Message sent to client successfully.');
            
        } catch (\Exception $e) {
            Log::error('Failed to add producer comment', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage()
            ]);
            Toaster::error('Failed to send message. Please try again.');
        }
    }

    /**
     * Get display type for event
     */
    protected function getEventDisplayType($event): string
    {
        return match($event->event_type) {
            'client_comment' => 'client_message',
            'producer_comment' => 'producer_message',
            'client_approved' => 'approval',
            'client_revisions_requested' => 'revision_request',
            'submission_recalled' => 'recall',
            'status_change' => 'status_update',
            'file_uploaded' => 'file_activity',
            default => 'general'
        };
    }

    /**
     * Get display content for event
     */
    protected function getEventDisplayContent($event): string
    {
        if (!empty($event->comment)) {
            return $event->comment;
        }

        // Generate default content based on event type
        return match($event->event_type) {
            'client_approved' => 'Client approved the submission',
            'submission_recalled' => 'Producer recalled submission to make changes',
            'file_uploaded' => 'Files were uploaded',
            'status_change' => 'Project status was updated',
            default => 'Project activity occurred'
        };
    }

    /**
     * Get event border color for timeline display
     */
    public function getEventBorderColor($item): string
    {
        return match($item['type']) {
            'client_message' => 'border-l-blue-400',
            'producer_message' => 'border-l-purple-400',
            'approval' => 'border-l-green-400',
            'revision_request' => 'border-l-amber-400',
            'recall' => 'border-l-orange-400',
            'status_update' => 'border-l-gray-400',
            'file_activity' => 'border-l-indigo-400',
            default => 'border-l-gray-300'
        };
    }

    /**
     * Get event background color for timeline icons
     */
    public function getEventBgColor($item): string
    {
        return match($item['type']) {
            'client_message' => 'bg-blue-500',
            'producer_message' => 'bg-purple-500',
            'approval' => 'bg-green-500',
            'revision_request' => 'bg-amber-500',
            'recall' => 'bg-orange-500',
            'status_update' => 'bg-gray-500',
            'file_activity' => 'bg-indigo-500',
            default => 'bg-gray-400'
        };
    }

    /**
     * Get event icon for timeline display
     */
    public function getEventIcon($item): string
    {
        return match($item['type']) {
            'client_message' => 'fas fa-comment',
            'producer_message' => 'fas fa-reply',
            'approval' => 'fas fa-check',
            'revision_request' => 'fas fa-edit',
            'recall' => 'fas fa-undo',
            'status_update' => 'fas fa-exchange-alt',
            'file_activity' => 'fas fa-file',
            default => 'fas fa-circle'
        };
    }

    /**
     * Get event title for timeline display
     */
    public function getEventTitle($item): string
    {
        return match($item['type']) {
            'client_message' => 'Client Message',
            'producer_message' => 'Your Message',
            'approval' => 'Client Approval',
            'revision_request' => 'Revision Request',
            'recall' => 'Submission Recalled',
            'status_update' => 'Status Update',
            'file_activity' => 'File Activity',
            default => 'Activity'
        };
    }

    /**
     * Get status color for activity dashboard
     */
    public function getStatusColor($status): string
    {
        return match($status) {
            \App\Models\Pitch::STATUS_IN_PROGRESS => 'bg-blue-500',
            \App\Models\Pitch::STATUS_READY_FOR_REVIEW => 'bg-purple-500',
            \App\Models\Pitch::STATUS_REVISIONS_REQUESTED => 'bg-amber-500',
            \App\Models\Pitch::STATUS_APPROVED => 'bg-green-500',
            \App\Models\Pitch::STATUS_DENIED => 'bg-red-500',
            \App\Models\Pitch::STATUS_COMPLETED => 'bg-emerald-500',
            default => 'bg-gray-500'
        };
    }

    /**
     * Get status icon for activity dashboard
     */
    public function getStatusIcon($status): string
    {
        return match($status) {
            \App\Models\Pitch::STATUS_IN_PROGRESS => 'fas fa-cog',
            \App\Models\Pitch::STATUS_READY_FOR_REVIEW => 'fas fa-eye',
            \App\Models\Pitch::STATUS_REVISIONS_REQUESTED => 'fas fa-edit',
            \App\Models\Pitch::STATUS_APPROVED => 'fas fa-check',
            \App\Models\Pitch::STATUS_DENIED => 'fas fa-times',
            \App\Models\Pitch::STATUS_COMPLETED => 'fas fa-trophy',
            default => 'fas fa-question'
        };
    }

    /**
     * Format file size for display
     */
    public function formatFileSize($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get client-uploaded files (project files)
     */
    public function getClientFilesProperty()
    {
        return $this->project->files()->with('project')->get();
    }

    /**
     * Get producer-uploaded files (pitch files)
     */
    public function getProducerFilesProperty()
    {
        return $this->pitch->files()->with('pitch')->get();
    }

    /**
     * Get total file count for both types
     */
    public function getTotalFileCountProperty()
    {
        return $this->clientFiles->count() + $this->producerFiles->count();
    }

    /**
     * Delete a client-uploaded file (project file)
     */
    public function deleteClientFile(FileManagementService $fileService)
    {
        if (!$this->clientFileIdToDelete) {
            return;
        }

        try {
            $file = $this->project->files()->findOrFail($this->clientFileIdToDelete);
            $this->authorize('delete', $file);
            
            $fileName = $file->file_name;
            $fileService->deleteProjectFile($file);
            $this->updateStorageInfo();
            
            Toaster::success("Client file '{$fileName}' deleted successfully.");
            $this->cancelDeleteClientFile();
            
        } catch (\Exception $e) {
            Log::error('Client file deletion failed', [
                'file_id' => $this->clientFileIdToDelete,
                'project_id' => $this->project->id,
                'error' => $e->getMessage()
            ]);
            Toaster::error('Unable to delete file.');
        }
    }

    /**
     * Confirm deletion of a client-uploaded file
     */
    public function confirmDeleteClientFile($fileId)
    {
        try {
            $file = $this->project->files()->findOrFail($fileId);
            $this->authorize('delete', $file);
            
            // Show confirmation modal
            $this->clientFileIdToDelete = $fileId;
            $this->clientFileNameToDelete = $file->file_name;
            $this->showDeleteClientFileModal = true;
            
        } catch (\Exception $e) {
            Log::error('Client file deletion confirmation failed', [
                'file_id' => $fileId,
                'project_id' => $this->project->id,
                'error' => $e->getMessage()
            ]);
            Toaster::error('Unable to delete file.');
        }
    }

    /**
     * Cancel client file deletion
     */
    public function cancelDeleteClientFile()
    {
        $this->showDeleteClientFileModal = false;
        $this->clientFileIdToDelete = null;
        $this->clientFileNameToDelete = '';
    }
} 