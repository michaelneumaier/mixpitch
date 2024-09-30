<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Project;
use Livewire\WithPagination;

class ProjectsComponent extends Component
{
    use WithPagination;

    public $genres = [];
    public $statuses = [];
    public $search = '';
    public $sortBy = 'latest';
    public $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'latest'],
        'genres' => ['except' => []],
        'statuses' => ['except' => []],
    ];

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
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        switch ($this->sortBy) {
            case 'budget_high_low':
                $query->orderBy('budget', 'desc');
                break;
            case 'budget_low_high':
                $query->orderBy('budget', 'asc');
                break;
            case 'deadline':
                $query->orderBy('deadline', 'asc');
                break;
            case 'oldest':
                $query->oldest();
                break;
            default:
                $query->latest();
                break;
        }

        return view('livewire.projects-component', [
            'projects' => $query->paginate($this->perPage),
        ]);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSortBy()
    {
        $this->resetPage();
    }

    public function updatedGenres()
    {
        $this->resetPage();
    }

    public function updatedStatuses()
    {
        $this->resetPage();
    }

    #[On('filters-updated')]
    public function applyFilters($filters)
    {
        $this->genres = $filters['genres'];
        $this->statuses = $filters['statuses'];
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->genres = [];
        $this->statuses = [];
        $this->search = '';
        $this->sortBy = 'latest';
        $this->resetPage();
    }

    public function loadMore()
    {
        $this->perPage += 12;
    }
}

