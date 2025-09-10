<?php

namespace App\Livewire;

use Livewire\Component;

class ProjectCard extends Component
{
    public $project;

    public $isDashboardView = false;

    public function cardClickRoute()
    {
        return $this->redirect(route('projects.show', $this->project), navigate: true);
    }

    public function render()
    {
        return view('livewire.project-card');
    }
}
