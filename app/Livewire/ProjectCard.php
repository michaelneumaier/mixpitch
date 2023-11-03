<?php

namespace App\Livewire;

use Livewire\Component;

class ProjectCard extends Component
{
    public $project;
    public $isDashboardView = false;

    public function cardClickRoute()
    {
        return redirect()->route('projects.show', $this->project);
    }

    public function publish()
    {
        $this->project->setStatus('open');
    }

    public function unpublish()
    {
        $this->project->setStatus('unpublished');
    }

    public function render()
    {
        return view('livewire.project-card');
    }
}
