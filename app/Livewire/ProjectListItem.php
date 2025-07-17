<?php

namespace App\Livewire;

use Livewire\Component;

class ProjectListItem extends Component
{
    public $project;

    public $showFullDescription = false;

    public $formattedCollaborationTypes = [];

    public $formattedProjectType;

    public $formattedGenre;

    public function mount($project)
    {
        $this->project = $project;
        $this->formatCollaborationTypes();
        $this->formatProjectType();
        $this->formatGenre();
    }

    /**
     * Format the project type for display
     */
    protected function formatProjectType()
    {
        if (! empty($this->project->project_type)) {
            $this->formattedProjectType = $this->formatTypeString($this->project->project_type);
        } else {
            $this->formattedProjectType = '';
        }
    }

    /**
     * Format the genre for display
     */
    protected function formatGenre()
    {
        if (! empty($this->project->genre)) {
            $this->formattedGenre = $this->formatTypeString($this->project->genre);
        } else {
            $this->formattedGenre = '';
        }
    }

    /**
     * Format collaboration types for display
     */
    protected function formatCollaborationTypes()
    {
        $types = [];

        if (! empty($this->project->collaboration_type)) {
            if (is_array($this->project->collaboration_type)) {
                // Handle array of strings
                foreach ($this->project->collaboration_type as $key => $value) {
                    if (is_string($key) && $value && $value !== false) {
                        // Key-value pair where value is truthy (checkbox style)
                        $types[] = $this->formatTypeString($key);
                    } elseif (is_string($value) && ! empty($value)) {
                        // Array of strings
                        $types[] = $this->formatTypeString($value);
                    } elseif (is_numeric($key) && is_string($value) && ! empty($value)) {
                        // Indexed array of strings
                        $types[] = $this->formatTypeString($value);
                    }
                }
            } elseif (is_string($this->project->collaboration_type)) {
                // Handle single string
                $types[] = $this->formatTypeString($this->project->collaboration_type);
            }
        }

        $this->formattedCollaborationTypes = $types;
    }

    /**
     * Format a type string by replacing underscores with spaces and capitalizing words
     */
    protected function formatTypeString($string)
    {
        // Replace underscores with spaces
        $string = str_replace('_', ' ', $string);

        // Capitalize each word
        return ucwords($string);
    }

    public function toggleDescription()
    {
        $this->showFullDescription = ! $this->showFullDescription;
    }

    public function viewProject()
    {
        return redirect()->route('projects.show', $this->project);
    }

    public function render()
    {
        return view('livewire.project-list-item');
    }
}
