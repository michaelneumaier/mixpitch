<?php

namespace App\Livewire\Profile;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

// use Illuminate\Support\Facades\Log; // Removed Log facade

class ClientActivitySummary extends Component
{
    public User $client;

    public int $totalProjects = 0;

    public int $hiredProjectsCount = 0;

    public Collection $recentProjects;

    public Collection $completedProjects;

    public function mount(User $client)
    {
        $this->client = $client;
        $this->loadClientActivity();
    }

    public function loadClientActivity()
    {
        // Log::info('Loading ClientActivitySummary for User ID: ' . $this->client->id);

        // Total Projects: Query directly on the relationship
        $this->totalProjects = $this->client->projects()->count();
        // Log::info('Total Projects Count: ' . $this->totalProjects);

        // Hired Projects: Query separately for projects considered 'hired'
        // Assuming 'in_progress' or 'completed' signifies a hire for now.
        $this->hiredProjectsCount = $this->client->projects()
            ->whereIn('status', [Project::STATUS_IN_PROGRESS, Project::STATUS_COMPLETED])
            ->count();
        // Log::info('Hired Projects Count: ' . $this->hiredProjectsCount);

        // Recent Projects: Fetch the latest published projects
        $this->recentProjects = $this->client->projects()
            ->where('is_published', true)
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'slug', 'status', 'created_at', 'is_published']); // Select specific columns
        // Log::info('Recent Projects Found: ' . $this->recentProjects->count());

        // Completed Projects: Fetch the latest completed published projects
        $this->completedProjects = $this->client->projects()
            ->where('status', Project::STATUS_COMPLETED)
            ->where('is_published', true)
            ->when($this->client->projects()->whereNotNull('completed_at')->exists(),
                fn ($query) => $query->latest('completed_at'), // Order by completion date if available
                fn ($query) => $query->latest('updated_at')  // Fallback to updated_at
            )
            ->limit(5)
            ->get(['id', 'name', 'slug', 'status', 'completed_at', 'created_at', 'is_published']); // Select specific columns
        // Log::info('Completed Projects Found: ' . $this->completedProjects->count());

        // Initialize collections if null (shouldn't be needed with get() but safe)
        $this->recentProjects = $this->recentProjects ?? collect();
        $this->completedProjects = $this->completedProjects ?? collect();
    }

    public function render()
    {
        return view('livewire.profile.client-activity-summary');
    }
}
