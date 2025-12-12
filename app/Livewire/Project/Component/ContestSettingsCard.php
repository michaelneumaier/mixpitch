<?php

namespace App\Livewire\Project\Component;

use App\Models\Project;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * ContestSettingsCard - Contest-specific settings management
 *
 * Handles deadline editing for contest projects.
 */
class ContestSettingsCard extends Component
{
    public Project $project;

    public array $workflowColors = [];

    public ?string $submissionDeadline = null;

    public ?string $judgingDeadline = null;

    public bool $isEditing = false;

    // Local editing copies
    public ?string $editSubmissionDeadline = null;

    public ?string $editJudgingDeadline = null;

    public function mount(
        Project $project,
        array $workflowColors = [],
        ?string $submissionDeadline = null,
        ?string $judgingDeadline = null
    ): void {
        $this->project = $project;
        $this->workflowColors = $workflowColors;
        $this->submissionDeadline = $submissionDeadline;
        $this->judgingDeadline = $judgingDeadline;
    }

    /**
     * Start editing deadlines
     */
    public function startEditing(): void
    {
        $this->editSubmissionDeadline = $this->submissionDeadline;
        $this->editJudgingDeadline = $this->judgingDeadline;
        $this->isEditing = true;
    }

    /**
     * Cancel editing
     */
    public function cancelEditing(): void
    {
        $this->isEditing = false;
        $this->editSubmissionDeadline = null;
        $this->editJudgingDeadline = null;
    }

    /**
     * Save updated deadlines
     */
    public function saveDeadlines(): void
    {
        try {
            $this->authorize('update', $this->project);

            $updates = [];

            // Convert submission deadline to UTC if provided
            if ($this->editSubmissionDeadline) {
                $utcSubmission = $this->convertDateTimeToUtc($this->editSubmissionDeadline);

                // Validate submission deadline is in the future
                if ($utcSubmission->isPast()) {
                    Toaster::error('Submission deadline must be in the future.');

                    return;
                }

                $updates['submission_deadline'] = $utcSubmission->toDateTimeString();
            } else {
                $updates['submission_deadline'] = null;
            }

            // Convert judging deadline to UTC if provided
            if ($this->editJudgingDeadline) {
                $utcJudging = $this->convertDateTimeToUtc($this->editJudgingDeadline);

                // Validate judging deadline is after submission deadline
                if (isset($updates['submission_deadline']) && $utcJudging->lte(Carbon::parse($updates['submission_deadline']))) {
                    Toaster::error('Judging deadline must be after submission deadline.');

                    return;
                }

                $updates['judging_deadline'] = $utcJudging->toDateTimeString();
            } else {
                $updates['judging_deadline'] = null;
            }

            // Update the project
            Project::where('id', $this->project->id)->update($updates);
            $this->project->refresh();

            // Update local properties with new converted values
            $this->refreshDeadlineDisplayValues();

            $this->isEditing = false;
            $this->editSubmissionDeadline = null;
            $this->editJudgingDeadline = null;

            Toaster::success('Contest deadlines updated successfully!');

            // Notify parent to refresh if needed
            $this->dispatch('contest-deadlines-updated');

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
     * Refresh deadline display values after update
     */
    private function refreshDeadlineDisplayValues(): void
    {
        $timezoneService = app(TimezoneService::class);

        if ($this->project->submission_deadline) {
            $rawSubmissionDeadline = $this->project->getRawOriginal('submission_deadline');
            if ($rawSubmissionDeadline) {
                $utcTime = Carbon::createFromFormat('Y-m-d H:i:s', $rawSubmissionDeadline, 'UTC');
                $this->submissionDeadline = $timezoneService->convertToUserTimezone($utcTime, auth()->user())->format('Y-m-d\TH:i');
            }
        } else {
            $this->submissionDeadline = null;
        }

        if ($this->project->judging_deadline) {
            $rawJudgingDeadline = $this->project->getRawOriginal('judging_deadline');
            if ($rawJudgingDeadline) {
                $utcTime = Carbon::createFromFormat('Y-m-d H:i:s', $rawJudgingDeadline, 'UTC');
                $this->judgingDeadline = $timezoneService->convertToUserTimezone($utcTime, auth()->user())->format('Y-m-d\TH:i');
            }
        } else {
            $this->judgingDeadline = null;
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
        return view('livewire.project.component.contest-settings-card');
    }
}
