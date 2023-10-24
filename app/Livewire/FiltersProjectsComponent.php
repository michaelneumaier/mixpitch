<?php

namespace App\Livewire;

use Livewire\Component;

class FiltersProjectsComponent extends Component
{
    public $genres = [];
    public $statuses = [];
    protected $listeners = [
        'filtersUpdated' => 'updateFilters',
    ];

    public function render()
    {
        return view('livewire.filters-projects-component');
    }

    public function updatedGenres()
    {
        $this->dispatch('filtersUpdated', genres: $this->genres, statuses: $this->statuses);
    }

    public function updatedStatuses()
    {
        $this->dispatch('filtersUpdated', genres: $this->genres, statuses: $this->statuses);
    }

    public function updateFilters($filters)
    {
        $this->genres = $filters['genres'];
        $this->statuses = $filters['statuses'];
    }

    public function clearFilters()
    {
        $this->genres = [];
        $this->statuses = [];
        $this->dispatch('filtersUpdated', genres: $this->genres, statuses: $this->statuses);
    }
}
