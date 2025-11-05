<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class WorkflowDropdown extends Component
{
    // Configurable button properties
    public string $variant = 'primary';

    public ?string $color = null;

    public ?string $size = null;

    public string $label = 'Create New Project';

    public bool $fullWidth = true;

    public string $align = 'start';

    /**
     * Select a workflow type and open the creation modal
     */
    public function selectWorkflow(string $workflowType): void
    {
        // Dispatch event to open the QuickProjectModal with the selected workflow
        $this->dispatch('openQuickProjectModal', workflowType: $workflowType);
    }

    /**
     * Get available workflow types for the dropdown
     */
    public function getWorkflowTypesProperty(): array
    {
        return [
            [
                'value' => Project::WORKFLOW_TYPE_STANDARD,
                'name' => 'Standard Project',
                'description' => 'Open marketplace for multiple submissions',
                'icon' => 'megaphone',
                'color' => 'blue',
            ],
            [
                'value' => Project::WORKFLOW_TYPE_CONTEST,
                'name' => 'Contest',
                'description' => 'Competition with prizes and judging',
                'icon' => 'trophy',
                'color' => 'orange',
            ],
            [
                'value' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
                'name' => 'Client Management',
                'description' => 'Manage work for external clients',
                'icon' => 'users',
                'color' => 'purple',
            ],
            // Direct Hire is intentionally hidden (minimal implementation)
        ];
    }

    public function render()
    {
        return view('livewire.workflow-dropdown');
    }
}
