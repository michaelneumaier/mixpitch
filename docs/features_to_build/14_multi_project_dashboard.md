# Multi-Project Dashboard Implementation Plan

## Feature Overview

The Multi-Project Dashboard provides large studios and production companies with a comprehensive overview of all their active projects, team assignments, deadlines, and progress status. This centralized management interface enables efficient resource allocation, deadline tracking, and workload distribution across multiple simultaneous projects.

### Core Functionality
- **Project Overview Grid**: Table-based view of all projects with key metrics
- **Advanced Filtering**: Filter by client, engineer, deadline, status, and revenue
- **Resource Management**: Team member workload and availability tracking
- **Deadline Monitoring**: Visual deadline warnings and critical path analysis
- **Revenue Analytics**: Financial performance tracking across projects
- **Bulk Operations**: Batch actions for project management efficiency
- **Export Capabilities**: CSV and PDF reports for stakeholder communication

## Technical Architecture

### Database Schema

```sql
-- Dashboard view configurations and saved filters
CREATE TABLE dashboard_views (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    view_type ENUM('personal', 'team', 'shared') DEFAULT 'personal',
    filters JSON NOT NULL,
    columns JSON NOT NULL,
    sort_config JSON NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT FALSE,
    usage_count INT UNSIGNED DEFAULT 0,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    INDEX idx_user_views (user_id, view_type),
    INDEX idx_team_views (team_id, is_public),
    INDEX idx_default_views (user_id, is_default)
);

-- Project statistics and computed metrics cache
CREATE TABLE project_dashboard_stats (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    completion_percentage DECIMAL(5,2) DEFAULT 0,
    days_remaining INT NULL,
    is_overdue BOOLEAN DEFAULT FALSE,
    total_revenue DECIMAL(10,2) DEFAULT 0,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    outstanding_amount DECIMAL(10,2) DEFAULT 0,
    active_team_members INT UNSIGNED DEFAULT 0,
    revision_count INT UNSIGNED DEFAULT 0,
    file_count INT UNSIGNED DEFAULT 0,
    last_activity_at TIMESTAMP NULL,
    priority_score DECIMAL(5,2) DEFAULT 0,
    risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    computed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
    UNIQUE KEY unique_project_stats (project_id),
    INDEX idx_team_stats (team_id, computed_at),
    INDEX idx_overdue (is_overdue, days_remaining),
    INDEX idx_priority (priority_score, risk_level),
    INDEX idx_expires (expires_at)
);

-- Team member workload and capacity tracking
CREATE TABLE team_member_workload (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    week_start_date DATE NOT NULL,
    active_projects INT UNSIGNED DEFAULT 0,
    total_hours_allocated DECIMAL(5,2) DEFAULT 0,
    total_hours_capacity DECIMAL(5,2) DEFAULT 40.00,
    utilization_percentage DECIMAL(5,2) DEFAULT 0,
    overload_hours DECIMAL(5,2) DEFAULT 0,
    availability_status ENUM('available', 'busy', 'overloaded', 'unavailable') DEFAULT 'available',
    workload_data JSON DEFAULT '{}',
    computed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_week (user_id, week_start_date),
    INDEX idx_team_workload (team_id, week_start_date),
    INDEX idx_utilization (utilization_percentage, availability_status),
    INDEX idx_week_workload (week_start_date, availability_status)
);

-- Dashboard alerts and notifications
CREATE TABLE dashboard_alerts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    project_id BIGINT UNSIGNED NULL,
    alert_type ENUM('deadline_warning', 'budget_exceeded', 'team_overload', 'client_feedback_pending', 'payment_overdue') NOT NULL,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(500) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_dismissed BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NULL,
    metadata JSON DEFAULT '{}',
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_user_alerts (user_id, is_read, created_at),
    INDEX idx_team_alerts (team_id, severity, created_at),
    INDEX idx_project_alerts (project_id, alert_type),
    INDEX idx_expires (expires_at)
);

-- Export and report generation tracking
CREATE TABLE dashboard_exports (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    export_type ENUM('csv', 'pdf', 'xlsx') NOT NULL,
    view_config JSON NOT NULL,
    filters JSON NOT NULL,
    file_path VARCHAR(500) NULL,
    download_url VARCHAR(500) NULL,
    total_projects INT UNSIGNED DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
    INDEX idx_user_exports (user_id, status, created_at),
    INDEX idx_expires (expires_at)
);

-- Dashboard widget configurations
CREATE TABLE dashboard_widgets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    widget_type ENUM('project_summary', 'revenue_chart', 'team_workload', 'deadline_calendar', 'recent_activity') NOT NULL,
    position_x INT UNSIGNED DEFAULT 0,
    position_y INT UNSIGNED DEFAULT 0,
    width INT UNSIGNED DEFAULT 4,
    height INT UNSIGNED DEFAULT 3,
    config JSON DEFAULT '{}',
    is_visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
    INDEX idx_user_widgets (user_id, is_visible),
    INDEX idx_team_widgets (team_id, widget_type)
);
```

### Service Architecture

#### MultiProjectDashboardService
```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDashboardStats;
use App\Models\TeamMemberWorkload;
use App\Models\DashboardView;
use App\Models\DashboardAlert;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MultiProjectDashboardService
{
    public function getDashboardData(User $user, ?Team $team = null, array $filters = []): array
    {
        $cacheKey = $this->getDashboardCacheKey($user->id, $team?->id, $filters);
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user, $team, $filters) {
            return $this->buildDashboardData($user, $team, $filters);
        });
    }

    private function buildDashboardData(User $user, ?Team $team, array $filters): array
    {
        // Build base query for projects
        $projectsQuery = $this->buildProjectsQuery($user, $team, $filters);
        
        // Get projects with computed stats
        $projects = $projectsQuery->with([
            'user',
            'approvedPitch.user',
            'projectFiles',
            'pitchEvents' => function ($query) {
                $query->latest()->limit(3);
            },
            'dashboardStats'
        ])->get();

        // Compute summary statistics
        $summary = $this->computeSummaryStats($projects);
        
        // Get team workload data
        $teamWorkload = $team ? $this->getTeamWorkloadData($team) : [];
        
        // Get active alerts
        $alerts = $this->getActiveAlerts($user, $team);
        
        // Get recent activity
        $recentActivity = $this->getRecentActivity($user, $team);

        return [
            'projects' => $projects,
            'summary' => $summary,
            'team_workload' => $teamWorkload,
            'alerts' => $alerts,
            'recent_activity' => $recentActivity,
            'filters_applied' => $filters
        ];
    }

    private function buildProjectsQuery(User $user, ?Team $team, array $filters)
    {
        $query = Project::query();

        // Base access control
        if ($team) {
            $query->where('team_id', $team->id);
        } else {
            $query->where('user_id', $user->id);
        }

        // Apply filters
        if (!empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (!empty($filters['workflow_type'])) {
            $query->whereIn('workflow_type', (array) $filters['workflow_type']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('user_id', $filters['client_id']);
        }

        if (!empty($filters['producer_id'])) {
            $query->whereHas('approvedPitch', function ($q) use ($filters) {
                $q->where('user_id', $filters['producer_id']);
            });
        }

        if (!empty($filters['deadline_from'])) {
            $query->where('deadline', '>=', $filters['deadline_from']);
        }

        if (!empty($filters['deadline_to'])) {
            $query->where('deadline', '<=', $filters['deadline_to']);
        }

        if (!empty($filters['overdue_only'])) {
            $query->where('deadline', '<', now())
                  ->whereNotIn('status', ['completed', 'cancelled']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        // Default ordering
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        $query->orderBy($sortBy, $sortDirection);

        return $query;
    }

    private function computeSummaryStats(Collection $projects): array
    {
        $totalProjects = $projects->count();
        $activeProjects = $projects->whereIn('status', ['pending', 'in_progress', 'ready_for_review'])->count();
        $completedProjects = $projects->where('status', 'completed')->count();
        $overdueProjects = $projects->filter(function ($project) {
            return $project->deadline && $project->deadline->isPast() && 
                   !in_array($project->status, ['completed', 'cancelled']);
        })->count();

        $totalRevenue = $projects->sum(function ($project) {
            return $project->dashboardStats?->total_revenue ?? 0;
        });

        $paidAmount = $projects->sum(function ($project) {
            return $project->dashboardStats?->paid_amount ?? 0;
        });

        $outstandingAmount = $projects->sum(function ($project) {
            return $project->dashboardStats?->outstanding_amount ?? 0;
        });

        $averageCompletionTime = $projects->where('status', 'completed')
            ->filter(function ($project) {
                return $project->completed_at && $project->created_at;
            })
            ->avg(function ($project) {
                return $project->created_at->diffInDays($project->completed_at);
            });

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'overdue_projects' => $overdueProjects,
            'completion_rate' => $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100, 1) : 0,
            'total_revenue' => $totalRevenue,
            'paid_amount' => $paidAmount,
            'outstanding_amount' => $outstandingAmount,
            'collection_rate' => $totalRevenue > 0 ? round(($paidAmount / $totalRevenue) * 100, 1) : 0,
            'average_completion_days' => round($averageCompletionTime ?? 0, 1)
        ];
    }

    public function updateProjectStats(Project $project): ProjectDashboardStats
    {
        $stats = ProjectDashboardStats::updateOrCreate(
            ['project_id' => $project->id],
            $this->calculateProjectStats($project)
        );

        return $stats;
    }

    private function calculateProjectStats(Project $project): array
    {
        $completionPercentage = $this->calculateCompletionPercentage($project);
        $daysRemaining = $project->deadline ? now()->diffInDays($project->deadline, false) : null;
        $isOverdue = $project->deadline && $project->deadline->isPast() && 
                     !in_array($project->status, ['completed', 'cancelled']);

        $revenue = $this->calculateProjectRevenue($project);
        $teamMembers = $this->getActiveTeamMemberCount($project);
        $revisionCount = $project->pitchEvents()->where('event_type', 'revision_requested')->count();
        $fileCount = $project->projectFiles()->count() + 
                     ($project->approvedPitch?->pitchFiles()->count() ?? 0);

        $lastActivity = $this->getLastActivityTime($project);
        $priorityScore = $this->calculatePriorityScore($project, $daysRemaining, $isOverdue);
        $riskLevel = $this->assessRiskLevel($project, $priorityScore, $isOverdue);

        return [
            'team_id' => $project->team_id,
            'completion_percentage' => $completionPercentage,
            'days_remaining' => $daysRemaining,
            'is_overdue' => $isOverdue,
            'total_revenue' => $revenue['total'],
            'paid_amount' => $revenue['paid'],
            'outstanding_amount' => $revenue['outstanding'],
            'active_team_members' => $teamMembers,
            'revision_count' => $revisionCount,
            'file_count' => $fileCount,
            'last_activity_at' => $lastActivity,
            'priority_score' => $priorityScore,
            'risk_level' => $riskLevel,
            'computed_at' => now(),
            'expires_at' => now()->addHours(6)
        ];
    }

    private function calculateCompletionPercentage(Project $project): float
    {
        $milestones = [
            'created' => 10,
            'has_approved_pitch' => 30,
            'files_uploaded' => 50,
            'ready_for_review' => 80,
            'completed' => 100
        ];

        $percentage = $milestones['created'];

        if ($project->approvedPitch) {
            $percentage = $milestones['has_approved_pitch'];
        }

        if ($project->projectFiles()->exists() || $project->approvedPitch?->pitchFiles()->exists()) {
            $percentage = $milestones['files_uploaded'];
        }

        if ($project->status === 'ready_for_review') {
            $percentage = $milestones['ready_for_review'];
        }

        if ($project->status === 'completed') {
            $percentage = $milestones['completed'];
        }

        return $percentage;
    }

    private function calculateProjectRevenue(Project $project): array
    {
        $totalRevenue = 0;
        $paidAmount = 0;

        if ($project->approvedPitch && $project->approvedPitch->quoted_price) {
            $totalRevenue = $project->approvedPitch->quoted_price;
        }

        // Calculate paid amount from transactions
        $paidAmount = $project->transactions()
            ->where('type', 'payment')
            ->where('status', 'completed')
            ->sum('amount') / 100; // Convert from cents

        return [
            'total' => $totalRevenue,
            'paid' => $paidAmount,
            'outstanding' => max(0, $totalRevenue - $paidAmount)
        ];
    }

    private function getActiveTeamMemberCount(Project $project): int
    {
        if ($project->team_id) {
            return $project->teamMembers()
                ->where('status', 'active')
                ->count();
        }

        // For non-team projects, count client + producer
        $count = 1; // Client
        if ($project->approvedPitch) {
            $count++; // Producer
        }

        return $count;
    }

    private function getLastActivityTime(Project $project): ?Carbon
    {
        $latestActivity = null;

        // Check pitch events
        $latestPitchEvent = $project->pitchEvents()->latest()->first();
        if ($latestPitchEvent) {
            $latestActivity = $latestPitchEvent->created_at;
        }

        // Check file uploads
        $latestFile = $project->projectFiles()->latest()->first();
        if ($latestFile && (!$latestActivity || $latestFile->created_at->gt($latestActivity))) {
            $latestActivity = $latestFile->created_at;
        }

        // Check pitch file uploads
        if ($project->approvedPitch) {
            $latestPitchFile = $project->approvedPitch->pitchFiles()->latest()->first();
            if ($latestPitchFile && (!$latestActivity || $latestPitchFile->created_at->gt($latestActivity))) {
                $latestActivity = $latestPitchFile->created_at;
            }
        }

        return $latestActivity;
    }

    private function calculatePriorityScore(Project $project, ?int $daysRemaining, bool $isOverdue): float
    {
        $score = 0;

        // Base score from deadline urgency
        if ($daysRemaining !== null) {
            if ($isOverdue) {
                $score += 100; // Highest priority for overdue
            } elseif ($daysRemaining <= 1) {
                $score += 90;
            } elseif ($daysRemaining <= 3) {
                $score += 70;
            } elseif ($daysRemaining <= 7) {
                $score += 50;
            } elseif ($daysRemaining <= 14) {
                $score += 30;
            }
        }

        // Revenue-based priority
        $revenue = $project->approvedPitch?->quoted_price ?? 0;
        if ($revenue > 5000) {
            $score += 20;
        } elseif ($revenue > 2000) {
            $score += 10;
        } elseif ($revenue > 500) {
            $score += 5;
        }

        // Client priority (could be based on client tier)
        if ($project->user->client_tier === 'premium') {
            $score += 15;
        }

        // Activity recency
        $lastActivity = $this->getLastActivityTime($project);
        if ($lastActivity && $lastActivity->diffInDays(now()) > 7) {
            $score += 10; // Inactive projects need attention
        }

        return min(100, max(0, $score));
    }

    private function assessRiskLevel(Project $project, float $priorityScore, bool $isOverdue): string
    {
        if ($isOverdue || $priorityScore >= 90) {
            return 'critical';
        } elseif ($priorityScore >= 70) {
            return 'high';
        } elseif ($priorityScore >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    public function getTeamWorkloadData(Team $team): array
    {
        $weekStart = now()->startOfWeek();
        
        $workloads = TeamMemberWorkload::where('team_id', $team->id)
            ->where('week_start_date', $weekStart->toDateString())
            ->with('user')
            ->get();

        $summary = [
            'total_members' => $team->teamMembers()->where('status', 'active')->count(),
            'available_members' => $workloads->where('availability_status', 'available')->count(),
            'busy_members' => $workloads->where('availability_status', 'busy')->count(),
            'overloaded_members' => $workloads->where('availability_status', 'overloaded')->count(),
            'average_utilization' => $workloads->avg('utilization_percentage') ?? 0,
            'total_capacity_hours' => $workloads->sum('total_hours_capacity'),
            'total_allocated_hours' => $workloads->sum('total_hours_allocated')
        ];

        return [
            'summary' => $summary,
            'members' => $workloads
        ];
    }

    public function updateTeamWorkload(Team $team): void
    {
        $weekStart = now()->startOfWeek();
        $teamMembers = $team->teamMembers()->where('status', 'active')->with('user')->get();

        foreach ($teamMembers as $member) {
            $workloadData = $this->calculateMemberWorkload($member->user, $team, $weekStart);
            
            TeamMemberWorkload::updateOrCreate(
                [
                    'user_id' => $member->user_id,
                    'team_id' => $team->id,
                    'week_start_date' => $weekStart->toDateString()
                ],
                $workloadData
            );
        }
    }

    private function calculateMemberWorkload(User $user, Team $team, Carbon $weekStart): array
    {
        // Get projects assigned to this user
        $activeProjects = Project::where('team_id', $team->id)
            ->whereIn('status', ['pending', 'in_progress', 'ready_for_review'])
            ->whereHas('teamMembers', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('status', 'active');
            })
            ->count();

        // Calculate allocated hours (this would integrate with time tracking)
        $allocatedHours = $this->getWeeklyAllocatedHours($user, $weekStart);
        
        // Get member capacity (default 40 hours, could be configurable)
        $capacityHours = $user->weekly_capacity ?? 40;
        
        $utilizationPercentage = $capacityHours > 0 ? ($allocatedHours / $capacityHours) * 100 : 0;
        $overloadHours = max(0, $allocatedHours - $capacityHours);
        
        $availabilityStatus = match (true) {
            $utilizationPercentage >= 100 => 'overloaded',
            $utilizationPercentage >= 85 => 'busy',
            $utilizationPercentage >= 20 => 'available',
            default => 'available'
        };

        return [
            'active_projects' => $activeProjects,
            'total_hours_allocated' => $allocatedHours,
            'total_hours_capacity' => $capacityHours,
            'utilization_percentage' => round($utilizationPercentage, 2),
            'overload_hours' => $overloadHours,
            'availability_status' => $availabilityStatus,
            'workload_data' => [
                'projects_breakdown' => $this->getProjectBreakdown($user, $team),
                'week_start' => $weekStart->toDateString()
            ],
            'computed_at' => now()
        ];
    }

    private function getWeeklyAllocatedHours(User $user, Carbon $weekStart): float
    {
        // This would integrate with the time tracking system
        // For now, estimate based on active projects
        $activeProjects = Project::whereHas('teamMembers', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('status', 'active');
        })->count();

        // Rough estimate: 8-12 hours per active project
        return $activeProjects * 10;
    }

    private function getProjectBreakdown(User $user, Team $team): array
    {
        return Project::where('team_id', $team->id)
            ->whereIn('status', ['pending', 'in_progress', 'ready_for_review'])
            ->whereHas('teamMembers', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('status', 'active');
            })
            ->select('id', 'name', 'deadline', 'status')
            ->get()
            ->toArray();
    }

    public function getActiveAlerts(User $user, ?Team $team = null): array
    {
        $query = DashboardAlert::where('user_id', $user->id)
            ->where('is_dismissed', false);

        if ($team) {
            $query->where('team_id', $team->id);
        }

        return $query->orderBy('severity')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function createAlert(User $user, string $type, string $severity, string $title, string $message, array $metadata = []): DashboardAlert
    {
        return DashboardAlert::create([
            'user_id' => $user->id,
            'team_id' => $metadata['team_id'] ?? null,
            'project_id' => $metadata['project_id'] ?? null,
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'action_url' => $metadata['action_url'] ?? null,
            'metadata' => $metadata,
            'expires_at' => $metadata['expires_at'] ?? now()->addDays(7)
        ]);
    }

    private function getRecentActivity(User $user, ?Team $team = null): array
    {
        // This would gather recent activities across projects
        // Implementation would depend on existing activity logging
        return [];
    }

    private function getDashboardCacheKey(int $userId, ?int $teamId, array $filters): string
    {
        $filterHash = md5(json_encode($filters));
        return "dashboard_{$userId}_{$teamId}_{$filterHash}";
    }

    public function saveDashboardView(User $user, string $name, array $filters, array $columns, array $sortConfig, ?Team $team = null): DashboardView
    {
        return DashboardView::create([
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'name' => $name,
            'view_type' => $team ? 'team' : 'personal',
            'filters' => $filters,
            'columns' => $columns,
            'sort_config' => $sortConfig
        ]);
    }

    public function exportDashboardData(User $user, ?Team $team, array $filters, string $format = 'csv'): string
    {
        $data = $this->getDashboardData($user, $team, $filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCSV($data);
            case 'pdf':
                return $this->exportToPDF($data);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    private function exportToCSV(array $data): string
    {
        $csvData = [];
        $csvData[] = [
            'Project Name',
            'Client',
            'Producer',
            'Status',
            'Workflow Type',
            'Created Date',
            'Deadline',
            'Days Remaining',
            'Completion %',
            'Total Revenue',
            'Paid Amount',
            'Outstanding',
            'Team Members',
            'Risk Level'
        ];

        foreach ($data['projects'] as $project) {
            $stats = $project->dashboardStats;
            
            $csvData[] = [
                $project->name,
                $project->user->name,
                $project->approvedPitch?->user->name ?? '',
                ucfirst($project->status),
                str_replace('_', ' ', ucwords($project->workflow_type, '_')),
                $project->created_at->format('Y-m-d'),
                $project->deadline?->format('Y-m-d') ?? '',
                $stats?->days_remaining ?? '',
                $stats?->completion_percentage ?? 0,
                '$' . number_format($stats?->total_revenue ?? 0, 2),
                '$' . number_format($stats?->paid_amount ?? 0, 2),
                '$' . number_format($stats?->outstanding_amount ?? 0, 2),
                $stats?->active_team_members ?? 0,
                ucfirst($stats?->risk_level ?? 'low')
            ];
        }

        $filename = 'dashboard_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $path = "dashboard-exports/{$filename}";
        
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }
        
        Storage::disk('s3')->put($path, $csvContent);
        
        return $path;
    }
}
```

## UI Implementation

### Multi-Project Dashboard Component
```php
<?php

namespace App\Livewire\Dashboard;

use App\Models\Team;
use App\Models\DashboardView;
use App\Services\MultiProjectDashboardService;
use Livewire\Component;
use Livewire\WithPagination;

class MultiProjectDashboard extends Component
{
    use WithPagination;

    public ?Team $selectedTeam = null;
    public array $filters = [
        'status' => [],
        'workflow_type' => [],
        'client_id' => null,
        'producer_id' => null,
        'deadline_from' => '',
        'deadline_to' => '',
        'overdue_only' => false,
        'search' => '',
        'sort_by' => 'created_at',
        'sort_direction' => 'desc'
    ];
    
    public array $selectedColumns = [
        'name', 'client', 'producer', 'status', 'deadline', 'completion', 'revenue'
    ];
    
    public array $availableColumns = [
        'name' => 'Project Name',
        'client' => 'Client',
        'producer' => 'Producer', 
        'status' => 'Status',
        'workflow_type' => 'Type',
        'created_at' => 'Created',
        'deadline' => 'Deadline',
        'completion' => 'Completion %',
        'revenue' => 'Revenue',
        'team_members' => 'Team',
        'risk_level' => 'Risk',
        'last_activity' => 'Last Activity'
    ];

    public bool $showFilters = false;
    public bool $showColumnSelector = false;
    public bool $showSaveViewModal = false;
    public string $saveViewName = '';
    public array $dashboardData = [];
    public bool $autoRefresh = true;

    protected $rules = [
        'saveViewName' => 'required|string|max:255'
    ];

    public function mount(?Team $team = null)
    {
        $this->selectedTeam = $team;
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $service = app(MultiProjectDashboardService::class);
        $this->dashboardData = $service->getDashboardData(auth()->user(), $this->selectedTeam, $this->filters);
    }

    public function updatedFilters()
    {
        $this->resetPage();
        $this->loadDashboardData();
    }

    public function toggleFilter(string $filterName, $value)
    {
        if (is_array($this->filters[$filterName])) {
            $index = array_search($value, $this->filters[$filterName]);
            if ($index !== false) {
                unset($this->filters[$filterName][$index]);
                $this->filters[$filterName] = array_values($this->filters[$filterName]);
            } else {
                $this->filters[$filterName][] = $value;
            }
        } else {
            $this->filters[$filterName] = $this->filters[$filterName] === $value ? null : $value;
        }
        
        $this->updatedFilters();
    }

    public function sortBy(string $column)
    {
        if ($this->filters['sort_by'] === $column) {
            $this->filters['sort_direction'] = $this->filters['sort_direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            $this->filters['sort_by'] = $column;
            $this->filters['sort_direction'] = 'asc';
        }
        
        $this->updatedFilters();
    }

    public function saveView(MultiProjectDashboardService $service)
    {
        $this->validate();

        $service->saveDashboardView(
            auth()->user(),
            $this->saveViewName,
            $this->filters,
            $this->selectedColumns,
            [
                'sort_by' => $this->filters['sort_by'],
                'sort_direction' => $this->filters['sort_direction']
            ],
            $this->selectedTeam
        );

        $this->reset(['showSaveViewModal', 'saveViewName']);
        
        $this->dispatch('view-saved', [
            'message' => 'Dashboard view saved successfully!'
        ]);
    }

    public function loadView(int $viewId)
    {
        $view = DashboardView::where('user_id', auth()->id())->findOrFail($viewId);
        
        $this->filters = $view->filters;
        $this->selectedColumns = $view->columns;
        
        $view->increment('usage_count');
        $view->update(['last_used_at' => now()]);
        
        $this->loadDashboardData();
    }

    public function exportData(string $format, MultiProjectDashboardService $service)
    {
        try {
            $filePath = $service->exportDashboardData(auth()->user(), $this->selectedTeam, $this->filters, $format);
            
            $this->dispatch('export-ready', [
                'message' => 'Export generated successfully!',
                'download_url' => Storage::disk('s3')->temporaryUrl($filePath, now()->addMinutes(30))
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('export-failed', [
                'message' => 'Export failed: ' . $e->getMessage()
            ]);
        }
    }

    public function refreshData()
    {
        $this->loadDashboardData();
        
        $this->dispatch('data-refreshed', [
            'message' => 'Dashboard data refreshed'
        ]);
    }

    public function render()
    {
        $savedViews = DashboardView::where('user_id', auth()->id())
            ->where('team_id', $this->selectedTeam?->id)
            ->orderBy('name')
            ->get();

        return view('livewire.dashboard.multi-project-dashboard', [
            'savedViews' => $savedViews
        ]);
    }
}
```

### Blade Template
```blade
<div class="space-y-6" wire:poll.30s="refreshData">
    {{-- Dashboard Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">
                @if($selectedTeam)
                    {{ $selectedTeam->name }} Dashboard
                @else
                    Projects Dashboard
                @endif
            </flux:heading>
            <flux:text variant="muted">
                Manage and monitor all your active projects
            </flux:text>
        </div>
        
        <div class="flex items-center space-x-3">
            <flux:button 
                wire:click="refreshData" 
                variant="outline" 
                size="sm"
            >
                <flux:icon icon="arrow-path" class="w-4 h-4" />
                Refresh
            </flux:button>
            
            <flux:button 
                wire:click="$toggle('showFilters')" 
                variant="outline" 
                size="sm"
            >
                <flux:icon icon="funnel" class="w-4 h-4" />
                Filters
                @if(array_filter($filters))
                    <flux:badge variant="primary" size="xs" class="ml-1">
                        {{ count(array_filter($filters)) }}
                    </flux:badge>
                @endif
            </flux:button>
            
            <flux:dropdown>
                <flux:button variant="outline" size="sm">
                    <flux:icon icon="cog-6-tooth" class="w-4 h-4" />
                    View Options
                </flux:button>
                
                <flux:dropdown.content>
                    <flux:dropdown.item wire:click="$set('showColumnSelector', true)">
                        <flux:icon icon="view-columns" class="w-4 h-4" />
                        Customize Columns
                    </flux:dropdown.item>
                    
                    <flux:dropdown.item wire:click="$set('showSaveViewModal', true)">
                        <flux:icon icon="bookmark" class="w-4 h-4" />
                        Save Current View
                    </flux:dropdown.item>
                    
                    <flux:dropdown.separator />
                    
                    <flux:dropdown.item wire:click="exportData('csv')">
                        <flux:icon icon="document-arrow-down" class="w-4 h-4" />
                        Export CSV
                    </flux:dropdown.item>
                    
                    <flux:dropdown.item wire:click="exportData('pdf')">
                        <flux:icon icon="document-arrow-down" class="w-4 h-4" />
                        Export PDF
                    </flux:dropdown.item>
                </flux:dropdown.content>
            </flux:dropdown>
        </div>
    </div>

    {{-- Summary Cards --}}
    @if(!empty($dashboardData['summary']))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card>
                <flux:card.body class="text-center p-4">
                    <div class="text-2xl font-bold text-blue-600">
                        {{ $dashboardData['summary']['active_projects'] }}
                    </div>
                    <div class="text-sm text-gray-600">Active Projects</div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $dashboardData['summary']['total_projects'] }} total
                    </div>
                </flux:card.body>
            </flux:card>
            
            <flux:card>
                <flux:card.body class="text-center p-4">
                    <div class="text-2xl font-bold text-green-600">
                        {{ $dashboardData['summary']['completion_rate'] }}%
                    </div>
                    <div class="text-sm text-gray-600">Completion Rate</div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $dashboardData['summary']['completed_projects'] }} completed
                    </div>
                </flux:card.body>
            </flux:card>
            
            <flux:card>
                <flux:card.body class="text-center p-4">
                    <div class="text-2xl font-bold text-purple-600">
                        ${{ number_format($dashboardData['summary']['total_revenue'], 0) }}
                    </div>
                    <div class="text-sm text-gray-600">Total Revenue</div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $dashboardData['summary']['collection_rate'] }}% collected
                    </div>
                </flux:card.body>
            </flux:card>
            
            <flux:card>
                <flux:card.body class="text-center p-4">
                    <div class="text-2xl font-bold {{ $dashboardData['summary']['overdue_projects'] > 0 ? 'text-red-600' : 'text-gray-600' }}">
                        {{ $dashboardData['summary']['overdue_projects'] }}
                    </div>
                    <div class="text-sm text-gray-600">Overdue Projects</div>
                    <div class="text-xs text-gray-500 mt-1">
                        Avg: {{ $dashboardData['summary']['average_completion_days'] }} days
                    </div>
                </flux:card.body>
            </flux:card>
        </div>
    @endif

    {{-- Alerts Panel --}}
    @if(!empty($dashboardData['alerts']))
        <flux:card class="border-l-4 border-l-orange-500">
            <flux:card.header>
                <flux:heading size="base">Active Alerts</flux:heading>
            </flux:card.header>
            <flux:card.body>
                <div class="space-y-2">
                    @foreach(array_slice($dashboardData['alerts'], 0, 3) as $alert)
                        <div class="flex items-start space-x-3 p-2 rounded {{ $alert['severity'] === 'critical' ? 'bg-red-50' : ($alert['severity'] === 'warning' ? 'bg-yellow-50' : 'bg-blue-50') }}">
                            <flux:icon 
                                icon="{{ $alert['severity'] === 'critical' ? 'exclamation-triangle' : ($alert['severity'] === 'warning' ? 'exclamation-circle' : 'information-circle') }}" 
                                class="w-5 h-5 {{ $alert['severity'] === 'critical' ? 'text-red-600' : ($alert['severity'] === 'warning' ? 'text-yellow-600' : 'text-blue-600') }} mt-0.5"
                            />
                            <div class="flex-1">
                                <div class="font-medium text-sm">{{ $alert['title'] }}</div>
                                <div class="text-sm text-gray-600">{{ $alert['message'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </flux:card.body>
        </flux:card>
    @endif

    {{-- Filters Panel --}}
    @if($showFilters)
        <flux:card>
            <flux:card.body>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <flux:field>
                        <flux:label>Search</flux:label>
                        <flux:input 
                            wire:model.live.debounce.300ms="filters.search" 
                            placeholder="Search projects..."
                        />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model.live="filters.status" multiple>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="ready_for_review">Ready for Review</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </flux:select>
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Deadline From</flux:label>
                        <flux:input 
                            type="date" 
                            wire:model.live="filters.deadline_from"
                        />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Deadline To</flux:label>
                        <flux:input 
                            type="date" 
                            wire:model.live="filters.deadline_to"
                        />
                    </flux:field>
                </div>
                
                <div class="flex items-center justify-between mt-4">
                    <flux:checkbox wire:model.live="filters.overdue_only">
                        Show only overdue projects
                    </flux:checkbox>
                    
                    <flux:button 
                        wire:click="filters = []" 
                        variant="outline" 
                        size="sm"
                    >
                        Clear Filters
                    </flux:button>
                </div>
            </flux:card.body>
        </flux:card>
    @endif

    {{-- Projects Table --}}
    <flux:card>
        <flux:table>
            <flux:table.header>
                <flux:table.row>
                    @foreach($selectedColumns as $column)
                        <flux:table.cell>
                            <button 
                                wire:click="sortBy('{{ $column }}')"
                                class="flex items-center space-x-1 hover:text-blue-600"
                            >
                                <span>{{ $availableColumns[$column] }}</span>
                                @if($filters['sort_by'] === $column)
                                    <flux:icon 
                                        icon="{{ $filters['sort_direction'] === 'asc' ? 'chevron-up' : 'chevron-down' }}" 
                                        class="w-4 h-4"
                                    />
                                @endif
                            </button>
                        </flux:table.cell>
                    @endforeach
                    <flux:table.cell>Actions</flux:table.cell>
                </flux:table.row>
            </flux:table.header>
            
            <flux:table.body>
                @forelse($dashboardData['projects'] ?? [] as $project)
                    <flux:table.row class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        @foreach($selectedColumns as $column)
                            <flux:table.cell>
                                @switch($column)
                                    @case('name')
                                        <div>
                                            <a href="{{ route('projects.show', $project) }}" 
                                               class="font-medium text-blue-600 hover:text-blue-800">
                                                {{ $project->name }}
                                            </a>
                                            @if($project->dashboardStats?->risk_level === 'critical')
                                                <flux:badge variant="danger" size="xs" class="ml-2">Critical</flux:badge>
                                            @elseif($project->dashboardStats?->risk_level === 'high')
                                                <flux:badge variant="warning" size="xs" class="ml-2">High Risk</flux:badge>
                                            @endif
                                        </div>
                                        @break
                                        
                                    @case('client')
                                        {{ $project->user->name }}
                                        @break
                                        
                                    @case('producer')
                                        {{ $project->approvedPitch?->user->name ?? 'Unassigned' }}
                                        @break
                                        
                                    @case('status')
                                        <flux:badge 
                                            variant="{{ $project->status === 'completed' ? 'success' : ($project->status === 'in_progress' ? 'warning' : 'outline') }}"
                                            size="sm"
                                        >
                                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                        </flux:badge>
                                        @break
                                        
                                    @case('deadline')
                                        @if($project->deadline)
                                            <div class="{{ $project->dashboardStats?->is_overdue ? 'text-red-600 font-medium' : '' }}">
                                                {{ $project->deadline->format('M j, Y') }}
                                                @if($project->dashboardStats?->is_overdue)
                                                    <div class="text-xs">Overdue</div>
                                                @elseif($project->dashboardStats?->days_remaining !== null)
                                                    <div class="text-xs text-gray-500">
                                                        {{ $project->dashboardStats->days_remaining }} days
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-400">No deadline</span>
                                        @endif
                                        @break
                                        
                                    @case('completion')
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                                <div 
                                                    class="bg-blue-600 h-2 rounded-full" 
                                                    style="width: {{ $project->dashboardStats?->completion_percentage ?? 0 }}%"
                                                ></div>
                                            </div>
                                            <span class="text-sm">{{ $project->dashboardStats?->completion_percentage ?? 0 }}%</span>
                                        </div>
                                        @break
                                        
                                    @case('revenue')
                                        <div>
                                            <div class="font-medium">${{ number_format($project->dashboardStats?->total_revenue ?? 0) }}</div>
                                            @if(($project->dashboardStats?->outstanding_amount ?? 0) > 0)
                                                <div class="text-xs text-red-600">
                                                    ${{ number_format($project->dashboardStats->outstanding_amount) }} outstanding
                                                </div>
                                            @endif
                                        </div>
                                        @break
                                        
                                    @case('team_members')
                                        {{ $project->dashboardStats?->active_team_members ?? 0 }} members
                                        @break
                                        
                                    @case('last_activity')
                                        @if($project->dashboardStats?->last_activity_at)
                                            {{ $project->dashboardStats->last_activity_at->diffForHumans() }}
                                        @else
                                            <span class="text-gray-400">No activity</span>
                                        @endif
                                        @break
                                        
                                    @default
                                        {{ $project->{$column} ?? '' }}
                                @endswitch
                            </flux:table.cell>
                        @endforeach
                        
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm">
                                    <flux:icon icon="ellipsis-horizontal" class="w-4 h-4" />
                                </flux:button>
                                
                                <flux:dropdown.content>
                                    <flux:dropdown.item href="{{ route('projects.show', $project) }}">
                                        View Project
                                    </flux:dropdown.item>
                                    
                                    @if($project->status !== 'completed')
                                        <flux:dropdown.item href="{{ route('projects.edit', $project) }}">
                                            Edit Project
                                        </flux:dropdown.item>
                                    @endif
                                    
                                    <flux:dropdown.separator />
                                    
                                    <flux:dropdown.item>
                                        Project Analytics
                                    </flux:dropdown.item>
                                </flux:dropdown.content>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="{{ count($selectedColumns) + 1 }}" class="text-center text-gray-500 py-8">
                            No projects found matching your criteria.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.body>
        </flux:table>
    </flux:card>

    {{-- Save View Modal --}}
    @if($showSaveViewModal)
        <flux:modal wire:model="showSaveViewModal">
            <flux:modal.header>
                <flux:heading>Save Dashboard View</flux:heading>
            </flux:modal.header>
            
            <flux:modal.body>
                <flux:field>
                    <flux:label>View Name</flux:label>
                    <flux:input 
                        wire:model="saveViewName" 
                        placeholder="Enter a name for this view"
                    />
                    <flux:error name="saveViewName" />
                </flux:field>
            </flux:modal.body>
            
            <flux:modal.footer>
                <flux:button 
                    wire:click="$set('showSaveViewModal', false)" 
                    variant="outline"
                >
                    Cancel
                </flux:button>
                <flux:button 
                    wire:click="saveView" 
                    variant="primary"
                >
                    Save View
                </flux:button>
            </flux:modal.footer>
        </flux:modal>
    @endif
</div>

@script
<script>
    $wire.on('view-saved', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
    });

    $wire.on('export-ready', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
        
        // Auto-download the file
        window.open(data.download_url, '_blank');
    });

    $wire.on('export-failed', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'error', message: data.message }
        }));
    });

    $wire.on('data-refreshed', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'info', message: data.message, duration: 2000 }
        }));
    });
</script>
@endscript
```

## Testing Strategy

### Feature Tests
```php
<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Models\Team;
use App\Models\Project;
use App\Models\Pitch;
use App\Services\MultiProjectDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class MultiProjectDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_project_summary_stats(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        
        // Create test projects with different statuses
        Project::factory()->for($user)->create(['team_id' => $team->id, 'status' => 'completed']);
        Project::factory()->for($user)->create(['team_id' => $team->id, 'status' => 'in_progress']);
        Project::factory()->for($user)->create(['team_id' => $team->id, 'status' => 'in_progress']);
        
        $service = new MultiProjectDashboardService();
        $data = $service->getDashboardData($user, $team);

        $this->assertEquals(3, $data['summary']['total_projects']);
        $this->assertEquals(2, $data['summary']['active_projects']);
        $this->assertEquals(1, $data['summary']['completed_projects']);
        $this->assertEquals(33.3, $data['summary']['completion_rate']);
    }

    public function test_dashboard_filters_projects_correctly(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        
        Project::factory()->for($user)->create([
            'team_id' => $team->id,
            'status' => 'completed',
            'name' => 'Completed Project'
        ]);
        
        Project::factory()->for($user)->create([
            'team_id' => $team->id,
            'status' => 'in_progress',
            'name' => 'Active Project'
        ]);

        $service = new MultiProjectDashboardService();
        
        // Filter by status
        $data = $service->getDashboardData($user, $team, ['status' => ['completed']]);
        $this->assertEquals(1, count($data['projects']));
        $this->assertEquals('Completed Project', $data['projects'][0]->name);
        
        // Filter by search
        $data = $service->getDashboardData($user, $team, ['search' => 'Active']);
        $this->assertEquals(1, count($data['projects']));
        $this->assertEquals('Active Project', $data['projects'][0]->name);
    }

    public function test_project_stats_calculation(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create([
            'deadline' => now()->addDays(5)
        ]);
        
        $pitch = Pitch::factory()->for($project)->create([
            'user_id' => User::factory()->create()->id,
            'status' => 'approved',
            'quoted_price' => 1000
        ]);
        
        $project->update(['approved_pitch_id' => $pitch->id]);

        $service = new MultiProjectDashboardService();
        $stats = $service->updateProjectStats($project);

        $this->assertEquals($project->id, $stats->project_id);
        $this->assertEquals(5, $stats->days_remaining);
        $this->assertFalse($stats->is_overdue);
        $this->assertEquals(1000, $stats->total_revenue);
        $this->assertGreaterThan(0, $stats->completion_percentage);
    }

    public function test_overdue_project_detection(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create([
            'deadline' => now()->subDays(3),
            'status' => 'in_progress'
        ]);

        $service = new MultiProjectDashboardService();
        $stats = $service->updateProjectStats($project);

        $this->assertTrue($stats->is_overdue);
        $this->assertEquals(-3, $stats->days_remaining);
        $this->assertGreaterThanOrEqual(90, $stats->priority_score);
    }

    public function test_team_workload_calculation(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        
        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => 'engineer',
            'status' => 'active'
        ]);

        // Create active projects for the user
        $projects = Project::factory()->count(3)->create(['team_id' => $team->id]);
        foreach ($projects as $project) {
            ProjectTeamMember::factory()->create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'status' => 'active'
            ]);
        }

        $service = new MultiProjectDashboardService();
        $service->updateTeamWorkload($team);

        $workload = TeamMemberWorkload::where('user_id', $user->id)
            ->where('team_id', $team->id)
            ->first();

        $this->assertNotNull($workload);
        $this->assertEquals(3, $workload->active_projects);
        $this->assertGreaterThan(0, $workload->total_hours_allocated);
    }

    public function test_dashboard_view_saving(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $service = new MultiProjectDashboardService();

        $filters = ['status' => ['in_progress'], 'search' => 'test'];
        $columns = ['name', 'client', 'status'];
        $sortConfig = ['sort_by' => 'name', 'sort_direction' => 'asc'];

        $view = $service->saveDashboardView($user, 'My Custom View', $filters, $columns, $sortConfig, $team);

        $this->assertEquals('My Custom View', $view->name);
        $this->assertEquals($filters, $view->filters);
        $this->assertEquals($columns, $view->columns);
        $this->assertEquals($sortConfig, $view->sort_config);
        $this->assertEquals($team->id, $view->team_id);
    }

    public function test_csv_export_generation(): void
    {
        Storage::fake('s3');
        
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        
        Project::factory()->count(3)->for($user)->create(['team_id' => $team->id]);

        $service = new MultiProjectDashboardService();
        $filePath = $service->exportDashboardData($user, $team, [], 'csv');

        Storage::disk('s3')->assertExists($filePath);
        
        $content = Storage::disk('s3')->get($filePath);
        $lines = explode("\n", trim($content));
        
        // Should have header + 3 data rows
        $this->assertCount(4, $lines);
        $this->assertStringContainsString('Project Name', $lines[0]);
    }
}
```

### Unit Tests
```php
<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Pitch;
use App\Services\MultiProjectDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class MultiProjectDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_completion_percentage_calculation(): void
    {
        $service = new MultiProjectDashboardService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateCompletionPercentage');
        $method->setAccessible(true);

        // New project should be 10%
        $project = Project::factory()->make(['status' => 'pending']);
        $percentage = $method->invoke($service, $project);
        $this->assertEquals(10, $percentage);

        // Project with approved pitch should be 30%
        $project->setRelation('approvedPitch', Pitch::factory()->make());
        $percentage = $method->invoke($service, $project);
        $this->assertEquals(30, $percentage);

        // Completed project should be 100%
        $project->status = 'completed';
        $percentage = $method->invoke($service, $project);
        $this->assertEquals(100, $percentage);
    }

    public function test_priority_score_calculation(): void
    {
        $service = new MultiProjectDashboardService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculatePriorityScore');
        $method->setAccessible(true);

        $project = Project::factory()->make();
        
        // Overdue project should have high priority
        $score = $method->invoke($service, $project, -1, true);
        $this->assertGreaterThanOrEqual(100, $score);
        
        // Project due tomorrow should have high priority
        $score = $method->invoke($service, $project, 1, false);
        $this->assertGreaterThanOrEqual(90, $score);
        
        // Project due in 2 weeks should have low priority
        $score = $method->invoke($service, $project, 14, false);
        $this->assertLessThan(50, $score);
    }

    public function test_risk_level_assessment(): void
    {
        $service = new MultiProjectDashboardService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('assessRiskLevel');
        $method->setAccessible(true);

        $project = Project::factory()->make();
        
        // Overdue should be critical
        $risk = $method->invoke($service, $project, 95, true);
        $this->assertEquals('critical', $risk);
        
        // High priority should be high risk
        $risk = $method->invoke($service, $project, 75, false);
        $this->assertEquals('high', $risk);
        
        // Low priority should be low risk
        $risk = $method->invoke($service, $project, 20, false);
        $this->assertEquals('low', $risk);
    }

    public function test_revenue_calculation(): void
    {
        $service = new MultiProjectDashboardService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateProjectRevenue');
        $method->setAccessible(true);

        $project = Project::factory()->make();
        $pitch = Pitch::factory()->make(['quoted_price' => 1500]);
        $project->setRelation('approvedPitch', $pitch);
        
        // Mock transactions
        $project->setRelation('transactions', collect([
            (object) ['type' => 'payment', 'status' => 'completed', 'amount' => 100000] // $1000 in cents
        ]));

        $revenue = $method->invoke($service, $project);

        $this->assertEquals(1500, $revenue['total']);
        $this->assertEquals(1000, $revenue['paid']);
        $this->assertEquals(500, $revenue['outstanding']);
    }

    public function test_cache_key_generation(): void
    {
        $service = new MultiProjectDashboardService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getDashboardCacheKey');
        $method->setAccessible(true);

        $cacheKey = $method->invoke($service, 123, 456, ['status' => 'active']);
        
        $this->assertStringContainsString('dashboard_123_456_', $cacheKey);
        $this->assertIsString($cacheKey);
    }
}
```

## Implementation Steps

### Phase 1: Core Dashboard Infrastructure (Week 1)
1. **Database Setup**
   - Create dashboard tables and statistics caching
   - Set up workload tracking and alert systems
   - Add indexes for performance optimization

2. **Dashboard Service**
   - Implement project statistics calculation
   - Create team workload analysis
   - Add filtering and sorting logic

3. **Statistics Engine**
   - Background job for stats computation
   - Cache management and invalidation
   - Performance optimization for large datasets

### Phase 2: UI Implementation (Week 2)
1. **Main Dashboard Component**
   - Project grid with customizable columns
   - Advanced filtering and search
   - Real-time data updates

2. **Summary Widgets**
   - Project summary cards
   - Team workload visualization
   - Alert and notification panel

3. **Interactive Features**
   - Drag-and-drop column customization
   - Saved view management
   - Bulk action capabilities

### Phase 3: Advanced Features (Week 3)
1. **Analytics and Reporting**
   - Export functionality (CSV, PDF)
   - Custom report generation
   - Historical trend analysis

2. **Alert System**
   - Automated alert generation
   - Customizable alert rules
   - Notification integration

3. **Team Management Integration**
   - Workload balancing tools
   - Capacity planning features
   - Resource allocation insights

### Phase 4: Polish and Optimization (Week 4)
1. **Performance Optimization**
   - Query optimization for large datasets
   - Efficient caching strategies
   - Progressive loading for large teams

2. **User Experience Enhancements**
   - Mobile responsive design
   - Keyboard shortcuts and hotkeys
   - Accessibility improvements

3. **Integration and APIs**
   - API endpoints for external tools
   - Webhook integration for real-time updates
   - Third-party dashboard tools integration

## Security Considerations

### Data Access Control
- **Team Isolation**: Proper segregation of team data and permissions
- **Role-Based Filtering**: Dashboard data filtered by user permissions
- **Audit Trail**: Complete logging of dashboard access and actions
- **API Security**: Secure endpoints with proper authentication

### Performance Security
- **Query Limits**: Prevent resource exhaustion with query limits
- **Cache Security**: Secure caching with user-specific keys
- **Rate Limiting**: Prevent abuse of dashboard refresh and export features
- **Data Sanitization**: Proper sanitization of filter inputs

### Privacy Protection
- **Data Anonymization**: Optional anonymization for sensitive projects
- **Export Controls**: Secure export with expiring download links
- **Access Logging**: Track who accessed what project data
- **GDPR Compliance**: Respect data retention and deletion policies

This comprehensive implementation plan provides large studios with powerful project management capabilities while maintaining MixPitch's focus on usability and performance.