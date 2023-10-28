<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class FiltersProjectsComponent extends Component
{
    public $genres = [];
    public $statuses = [];
    // protected $listeners = [
    //     'filtersUpdated' => 'updateFilters',
    // ];

    public function render()
    {
        return view('livewire.filters-projects-component');
    }

    public function dispatchFiltersUpdated()
    {
        $this->dispatch('filters-updated', ['genres' => $this->genres, 'statuses' => $this->statuses]);
        //$this->dispatch('filtersUpdated', genres: $this->genres, statuses: $this->statuses);
    }

    public function updatedGenres()
    {
        $this->dispatchFiltersUpdated();

    }

    public function updatedStatuses()
    {
        $this->dispatchFiltersUpdated();

    }

    #[On('filters-updated')]
    public function updateFilters($filters)
    {
        $this->genres = $filters['genres'];
        $this->statuses = $filters['statuses'];
    }

    public function clearFilters()
    {
        $this->genres = [];
        $this->statuses = [];
        $this->dispatchFiltersUpdated();
    }
}
