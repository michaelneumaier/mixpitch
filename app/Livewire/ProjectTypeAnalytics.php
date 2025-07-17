<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectType;
use Livewire\Component;

class ProjectTypeAnalytics extends Component
{
    public $selectedPeriod = '30'; // Days

    public function getProjectTypeStatsProperty()
    {
        return ProjectType::withCount(['projects' => function ($query) {
            if ($this->selectedPeriod !== 'all') {
                $query->where('created_at', '>=', now()->subDays($this->selectedPeriod));
            }
        }])
            ->with(['projects' => function ($query) {
                if ($this->selectedPeriod !== 'all') {
                    $query->where('created_at', '>=', now()->subDays($this->selectedPeriod));
                }
            }])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($projectType) {
                $projects = $projectType->projects;
                $totalBudget = $projects->sum('budget');
                $avgBudget = $projects->count() > 0 ? $projects->avg('budget') : 0;
                $activeProjects = $projects->where('status', '!=', Project::STATUS_COMPLETED)->count();
                $completedProjects = $projects->where('status', Project::STATUS_COMPLETED)->count();

                return [
                    'id' => $projectType->id,
                    'name' => $projectType->name,
                    'slug' => $projectType->slug,
                    'icon' => $projectType->getIconClass(),
                    'color' => $projectType->color,
                    'colors' => $projectType->getColorClasses(),
                    'description' => $projectType->description,
                    'project_count' => $projectType->projects_count,
                    'total_budget' => $totalBudget,
                    'avg_budget' => $avgBudget,
                    'active_projects' => $activeProjects,
                    'completed_projects' => $completedProjects,
                    'completion_rate' => $projectType->projects_count > 0
                        ? round(($completedProjects / $projectType->projects_count) * 100, 1)
                        : 0,
                ];
            });
    }

    public function getTotalStatsProperty()
    {
        $query = Project::query();

        if ($this->selectedPeriod !== 'all') {
            $query->where('created_at', '>=', now()->subDays($this->selectedPeriod));
        }

        $projects = $query->get();

        return [
            'total_projects' => $projects->count(),
            'total_budget' => $projects->sum('budget'),
            'avg_budget' => $projects->count() > 0 ? $projects->avg('budget') : 0,
            'active_projects' => $projects->where('status', '!=', Project::STATUS_COMPLETED)->count(),
            'completed_projects' => $projects->where('status', Project::STATUS_COMPLETED)->count(),
            'completion_rate' => $projects->count() > 0
                ? round(($projects->where('status', Project::STATUS_COMPLETED)->count() / $projects->count()) * 100, 1)
                : 0,
        ];
    }

    public function getPopularityChartDataProperty()
    {
        $stats = $this->projectTypeStats;

        return [
            'labels' => $stats->pluck('name')->toArray(),
            'data' => $stats->pluck('project_count')->toArray(),
            'colors' => $stats->map(function ($stat) {
                return $this->getTailwindColorHex($stat['color']);
            })->toArray(),
        ];
    }

    public function getBudgetChartDataProperty()
    {
        $stats = $this->projectTypeStats->where('total_budget', '>', 0);

        return [
            'labels' => $stats->pluck('name')->toArray(),
            'data' => $stats->pluck('total_budget')->toArray(),
            'colors' => $stats->map(function ($stat) {
                return $this->getTailwindColorHex($stat['color']);
            })->toArray(),
        ];
    }

    private function getTailwindColorHex($colorName)
    {
        $colorMap = [
            'blue' => '#3B82F6',
            'purple' => '#8B5CF6',
            'pink' => '#EC4899',
            'green' => '#10B981',
            'orange' => '#F97316',
            'red' => '#EF4444',
            'yellow' => '#F59E0B',
            'indigo' => '#6366F1',
            'gray' => '#6B7280',
            'teal' => '#14B8A6',
        ];

        return $colorMap[$colorName] ?? '#6B7280';
    }

    public function render()
    {
        return view('livewire.project-type-analytics');
    }
}
