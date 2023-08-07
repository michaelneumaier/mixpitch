<?php

namespace App\Http\Livewire;

use App\Models\Project;
use Livewire\Component;
use Livewire\WithFileUploads;

class UploadProjectComponent extends Component
{
    use WithFileUploads;

    public $step = 1;
    public $projectName;
    public $projectGenre;
    public $projectImage;
    public $files;

    public $projectId;

    public $projectSlug;

    public function nextStep()
    {
        $this->step++;
    }

    public function render()
    {
        return view('livewire.upload-project-component');
    }

    public function saveProject()
    {
        // Validate your data
        $this->validate([
            'projectName' => 'required|max:255',
            'projectGenre' => 'required|in:Pop,Rock,Country,Hip Hop,Jazz',
            'projectImage' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $project = new Project();
        $project->user_id = auth()->id();
        $project->name = $this->projectName;
        $project->genre = $this->projectGenre;
        if ($this->projectImage) {
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
