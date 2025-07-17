<?php

namespace App\Livewire\Project\Component;

use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ContestPrizes extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;

        // Ensure this component only renders for contest workflow projects
        if (! $this->project->isContest()) {
            abort(404);
        }
    }

    public function render()
    {
        return view('livewire.project.component.contest-prizes');
    }
}
