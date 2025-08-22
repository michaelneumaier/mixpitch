<?php

namespace App\Livewire;

use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SidebarWorkItems extends Component
{
    public function getWorkItemsProperty()
    {
        if (! Auth::check()) {
            return collect();
        }

        $user = Auth::user();
        $workItems = new Collection;

        // Define active statuses
        $activeProjectStatuses = [
            Project::STATUS_UNPUBLISHED,
            Project::STATUS_OPEN,
            Project::STATUS_IN_PROGRESS,
            Project::STATUS_COMPLETED,
        ];

        $activePitchStatuses = [
            Pitch::STATUS_PENDING, Pitch::STATUS_IN_PROGRESS, Pitch::STATUS_READY_FOR_REVIEW,
            Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_CONTEST_ENTRY, Pitch::STATUS_AWAITING_ACCEPTANCE,
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, Pitch::STATUS_APPROVED, Pitch::STATUS_COMPLETED,
            Pitch::STATUS_CONTEST_WINNER, Pitch::STATUS_CONTEST_RUNNER_UP,
        ];

        // Get user's projects (as musician/owner)
        $userProjects = Project::where('user_id', $user->id)
            ->whereIn('status', $activeProjectStatuses)
            ->orderBy('updated_at', 'desc')
            ->get();

        // Get pitches where user is the producer
        $userPitches = Pitch::where('user_id', $user->id)
            ->whereIn('status', $activePitchStatuses)
            ->orderBy('updated_at', 'desc')
            ->get();

        // Get client management projects where user is the client
        $clientProjects = Project::where(function ($query) use ($user) {
            $query->where('client_user_id', $user->id)
                ->orWhere('client_email', $user->email);
        })
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereIn('status', $activeProjectStatuses)
            ->orderBy('updated_at', 'desc')
            ->get();

        // Merge collections
        $workItems = $userProjects->merge($userPitches)->merge($clientProjects);

        return $workItems->sortByDesc('updated_at')->take(5); // Show latest 5 items
    }

    public function getWorkItemCounts()
    {
        if (! Auth::check()) {
            return [
                'projects' => 0,
                'pitches' => 0,
                'contests' => 0,
                'client_projects' => 0,
                'total' => 0,
            ];
        }

        $user = Auth::user();

        // Count active projects
        $projectsCount = Project::where('user_id', $user->id)
            ->whereIn('status', [
                Project::STATUS_UNPUBLISHED,
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])
            ->count();

        // Count active pitches
        $pitchesCount = Pitch::where('user_id', $user->id)
            ->whereIn('status', [
                Pitch::STATUS_PENDING, Pitch::STATUS_IN_PROGRESS, Pitch::STATUS_READY_FOR_REVIEW,
                Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_AWAITING_ACCEPTANCE,
                Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, Pitch::STATUS_APPROVED,
            ])
            ->count();

        // Count contest entries
        $contestsCount = Pitch::where('user_id', $user->id)
            ->whereIn('status', [
                Pitch::STATUS_CONTEST_ENTRY,
                Pitch::STATUS_CONTEST_WINNER,
                Pitch::STATUS_CONTEST_RUNNER_UP,
            ])
            ->count();

        // Count client projects
        $clientProjectsCount = Project::where(function ($query) use ($user) {
            $query->where('client_user_id', $user->id)
                ->orWhere('client_email', $user->email);
        })
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereIn('status', [
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])
            ->count();

        $total = $projectsCount + $pitchesCount + $contestsCount + $clientProjectsCount;

        return [
            'projects' => $projectsCount,
            'pitches' => $pitchesCount,
            'contests' => $contestsCount,
            'client_projects' => $clientProjectsCount,
            'total' => $total,
        ];
    }

    public function render()
    {
        return view('livewire.sidebar-work-items', [
            'workItems' => $this->workItems,
            'counts' => $this->getWorkItemCounts(),
        ]);
    }
}
