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
    public $projectTypes = [];
    public $search = '';
    public $sortBy = 'latest';
    public $perPage = 12;
    public $viewMode = 'list'; // 'card' or 'list'

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'latest'],
        'genres' => ['except' => []],
        'statuses' => ['except' => []],
        'projectTypes' => ['except' => []],
        'viewMode' => ['except' => 'list'],
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
        if (!empty($this->projectTypes)) {
            $query->whereIn('project_type', $this->projectTypes);
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

    public function updatedProjectTypes()
    {
        $this->resetPage();
    }

    #[On('filters-updated')]
    public function applyFilters($filters)
    {
        $this->genres = $filters['genres'];
        $this->statuses = $filters['statuses'];
        $this->projectTypes = $filters['projectTypes'];
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->genres = [];
        $this->statuses = [];
        $this->projectTypes = [];
        $this->search = '';
        $this->sortBy = 'latest';
        $this->resetPage();
    }

    public function loadMore()
    {
        $this->perPage += 12;
    }

    /**
     * Remove a specific genre filter
     */
    public function removeGenre($genre)
    {
        $this->genres = array_filter($this->genres, function ($item) use ($genre) {
            return $item !== $genre;
        });
        $this->resetPage();
    }

    /**
     * Remove a specific status filter
     */
    public function removeStatus($status)
    {
        $this->statuses = array_filter($this->statuses, function ($item) use ($status) {
            return $item !== $status;
        });
        $this->resetPage();
    }

    /**
     * Remove a specific project type filter
     */
    public function removeProjectType($projectType)
    {
        $this->projectTypes = array_filter($this->projectTypes, function ($item) use ($projectType) {
            return $item !== $projectType;
        });
        $this->resetPage();
    }

    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'card' ? 'list' : 'card';
    }
}
