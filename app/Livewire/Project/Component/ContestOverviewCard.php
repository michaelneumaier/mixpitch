<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * ContestOverviewCard - Overview dashboard for Contest projects
 *
 * Displays contest status, deadline countdowns, entry metrics, and prize pool.
 */
class ContestOverviewCard extends Component
{
    public Project $project;

    public array $workflowColors = [];

    public function mount(Project $project, array $workflowColors = []): void
    {
        $this->project = $project;
        $this->workflowColors = $workflowColors;
    }

    /**
     * Get contest metrics for display
     */
    #[Computed]
    public function contestMetrics(): array
    {
        $entries = $this->project->pitches;

        return [
            'total_entries' => $entries->count(),
            'total_files' => $this->project->files()->count(),
            'prize_pool' => $this->project->contestPrizes->sum('amount'),
            'prize_count' => $this->project->contestPrizes->count(),
            'has_winner' => $this->project->isJudgingFinalized(),
            'winner_entry' => $entries->where('status', Pitch::STATUS_CONTEST_WINNER)->first(),
            'runner_up_entries' => $entries->where('status', Pitch::STATUS_CONTEST_RUNNER_UP),
            'days_active' => now()->diffInDays($this->project->created_at),
        ];
    }

    /**
     * Get deadline information
     */
    #[Computed]
    public function deadlineInfo(): array
    {
        $submissionDeadline = $this->project->submission_deadline;
        $judgingDeadline = $this->project->judging_deadline;

        $now = now();

        return [
            'submission_deadline' => $submissionDeadline,
            'judging_deadline' => $judgingDeadline,
            'submission_open' => $submissionDeadline ? $now->lt($submissionDeadline) : true,
            'submission_days_left' => $submissionDeadline ? (int) $now->diffInDays($submissionDeadline, false) : null,
            'submission_hours_left' => $submissionDeadline ? (int) $now->diffInHours($submissionDeadline, false) : null,
            'judging_days_left' => $judgingDeadline ? (int) $now->diffInDays($judgingDeadline, false) : null,
            'judging_phase' => $submissionDeadline && $now->gte($submissionDeadline) && (! $judgingDeadline || $now->lt($judgingDeadline)),
        ];
    }

    /**
     * Get the current contest phase
     */
    #[Computed]
    public function contestPhase(): array
    {
        $deadlineInfo = $this->deadlineInfo;
        $metrics = $this->contestMetrics;

        if (! $this->project->is_published) {
            return [
                'phase' => 'draft',
                'heading' => 'Contest Not Published',
                'message' => 'Your contest is in draft mode. Publish it to start accepting entries.',
                'icon' => 'document-text',
                'color' => 'warning',
            ];
        }

        if ($metrics['has_winner']) {
            return [
                'phase' => 'completed',
                'heading' => 'Contest Completed',
                'message' => 'Winner has been selected! '.($metrics['winner_entry'] ? $metrics['winner_entry']->user->name.' won the contest.' : ''),
                'icon' => 'trophy',
                'color' => 'success',
            ];
        }

        if ($deadlineInfo['judging_phase']) {
            return [
                'phase' => 'judging',
                'heading' => 'Judging Phase',
                'message' => 'Submissions are closed. Review entries and select a winner.',
                'icon' => 'scale',
                'color' => 'info',
            ];
        }

        if ($deadlineInfo['submission_open']) {
            $daysLeft = $deadlineInfo['submission_days_left'];
            $hoursLeft = $deadlineInfo['submission_hours_left'];

            if ($daysLeft !== null) {
                if ($daysLeft <= 0 && $hoursLeft > 0) {
                    $timeMessage = $hoursLeft.' hour'.($hoursLeft !== 1 ? 's' : '').' left to submit';
                } elseif ($daysLeft > 0) {
                    $timeMessage = $daysLeft.' day'.($daysLeft !== 1 ? 's' : '').' left to submit';
                } else {
                    $timeMessage = 'Deadline ending soon';
                }
            } else {
                $timeMessage = 'No deadline set';
            }

            return [
                'phase' => 'submission',
                'heading' => 'Accepting Entries',
                'message' => $timeMessage.'. '.$metrics['total_entries'].' '.($metrics['total_entries'] === 1 ? 'entry' : 'entries').' received so far.',
                'icon' => 'users',
                'color' => 'info',
            ];
        }

        return [
            'phase' => 'active',
            'heading' => 'Contest Active',
            'message' => 'Your contest is live and accepting entries.',
            'icon' => 'rocket-launch',
            'color' => 'info',
        ];
    }

    /**
     * Handle action button click
     */
    public function handleAction(string $event): void
    {
        if ($event === 'switch-to-entries') {
            $this->dispatch('switchTab', tab: 'entries');
        } elseif ($event === 'switch-to-judging') {
            $this->dispatch('switchTab', tab: 'judging');
        } elseif ($event === 'switch-to-prizes') {
            $this->dispatch('switchTab', tab: 'prizes');
        } elseif ($event === 'publish-project') {
            $this->dispatch('publish-project');
        }
    }

    public function render()
    {
        return view('livewire.project.component.contest-overview-card');
    }
}
