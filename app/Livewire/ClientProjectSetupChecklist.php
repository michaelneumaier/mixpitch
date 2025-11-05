<?php

namespace App\Livewire;

use App\Models\Pitch;
use App\Models\Project;
use Livewire\Component;

class ClientProjectSetupChecklist extends Component
{
    public Project $project;

    public Pitch $pitch;

    public bool $shouldShow = true;

    public string $variant = 'callout'; // 'callout' or 'badge'

    protected $listeners = [
        'refreshChecklist' => '$refresh',
        'fileVersionChanged' => 'refreshFromParent',
        'file-deleted' => 'refreshFromParent',
    ];

    public function mount(Project $project, Pitch $pitch): void
    {
        $this->project = $project;
        $this->pitch = $pitch;
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
     * Refresh component data when parent component updates project/pitch state
     */
    public function refreshFromParent(): void
    {
        $this->project->refresh();
        $this->pitch->refresh();
        $this->updateShouldShow();
    }

    /**
     * Get checklist items based on client project state
     */
    public function getChecklistItemsProperty(): array
    {
        $items = [];

        // Always complete (created via modal)
        $items[] = [
            'key' => 'created',
            'label' => 'Project created',
            'status' => 'complete',
            'icon' => 'check-circle',
        ];

        $items[] = [
            'key' => 'client',
            'label' => 'Client information set',
            'status' => 'complete',
            'icon' => 'check-circle',
            'description' => $this->project->client_email,
        ];

        $items[] = [
            'key' => 'payment',
            'label' => 'Payment amount configured',
            'status' => 'complete',
            'icon' => 'check-circle',
            'description' => '$'.number_format($this->project->payment_amount, 2),
        ];

        // Client reference files (optional)
        if (! $this->project->files()->count()) {
            $items[] = [
                'key' => 'client_files',
                'label' => 'Upload client reference files',
                'status' => 'optional',
                'priority' => 'medium',
                'icon' => 'folder-open',
                'description' => 'Files from your client with requirements/examples',
            ];
        } else {
            $items[] = [
                'key' => 'client_files',
                'label' => 'Client reference files uploaded',
                'status' => 'complete',
                'icon' => 'check-circle',
                'description' => $this->project->files()->count().' '.'file(s)',
            ];
        }

        // Producer deliverables (required for submission)
        if (! $this->pitch->files()->count()) {
            $items[] = [
                'key' => 'deliverables',
                'label' => 'Upload your deliverables',
                'status' => 'incomplete',
                'priority' => 'high',
                'icon' => 'musical-note',
                'description' => 'Files you\'ll send to the client for review',
            ];
        } else {
            $items[] = [
                'key' => 'deliverables',
                'label' => 'Your deliverables uploaded',
                'status' => 'complete',
                'icon' => 'check-circle',
                'description' => $this->pitch->files()->count().' '.'file(s)',
            ];
        }

        // Project description
        if (empty($this->project->description) || strlen($this->project->description) < 20) {
            $items[] = [
                'key' => 'description',
                'label' => 'Add project description',
                'status' => 'optional',
                'priority' => 'medium',
                'icon' => 'document-text',
                'description' => 'Helps you and your client stay aligned',
            ];
        } else {
            $items[] = [
                'key' => 'description',
                'label' => 'Project description added',
                'status' => 'complete',
                'icon' => 'check-circle',
            ];
        }

        // Deadline (optional but recommended)
        if (! $this->project->deadline) {
            $items[] = [
                'key' => 'deadline',
                'label' => 'Set project deadline',
                'status' => 'optional',
                'priority' => 'medium',
                'icon' => 'calendar',
                'description' => 'Helps manage client expectations',
            ];
        } else {
            $items[] = [
                'key' => 'deadline',
                'label' => 'Project deadline set',
                'status' => 'complete',
                'icon' => 'check-circle',
            ];
        }

        // License configuration (optional)
        if (! $this->project->license_template_id && ! $this->project->custom_license_terms) {
            $items[] = [
                'key' => 'license',
                'label' => 'Configure license agreement',
                'status' => 'optional',
                'priority' => 'low',
                'icon' => 'document-check',
                'description' => 'Define usage rights for your work',
            ];
        } else {
            $items[] = [
                'key' => 'license',
                'label' => 'License configured',
                'status' => 'complete',
                'icon' => 'check-circle',
            ];
        }

        // Client review submission
        if ($this->pitch->status === Pitch::STATUS_IN_PROGRESS && $this->pitch->files()->count() > 0) {
            $items[] = [
                'key' => 'submit',
                'label' => 'Submit for client review',
                'status' => 'ready',
                'priority' => 'high',
                'icon' => 'paper-airplane',
                'description' => 'Send your deliverables to the client',
            ];
        } elseif ($this->pitch->status === Pitch::STATUS_READY_FOR_REVIEW) {
            $items[] = [
                'key' => 'submitted',
                'label' => 'Submitted for client review',
                'status' => 'complete',
                'icon' => 'check-circle',
                'description' => 'Waiting for client feedback',
            ];
        } elseif ($this->pitch->status === Pitch::STATUS_CLIENT_REVISIONS_REQUESTED) {
            $items[] = [
                'key' => 'revisions',
                'label' => 'Client requested revisions',
                'status' => 'incomplete',
                'priority' => 'high',
                'icon' => 'arrow-path',
                'description' => 'Update your deliverables and resubmit',
            ];
        } elseif ($this->pitch->status === Pitch::STATUS_COMPLETED) {
            $items[] = [
                'key' => 'completed',
                'label' => 'Project completed',
                'status' => 'complete',
                'icon' => 'check-circle',
                'description' => 'Client approved your work',
            ];
        }

        return $items;
    }

    /**
     * Calculate completion percentage
     */
    public function getCompletionPercentageProperty(): int
    {
        $items = $this->checklistItems;

        $completed = collect($items)->where('status', 'complete')->count();
        $total = collect($items)->whereIn('status', ['complete', 'incomplete', 'ready'])->count();

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
        return collect($this->checklistItems)->whereIn('status', ['incomplete', 'ready'])->count();
    }

    /**
     * Get only actionable items (hide completed items)
     */
    public function getActionableItemsProperty(): array
    {
        return collect($this->checklistItems)
            ->whereIn('status', ['incomplete', 'optional', 'ready'])
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
            ->whereIn('status', ['incomplete', 'ready'])
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

    /**
     * Determine if checklist should be shown
     */
    public function getShouldShowProperty(): bool
    {
        // Don't show if there are no actionable items left
        if (empty($this->actionableItems)) {
            return false;
        }

        return true;
    }

    public function render()
    {
        $viewName = $this->variant === 'badge'
            ? 'livewire.client-project-setup-checklist-badge'
            : 'livewire.client-project-setup-checklist';

        return view($viewName);
    }
}
