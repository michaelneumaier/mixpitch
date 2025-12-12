<?php

namespace App\Livewire\Project;

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
use Livewire\Component;
use Livewire\WithFileUploads;
use Masmerise\Toaster\Toaster;

/**
 * ManageContestProject - Handles Contest project workflows
 *
 * Provides a tabbed interface with 6 tabs:
 * - Overview: Contest status, deadline countdowns, entry count, prize pool
 * - Entries: All contest entries (ContestJudging component)
 * - Judging: Winner selection interface
 * - Prizes: Prize configuration and display
 * - Files: Contest reference files
 * - Settings: Deadlines, early closure, contest settings
 */
class ManageContestProject extends Component
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

    // Contest deadline properties
    public ?string $submission_deadline = null;

    public ?string $judging_deadline = null;

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
        'confirm-delete-project' => 'confirmDeleteProject',
        'toggle-auto-allow-access' => 'handleToggleAutoAllowAccess',
        // Tab switching
        'switchTab' => 'handleSwitchTab',
        'switchToTab' => 'handleSwitchTab',
        // Contest-specific events
        'contest-winner-selected' => 'refreshContestData',
        'contest-closed-early' => 'refreshContestData',
    ];

    public function mount(Project $project): void
    {
        // Redirect non-contest projects to appropriate components
        if ($project->isClientManagement()) {
            $this->redirect(route('projects.manage-client', $project), navigate: true);

            return;
        }

        if (! $project->isContest()) {
            $this->redirect(route('projects.manage-standard', $project), navigate: true);

            return;
        }

        try {
            $this->authorize('update', $project);
        } catch (AuthorizationException $e) {
            abort(403, 'You are not authorized to manage this contest.');
        }

        $this->project = $project;
        $this->autoAllowAccess = $this->project->auto_allow_access ?? false;

        // Eager load contest entries
        $this->project->load(['pitches.user', 'pitches.snapshots', 'files', 'contestPrizes']);

        // Initialize the form object
        $this->form = new ProjectForm($this, 'form');
        $this->form->fill($this->project);

        // Initialize contest deadlines
        $this->initializeContestDeadlines();

        // Handle mapping collaboration types
        $this->mapCollaborationTypesToForm($this->project->collaboration_type);

        // Preview track logic
        $this->checkPreviewTrackStatus();
    }

    /**
     * Initialize contest deadlines from project
     */
    private function initializeContestDeadlines(): void
    {
        $timezoneService = app(\App\Services\TimezoneService::class);

        if ($this->project->submission_deadline) {
            $rawSubmissionDeadline = $this->project->getRawOriginal('submission_deadline');
            if ($rawSubmissionDeadline) {
                $utcTime = Carbon::createFromFormat('Y-m-d H:i:s', $rawSubmissionDeadline, 'UTC');
                $this->submission_deadline = $timezoneService->convertToUserTimezone($utcTime, auth()->user())->format('Y-m-d\TH:i');
            }
        }

        if ($this->project->judging_deadline) {
            $rawJudgingDeadline = $this->project->getRawOriginal('judging_deadline');
            if ($rawJudgingDeadline) {
                $utcTime = Carbon::createFromFormat('Y-m-d H:i:s', $rawJudgingDeadline, 'UTC');
                $this->judging_deadline = $timezoneService->convertToUserTimezone($utcTime, auth()->user())->format('Y-m-d\TH:i');
            }
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
     * Refresh contest data after changes
     */
    public function refreshContestData(): void
    {
        $this->project->refresh();
        $this->project->load(['pitches.user', 'pitches.snapshots', 'contestPrizes']);
    }

    /**
     * Get contest entry count for badge
     */
    #[Computed]
    public function entryCount(): int
    {
        return $this->project->pitches->count();
    }

    /**
     * Check if judging can happen (submission deadline passed)
     */
    #[Computed]
    public function canJudge(): bool
    {
        return $this->project->isSubmissionPeriodClosed() && ! $this->project->isJudgingFinalized();
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
            }

            if (isset($updates['genre'])) {
                $rules['genre'] = 'nullable|string|max:100';
            }

            if (isset($updates['description'])) {
                $rules['description'] = 'nullable|string|max:5000';
            }

            if (isset($updates['notes'])) {
                $rules['notes'] = 'nullable|string|max:10000';
            }

            $validated = validator($updates, $rules, $messages)->validate();

            Project::where('id', $this->project->id)->update($validated);

            Toaster::success('Contest details updated successfully!');
            $this->skipRender();

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this contest.');
            $this->skipRender();
        } catch (\Exception $e) {
            Log::error('Error updating contest details', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update contest details.');
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
            ])->validate();

            Project::where('id', $this->project->id)->update([
                'name' => $validated['title'],
                'title' => $validated['title'],
            ]);

            Toaster::success('Contest title updated successfully!');
            $this->skipRender();

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this contest.');
            $this->skipRender();
        } catch (\Exception $e) {
            Log::error('Error updating contest title', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update contest title.');
            $this->skipRender();
        }
    }

    /**
     * Update contest deadlines
     */
    public function updateContestDeadlines(?string $submissionDeadline, ?string $judgingDeadline): void
    {
        try {
            $this->authorize('update', $this->project);

            $updates = [];

            if ($submissionDeadline) {
                $utcSubmission = $this->convertDateTimeToUtc($submissionDeadline);
                if ($utcSubmission->isPast()) {
                    Toaster::error('Submission deadline must be in the future.');

                    return;
                }
                $updates['submission_deadline'] = $utcSubmission->toDateTimeString();
            } else {
                $updates['submission_deadline'] = null;
            }

            if ($judgingDeadline) {
                $utcJudging = $this->convertDateTimeToUtc($judgingDeadline);
                if (isset($updates['submission_deadline']) && $utcJudging->lte(Carbon::parse($updates['submission_deadline']))) {
                    Toaster::error('Judging deadline must be after submission deadline.');

                    return;
                }
                $updates['judging_deadline'] = $utcJudging->toDateTimeString();
            } else {
                $updates['judging_deadline'] = null;
            }

            Project::where('id', $this->project->id)->update($updates);
            $this->project->refresh();

            // Update local properties
            $this->initializeContestDeadlines();

            Toaster::success('Contest deadlines updated successfully!');

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this contest.');
        } catch (\Exception $e) {
            Log::error('Error updating contest deadlines', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update contest deadlines.');
        }
    }

    /**
     * Publish the contest.
     */
    public function publish(): void
    {
        $this->authorize('publish', $this->project);

        $this->project->publish();
        $this->project->refresh();

        Toaster::success('Contest published successfully.');
        $this->dispatch('project-updated');
    }

    /**
     * Unpublish the contest.
     */
    public function unpublish(): void
    {
        $this->authorize('unpublish', $this->project);

        $this->project->unpublish();
        $this->project->refresh();

        Toaster::success('Contest unpublished successfully.');
        $this->dispatch('project-updated');
    }

    /**
     * Toggle the preview track for the contest.
     */
    public function togglePreviewTrack(ProjectFile $file, FileManagementService $fileManagementService): void
    {
        try {
            $this->authorize('update', $this->project);

            if ($this->hasPreviewTrack && $this->project->preview_track == $file->id) {
                $fileManagementService->clearProjectPreviewTrack($this->project);
                $this->hasPreviewTrack = false;
                $this->audioUrl = null;
                Toaster::success('Preview track cleared.');
            } else {
                $fileManagementService->setProjectPreviewTrack($this->project, $file);
                $this->project->refresh();

                if ($this->project->hasPreviewTrack()) {
                    $this->hasPreviewTrack = true;
                    Toaster::success('Preview track updated.');
                }
            }

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to change the preview track.');
        } catch (\Exception $e) {
            Log::error('Error toggling contest preview track', [
                'project_id' => $this->project->id,
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Could not update preview track.');
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
     * Delete the contest and all associated data
     */
    public function deleteProject()
    {
        $this->authorize('delete', $this->project);

        try {
            $projectTitle = $this->project->title;
            $this->project->delete();

            Toaster::success("Contest '{$projectTitle}' deleted successfully.");

            return $this->redirect(route('projects.index'), navigate: true);

        } catch (\Exception $e) {
            Log::error('Error deleting contest', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to delete contest. Please try again.');
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
            'contestPrizes',
        ]);

        $newlyUploadedFileIds = session('newly_uploaded_file_ids', []);

        return view('livewire.project.manage-contest-project', [
            'newlyUploadedFileIds' => $newlyUploadedFileIds,
        ])->layout('components.layouts.app-sidebar');
    }
}
