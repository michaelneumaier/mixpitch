<?php

namespace App\Livewire\Project;

use App\Exceptions\File\FileDeletionException;
use App\Models\Pitch;
use App\Models\Project;
use App\Services\FileManagementService;
use App\Services\NotificationService;
use App\Services\PitchWorkflowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class ManageClientProject extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public Pitch $pitch;

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
    public $statusFeedbackMessage = null;

    public $canResubmit = false; // Track if files have changed since last submission

    // Communication features
    public $newComment = '';

    public $showCommunicationTimeline = true;

    // Component refresh control
    public int $refreshKey = 0;

    public $fileListKey;

    // Watermarking controls
    public $watermarkingEnabled = false;

    public $showWatermarkingInfo = false;

    // Version navigation
    public ?int $selectedSnapshotId = null; // null = working version (unsaved)

    public bool $viewingHistory = false; // Flag for UI indicators

    // Email preferences
    public array $producerEmailPreferences = [];

    protected $listeners = [
        'filesUploaded' => '$refresh',
        'fileDeleted' => '$refresh',
        'milestonesUpdated' => '$refresh',
        'budgetUpdated' => '$refresh',
        'refreshClientFiles' => '$refresh',
        'pitchStatusChanged' => 'refreshPitchStatus',
        'requestCommentsRefresh' => 'refreshCommentsForFileList',
    ];

    protected $rules = [
        'newComment' => 'required|string|max:2000',
    ];

    public function mount(Project $project)
    {
        // Verify this is a client management project
        if (! $project->isClientManagement()) {
            abort(404, 'This page is only available for client management projects.');
        }

        // Initialize file list key
        $this->fileListKey = time();

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

        $this->loadStatusFeedback();
        $this->checkResubmissionEligibility();

        // Initialize email preferences
        $this->producerEmailPreferences = $this->project->producer_email_preferences ?? $this->project->getDefaultEmailPreferences();

        // Initialize watermarking preference
        $this->watermarkingEnabled = $this->pitch->watermarking_enabled ?? false;
    }

    /**
     * Handle pitch status changes from child components
     */
    public function refreshPitchStatus()
    {
        $this->pitch->refresh();
        $this->loadStatusFeedback();
    }

    /**
     * Handle file list comment refresh requests
     */
    public function refreshCommentsForFileList($data)
    {
        // Verify the request is for our pitch files
        if ($data['modelType'] === 'pitch' && $data['modelId'] === $this->pitch->id) {
            \Illuminate\Support\Facades\Log::info('🔄 Refreshing comments for FileList', [
                'pitch_id' => $this->pitch->id,
                'refresh_key_before' => $this->refreshKey,
            ]);

            // Clear all comment-related cached properties to force fresh data
            unset($this->fileCommentsData);
            unset($this->fileCommentsSummary);
            unset($this->fileCommentsTotals);

            // Refresh the pitch to get latest data
            $this->pitch->refresh();

            // Increment refresh key to force FileList component re-render
            $this->refreshKey++;

            \Illuminate\Support\Facades\Log::info('🔄 Comment refresh completed', [
                'pitch_id' => $this->pitch->id,
                'refresh_key_after' => $this->refreshKey,
            ]);

            // Force component re-render by dispatching refresh to self
            $this->dispatch('$refresh');
        }
    }

    /**
     * Refresh only comment data without full component refresh to preserve Alpine.js state
     */
    public function refreshCommentsOnly(): void
    {
        // Clear comment-related cached properties to force fresh data
        unset($this->fileCommentsData);
        unset($this->fileCommentsSummary);
        unset($this->fileCommentsTotals);

        // Refresh the pitch to get latest comment data
        $this->pitch->refresh();

        // Increment refresh key to force FileList component re-render and preserve Alpine.js state
        $this->refreshKey++;

        // This preserves Alpine.js state including comment expansion
    }

    public function render()
    {
        return view('livewire.project.manage-client-project')->layout('components.layouts.app-sidebar');
    }

    /**
     * Load status feedback message if applicable
     */
    protected function loadStatusFeedback()
    {
        if (in_array($this->pitch->status, [Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, Pitch::STATUS_DENIED])) {
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
            ->whereIn('event_type', ['revisions_requested', 'client_revisions_requested', 'pitch_denied'])
            ->latest()
            ->first();

        return $latestEvent ? $latestEvent->comment : null;
    }

    /**
     * Get the latest feedback event with metadata
     */
    protected function getLatestFeedbackEvent(): ?\App\Models\PitchEvent
    {
        return $this->pitch->events()
            ->whereIn('event_type', ['revisions_requested', 'client_revisions_requested', 'pitch_denied'])
            ->latest()
            ->first();
    }

    /**
     * Refresh component data after file uploads
     */
    public function refreshData()
    {
        $this->pitch->refresh();
        $this->checkResubmissionEligibility(); // Check if new files enable resubmission
    }

    public function handleFileUpload()
    {
        $this->refreshData();

        // Dispatch a browser event to reinitialize Alpine components
        $this->dispatch('alpine-reinit');
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
                // Mark current snapshot as cancelled if it exists and is pending
                $currentSnapshot = $this->pitch->currentSnapshot;
                if ($currentSnapshot && $currentSnapshot->status === \App\Models\PitchSnapshot::STATUS_PENDING) {
                    $currentSnapshot->status = \App\Models\PitchSnapshot::STATUS_CANCELLED;
                    $currentSnapshot->save();

                    \Illuminate\Support\Facades\Log::info('Marked snapshot as CANCELLED when recalling submission.', [
                        'pitch_id' => $this->pitch->id,
                        'snapshot_id' => $currentSnapshot->id,
                    ]);
                }

                // Update pitch status and clear current snapshot reference
                $this->pitch->status = \App\Models\Pitch::STATUS_IN_PROGRESS;
                $this->pitch->current_snapshot_id = null;
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
                'error' => $e->getMessage(),
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

        try {
            // Update watermarking preference before submission
            $this->pitch->update([
                'watermarking_enabled' => $this->watermarkingEnabled,
            ]);

            // No response to feedback on submission - that's handled separately
            $pitchWorkflowService->submitPitchForReview($this->pitch, Auth::user(), '');

            Toaster::success('Pitch submitted for client review successfully.');
            $this->pitch->refresh();
            $this->loadStatusFeedback();

        } catch (\Exception $e) {
            Log::warning('Pitch submission failed', [
                'pitch_id' => $this->pitch->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
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
     * Play a client file (project file) in the global audio player
     */
    public function playFile($fileId)
    {
        try {
            $file = $this->project->files()->findOrFail($fileId);
            $this->authorize('view', $file);

            // Check if it's an audio file
            if (! $file->isAudioFile()) {
                Toaster::error('Only audio files can be played.');

                return;
            }

            // Dispatch event to play in global player
            $this->dispatch('playProjectFile', projectFileId: $file->id);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Toaster::error('File not found.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to play this file.');
        } catch (\Exception $e) {
            Log::error('Error playing client file', [
                'file_id' => $fileId,
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Could not play file: '.$e->getMessage());
        }
    }

    /**
     * Play a pitch file in the global audio player
     */
    public function playPitchFile($fileId)
    {
        try {
            $file = $this->pitch->files()->findOrFail($fileId);
            $this->authorize('view', $file);

            // Check if it's an audio file
            if (! $file->isAudioFile()) {
                Toaster::error('Only audio files can be played.');

                return;
            }

            // Dispatch event to play in global player
            $this->dispatch('playPitchFile', pitchFileId: $file->id);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Toaster::error('File not found.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to play this file.');
        } catch (\Exception $e) {
            Log::error('Error playing pitch file', [
                'file_id' => $fileId,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Could not play file: '.$e->getMessage());
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
        if (! $this->fileIdToDelete) {
            return;
        }

        $file = $this->pitch->files()->findOrFail($this->fileIdToDelete);

        try {
            $this->authorize('deleteFile', $file);
            $fileManagementService->deletePitchFile($file);

            Toaster::success("File '{$file->file_name}' deleted successfully.");
            $this->dispatch('fileDeleted');
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
                'error' => $e->getMessage(),
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

            // Use the dedicated preview route for project owners
            $previewUrl = route('client.portal.preview', ['project' => $this->project->id]);

            // Log the preview access
            Log::info('🔍 CLIENT PORTAL PREVIEW REQUESTED', [
                'project_id' => $this->project->id,
                'preview_url' => $previewUrl,
                'requested_by' => auth()->user()->name.' (Project Owner)',
            ]);

            // Redirect to the preview route
            return redirect($previewUrl);

        } catch (\Exception $e) {
            Log::error('Error accessing client portal preview', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to access client portal preview.');
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
                'file_uploaded',
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
                'event' => $event,
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
                        'comment_type' => 'producer_update',
                    ],
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

            // Dispatch event to close the message form
            $this->dispatch('messageAdded');

        } catch (\Exception $e) {
            Log::error('Failed to add producer comment', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to send message. Please try again.');
        }
    }

    /**
     * Delete a producer comment
     */
    public function deleteProducerComment($eventId)
    {
        try {
            $event = $this->pitch->events()->findOrFail($eventId);

            // Verify this is a producer comment and belongs to current user
            if ($event->event_type !== 'producer_comment' || $event->created_by !== auth()->id()) {
                Toaster::error('You can only delete your own messages.');

                return;
            }

            $event->delete();
            $this->pitch->refresh();

            Toaster::success('Message deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete producer comment', [
                'event_id' => $eventId,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to delete message. Please try again.');
        }
    }

    /**
     * Get display type for event
     */
    protected function getEventDisplayType($event): string
    {
        return match ($event->event_type) {
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
        if (! empty($event->comment)) {
            return $event->comment;
        }

        // Generate default content based on event type
        return match ($event->event_type) {
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
        return match ($item['type']) {
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
        return match ($item['type']) {
            'client_message' => 'bg-blue-500',
            'producer_message' => 'bg-purple-500',
            'approval' => 'bg-green-500',
            'revision_request' => 'bg-amber-500',
            'recall' => 'bg-orange-300',
            'status_update' => 'bg-gray-300',
            'file_activity' => 'bg-indigo-300',
            default => 'bg-gray-300'
        };
    }

    /**
     * Get event icon color for timeline display (ensures contrast)
     */
    public function getEventIconColor($item): string
    {
        return match ($item['type']) {
            'client_message', 'producer_message', 'approval', 'revision_request' => 'text-white',
            'recall', 'status_update', 'file_activity' => 'text-gray-700',
            default => 'text-gray-700'
        };
    }

    /**
     * Get event icon for timeline display
     */
    public function getEventIcon($item): string
    {
        return match ($item['type']) {
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
        return match ($item['type']) {
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
        return match ($status) {
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
        return match ($status) {
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
     * Get snapshot history for version navigation
     */
    public function getSnapshotHistoryProperty()
    {
        return $this->pitch->snapshots()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($snapshot) {
                $fileIds = $snapshot->snapshot_data['file_ids'] ?? [];

                return [
                    'id' => $snapshot->id,
                    'version' => $snapshot->snapshot_data['version'] ?? $snapshot->version,
                    'status' => $snapshot->status,
                    'status_label' => $snapshot->status_label,
                    'submitted_at' => $snapshot->created_at,
                    'file_count' => count($fileIds),
                    'response_to_feedback' => $snapshot->snapshot_data['response_to_feedback'] ?? null,
                ];
            });
    }

    /**
     * Get the display files based on selected version
     */
    public function getDisplayFilesProperty()
    {
        // If viewing a historical snapshot, return files from that snapshot
        if ($this->selectedSnapshotId !== null) {
            $snapshot = $this->pitch->snapshots()->find($this->selectedSnapshotId);

            if ($snapshot) {
                $fileIds = $snapshot->snapshot_data['file_ids'] ?? [];

                return $this->pitch->files()->whereIn('id', $fileIds)->with('pitch')->get();
            }
        }

        // Default: return all current files (working version)
        return $this->producerFiles;
    }

    /**
     * Get current version label for display
     */
    public function getCurrentVersionLabelProperty()
    {
        if ($this->selectedSnapshotId === null) {
            return 'Working Version (V'.$this->getNextVersionNumber().')';
        }

        $snapshot = collect($this->snapshotHistory)->firstWhere('id', $this->selectedSnapshotId);

        if ($snapshot) {
            return 'Version '.$snapshot['version'].' - '.$snapshot['status_label'];
        }

        return 'Unknown Version';
    }

    /**
     * Get the next version number (for unsaved working version)
     */
    public function getNextVersionNumber(): int
    {
        $latestSnapshot = $this->pitch->snapshots()->orderBy('created_at', 'desc')->first();

        if ($latestSnapshot) {
            $latestVersion = $latestSnapshot->snapshot_data['version'] ?? $latestSnapshot->version ?? 1;

            return $latestVersion + 1;
        }

        return 1; // First version if no snapshots exist
    }

    /**
     * Switch to a specific version (null = working version)
     */
    public function switchToVersion(?int $snapshotId): void
    {
        $this->selectedSnapshotId = $snapshotId;
        $this->viewingHistory = $snapshotId !== null;

        // Refresh the file list display
        $this->refreshKey++;
    }

    /**
     * Get file comments data for the file-list component
     * Updated to use the new file_comments table with polymorphic relationships
     */
    public function getFileCommentsDataProperty()
    {
        // Get all pitch files for this pitch
        $pitchFileIds = $this->pitch->files()->pluck('id')->toArray();

        // Get comments for all pitch files using the new file_comments system
        // Only fetch parent comments (not replies) - replies are loaded via the 'replies.user' relationship
        return \App\Models\FileComment::where('commentable_type', \App\Models\PitchFile::class)
            ->whereIn('commentable_id', $pitchFileIds)
            ->whereNull('parent_id') // Only parent comments, not replies
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($comment) {
                // Map the FileComment to match the expected structure
                // Add client_name and producer_name for backward compatibility
                $comment->client_name = $this->project->client_name ?: 'Client';
                $comment->producer_name = $comment->user->name ?? 'Producer';

                // Add metadata structure to match old system
                $comment->metadata = [
                    'file_id' => $comment->commentable_id,
                    'client_name' => $comment->client_name,
                    'producer_name' => $comment->producer_name,
                    'type' => $comment->is_client_comment ? 'client_comment' : 'producer_comment',
                ];

                // Add event_type for compatibility with old template logic
                $comment->event_type = $comment->is_client_comment ? 'client_file_comment' : 'producer_comment';

                return $comment;
            });
    }

    /**
     * Get file comments summary grouped by file for feedback overview
     */
    public function getFileCommentsSummaryProperty()
    {
        $comments = $this->fileCommentsData;
        $files = $this->pitch->files()->get()->keyBy('id');

        return $comments->groupBy('commentable_id')->map(function ($fileComments, $fileId) use ($files) {
            $file = $files->get($fileId);
            if (! $file) {
                return null;
            }

            $unresolvedCount = $fileComments->where('resolved', false)->count();
            $resolvedCount = $fileComments->where('resolved', true)->count();
            $totalCount = $fileComments->count();

            // Get the most recent unresolved comment for preview
            $latestUnresolved = $fileComments->where('resolved', false)->sortByDesc('created_at')->first();

            return [
                'file' => $file,
                'total_comments' => $totalCount,
                'unresolved_count' => $unresolvedCount,
                'resolved_count' => $resolvedCount,
                'latest_unresolved' => $latestUnresolved,
                'needs_attention' => $unresolvedCount > 0,
            ];
        })->filter()->values(); // Remove null entries and reset keys
    }

    /**
     * Get file comments summary totals for template display
     */
    public function getFileCommentsTotalsProperty()
    {
        $summary = $this->fileCommentsSummary;

        return [
            'unresolved' => $summary->sum('unresolved_count'),
            'total' => $summary->sum('total_comments'),
        ];
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
        if (! $this->clientFileIdToDelete) {
            return;
        }

        try {
            $file = $this->project->files()->findOrFail($this->clientFileIdToDelete);
            $this->authorize('delete', $file);

            $fileName = $file->file_name;
            $fileService->deleteProjectFile($file);

            Toaster::success("Client file '{$fileName}' deleted successfully.");
            $this->cancelDeleteClientFile();

        } catch (\Exception $e) {
            Log::error('Client file deletion failed', [
                'file_id' => $this->clientFileIdToDelete,
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
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
                'error' => $e->getMessage(),
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

    /**
     * Livewire hook: Called when a property is updated
     */
    public function updatedWatermarkingEnabled($value)
    {
        // Save preference immediately when toggled
        $this->pitch->update([
            'watermarking_enabled' => $value,
        ]);

        $this->dispatch('watermarking-toggled', [
            'enabled' => $value,
        ]);

        Toaster::success($value ? 'Audio protection enabled' : 'Audio protection disabled');
    }

    /**
     * Get audio files that would be affected by watermarking
     */
    public function getAudioFilesProperty()
    {
        return $this->producerFiles->filter(function ($file) {
            return in_array(pathinfo($file->file_name, PATHINFO_EXTENSION), ['mp3', 'wav', 'm4a', 'aac', 'flac']);
        });
    }

    /**
     * Format file size for display
     */
    public function formatFileSize(int $bytes, int $precision = 2): string
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

    // ----- File List Component Event Handlers -----

    /**
     * Handle file action events from file-list component
     */
    #[On('fileAction')]
    public function handleFileAction($data)
    {
        $action = $data['action'];
        $fileId = $data['fileId'];
        $modelType = $data['modelType'] ?? null;

        // Route to appropriate handler based on action and model type
        if ($modelType === 'project') {
            // Client files (project files)
            switch ($action) {
                case 'playFile':
                    $this->playFile($fileId);
                    break;
                case 'downloadClientFile':
                    $this->downloadClientFile($fileId, app(FileManagementService::class));
                    break;
                case 'confirmDeleteClientFile':
                    $this->confirmDeleteClientFile($fileId);
                    break;
            }
        } elseif ($modelType === 'pitch') {
            // Producer files (pitch files)
            switch ($action) {
                case 'playPitchFile':
                    $this->playPitchFile($fileId);
                    break;
                case 'downloadFile':
                    $this->downloadFile($fileId, app(FileManagementService::class));
                    break;
                case 'confirmDeleteFile':
                    $this->confirmDeleteFile($fileId);
                    break;
            }
        }
    }

    /**
     * Handle file list refresh requests from file-list component
     */
    #[On('fileListRefreshRequested')]
    public function handleFileListRefresh($data)
    {
        $modelType = $data['modelType'] ?? null;
        $source = $data['source'] ?? 'unknown';

        // Refresh the appropriate file collection
        if ($modelType === 'project') {
            // Refresh client files by re-fetching the property
            $this->project->load('files');
        }

        // Log the refresh for debugging if needed
        Log::info('File list refreshed', [
            'model_type' => $modelType,
            'source' => $source,
            'project_id' => $this->project->id,
        ]);
    }

    /**
     * Handle comment action events from file-list component
     */
    #[On('commentAction')]
    public function handleCommentAction($data)
    {
        $action = $data['action'];
        $commentId = $data['commentId'] ?? null;
        $response = $data['response'] ?? null;
        $modelType = $data['modelType'] ?? null;

        switch ($action) {
            case 'markFileCommentResolved':
                $this->markFileCommentResolved($commentId);
                break;
            case 'respondToFileComment':
                $this->respondToFileComment($commentId, $response);
                break;
            case 'createFileComment':
                $fileId = $data['fileId'] ?? null;
                $comment = $data['comment'] ?? null;
                $this->createFileComment($fileId, $comment);
                break;
            case 'deleteFileComment':
                $this->deleteFileComment($commentId);
                break;
            case 'unresolveFileComment':
                $this->unresolveFileComment($commentId);
                break;
        }
    }

    /**
     * Mark a file comment as resolved using the new FileComment system
     */
    public function markFileCommentResolved($commentId)
    {
        try {
            $comment = \App\Models\FileComment::findOrFail($commentId);

            // Verify the comment belongs to a file in this pitch
            $pitchFileIds = $this->pitch->files()->pluck('id')->toArray();

            if ($comment->commentable_type !== \App\Models\PitchFile::class ||
                ! in_array($comment->commentable_id, $pitchFileIds)) {
                throw new \Exception('Comment does not belong to this pitch');
            }

            // Mark the comment as resolved
            $comment->update(['resolved' => true]);

            $this->pitch->refresh();

            // Force refresh by clearing cached properties
            unset($this->fileCommentsData);

            // Dispatch event to refresh the file-list component using consistent refresh mechanism
            $this->dispatch('requestCommentsRefresh', [
                'modelType' => 'pitch',
                'modelId' => $this->pitch->id,
            ]);

            Toaster::success('Comment marked as addressed.');
        } catch (\Exception $e) {
            Log::error('Failed to mark comment as resolved', [
                'comment_id' => $commentId,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to mark comment as addressed.');
        }
    }

    /**
     * Respond to a file comment using the new FileComment system
     */
    public function respondToFileComment($commentId, $response)
    {
        try {
            $originalComment = \App\Models\FileComment::findOrFail($commentId);

            // Verify the comment belongs to a file in this pitch
            $pitchFileIds = $this->pitch->files()->pluck('id')->toArray();

            if ($originalComment->commentable_type !== \App\Models\PitchFile::class ||
                ! in_array($originalComment->commentable_id, $pitchFileIds)) {
                throw new \Exception('Comment does not belong to this pitch');
            }

            // Create a reply to the original comment
            \App\Models\FileComment::create([
                'commentable_type' => $originalComment->commentable_type,
                'commentable_id' => $originalComment->commentable_id,
                'user_id' => auth()->id(),
                'parent_id' => $commentId, // This makes it a reply
                'comment' => trim($response),
                'timestamp' => 0.0,
                'resolved' => false,
                'is_client_comment' => false, // Producer response
                'client_email' => null,
            ]);

            // Mark original comment as resolved since it was responded to
            $originalComment->update(['resolved' => true]);

            $this->pitch->refresh();

            // Force refresh by clearing cached properties
            unset($this->fileCommentsData);

            // Dispatch event to refresh the file-list component using consistent refresh mechanism
            $this->dispatch('requestCommentsRefresh', [
                'modelType' => 'pitch',
                'modelId' => $this->pitch->id,
            ]);

            Toaster::success('Response sent successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to respond to comment', [
                'comment_id' => $commentId,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to send response.');
        }
    }

    /**
     * Create a new comment on a file using the new FileComment system
     */
    public function createFileComment($fileId, $comment)
    {
        if (empty(trim($comment))) {
            Toaster::error('Comment cannot be empty.');

            return;
        }

        try {
            // Verify the file belongs to this pitch
            $file = $this->pitch->files()->findOrFail($fileId);

            // Create the comment using the new FileComment model
            \App\Models\FileComment::create([
                'commentable_type' => \App\Models\PitchFile::class,
                'commentable_id' => $fileId,
                'user_id' => auth()->id(),
                'comment' => trim($comment),
                'timestamp' => 0.0, // Can be set later if needed for audio player integration
                'resolved' => false,
                'is_client_comment' => false, // Producer comment
                'client_email' => null,
            ]);

            $this->pitch->refresh();

            // Force refresh by clearing cached properties
            unset($this->fileCommentsData);

            // Dispatch event to update comments display using consistent refresh mechanism
            $this->dispatch('requestCommentsRefresh', [
                'modelType' => 'pitch',
                'modelId' => $this->pitch->id,
            ]);

            Toaster::success('Comment added successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create file comment', [
                'file_id' => $fileId,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to add comment.');
        }
    }

    /**
     * Delete a file comment using the new FileComment system
     */
    public function deleteFileComment($commentId)
    {
        try {
            $comment = \App\Models\FileComment::findOrFail($commentId);

            // Verify the comment belongs to a file in this pitch
            $pitchFileIds = $this->pitch->files()->pluck('id')->toArray();

            if ($comment->commentable_type !== \App\Models\PitchFile::class ||
                ! in_array($comment->commentable_id, $pitchFileIds)) {
                throw new \Exception('Comment does not belong to this pitch');
            }

            // Store file info for success message
            $fileId = $comment->commentable_id;

            // Delete the comment
            $comment->delete();

            $this->pitch->refresh();

            // Force refresh by clearing cached properties
            unset($this->fileCommentsData);

            // Dispatch event to update comments display using same refresh mechanism as reply creation
            $this->dispatch('requestCommentsRefresh', [
                'modelType' => 'pitch',
                'modelId' => $this->pitch->id,
            ]);

            Toaster::success('Comment deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete file comment', [
                'comment_id' => $commentId,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to delete comment.');
        }
    }

    /**
     * Unresolve a file comment using the new FileComment system
     */
    public function unresolveFileComment($commentId)
    {
        try {
            $comment = \App\Models\FileComment::findOrFail($commentId);

            // Verify the comment belongs to a file in this pitch
            $pitchFileIds = $this->pitch->files()->pluck('id')->toArray();

            if ($comment->commentable_type !== \App\Models\PitchFile::class ||
                ! in_array($comment->commentable_id, $pitchFileIds)) {
                throw new \Exception('Comment does not belong to this pitch');
            }

            // Mark the comment as unresolved
            $comment->update(['resolved' => false]);

            $this->pitch->refresh();

            // Force refresh by clearing cached properties
            unset($this->fileCommentsData);

            // Dispatch event to update comments display using same refresh mechanism
            $this->dispatch('requestCommentsRefresh', [
                'modelType' => 'pitch',
                'modelId' => $this->pitch->id,
            ]);

            Toaster::success('Comment marked as unresolved.');
        } catch (\Exception $e) {
            Log::error('Failed to unresolve file comment', [
                'comment_id' => $commentId,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to unresolve comment.');
        }
    }

    /**
     * Handle refresh client files event from link importer
     */
    #[On('refreshClientFiles')]
    public function refreshClientFiles()
    {
        // Refresh the project files relationship
        $this->project->load('files');

        // Increment refresh key to force file-list component to re-render
        $this->refreshKey++;
    }

    /**
     * Update a specific producer email preference.
     */
    public function updateProducerEmailPreference(string $type, bool $enabled): void
    {
        try {
            $this->project->updateProducerEmailPreference($type, $enabled);
            $this->producerEmailPreferences[$type] = $enabled;

            Toaster::success('Email preference updated');
        } catch (\Exception $e) {
            Log::error('Failed to update producer email preference', [
                'error' => $e->getMessage(),
                'project_id' => $this->project->id,
                'type' => $type,
            ]);
            Toaster::error('Failed to update email preference');
        }
    }
}
