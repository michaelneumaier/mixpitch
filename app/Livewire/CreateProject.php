<?php

namespace App\Livewire;

use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateProject extends Component
{
    use WithFileUploads;

    public ProjectForm $form;

    public function save()
    {
        $this->validate();
        $project = new Project();
        $project->user_id = auth()->id();
        $project->name = $this->form->name;
        $project->artist_name = $this->form->artistName;
        if ($this->form->projectImage) {
            $path = $this->form->projectImage->store('images', 'public');
            $project->image_path = "/{$path}";
        }
        $project->project_type = $this->form->projectType;
        $project->description = $this->form->description;
        $project->genre = $this->form->genre;
        $project->collaboration_type = [
        'mixing' => $this->form->collaborationTypeMixing,
        'mastering' => $this->form->collaborationTypeMastering,
        'production' => $this->form->collaborationTypeProduction,
        'songwriting' => $this->form->collaborationTypeSongwriting,
        'vocal_tuning' => $this->form->collaborationTypeVocalTuning,
        ];
        $project->budget = $this->form->budget;
        $project->deadline = $this->form->deadline;
        $project->preview_track = $this->form->track;
        $project->notes = $this->form->notes;
        $project->save();
        return redirect()->route('projects.show', $project);

    }

    public function render()
    {
        return view('livewire.create-project');
    }
}
