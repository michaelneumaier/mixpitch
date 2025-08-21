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

        // Count active projects (excluding client management projects)
        $projectsCount = Project::where('user_id', $user->id)
            ->where('workflow_type', '!=', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereIn('status', [
                Project::STATUS_UNPUBLISHED,
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])
            ->count();

        // Count active pitches
        $pitchesCount = Pitch::where('producer_id', $user->id)
            ->whereIn('status', [
                Pitch::STATUS_PENDING, Pitch::STATUS_IN_PROGRESS, Pitch::STATUS_READY_FOR_REVIEW,
                Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_AWAITING_ACCEPTANCE,
                Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, Pitch::STATUS_APPROVED,
            ])
            ->count();

        // Count contest entries
        $contestsCount = Pitch::where('producer_id', $user->id)
            ->whereIn('status', [
                Pitch::STATUS_CONTEST_ENTRY,
                Pitch::STATUS_CONTEST_WINNER,
                Pitch::STATUS_CONTEST_RUNNER_UP,
            ])
            ->count();

        // Count client projects (where user is the producer working on client projects)
        $clientProjectsCount = Project::where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereIn('status', [
                Project::STATUS_UNPUBLISHED,
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

    public function getRecentProjects($limit = 5)
    {
        if (! Auth::check()) {
            return collect();
        }

        $user = Auth::user();

        return Project::where('user_id', $user->id)
            ->where('workflow_type', '!=', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
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

        return Pitch::where('producer_id', $user->id)
            ->whereIn('status', [
                Pitch::STATUS_PENDING,
                Pitch::STATUS_IN_PROGRESS,
                Pitch::STATUS_READY_FOR_REVIEW,
                Pitch::STATUS_REVISIONS_REQUESTED,
                Pitch::STATUS_AWAITING_ACCEPTANCE,
                Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                Pitch::STATUS_APPROVED,
            ])
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

        return Pitch::where('producer_id', $user->id)
            ->whereIn('status', [
                Pitch::STATUS_CONTEST_ENTRY,
                Pitch::STATUS_CONTEST_WINNER,
                Pitch::STATUS_CONTEST_RUNNER_UP,
            ])
            ->with('project')
            ->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function getRecentClientProjects($limit = 5)
    {
        if (! Auth::check()) {
            return collect();
        }

        $user = Auth::user();

        return Project::where('user_id', $user->id)
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
