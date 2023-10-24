<?php

namespace App\Http\Livewire;

use App\Models\Project;
use Livewire\Component;

class CreateProject extends Component
{

    public $name;
    public $artistName;
    public $projectType;
    public $description;
    public $genre;
    public $projectImage;
    public $collaborationType = [
        'mixing' => false,
        'mastering' => false,
        'production' => false,
        'songwriting' => false,
        'vocal_tuning' => false
    ];
    public $budget;
    public $deadline;
    public $track;
    public $notes;

    protected $rules = [
        'name' => 'required|string|min:5|max:255',
        'artistName' => 'nullable|string|max:255',
        'projectType' => 'required|in:single,album,ep,other',
        'description' => 'required|string|max:1000',
        'genre' => 'required|string|max:255',
        'projectImage' => 'nullable|image|max:2048',
        'collaborationType.mixing' => 'boolean',
        'collaborationType.mastering' => 'boolean',
        'collaborationType.production' => 'boolean',
        'collaborationType.songwriting' => 'boolean',
        'collaborationType.vocal_tuning' => 'boolean',
        'budget' => 'required|numeric|min:0',
        'deadline' => 'required|date|after:today',
        'track' => 'nullable|mimes:mp3,wav,aiff|max:20480',
        'notes' => 'nullable|string|max:1000'
    ];


    public function submitForm()
    {
        // Logic to save your project or perform other actions.

        // Once done, send a browser event to show the notification.
        $this->dispatchBrowserEvent('notify', ['msg' => 'Project successfully created!']);
    }

    // public function updated($propertyName)
    // {
    //     $this->validateOnly($propertyName);
    // }

    public function createProject()
    {
        // Validation
        $this->validate($this->rules);

        $this->dispatchBrowserEvent('notify', ['msg' => 'Project successfully created!']);
    }

    public function render()
    {
        return view('livewire.create-project');
    }
}
