<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;

class ManageProject extends Component
{
    public Project $project;

    public $audioUrl;

    public function mount()
    {
        if ($this->project->hasPreviewTrack()) {
            $this->audioUrl = $this->project->previewTrackPath();
        }
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
        return view('livewire.project.page.manage-project');
    }
}
