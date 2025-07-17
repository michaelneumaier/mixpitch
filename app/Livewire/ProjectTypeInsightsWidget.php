<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectType;
use Livewire\Component;

class ProjectTypeInsightsWidget extends Component
{
    public function getMostPopularTypeProperty()
    {
        return ProjectType::withCount('projects')
            ->where('is_active', true)
            ->orderBy('projects_count', 'desc')
            ->first();
    }

    public function getRecentTrendsProperty()
    {
        $recentProjects = Project::where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('project_type_id')
            ->with('projectType')
            ->get();

        $typeCounts = $recentProjects->groupBy('project_type_id')
            ->map(function ($projects) {
                return [
                    'count' => $projects->count(),
                    'type' => $projects->first()->projectType,
                ];
            })
            ->sortByDesc('count')
            ->take(3);

        return $typeCounts;
    }

    public function getQuickStatsProperty()
    {
        $totalProjects = Project::whereNotNull('project_type_id')->count();
        $activeTypes = ProjectType::where('is_active', true)->count();
        $recentProjects = Project::where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('project_type_id')
            ->count();

        return [
            'total_projects' => $totalProjects,
            'active_types' => $activeTypes,
            'recent_projects' => $recentProjects,
            'growth_rate' => $recentProjects > 0 ? round(($recentProjects / 7), 1) : 0,
        ];
    }

    public function render()
    {
        return view('livewire.project-type-insights-widget');
    }
}
