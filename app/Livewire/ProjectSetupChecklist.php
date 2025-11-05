<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectSetupChecklist extends Component
{
    public Project $project;

    public bool $shouldShow = true;

    public string $variant = 'callout'; // 'callout' or 'badge'

    protected $listeners = [
        'refreshChecklist' => '$refresh',
        'project-updated' => 'refreshFromParent',
        'project-details-updated' => 'refreshFromParent',
        'file-deleted' => 'refreshFromParent',
    ];

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->updateShouldShow();
    }

    public function updated(): void
    {
        $this->updateShouldShow();
    }

    protected function updateShouldShow(): void
    {
        $this->shouldShow = ! empty($this->actionableItems);
    }

    /**
     * Refresh component data when parent component updates project state
     */
    public function refreshFromParent(): void
    {
        $this->project->refresh();
        $this->updateShouldShow();
    }

    /**
     * Get checklist items based on project state and workflow type
     */
    public function getChecklistItemsProperty(): array
    {
        $items = [];

        // Always complete
        $items[] = [
            'key' => 'created',
            'label' => 'Project created',
            'status' => 'complete',
            'icon' => 'check-circle',
        ];

        // Description check
        if (empty($this->project->description) || strlen($this->project->description) < 20) {
            $items[] = [
                'key' => 'description',
                'label' => 'Add detailed project description',
                'status' => 'incomplete',
                'priority' => 'high',
                'icon' => 'document-text',
                'action' => 'scroll-to-section',
                'target' => 'description-section',
            ];
        } else {
            $items[] = [
                'key' => 'description',
                'label' => 'Project description added',
                'status' => 'complete',
                'icon' => 'check-circle',
            ];
        }

        // Collaboration services check
        $collaborationTypes = $this->project->collaboration_type;
        if (empty($collaborationTypes) || count($collaborationTypes) === 0) {
            $items[] = [
                'key' => 'services',
                'label' => 'Select collaboration services',
                'status' => 'incomplete',
                'priority' => 'medium',
                'icon' => 'wrench-screwdriver',
            ];
        } else {
            $items[] = [
                'key' => 'services',
                'label' => 'Collaboration services selected',
                'status' => 'complete',
                'icon' => 'check-circle',
            ];
        }

        // Project files check
        if (! $this->project->files()->count()) {
            $items[] = [
                'key' => 'files',
                'label' => 'Upload project files',
                'status' => 'optional',
                'priority' => 'medium',
                'icon' => 'musical-note',
                'description' => 'Reference tracks help producers understand your style',
            ];
        } else {
            $items[] = [
                'key' => 'files',
                'label' => 'Project files uploaded',
                'status' => 'complete',
                'icon' => 'check-circle',
            ];
        }

        // Workflow-specific items
        if ($this->project->isStandard()) {
            $items = array_merge($items, $this->getStandardWorkflowItems());
        } elseif ($this->project->isContest()) {
            $items = array_merge($items, $this->getContestWorkflowItems());
        } elseif ($this->project->isDirectHire()) {
            $items = array_merge($items, $this->getDirectHireWorkflowItems());
        }

        // License (all workflows)
        if (! $this->project->license_template_id && ! $this->project->custom_license_terms) {
            $items[] = [
                'key' => 'license',
                'label' => 'Set license terms',
                'status' => 'optional',
                'priority' => 'low',
                'icon' => 'document-check',
                'description' => 'Define usage rights for your work',
            ];
        }

        // Publishing (final step)
        if (! $this->project->is_published) {
            $requiredComplete = collect($items)->where('status', 'incomplete')->isEmpty();

            $items[] = [
                'key' => 'publish',
                'label' => 'Publish project',
                'status' => $requiredComplete ? 'ready' : 'blocked',
                'priority' => 'high',
                'icon' => 'rocket-launch',
                'description' => $requiredComplete ? 'Ready to publish!' : 'Complete required items first',
            ];
        } else {
            $items[] = [
                'key' => 'published',
                'label' => 'Project published',
                'status' => 'complete',
                'icon' => 'check-circle',
            ];
        }

        return $items;
    }

    /**
     * Get standard workflow specific items
     */
    protected function getStandardWorkflowItems(): array
    {
        $items = [];

        // Budget
        if ($this->project->budget === 0) {
            $items[] = [
                'key' => 'budget',
                'label' => 'Set project budget (Free or Paid)',
                'status' => 'optional',
                'priority' => 'high',
                'icon' => 'currency-dollar',
                'description' => 'Paid projects receive more professional pitches',
            ];
        } else {
            $items[] = [
                'key' => 'budget',
                'label' => 'Project budget set',
                'status' => 'complete',
                'icon' => 'check-circle',
            ];
        }

        // Deadline
        if (! $this->project->deadline) {
            $items[] = [
                'key' => 'deadline',
                'label' => 'Set submission deadline',
                'status' => 'optional',
                'priority' => 'medium',
                'icon' => 'calendar',
                'description' => 'Recommended for time-sensitive projects',
            ];
        }

        return $items;
    }

    /**
     * Get contest workflow specific items
     */
    protected function getContestWorkflowItems(): array
    {
        $items = [];

        // Submission deadline (already set in modal, but check)
        $items[] = [
            'key' => 'submission_deadline',
            'label' => 'Submission deadline set',
            'status' => 'complete',
            'icon' => 'check-circle',
        ];

        // Prizes
        if (! $this->project->prize_amount) {
            $items[] = [
                'key' => 'prizes',
                'label' => 'Configure contest prizes',
                'status' => 'incomplete',
                'priority' => 'high',
                'icon' => 'trophy',
                'description' => 'Required for contest projects',
            ];
        } else {
            $items[] = [
                'key' => 'prizes',
                'label' => 'Contest prizes configured',
                'status' => 'complete',
                'icon' => 'check-circle',
            ];
        }

        return $items;
    }

    /**
     * Get direct hire workflow specific items
     */
    protected function getDirectHireWorkflowItems(): array
    {
        return [
            [
                'key' => 'direct_hire_info',
                'label' => 'Private project assigned to specific producer',
                'status' => 'info',
                'icon' => 'information-circle',
                'description' => 'This is a direct hire project',
            ],
        ];
    }

    /**
     * Calculate completion percentage
     */
    public function getCompletionPercentageProperty(): int
    {
        $items = $this->checklistItems;

        $completed = collect($items)->where('status', 'complete')->count();
        $total = collect($items)->whereIn('status', ['complete', 'incomplete', 'ready', 'blocked'])->count();

        if ($total === 0) {
            return 100;
        }

        return (int) round(($completed / $total) * 100);
    }

    /**
     * Get incomplete items count
     */
    public function getIncompleteCountProperty(): int
    {
        return collect($this->checklistItems)->where('status', 'incomplete')->count();
    }

    /**
     * Get only actionable items (hide completed items)
     */
    public function getActionableItemsProperty(): array
    {
        return collect($this->checklistItems)
            ->whereIn('status', ['incomplete', 'optional', 'ready', 'blocked'])
            ->values()
            ->toArray();
    }

    /**
     * Get actionable items grouped by required vs optional
     */
    public function getGroupedItemsProperty(): array
    {
        $actionableItems = $this->actionableItems;

        $required = collect($actionableItems)
            ->whereIn('status', ['incomplete', 'ready', 'blocked'])
            ->values()
            ->toArray();

        $optional = collect($actionableItems)
            ->where('status', 'optional')
            ->values()
            ->toArray();

        return [
            'required' => $required,
            'optional' => $optional,
        ];
    }

    public function render()
    {
        $viewName = $this->variant === 'badge'
            ? 'livewire.project-setup-checklist-badge'
            : 'livewire.project-setup-checklist';

        return view($viewName);
    }
}
