<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Rule;
use Livewire\Form;
use Livewire\WithFileUploads;
use App\Models\Project;
use Illuminate\Validation\Rule as ValidationRule;

class ProjectForm extends Form
{

    public $name;

    public $artistName;

    public $projectType = 'single';

    public $description;

    public $genre;

    public $projectImage;

    public $collaborationTypeMixing = false;

    public $collaborationTypeMastering = false;

    public $collaborationTypeProduction = false;

    public $collaborationTypeSongwriting = false;

    public $collaborationTypeVocalTuning = false;

    public $budgetType;

    public $budget;

    public $deadline;

    public $track;

    public $notes;

    public $submissionDeadline;
    public $judgingDeadline;
    public $prizeAmount;

    public function rules()
    {
        $rules = [
            'name' => 'required|string|min:5|max:80',
            'artistName' => 'nullable|string|max:30',
            'projectType' => ['required', 'string', 'max:50'],
            'description' => 'required|string|min:5|max:1000',
            'genre' => 'required|in:Blues,Classical,Country,Electronic,Folk,Funk,Hip-Hop,Jazz,Metal,Pop,Reggae,Rock,Soul,R&B,Punk',
            'projectImage' => 'nullable|image|max:2048',
            'collaborationTypeMixing' => 'boolean',
            'collaborationTypeMastering' => 'boolean',
            'collaborationTypeProduction' => 'boolean',
            'collaborationTypeSongwriting' => 'boolean',
            'collaborationTypeVocalTuning' => 'boolean',
            'budgetType' => 'required|in:free,paid',
            'budget' => 'nullable',
            'deadline' => 'nullable|date',
            'track' => 'nullable|mimes:mp3,wav,aiff|max:20480',
            'notes' => 'nullable|string|max:1000',
        ];

        return $rules;
    }

    /**
     * Format budget for database storage
     * Ensures budget is properly cast to a numeric value
     * 
     * @return int|float
     */
    public function getFormattedBudget()
    {
        if ($this->budgetType === 'free') {
            return 0;
        }
        
        if (empty($this->budget)) {
            return 0;
        }
        
        // Strip currency symbols and formatting
        $stripped = preg_replace('/[^\d.]/', '', $this->budget);
        
        // Convert to float if numeric
        return is_numeric($stripped) ? (float)$stripped : 0;
    }

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->name = $project->name;
        $this->artistName = $project->artist_name;
        $this->projectType = $project->project_type;
        $this->description = $project->description;
        $this->genre = $project->genre;
        $this->collaborationTypeMixing = in_array('Mixing', $project->collaboration_type ?? []);
        $this->collaborationTypeMastering = in_array('Mastering', $project->collaboration_type ?? []);
        $this->collaborationTypeProduction = in_array('Production', $project->collaboration_type ?? []);
        $this->collaborationTypeSongwriting = in_array('Songwriting', $project->collaboration_type ?? []);
        $this->collaborationTypeVocalTuning = in_array('Vocal Tuning', $project->collaboration_type ?? []);
        $this->budget = $project->budget ?? 0;
        $this->budgetType = $this->budget > 0 ? 'paid' : 'free';
        $this->notes = $project->notes;
    }
}
