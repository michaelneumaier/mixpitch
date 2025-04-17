<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class FiltersProjectsComponent extends Component
{
    public $genres = [];
    public $statuses = [];
    public $projectTypes = [];
    public $selected_collaboration_types = [];
    public $min_budget = null;
    public $max_budget = null;
    public $deadline_start = null;
    public $deadline_end = null;
    
    // Define available collaboration types (adjust as needed)
    public $availableCollaborationTypes = [
        'Mixing',
        'Mastering',
        'Production',
        'Songwriting',
        'Vocal Tuning',
        // Removed outdated types like 'Producer', 'Mixing Engineer', etc.
    ];

    public function mount(
        $genres = [], 
        $statuses = [], 
        $projectTypes = [], 
        $min_budget = null, 
        $max_budget = null, 
        $deadline_start = null, 
        $deadline_end = null, 
        $selected_collaboration_types = []
    ) {
        $this->genres = $genres;
        $this->statuses = $statuses;
        $this->projectTypes = $projectTypes;
        $this->min_budget = $min_budget;
        $this->max_budget = $max_budget;
        $this->deadline_start = $deadline_start;
        $this->deadline_end = $deadline_end;
        $this->selected_collaboration_types = $selected_collaboration_types;
    }

    public function render()
    {
        return view('livewire.filters-projects-component');
    }

    public function dispatchFiltersUpdated()
    {
        $this->dispatch('filters-updated', [
            'genres' => $this->genres, 
            'statuses' => $this->statuses,
            'projectTypes' => $this->projectTypes,
            'selected_collaboration_types' => $this->selected_collaboration_types,
            // 'selected_skills' => $this->selected_skills, // Add this when skills input is implemented
        ]);
    }

    public function updatedGenres()
    {
        $this->dispatchFiltersUpdated();
    }

    public function updatedStatuses()
    {
        $this->dispatchFiltersUpdated();
    }

    public function updatedProjectTypes()
    {
        $this->dispatchFiltersUpdated();
    }

    public function updatedSkillSearchPlaceholder() // Placeholder hook
    {
        // For now, this doesn't update the parent. 
        // When real skills filter is added, this will dispatch selected skills.
        //$this->dispatchFiltersUpdated(); 
    }

    public function updatedSelectedCollaborationTypes()
    {
        $this->dispatchFiltersUpdated();
    }

    #[On('filters-updated')]
    public function updateFilters($filters)
    {
        $this->genres = $filters['genres'] ?? [];
        $this->statuses = $filters['statuses'] ?? [];
        $this->projectTypes = $filters['projectTypes'] ?? [];
        $this->selected_collaboration_types = $filters['selected_collaboration_types'] ?? [];
        // $this->selected_skills = $filters['selected_skills'] ?? []; // Add when implemented
    }
    
    /**
     * Listen for the filters-cleared event from the parent and reset local state
     */
    #[On('filters-cleared')]
    public function resetFilters()
    {
        $this->genres = [];
        $this->statuses = [];
        $this->projectTypes = [];
        $this->selected_collaboration_types = [];
        $this->min_budget = null;
        $this->max_budget = null;
        $this->deadline_start = null;
        $this->deadline_end = null;
    }

    /**
     * Handle the "Clear All Filters" button click
     */
    public function clearAllFilters()
    {
        // Reset local state first
        $this->genres = [];
        $this->statuses = [];
        $this->projectTypes = [];
        $this->selected_collaboration_types = [];
        $this->min_budget = null;
        $this->max_budget = null;
        $this->deadline_start = null;
        $this->deadline_end = null;
        
        // Then call parent method to clear parent state and refresh the results
        $this->dispatch('clear-parent-filters');
    }
}
