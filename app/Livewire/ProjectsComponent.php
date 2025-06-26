<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Project;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

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
    public $min_budget = null;
    public $max_budget = null;
    public $deadline_start = null;
    public $deadline_end = null;
    public $selected_collaboration_types = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'latest'],
        'genres' => ['except' => []],
        'statuses' => ['except' => []],
        'projectTypes' => ['except' => []],
        'min_budget' => ['except' => null],
        'max_budget' => ['except' => null],
        'deadline_start' => ['except' => null],
        'deadline_end' => ['except' => null],
        'selected_collaboration_types' => ['except' => []],
        'viewMode' => ['except' => 'list'],
    ];

    /**
     * Initialize the component and normalize the query parameters
     */
    public function mount()
    {
        // Convert empty string parameters to null
        $this->min_budget = $this->min_budget === '' ? null : $this->min_budget;
        $this->max_budget = $this->max_budget === '' ? null : $this->max_budget;
        $this->deadline_start = $this->deadline_start === '' ? null : $this->deadline_start;
        $this->deadline_end = $this->deadline_end === '' ? null : $this->deadline_end;
    }

    public function render()
    {
        $filters = [
            'genres' => $this->genres,
            'statuses' => $this->statuses,
            'projectTypes' => $this->projectTypes,
            'search' => $this->search,
            'min_budget' => $this->min_budget,
            'max_budget' => $this->max_budget,
            'deadline_start' => $this->deadline_start,
            'deadline_end' => $this->deadline_end,
            'selected_collaboration_types' => $this->selected_collaboration_types,
            'sortBy' => $this->sortBy,
        ];

        $query = Project::query()
            ->whereNotIn('status', ['unpublished'])
            ->where(function ($q) {
                $userId = Auth::id();
                $q->whereIn('workflow_type', [
                    Project::WORKFLOW_TYPE_STANDARD,
                    Project::WORKFLOW_TYPE_CONTEST
                ])
                ->orWhere(function ($subQ) use ($userId) {
                    $subQ->where('workflow_type', Project::WORKFLOW_TYPE_DIRECT_HIRE);
                    if ($userId) {
                        $subQ->where(function ($userCheck) use ($userId) {
                            $userCheck->where('user_id', $userId)
                                      ->orWhere('target_producer_id', $userId);
                        });
                    } else {
                        $subQ->whereRaw('1 = 0');
                    }
                });
                // REMOVED: Client Management projects should NEVER appear on /projects page
                // This is a public marketplace, not a place to manage private client projects
            })
            ->filterAndSort($filters);

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

    public function updatedMinBudget($value)
    {
        // Coerce empty string from input to null for query string consistency
        $this->min_budget = ($value === '' || $value === false) ? null : $value;
        $this->resetPage();
    }

    public function updatedMaxBudget($value)
    {
        // Coerce empty string from input to null for query string consistency
        $this->max_budget = ($value === '' || $value === false) ? null : $value;
        $this->resetPage();
    }

    public function updatedDeadlineStart()
    {
        $this->resetPage();
    }

    public function updatedDeadlineEnd()
    {
        $this->resetPage();
    }

    public function updatedSelectedCollaborationTypes()
    {
        $this->resetPage();
    }

    #[On('filters-updated')]
    public function applyFilters($filters)
    {
        $this->genres = $filters['genres'] ?? [];
        $this->statuses = $filters['statuses'] ?? [];
        $this->projectTypes = $filters['projectTypes'] ?? [];
        $this->min_budget = $filters['min_budget'] ?? null;
        $this->max_budget = $filters['max_budget'] ?? null;
        $this->deadline_start = $filters['deadline_start'] ?? null;
        $this->deadline_end = $filters['deadline_end'] ?? null;
        $this->selected_collaboration_types = $filters['selected_collaboration_types'] ?? [];
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->genres = [];
        $this->statuses = [];
        $this->projectTypes = [];
        $this->search = '';
        $this->sortBy = 'latest';
        $this->min_budget = null;
        $this->max_budget = null;
        $this->deadline_start = null;
        $this->deadline_end = null;
        $this->selected_collaboration_types = [];
        $this->resetPage();
        
        // Dispatch an event to notify child components that filters have been cleared
        $this->dispatch('filters-cleared');
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

    /**
     * Remove budget filter
     */
    public function removeBudgetFilter()
    {
        $this->min_budget = null;
        $this->max_budget = null;
        $this->resetPage();
    }

    /**
     * Remove deadline filter
     */
    public function removeDeadlineFilter()
    {
        $this->deadline_start = null;
        $this->deadline_end = null;
        $this->resetPage();
    }

    /**
     * Remove a specific collaboration type filter
     */
    public function removeCollaborationTypeFilter($type)
    {
        $this->selected_collaboration_types = array_filter($this->selected_collaboration_types, function ($item) use ($type) {
            return $item !== $type;
        });
        $this->resetPage();
    }

    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'card' ? 'list' : 'card';
    }
    
    /**
     * Listen for the clear-parent-filters event from child components
     */
    #[On('clear-parent-filters')]
    public function handleClearParentFilters()
    {
        $this->clearFilters();
    }
}
