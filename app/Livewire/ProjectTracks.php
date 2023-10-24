<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;

class ProjectTracks extends Component
{
    public $project;
    public $files;
    public $audioIndex;
    public $showTracks = false;


    public function mount(Project $project)
    {
        $this->project = $project;
        $this->files = $project->files;
        $this->audioIndex = 0;
    }

    public function incrementAudioIndex()
    {
        $this->audioIndex++;
        return $this->audioIndex;
    }

    public function render()
    {
        return view('livewire.project-tracks');
    }
}