<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\ClientReminder;
use App\Models\ClientView;
use App\Models\Project;
use App\Models\Pitch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ClientManagementDashboard extends Component
{
    use WithPagination;

    // Search and filtering
    public string $search = '';
    public string $statusFilter = 'all';
    public string $clientFilter = 'all';
    public string $sortBy = 'last_contacted_at';
    public string $sortDirection = 'desc';
    
    // Component properties
    public bool $expanded = false;
    public ?int $userId = null;

    // Modal and form state
    public ?Client $selectedClient = null;
    public bool $showClientModal = false;
    public string $clientName = '';
    public string $clientEmail = '';
    public string $clientCompany = '';
    public string $clientPhone = '';
    public string $clientNotes = '';
    public array $clientTags = [];
    public string $clientStatus = Client::STATUS_ACTIVE;

    // Saved views state
    public ?int $activeViewId = null;
    public array $availableViews = [];
    public string $newViewName = '';
    public bool $newViewDefault = false;

    // New reminder form state
    public ?int $newReminderClientId = null;
    public string $newReminderNote = '';
    public string $newReminderDueAt = '';
    public array $clientsForSelect = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'clientFilter' => ['except' => 'all'],
        'sortBy' => ['except' => 'last_contacted_at'],
        'sortDirection' => ['except' => 'desc'],
        'activeViewId' => ['except' => null],
    ];

    public function mount($userId = null, $expanded = false)
    {
        $this->userId = $userId ?? Auth::id();
        $this->expanded = $expanded;

        // Load saved views
        $this->availableViews = ClientView::where('user_id', $this->userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'filters', 'is_default'])
            ->toArray();

        // If no active view specified, use default if available
        if (!$this->activeViewId) {
            $defaultView = collect($this->availableViews)->firstWhere('is_default', true);
            if ($defaultView) {
                $this->activeViewId = $defaultView['id'];
                $this->applyViewFilters($defaultView['filters'] ?? []);
            }
        }

        // Load clients for quick reminder creation
        $this->clientsForSelect = Client::where('user_id', $this->userId)
            ->orderByRaw('COALESCE(NULLIF(name, ""), email) asc')
            ->get(['id', 'name', 'email'])
            ->map(fn($c) => [
                'id' => $c->id,
                'label' => $c->name ? ($c->name.' — '.$c->email) : $c->email,
            ])->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingClientFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage();
    }

    public function showClient(int $clientId)
    {
        $this->selectedClient = Client::with(['projects.pitches'])
            ->where('user_id', Auth::id())
            ->findOrFail($clientId);
        $this->showClientModal = true;
    }

    public function editClient(int $clientId)
    {
        $client = Client::where('user_id', Auth::id())->findOrFail($clientId);
        
        $this->selectedClient = $client;
        $this->clientName = $client->name ?? '';
        $this->clientEmail = $client->email;
        $this->clientCompany = $client->company ?? '';
        $this->clientPhone = $client->phone ?? '';
        $this->clientNotes = $client->notes ?? '';
        $this->clientTags = $client->tags ?? [];
        $this->clientStatus = $client->status;
        
        $this->showClientModal = true;
    }

    public function saveClient()
    {
        $this->validate();

        $clientData = [
            'user_id' => Auth::id(),
            'name' => $this->clientName ?: null,
            'email' => $this->clientEmail,
            'company' => $this->clientCompany ?: null,
            'phone' => $this->clientPhone ?: null,
            'notes' => $this->clientNotes ?: null,
            'tags' => empty($this->clientTags) ? null : $this->clientTags,
            'status' => $this->clientStatus,
        ];

        if ($this->selectedClient) {
            // Update existing client
            $this->selectedClient->update($clientData);
            session()->flash('success', 'Client updated successfully!');
        } else {
            // Create new client
            Client::create($clientData);
            session()->flash('success', 'Client created successfully!');
        }

        $this->closeClientModal();
    }

    public function deleteClient(int $clientId)
    {
        $client = Client::where('user_id', Auth::id())->findOrFail($clientId);
        
        // Check if client has any projects
        if ($client->projects()->count() > 0) {
            session()->flash('error', 'Cannot delete client with existing projects.');
            return;
        }
        
        $client->delete();
        session()->flash('success', 'Client deleted successfully!');
    }

    public function markAsContacted(int $clientId)
    {
        $client = Client::where('user_id', Auth::id())->findOrFail($clientId);
        $client->markAsContacted();
        
        session()->flash('success', 'Client marked as contacted!');
    }

    public function createProjectForClient(int $clientId)
    {
        $client = Client::where('user_id', Auth::id())->findOrFail($clientId);
        
        return redirect()->route('projects.create', [
            'workflow_type' => 'client_management',
            'client_email' => $client->email,
            'client_name' => $client->name,
        ]);
    }

    public function closeClientModal()
    {
        $this->showClientModal = false;
        $this->selectedClient = null;
        $this->reset([
            'clientName', 'clientEmail', 'clientCompany', 
            'clientPhone', 'clientNotes', 'clientTags', 'clientStatus'
        ]);
        $this->resetValidation();
    }

    public function openNewClientModal()
    {
        $this->reset([
            'clientName', 'clientEmail', 'clientCompany', 
            'clientPhone', 'clientNotes', 'clientTags'
        ]);
        $this->clientStatus = 'active';
        $this->selectedClient = null;
        $this->showClientModal = true;
    }

    public function addTag(string $tag)
    {
        $tag = trim($tag);
        if ($tag && !in_array($tag, $this->clientTags)) {
            $this->clientTags[] = $tag;
        }
    }

    public function removeTag(int $index)
    {
        unset($this->clientTags[$index]);
        $this->clientTags = array_values($this->clientTags);
    }

    public function getClientProjectsProperty()
    {
        $userId = $this->userId ?? Auth::id();
        
        $query = Project::where('projects.user_id', $userId)
            ->where('projects.workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->with(['pitches' => function ($query) {
                $query->with(['user', 'files', 'events']);
            }])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('projects.name', 'like', '%' . $this->search . '%')
                      ->orWhere('projects.client_name', 'like', '%' . $this->search . '%')
                      ->orWhere('projects.client_email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('projects.status', $this->statusFilter);
            })
            ->when($this->clientFilter !== 'all', function ($query) {
                $query->where('projects.client_id', $this->clientFilter);
            })
            ->orderBy('projects.created_at', $this->sortDirection);

        // Apply active saved view filters
        if ($this->activeViewId) {
            $view = ClientView::where('user_id', $userId)->find($this->activeViewId);
            if ($view) {
                $this->applyFiltersToQuery($query, $view->filters ?? []);
            }
        }

        return $query->paginate(20);
    }

    /**
     * Get all clients for the filter dropdown
     */
    public function getClientsForFilterProperty()
    {
        $userId = $this->userId ?? Auth::id();
        
        return Client::where('user_id', $userId)
            ->orderByRaw('COALESCE(NULLIF(name, ""), email) asc')
            ->get(['id', 'name', 'email'])
            ->map(fn($c) => [
                'id' => $c->id,
                'label' => $c->name ? ($c->name.' — '.$c->email) : $c->email,
            ]);
    }

    public function getStatsProperty()
    {
        $userId = $this->userId ?? Auth::id();
        
        $clientProjects = Project::where('user_id', $userId)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->get();
            
        return [
            'total_projects' => $clientProjects->count(),
            'active_projects' => $clientProjects->whereNotIn('status', [Project::STATUS_COMPLETED])->count(),
            'completed_projects' => $clientProjects->where('status', Project::STATUS_COMPLETED)->count(),
            // Prefer distinct client_id; fallback to distinct client_email for any legacy rows
            'unique_clients' => $this->getUniqueClientsCount($userId),
            'total_revenue' => $this->getTotalRevenue(),
            'avg_project_value' => $this->getAverageProjectValue(),
        ];
    }

    protected function getTotalRevenue()
    {
        $userId = $this->userId ?? Auth::id();
        
        return Project::where('projects.user_id', $userId)
            ->where('projects.workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->join('pitches', 'projects.id', '=', 'pitches.project_id')
            ->where('pitches.payment_status', 'paid')
            ->sum('pitches.payment_amount') ?? 0;
    }

    protected function getAverageProjectValue()
    {
        $userId = $this->userId ?? Auth::id();
        $totalRevenue = $this->getTotalRevenue();
        $completedProjects = Project::where('user_id', $userId)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->where('status', Project::STATUS_COMPLETED)
            ->count();
            
        return $completedProjects > 0 ? ($totalRevenue / $completedProjects) : 0;
    }

    protected function getUniqueClientsCount(int $userId): int
    {
        $distinctClientIds = Project::where('user_id', $userId)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereNotNull('client_id')
            ->distinct()
            ->count('client_id');

        $distinctEmailsWithoutClientId = Project::where('user_id', $userId)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereNull('client_id')
            ->whereNotNull('client_email')
            ->distinct()
            ->count('client_email');

        return $distinctClientIds + $distinctEmailsWithoutClientId;
    }

    // --- Reminders ---
    public function getUpcomingRemindersProperty()
    {
        $userId = $this->userId ?? Auth::id();

        return ClientReminder::with('client')
            ->where('user_id', $userId)
            ->where('status', ClientReminder::STATUS_PENDING)
            ->orderBy('due_at')
            ->limit(5)
            ->get();
    }

    public function completeReminder(int $reminderId): void
    {
        $userId = $this->userId ?? Auth::id();
        $reminder = ClientReminder::where('user_id', $userId)->findOrFail($reminderId);
        $reminder->update(['status' => ClientReminder::STATUS_COMPLETED]);
        session()->flash('success', 'Reminder completed.');
    }

    public function snoozeReminder(int $reminderId, string $period = '1d'): void
    {
        $userId = $this->userId ?? Auth::id();
        $reminder = ClientReminder::where('user_id', $userId)->findOrFail($reminderId);

        $newTime = now();
        switch ($period) {
            case '7d':
                $newTime = now()->addDays(7);
                break;
            case '1h':
                $newTime = now()->addHour();
                break;
            case '1d':
            default:
                $newTime = now()->addDay();
                break;
        }

        $reminder->update([
            'snooze_until' => $newTime,
            'due_at' => $newTime,
            'status' => ClientReminder::STATUS_SNOOZED,
        ]);

        session()->flash('success', 'Reminder snoozed.');
    }

     /**
      * Persist a saved view based on current filters.
      */
    public function saveCurrentView(): void
    {
        $name = trim($this->newViewName);
        if ($name === '') {
            session()->flash('error', 'Please provide a name for the view.');
            return;
        }

        $filters = [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'client' => $this->clientFilter,
        ];

        $view = ClientView::create([
            'user_id' => $this->userId,
            'name' => $name,
            'filters' => $filters,
            'is_default' => $this->newViewDefault,
        ]);

        if ($this->newViewDefault) {
            ClientView::where('user_id', $this->userId)
                ->where('id', '!=', $view->id)
                ->update(['is_default' => false]);
        }

        $this->availableViews = ClientView::where('user_id', $this->userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'filters', 'is_default'])
            ->toArray();
        $this->activeViewId = $view->id;
        $this->applyViewFilters($filters);

        $this->newViewName = '';
        $this->newViewDefault = false;
        $this->resetPage();
    }

    public function updatedActiveViewId($value): void
    {
        if (!$value) {
            return;
        }
        $view = ClientView::where('user_id', $this->userId)->find($value);
        if ($view) {
            $this->applyViewFilters($view->filters ?? []);
            $this->resetPage();
        }
    }

    protected function applyViewFilters(array $filters): void
    {
        $this->search = (string)($filters['search'] ?? '');
        $this->statusFilter = (string)($filters['status'] ?? 'all');
        $this->clientFilter = (string)($filters['client'] ?? 'all');
    }

    protected function applyFiltersToQuery($query, array $filters): void
    {
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('projects.status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('projects.name', 'like', "%{$term}%")
                  ->orWhere('projects.client_name', 'like', "%{$term}%")
                  ->orWhere('projects.client_email', 'like', "%{$term}%");
            });
        }
    }

    // --- LTV and Funnel Analytics ---
    public function getLtvStatsProperty(): array
    {
        $userId = $this->userId ?? Auth::id();

        $paid = Project::where('projects.user_id', $userId)
            ->where('projects.workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->join('pitches', 'projects.id', '=', 'pitches.project_id')
            ->where('pitches.payment_status', 'paid')
            ->sum('pitches.payment_amount') ?? 0;

        $uniqueClients = $this->getUniqueClientsCount($userId);
        $avgLtv = $uniqueClients > 0 ? ($paid / $uniqueClients) : 0;

        return [
            'total_ltv' => $paid,
            'avg_client_ltv' => $avgLtv,
        ];
    }

    public function getFunnelStatsProperty(): array
    {
        $userId = $this->userId ?? Auth::id();

        $totalProjects = Project::where('user_id', $userId)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->count();

        $submitted = Pitch::whereHas('project', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT);
            })
            ->whereIn('status', [
                Pitch::STATUS_PENDING,
                Pitch::STATUS_IN_PROGRESS,
            ])->count();

        $review = Pitch::whereHas('project', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT);
            })
            ->where('status', Pitch::STATUS_READY_FOR_REVIEW)
            ->count();

        $approvedCompleted = Pitch::whereHas('project', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT);
            })
            ->whereIn('status', [
                Pitch::STATUS_APPROVED,
                Pitch::STATUS_COMPLETED,
            ])->count();

        return [
            'created' => $totalProjects,
            'submitted' => $submitted,
            'review' => $review,
            'approved_completed' => $approvedCompleted,
        ];
    }

    // --- Reminder creation ---
    public function addReminder(): void
    {
        $userId = $this->userId ?? Auth::id();
        if (!$this->newReminderClientId || trim($this->newReminderDueAt) === '') {
            session()->flash('error', 'Please select a client and due date.');
            return;
        }

        try {
            $dueAt = Carbon::parse($this->newReminderDueAt);
        } catch (\Throwable $e) {
            session()->flash('error', 'Invalid due date/time.');
            return;
        }

        ClientReminder::create([
            'user_id' => $userId,
            'client_id' => $this->newReminderClientId,
            'due_at' => $dueAt,
            'note' => trim($this->newReminderNote) ?: null,
            'status' => ClientReminder::STATUS_PENDING,
        ]);

        $this->newReminderClientId = null;
        $this->newReminderNote = '';
        $this->newReminderDueAt = '';

        session()->flash('success', 'Reminder added.');
    }

    public function quickReminderForProject(int $projectId): void
    {
        $project = Project::with('client')->where('user_id', $this->userId ?? Auth::id())
            ->findOrFail($projectId);
        if ($project->client_id) {
            $this->newReminderClientId = $project->client_id;
            $this->newReminderNote = 'Follow up on "'.$project->name.'"';
            $this->newReminderDueAt = now()->addDay()->format('Y-m-d\TH:i');
            session()->flash('success', 'Client pre-filled for reminder. Choose due date and save.');
        } else {
            session()->flash('error', 'This project is not linked to a client.');
        }
    }

    public function createReminderForSelectedClient(): void
    {
        if (!$this->selectedClient) {
            session()->flash('error', 'No client selected.');
            return;
        }

        $this->newReminderClientId = $this->selectedClient->id;
        $this->newReminderNote = 'Follow up with '.$this->selectedClient->name ?: $this->selectedClient->email;
        $this->newReminderDueAt = now()->addDay()->format('Y-m-d\TH:i');
        $this->showClientModal = false;
        session()->flash('success', 'Reminder pre-filled. Choose due date and save.');
    }

    public function render()
    {
        return view('livewire.client-management-dashboard');
    }
}
