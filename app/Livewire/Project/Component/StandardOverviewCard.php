<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * StandardOverviewCard - Overview dashboard for Standard and Direct Hire projects
 *
 * Displays project status context, pitch metrics, and quick actions for the project owner.
 */
class StandardOverviewCard extends Component
{
    public Project $project;

    public array $workflowColors = [];

    public function mount(Project $project, array $workflowColors = []): void
    {
        $this->project = $project;
        $this->workflowColors = $workflowColors;
    }

    /**
     * Get project metrics for display
     */
    #[Computed]
    public function projectMetrics(): array
    {
        $pitches = $this->project->pitches;

        return [
            'total_files' => $this->project->files()->count(),
            'pitch_count' => $pitches->count(),
            'pending_count' => $pitches->where('status', Pitch::STATUS_PENDING)->count(),
            'in_progress_count' => $pitches->where('status', Pitch::STATUS_IN_PROGRESS)->count(),
            'ready_for_review_count' => $pitches->where('status', Pitch::STATUS_READY_FOR_REVIEW)->count(),
            'approved_count' => $pitches->where('status', Pitch::STATUS_APPROVED)->count(),
            'completed_count' => $pitches->where('status', Pitch::STATUS_COMPLETED)->count(),
            'denied_count' => $pitches->where('status', Pitch::STATUS_DENIED)->count(),
            'days_active' => now()->diffInDays($this->project->created_at),
        ];
    }

    /**
     * Get the current status context for display
     */
    #[Computed]
    public function statusContext(): array
    {
        $metrics = $this->projectMetrics;

        // Determine primary status message based on project state
        if (! $this->project->is_published) {
            return [
                'status' => 'draft',
                'heading' => 'Project Not Published',
                'message' => 'Your project is currently in draft mode. Publish it to start receiving pitches from producers.',
                'icon' => 'document-text',
                'color' => 'warning',
                'action' => 'Publish Project',
                'action_event' => 'publish-project',
            ];
        }

        if ($metrics['completed_count'] > 0) {
            return [
                'status' => 'completed',
                'heading' => 'Project Completed',
                'message' => 'Congratulations! This project has been successfully completed with a producer.',
                'icon' => 'check-circle',
                'color' => 'success',
                'action' => null,
                'action_event' => null,
            ];
        }

        if ($metrics['approved_count'] > 0) {
            return [
                'status' => 'approved',
                'heading' => 'Pitch Approved',
                'message' => 'You have approved a pitch. The producer is now working on the final deliverables.',
                'icon' => 'check-badge',
                'color' => 'success',
                'action' => 'View Approved Pitch',
                'action_event' => 'switch-to-pitches',
            ];
        }

        if ($metrics['ready_for_review_count'] > 0) {
            return [
                'status' => 'review',
                'heading' => 'Pitches Ready for Review',
                'message' => $metrics['ready_for_review_count'].' '.($metrics['ready_for_review_count'] === 1 ? 'pitch is' : 'pitches are').' waiting for your review.',
                'icon' => 'eye',
                'color' => 'info',
                'action' => 'Review Pitches',
                'action_event' => 'switch-to-pitches',
            ];
        }

        if ($metrics['in_progress_count'] > 0) {
            return [
                'status' => 'in_progress',
                'heading' => 'Producers Working',
                'message' => $metrics['in_progress_count'].' '.($metrics['in_progress_count'] === 1 ? 'producer is' : 'producers are').' currently working on pitches.',
                'icon' => 'arrow-path',
                'color' => 'info',
                'action' => 'View Progress',
                'action_event' => 'switch-to-pitches',
            ];
        }

        if ($metrics['pending_count'] > 0) {
            return [
                'status' => 'pending',
                'heading' => 'Pending Requests',
                'message' => $metrics['pending_count'].' '.($metrics['pending_count'] === 1 ? 'producer has' : 'producers have').' requested to work on your project.',
                'icon' => 'user-plus',
                'color' => 'warning',
                'action' => 'Review Requests',
                'action_event' => 'switch-to-pitches',
            ];
        }

        if ($metrics['pitch_count'] === 0) {
            return [
                'status' => 'waiting',
                'heading' => 'Waiting for Pitches',
                'message' => 'Your project is live! Share it to attract producers who can help bring your vision to life.',
                'icon' => 'clock',
                'color' => 'info',
                'action' => 'Share Project',
                'action_event' => 'share-project',
            ];
        }

        return [
            'status' => 'active',
            'heading' => 'Project Active',
            'message' => 'Your project is active and open for collaboration.',
            'icon' => 'rocket-launch',
            'color' => 'info',
            'action' => null,
            'action_event' => null,
        ];
    }

    /**
     * Handle action button click
     */
    public function handleAction(string $event): void
    {
        if ($event === 'switch-to-pitches') {
            $this->dispatch('switchTab', tab: 'pitches');
        } elseif ($event === 'publish-project') {
            $this->dispatch('publish-project');
        } elseif ($event === 'share-project') {
            // Could open a share modal in the future
            $this->dispatch('switchTab', tab: 'project');
        }
    }

    public function render()
    {
        return view('livewire.project.component.standard-overview-card');
    }
}
