<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectMixes extends Component
{
    public Project $project;
    public $audioIndex = 0;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function incrementAudioIndex()
    {
        $this->audioIndex++;
        //return $this->audioIndex;
    }

    public function render()
    {
        return view('livewire.project-mixes');
    }
}