<?php

namespace App\Http\Livewire;

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Project;
use Livewire\WithPagination;

class ProjectsComponent extends Component
{
    use WithPagination;

    public $genres = [];
    public $statuses = [];
    protected $listeners = ['filtersUpdated' => 'applyFilters'];

    public function render()
    {
        $query = Project::query();

        $query->whereNotIn('status', ['unpublished']);

        if (!empty($this->genres)) {
            $query->whereIn('genre', $this->genres);
        }
        if (!empty($this->statuses)) {
            $query->whereIn('status', $this->statuses);
        }

        return view('livewire.projects-component', [
            'projects' => $query->paginate(10),
        ]);
    }

    public function updatedGenres()
    {
        $this->resetPage();
    }

    public function updatedStatuses()
    {
        $this->resetPage();
    }

    public function applyFilters($filters)
    {
        $this->genres = $filters['genres'];
        $this->statuses = $filters['statuses'];
        $this->render();
    }

    public function clearFilters()
    {
        $this->genres = [];
        $this->statuses = [];
    }

}

