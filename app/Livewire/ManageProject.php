<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

/**
 * ManageProject - Router component for project management
 *
 * This component routes to the appropriate workflow-specific management component:
 * - ManageClientProject: For client management projects
 * - ManageContestProject: For contest projects
 * - ManageStandardProject: For standard and direct hire projects
 *
 * This maintains backwards compatibility with existing routes while delegating
 * to specialized components for each workflow type.
 */
class ManageProject extends Component
{
    public function mount(Project $project)
    {
        // Redirect client management projects to dedicated page
        if ($project->isClientManagement()) {
            return $this->redirect(route('projects.manage-client', $project), navigate: true);
        }

        // Redirect contest projects to dedicated page
        if ($project->isContest()) {
            return $this->redirect(route('projects.manage-contest', $project), navigate: true);
        }

        // Redirect standard and direct hire projects to dedicated page
        return $this->redirect(route('projects.manage-standard', $project), navigate: true);
    }

    public function render()
    {
        // This should never be called since mount always redirects
        return view('livewire.project.page.manage-project-router');
    }
}
