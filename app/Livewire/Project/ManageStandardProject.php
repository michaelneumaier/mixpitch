<?php

namespace App\Livewire\Project;

use App\Jobs\PostProjectToReddit;
use App\Livewire\Concerns\ManagesProjectFiles;
use App\Livewire\Concerns\ManagesProjectImages;
use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\FileManagementService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Masmerise\Toaster\Toaster;

/**
 * ManageStandardProject - Handles Standard and Direct Hire project workflows
 *
 * Provides a tabbed interface with 4 tabs:
 * - Overview: Project status context, pitch summaries, quick actions
 * - Pitches: Manage producer submissions and approvals
 * - Files: Upload and manage project files
 * - Project: Details, settings, and configuration
 */
class ManageStandardProject extends Component
{
    use ManagesProjectFiles;
    use ManagesProjectImages;
    use WithFileUploads;

    public Project $project;

    public ProjectForm $form;

    public string $activeMainTab = 'overview';

    public bool $autoAllowAccess;

    public $hasPreviewTrack = false;

    public $audioUrl;

    // Reddit posting state
    public bool $isPostingToReddit = false;

    public $redditPostingStartedAt = null;

    // Browser timezone for datetime-local conversion
    public $browserTimezone;

    protected $listeners = [
        'filesUploaded' => 'refreshProjectData',
        'fileListRefreshRequested' => 'refreshProjectData',
        'bulk-download-started' => 'trackBulkDownload',
        // Header component event listeners
        'show-image-upload' => 'showImageUpload',
        'remove-project-image' => 'removeProjectImage',
        'publish-project' => 'publish',
        'unpublish-project' => 'unpublish',
        'post-to-reddit' => 'postToReddit',
        'confirm-delete-project' => 'confirmDeleteProject',
        'toggle-auto-allow-access' => 'handleToggleAutoAllowAccess',
        // Tab switching
        'switchTab' => 'handleSwitchTab',
        'switchToTab' => 'handleSwitchTab',
    ];

    public function mount(Project $project): void
    {
        // Redirect non-standard/direct_hire projects to appropriate components
        if ($project->isClientManagement()) {
            $this->redirect(route('projects.manage-client', $project), navigate: true);

            return;
        }

        if ($project->isContest()) {
            $this->redirect(route('projects.manage-contest', $project), navigate: true);

            return;
        }

        try {
            $this->authorize('update', $project);
        } catch (AuthorizationException $e) {
            abort(403, 'You are not authorized to manage this project.');
        }

        $this->project = $project;
        $this->autoAllowAccess = $this->project->auto_allow_access ?? false;

        // Eager load relationships needed based on workflow type
        if ($this->project->isDirectHire()) {
            $this->project->load('targetProducer');
            $this->project->load(['pitches' => function ($query) {
                $query->with(['user', 'files', 'events']);
            }]);
        } else {
            $this->project->load('pitches.user');
        }

        // Initialize the form object
        $this->form = new ProjectForm($this, 'form');
        $this->form->fill($this->project);

        // Initialize timezone service for datetime conversions
        $this->initializeDeadlines();

        // Handle mapping collaboration types
        $this->mapCollaborationTypesToForm($this->project->collaboration_type);

        // Handle budget type
        $this->form->budgetType = $this->project->budget > 0 ? 'paid' : 'free';

        // Preview track logic
        $this->checkPreviewTrackStatus();
    }

    /**
     * Initialize deadlines from project
     */
    private function initializeDeadlines(): void
    {
        $timezoneService = app(\App\Services\TimezoneService::class);

        if ($this->project->deadline && $this->project->deadline instanceof Carbon) {
            $rawDeadline = $this->project->getRawOriginal('deadline');
            $utcTime = null;

            if ($rawDeadline) {
                if (strpos($rawDeadline, ':') !== false) {
                    $utcTime = Carbon::createFromFormat('Y-m-d H:i:s', $rawDeadline, 'UTC');
                } else {
                    $utcTime = Carbon::createFromFormat('Y-m-d', $rawDeadline, 'UTC')->startOfDay();
                }
                $this->form->deadline = $timezoneService->convertToUserTimezone($utcTime, auth()->user())->format('Y-m-d\TH:i');
            } else {
                $this->form->deadline = null;
            }
        } elseif (is_string($this->project->deadline)) {
            $this->form->deadline = $this->project->deadline;
        } else {
            $this->form->deadline = null;
        }
    }

    /**
     * Helper to map project collaboration types to form boolean properties.
     */
    private function mapCollaborationTypesToForm(array|string|null $types): void
    {
        if (is_string($types)) {
            $types = json_decode($types, true);
        }

        if (empty($types) || ! is_array($types)) {
            return;
        }

        $this->form->collaborationTypeMixing = in_array('Mixing', $types);
        $this->form->collaborationTypeMastering = in_array('Mastering', $types);
        $this->form->collaborationTypeProduction = in_array('Production', $types);
        $this->form->collaborationTypeSongwriting = in_array('Songwriting', $types);
        $this->form->collaborationTypeVocalTuning = in_array('Vocal Tuning', $types);
    }

    /**
     * Handle tab switching
     */
    public function handleSwitchTab(string $tab): void
    {
        $this->activeMainTab = $tab;
        $this->skipRender();
    }

    /**
     * Get pending pitch count for badge
     */
    #[Computed]
    public function pendingPitchCount(): int
    {
        return $this->project->pitches->where('status', 'pending')->count();
    }

    /**
     * Get action needed count (pitches needing attention)
     */
    #[Computed]
    public function actionNeededCount(): int
    {
        return $this->project->pitches->whereIn('status', ['pending', 'ready_for_review'])->count();
    }

    /**
     * Check if user can upload files to this project.
     */
    #[Computed]
    public function canUploadFiles(): bool
    {
        return Gate::allows('uploadFile', $this->project);
    }

    /**
     * Update project details inline
     */
    public function updateProjectDetailsInline(array $updates): void
    {
        try {
            $this->authorize('update', $this->project);

            $rules = [];
            $messages = [];

            if (isset($updates['artist_name'])) {
                $rules['artist_name'] = 'nullable|string|max:255';
                $messages['artist_name.max'] = 'Artist name cannot exceed 255 characters.';
            }

            if (isset($updates['genre'])) {
                $rules['genre'] = 'nullable|string|max:100';
                $messages['genre.max'] = 'Genre cannot exceed 100 characters.';
            }

            if (isset($updates['description'])) {
                $rules['description'] = 'nullable|string|max:5000';
                $messages['description.max'] = 'Description cannot exceed 5000 characters.';
            }

            if (isset($updates['notes'])) {
                $rules['notes'] = 'nullable|string|max:10000';
                $messages['notes.max'] = 'Notes cannot exceed 10000 characters.';
            }

            $validated = validator($updates, $rules, $messages)->validate();

            Project::where('id', $this->project->id)->update($validated);

            Toaster::success('Project details updated successfully!');
            $this->skipRender();

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
            $this->skipRender();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error updating project details', [
                'project_id' => $this->project->id,
                'updates' => $updates,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update project details. Please try again.');
            $this->skipRender();
        }
    }

    /**
     * Update project title inline
     */
    public function updateProjectTitle(string $newTitle): void
    {
        try {
            $this->authorize('update', $this->project);

            $validated = validator(['title' => $newTitle], [
                'title' => 'required|string|max:255|min:3',
            ], [
                'title.required' => 'Project title is required.',
                'title.min' => 'Project title must be at least 3 characters.',
                'title.max' => 'Project title cannot exceed 255 characters.',
            ])->validate();

            Project::where('id', $this->project->id)->update([
                'name' => $validated['title'],
                'title' => $validated['title'],
            ]);

            Toaster::success('Project title updated successfully!');
            $this->skipRender();

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
            $this->skipRender();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error updating project title', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update project title. Please try again.');
            $this->skipRender();
        }
    }

    /**
     * Update collaboration types inline
     */
    public function updateCollaborationTypes(array $types): void
    {
        try {
            $this->authorize('update', $this->project);

            if (empty($types)) {
                Toaster::error('Please select at least one collaboration type.');
                $this->skipRender();

                return;
            }

            $validTypes = ['Mixing', 'Mastering', 'Production', 'Songwriting', 'Vocal Tuning'];
            foreach ($types as $type) {
                if (! in_array($type, $validTypes)) {
                    Toaster::error('Invalid collaboration type: '.$type);
                    $this->skipRender();

                    return;
                }
            }

            Project::where('id', $this->project->id)->update([
                'collaboration_type' => $types,
            ]);

            $count = count($types);
            $message = $count === 1
                ? '1 collaboration type selected!'
                : $count.' collaboration types selected!';
            Toaster::success($message);
            $this->skipRender();

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
            $this->skipRender();
        } catch (\Exception $e) {
            Log::error('Error updating collaboration types', [
                'project_id' => $this->project->id,
                'types' => $types,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update collaboration types. Please try again.');
            $this->skipRender();
        }
    }

    /**
     * Update project budget inline
     */
    public function updateBudget(array $updates): void
    {
        try {
            $this->authorize('update', $this->project);

            if (! $this->project->isStandard()) {
                Toaster::error('Budget can only be updated for standard projects.');
                $this->skipRender();

                return;
            }

            $validated = validator($updates, [
                'budget_type' => 'required|in:free,paid',
                'budget' => 'nullable|numeric|min:0|max:999999.99',
            ])->validate();

            $budgetValue = ($validated['budget_type'] === 'free') ? 0 : (float) ($validated['budget'] ?? 0);

            if ($validated['budget_type'] === 'paid' && $budgetValue <= 0) {
                Toaster::error('Paid projects must have a budget greater than $0.');
                $this->skipRender();

                return;
            }

            Project::where('id', $this->project->id)->update([
                'budget' => $budgetValue,
            ]);

            $message = $validated['budget_type'] === 'free'
                ? 'Project marked as free!'
                : 'Budget updated to $'.number_format($budgetValue, 2).'!';
            Toaster::success($message);
            $this->skipRender();

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
            $this->skipRender();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error updating project budget', [
                'project_id' => $this->project->id,
                'updates' => $updates,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update budget. Please try again.');
            $this->skipRender();
        }
    }

    /**
     * Update project deadline inline
     */
    public function updateDeadline(?string $deadline): void
    {
        try {
            $this->authorize('update', $this->project);

            if (empty($deadline)) {
                Project::where('id', $this->project->id)->update(['deadline' => null]);
                Toaster::success('Deadline cleared!');
                $this->skipRender();

                return;
            }

            $utcDeadline = $this->convertDateTimeToUtc($deadline);

            if ($utcDeadline->isPast()) {
                Toaster::error('Deadline must be in the future.');
                $this->skipRender();

                return;
            }

            Project::where('id', $this->project->id)->update([
                'deadline' => $utcDeadline->toDateTimeString(),
            ]);

            Toaster::success('Deadline updated successfully!');
            $this->skipRender();

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
            $this->skipRender();
        } catch (\Exception $e) {
            Log::error('Error updating project deadline', [
                'project_id' => $this->project->id,
                'deadline' => $deadline,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update deadline. Please try again.');
            $this->skipRender();
        }
    }

    /**
     * Update project license settings inline
     */
    public function updateLicenseSettings(array $settings): void
    {
        try {
            $this->authorize('update', $this->project);

            $validated = validator($settings, [
                'requires_agreement' => 'boolean',
                'template_id' => 'nullable|exists:license_templates,id',
                'license_notes' => 'nullable|string|max:10000',
            ])->validate();

            Project::where('id', $this->project->id)->update([
                'requires_license_agreement' => $validated['requires_agreement'],
                'license_template_id' => $validated['template_id'],
                'license_notes' => $validated['license_notes'],
            ]);

            $this->project->refresh();

            Toaster::success('License settings updated successfully!');
            $this->skipRender();

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
            $this->skipRender();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error updating project license settings', [
                'project_id' => $this->project->id,
                'settings' => $settings,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update license settings. Please try again.');
            $this->skipRender();
        }
    }

    /**
     * Publish the project.
     */
    public function publish(): void
    {
        $this->authorize('publish', $this->project);

        $this->project->publish();
        $this->project->refresh();

        Toaster::success('Project published successfully.');
        $this->dispatch('project-updated');
    }

    /**
     * Unpublish the project.
     */
    public function unpublish(): void
    {
        $this->authorize('unpublish', $this->project);

        $this->project->unpublish();
        $this->project->refresh();

        Toaster::success('Project unpublished successfully.');
        $this->dispatch('project-updated');
    }

    /**
     * Toggle the preview track for the project.
     */
    public function togglePreviewTrack(ProjectFile $file, FileManagementService $fileManagementService): void
    {
        try {
            $this->authorize('update', $this->project);

            if ($this->hasPreviewTrack && $this->project->preview_track == $file->id) {
                $fileManagementService->clearProjectPreviewTrack($this->project);
                $this->hasPreviewTrack = false;
                $this->audioUrl = null;
                $this->dispatch('audioUrlUpdated', null);
                Toaster::success('Preview track cleared successfully.');
            } else {
                $fileManagementService->setProjectPreviewTrack($this->project, $file);
                $this->project->refresh();

                if ($this->project->hasPreviewTrack()) {
                    $this->hasPreviewTrack = true;
                    $this->dispatch('preview-track-updated');
                    Toaster::success('Preview track updated successfully.');
                } else {
                    Toaster::error('Could not set preview track. Please try again.');
                }
            }

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to change the preview track.');
        } catch (\Exception $e) {
            Log::error('Error toggling project preview track', [
                'project_id' => $this->project->id,
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Could not update preview track: '.$e->getMessage());
        }
    }

    /**
     * Confirm project deletion
     */
    public function confirmDeleteProject(): void
    {
        $this->authorize('delete', $this->project);
        $this->dispatch('modal-show', name: 'delete-project');
    }

    /**
     * Cancel project deletion
     */
    public function cancelDeleteProject(): void
    {
        $this->dispatch('modal-close', name: 'delete-project');
    }

    /**
     * Delete the project and all associated data
     */
    public function deleteProject()
    {
        $this->authorize('delete', $this->project);

        try {
            $projectTitle = $this->project->title;
            $this->project->delete();

            Toaster::success("Project '{$projectTitle}' deleted successfully.");

            return $this->redirect(route('projects.index'), navigate: true);

        } catch (\Exception $e) {
            Log::error('Error deleting project', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to delete project. Please try again.');
        }
    }

    /**
     * Post project to Reddit
     */
    public function postToReddit(): void
    {
        try {
            $this->authorize('update', $this->project);

            if ($this->isPostingToReddit) {
                Toaster::warning('Reddit posting is already in progress. Please wait...');

                return;
            }

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

            $recentPosts = auth()->user()->projects()
                ->whereNotNull('reddit_posted_at')
                ->where('reddit_posted_at', '>', now()->subHour())
                ->count();

            if ($recentPosts >= 3) {
                Toaster::error('You can only post 3 projects per hour to Reddit. Please try again later.');

                return;
            }

            $this->isPostingToReddit = true;
            $this->redditPostingStartedAt = now();

            PostProjectToReddit::dispatch($this->project);

            Toaster::success('Your project is being posted to r/MixPitch! This may take a few moments...');

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
            ]);
            Toaster::error('An error occurred while posting to Reddit. Please try again.');
        }
    }

    /**
     * Check Reddit posting status (called by polling)
     */
    #[On('checkRedditStatus')]
    public function checkRedditStatus(): void
    {
        $this->project->refresh();

        if ($this->project->hasBeenPostedToReddit()) {
            $this->isPostingToReddit = false;
            $this->redditPostingStartedAt = null;

            Toaster::success('Successfully posted to r/MixPitch!');
            $this->dispatch('stop-reddit-polling');

            return;
        }

        if ($this->redditPostingStartedAt && now()->diffInMinutes($this->redditPostingStartedAt) > 5) {
            $this->isPostingToReddit = false;
            $this->redditPostingStartedAt = null;

            Toaster::warning('Reddit posting is taking longer than expected. Please check back later.');
            $this->dispatch('stop-reddit-polling');
        }
    }

    /**
     * Handle toggle auto-allow access
     */
    public function handleToggleAutoAllowAccess(bool $autoAllowAccess): void
    {
        $this->autoAllowAccess = $autoAllowAccess;
        $this->project->update(['auto_allow_access' => $autoAllowAccess]);
        $this->project->refresh();

        $message = $autoAllowAccess ? 'Automatic access enabled.' : 'Automatic access disabled.';
        Toaster::success($message);
    }

    /**
     * Preview client portal - not applicable for standard projects
     */
    public function previewClientPortal(): void
    {
        Toaster::info('Client portal is only available for client management projects.');
    }

    /**
     * Check and set preview track status.
     */
    private function checkPreviewTrackStatus(): void
    {
        $this->hasPreviewTrack = $this->project->hasPreviewTrack();

        if ($this->hasPreviewTrack) {
            $previewFile = $this->project->files()->where('id', $this->project->preview_track)->first();
            if ($previewFile) {
                $this->audioUrl = $previewFile->signedUrl();
            }
        }
    }

    /**
     * Convert datetime-local input to UTC for database storage
     */
    private function convertDateTimeToUtc(string $dateTime): Carbon
    {
        $userTimezone = auth()->user()->getTimezone();

        if (str_contains($dateTime, 'T')) {
            $formattedDateTime = str_replace('T', ' ', $dateTime);
            if (substr_count($formattedDateTime, ':') === 1) {
                $formattedDateTime .= ':00';
            }

            return Carbon::createFromFormat('Y-m-d H:i:s', $formattedDateTime, $userTimezone)->utc();
        }

        return Carbon::parse($dateTime)->utc();
    }

    public function render()
    {
        $this->project->load([
            'pitches.user',
            'pitches.snapshots',
            'files',
        ]);

        $approvedPitches = $this->project->pitches->filter(function ($pitch) {
            return in_array($pitch->status, [
                \App\Models\Pitch::STATUS_APPROVED,
                \App\Models\Pitch::STATUS_COMPLETED,
            ]);
        })->sortByDesc(function ($pitch) {
            return $pitch->status === \App\Models\Pitch::STATUS_COMPLETED ? 1 : 0;
        });

        $hasCompletedPitch = $this->project->pitches->contains('status', \App\Models\Pitch::STATUS_COMPLETED);
        $approvedPitchesCount = $this->project->pitches->where('status', \App\Models\Pitch::STATUS_APPROVED)->count();
        $hasMultipleApprovedPitches = $approvedPitchesCount > 1;
        $newlyUploadedFileIds = session('newly_uploaded_file_ids', []);

        return view('livewire.project.manage-standard-project', [
            'approvedPitches' => $approvedPitches,
            'hasCompletedPitch' => $hasCompletedPitch,
            'hasMultipleApprovedPitches' => $hasMultipleApprovedPitches,
            'approvedPitchesCount' => $approvedPitchesCount,
            'newlyUploadedFileIds' => $newlyUploadedFileIds,
        ])->layout('components.layouts.app-sidebar');
    }
}
