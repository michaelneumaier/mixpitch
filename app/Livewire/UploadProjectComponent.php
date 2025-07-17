<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use Livewire\WithFileUploads;

class UploadProjectComponent extends Component
{
    use WithFileUploads;

    public $step = 1;

    public $projectName;

    public $projectGenre;

    public $projectDescription;

    public $projectImage = null;

    public $files;

    public $projectId;

    public $projectSlug;

    public function nextStep(): void
    {
        $this->step++;
    }

    public function render()
    {
        return view('livewire.upload-project-component');
    }

    public function saveProject(): void
    {

        // Validate your data
        $this->validate([
            'projectName' => 'required|max:255',
            'projectGenre' => 'required|in:Pop,Rock,Country,Hip Hop,Jazz',
            'projectDescription' => 'max:2048',
            'projectImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $project = new Project;
        $project->user_id = auth()->id();
        $project->name = $this->projectName;
        $project->description = $this->projectDescription;
        $project->genre = $this->projectGenre;
        if ($this->projectImage != null) {
            $imageName = $this->projectImage->store('images', 'public');
            $project->image_path = "/{$imageName}";
        }
        $project->save();

        // $project = Project::create([
        //     'user_id' => auth()->id(),
        //     'name' => $this->projectName,
        //     'genre' => $this->projectGenre,
        // ]);

        $this->projectId = $project->id;
        $this->projectSlug = $project->slug;
        $this->step = 2;

    }
}
