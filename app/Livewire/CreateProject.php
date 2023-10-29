<?php

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateProject extends Component
{
    use WithFileUploads;

    #[Rule('required|string|min:5|max:80')]
    public $name;

    #[Rule('nullable|string|max:30')]
    public $artistName;

    #[Rule('required|in:single,album,ep,other')]
    public $projectType;

    #[Rule('required|string|min:5|max:1000')]
    public $description;

    #[Rule('required|in:Blues,Classical,Country,Electronic,Folk,Funk,Hip-Hop,Jazz,Metal,Pop,Reggae,Rock,Soul,R&B,Punk')]
    public $genre;

    #[Rule('nullable|image|max:2048')]
    public $projectImage;

    #[Rule('boolean')]
    public $collaborationTypeMixing = false;

    #[Rule('boolean')]
    public $collaborationTypeMastering = false;

    #[Rule('boolean')]
    public $collaborationTypeProduction = false;

    #[Rule('boolean')]
    public $collaborationTypeSongwriting = false;

    #[Rule('boolean')]
    public $collaborationTypeVocalTuning = false;

    #[Rule('required|numeric|min:0')]
    public $budget = 0;

    #[Rule('required|date|after:today')]
    public $deadline;

    #[Rule('nullable|mimes:mp3,wav,aiff|max:20480')]
    public $track;

    #[Rule('nullable|string|max:1000')]
    public $notes;

    public function save()
    {
        $this->validate();
        $project = new Project();
        $project->user_id = auth()->id();
        $project->name = $this->name;
        $project->artist_name = $this->artistName;
        if ($this->projectImage) {
            $path = $this->projectImage->store('images', 'public');
            $project->image_path = "/{$path}";
        }
        $project->project_type = $this->projectType;
        $project->description = $this->description;
        $project->genre = $this->genre;
        $project->collaboration_type = [
        'mixing' => $this->collaborationTypeMixing,
        'mastering' => $this->collaborationTypeMastering,
        'production' => $this->collaborationTypeProduction,
        'songwriting' => $this->collaborationTypeSongwriting,
        'vocal_tuning' => $this->collaborationTypeVocalTuning,
        ];
        $project->budget = $this->budget;
        $project->deadline = $this->deadline;
        $project->preview_track = $this->track;
        $project->notes = $this->notes;
        $project->save();
        return redirect()->route('projects.show', $project);

    }

    public function render()
    {
        return view('livewire.create-project');
    }
}
