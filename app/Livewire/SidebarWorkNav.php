<?php

namespace App\Livewire;

use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SidebarWorkNav extends Component
{
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

        // Count active projects (excluding contest and client management projects)
        $projectsCount = Project::where('user_id', $user->id)
            ->whereNotIn('workflow_type', [
                Project::WORKFLOW_TYPE_CONTEST,
                Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            ])
            ->whereIn('status', [
                Project::STATUS_UNPUBLISHED,
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])
            ->count();

        // Count active pitches (excluding contest and client management pitches)
        $pitchesCount = Pitch::where('user_id', $user->id)
            ->whereIn('status', [
                Pitch::STATUS_PENDING, Pitch::STATUS_IN_PROGRESS, Pitch::STATUS_READY_FOR_REVIEW,
                Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_AWAITING_ACCEPTANCE,
                Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, Pitch::STATUS_APPROVED,
            ])
            ->whereHas('project', function ($query) {
                $query->whereNotIn('workflow_type', [
                    Project::WORKFLOW_TYPE_CONTEST,
                    Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
                ]);
            })
            ->count();

        // Count contest entries (pitches) and contest projects (created by user)
        $contestPitchesCount = Pitch::where('user_id', $user->id)
            ->whereIn('status', [
                Pitch::STATUS_CONTEST_ENTRY,
                Pitch::STATUS_CONTEST_WINNER,
                Pitch::STATUS_CONTEST_RUNNER_UP,
            ])
            ->count();

        $contestProjectsCount = Project::where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CONTEST)
            ->whereIn('status', [
                Project::STATUS_UNPUBLISHED,
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])
            ->count();

        $contestsCount = $contestPitchesCount + $contestProjectsCount;

        // Count client projects (where user is the producer working on client projects
        // OR where user is a registered client)
        $clientProjectsAsProducerCount = Project::where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereIn('status', [
                Project::STATUS_UNPUBLISHED,
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])
            ->count();

        $clientProjectsAsClientCount = Project::where(function ($query) use ($user) {
            $query->where('client_user_id', $user->id)
                ->orWhere('client_email', $user->email);
        })
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereIn('status', [
                Project::STATUS_UNPUBLISHED,
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])
            ->count();

        $clientProjectsCount = $clientProjectsAsProducerCount + $clientProjectsAsClientCount;

        $total = $projectsCount + $pitchesCount + $contestsCount + $clientProjectsCount;

        return [
            'projects' => $projectsCount,
            'pitches' => $pitchesCount,
            'contests' => $contestsCount,
            'client_projects' => $clientProjectsCount,
            'total' => $total,
        ];
    }

    public function getRecentProjects($limit = 5)
    {
        if (! Auth::check()) {
            return collect();
        }

        $user = Auth::user();

        return Project::where('user_id', $user->id)
            ->whereNotIn('workflow_type', [
                Project::WORKFLOW_TYPE_CONTEST,
                Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            ])
            ->whereIn('status', [
                Project::STATUS_UNPUBLISHED,
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])
            ->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function getRecentPitches($limit = 5)
    {
        if (! Auth::check()) {
            return collect();
        }

        $user = Auth::user();

        return Pitch::where('user_id', $user->id)
            ->whereIn('status', [
                Pitch::STATUS_PENDING,
                Pitch::STATUS_IN_PROGRESS,
                Pitch::STATUS_READY_FOR_REVIEW,
                Pitch::STATUS_REVISIONS_REQUESTED,
                Pitch::STATUS_AWAITING_ACCEPTANCE,
                Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                Pitch::STATUS_APPROVED,
            ])
            ->whereHas('project', function ($query) {
                $query->whereNotIn('workflow_type', [
                    Project::WORKFLOW_TYPE_CONTEST,
                    Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
                ]);
            })
            ->with('project')
            ->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function getRecentContests($limit = 5)
    {
        if (! Auth::check()) {
            return collect();
        }

        $user = Auth::user();

        // Get contest pitches (entries by this user)
        $contestPitches = Pitch::where('user_id', $user->id)
            ->whereIn('status', [
                Pitch::STATUS_CONTEST_ENTRY,
                Pitch::STATUS_CONTEST_WINNER,
                Pitch::STATUS_CONTEST_RUNNER_UP,
            ])
            ->with('project')
            ->get()
            ->map(function ($pitch) {
                return (object) [
                    'type' => 'pitch',
                    'id' => $pitch->id,
                    'name' => $pitch->project->name ?? 'Untitled Contest',
                    'route_name' => 'projects.pitches.show',
                    'route_params' => [$pitch->project, $pitch],
                    'updated_at' => $pitch->updated_at,
                    'status' => $pitch->status,
                    'project' => $pitch->project,
                ];
            });

        // Get contest projects (created by this user)
        $contestProjects = Project::where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CONTEST)
            ->whereIn('status', [
                Project::STATUS_UNPUBLISHED,
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])
            ->get()
            ->map(function ($project) {
                return (object) [
                    'type' => 'project',
                    'id' => $project->id,
                    'name' => $project->name ?? 'Untitled Contest',
                    'route_name' => 'projects.manage',
                    'route_params' => [$project],
                    'updated_at' => $project->updated_at,
                    'status' => $project->status,
                    'project' => $project,
                ];
            });

        // Combine and sort by updated_at, then take the limit
        return $contestPitches
            ->concat($contestProjects)
            ->sortByDesc('updated_at')
            ->take($limit)
            ->values();
    }

    public function getRecentClientProjects($limit = 5)
    {
        if (! Auth::check()) {
            return collect();
        }

        $user = Auth::user();

        // Fetch client projects where user is EITHER the producer OR the registered client
        return Project::where(function ($query) use ($user) {
            $query->where('user_id', $user->id) // Producer
                ->orWhere(function ($subQuery) use ($user) {
                    $subQuery->where('client_user_id', $user->id) // Registered client by ID
                        ->orWhere('client_email', $user->email); // Registered client by email
                });
        })
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereIn('status', [
                Project::STATUS_UNPUBLISHED,
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])
            ->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function render()
    {
        $counts = $this->getWorkItemCounts();

        return view('livewire.sidebar-work-nav', [
            'counts' => $counts,
            'projects' => $counts['projects'] > 0 ? $this->getRecentProjects() : collect(),
            'pitches' => $counts['pitches'] > 0 ? $this->getRecentPitches() : collect(),
            'contests' => $counts['contests'] > 0 ? $this->getRecentContests() : collect(),
            'clientProjects' => $counts['client_projects'] > 0 ? $this->getRecentClientProjects() : collect(),
        ]);
    }
}
