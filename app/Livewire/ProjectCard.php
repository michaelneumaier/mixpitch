<?php

namespace App\Livewire;

use Livewire\Component;

class ProjectCard extends Component
{
    public $project;
    public $isDashboardView = false;

    public function render()
    {
        return view('livewire.project-card');
    }
}