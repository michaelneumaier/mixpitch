<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Rule;
use Livewire\Form;
use Livewire\WithFileUploads;

class ProjectForm extends Form
{

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
    public $budget;

    #[Rule('required|date|after:today')]
    public $deadline;

    #[Rule('nullable|mimes:mp3,wav,aiff|max:20480')]
    public $track;

    #[Rule('nullable|string|max:1000')]
    public $notes;

}
