<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ClientManagementDashboard extends Component
{
    use WithPagination;

    // Search and filtering
    public string $search = '';
    public string $statusFilter = 'all';
    public string $sortBy = 'last_contacted_at';
    public string $sortDirection = 'desc';
    
    // Component properties
    public bool $expanded = false;
    public ?int $userId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'sortBy' => ['except' => 'last_contacted_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount($userId = null, $expanded = false)
    {
        $this->userId = $userId ?? Auth::id();
        $this->expanded = $expanded;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
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
        
        return Project::where('projects.user_id', $userId)
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
            ->orderBy('projects.created_at', $this->sortDirection)
            ->paginate(20);
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
            'unique_clients' => $clientProjects->whereNotNull('client_email')->unique('client_email')->count(),
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

    public function render()
    {
        return view('livewire.client-management-dashboard');
    }
}
