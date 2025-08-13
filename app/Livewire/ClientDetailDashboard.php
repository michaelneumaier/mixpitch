<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ClientDetailDashboard extends Component
{
    use WithPagination;

    public Client $client;
    public string $view = 'kanban'; // 'kanban' or 'list'
    
    // Search and filtering (client-scoped)
    public string $search = '';
    public string $statusFilter = 'all';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'sortDirection' => ['except' => 'desc'],
        'view' => ['except' => 'kanban'],
    ];

    public function mount(Client $client)
    {
        // Ensure the client belongs to the current user
        if ($client->user_id !== Auth::id()) {
            abort(403, 'Access denied. You can only view your own clients.');
        }
        
        $this->client = $client;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function switchView($view)
    {
        if (in_array($view, ['kanban', 'list'])) {
            $this->view = $view;
        }
    }

    /**
     * Get client-specific projects
     */
    public function getClientProjectsProperty()
    {
        $query = Project::where('user_id', Auth::id())
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->where('client_id', $this->client->id)
            ->with(['pitches' => function ($query) {
                $query->with(['user', 'files', 'events']);
            }])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('created_at', $this->sortDirection);

        return $query->paginate(20);
    }

    /**
     * Get client-specific statistics
     */
    public function getClientStatsProperty()
    {
        $projects = Project::where('user_id', Auth::id())
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->where('client_id', $this->client->id)
            ->with('pitches')
            ->get();

        $totalRevenue = $projects->sum(function ($project) {
            return $project->pitches->where('payment_status', 'paid')->sum('payment_amount');
        });

        $activeProjects = $projects->whereIn('status', ['open', 'in_progress'])->count();
        $completedProjects = $projects->where('status', 'completed')->count();
        $averageProjectValue = $projects->count() > 0 ? $totalRevenue / $projects->count() : 0;

        return [
            'total_projects' => $projects->count(),
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'total_revenue' => $totalRevenue,
            'average_project_value' => $averageProjectValue,
            'completion_rate' => $projects->count() > 0 ? round(($completedProjects / $projects->count()) * 100, 1) : 0,
        ];
    }

    public function render()
    {
        return view('livewire.client-detail-dashboard');
    }
}