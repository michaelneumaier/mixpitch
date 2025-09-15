# 17. Analytics Dashboard Implementation Plan

## Feature Overview

The Analytics Dashboard provides large studios with comprehensive insights into client behavior, engineer performance, project efficiency, and overall studio operations. This feature enables data-driven decision making through detailed charts, metrics, and reports while respecting privacy through aggregation and anonymization.

### Core Functionality
- **Client Analytics**: Track client listens, engagement patterns, and feedback behavior
- **Engineer Performance**: Monitor turnaround times, approval rates, and workload distribution
- **Project Duration Analysis**: Analyze project timelines and identify bottlenecks
- **Revenue Analytics**: Track earnings, project values, and financial trends
- **Usage Patterns**: Monitor platform usage, peak times, and feature adoption
- **Export & Reporting**: Generate professional reports for stakeholders
- **Real-time Dashboards**: Live updating metrics and notifications
- **Privacy Protection**: Aggregate and anonymize sensitive data appropriately

## Database Schema

### Analytics Events Table
```sql
CREATE TABLE analytics_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    project_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(100) NOT NULL,
    event_category ENUM('client_interaction', 'project_workflow', 'file_operation', 'system_usage', 'financial') NOT NULL,
    event_data JSON NULL,
    session_id VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    referrer VARCHAR(500) NULL,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    INDEX idx_analytics_events_user_id (user_id),
    INDEX idx_analytics_events_project_id (project_id),
    INDEX idx_analytics_events_type (event_type),
    INDEX idx_analytics_events_category (event_category),
    INDEX idx_analytics_events_occurred_at (occurred_at),
    INDEX idx_analytics_events_session (session_id)
);
```

### Daily Analytics Aggregates Table
```sql
CREATE TABLE daily_analytics_aggregates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    project_id BIGINT UNSIGNED NULL,
    metric_type VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,4) NOT NULL DEFAULT 0,
    additional_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_metric (date, user_id, project_id, metric_type),
    INDEX idx_daily_aggregates_date (date),
    INDEX idx_daily_aggregates_user_id (user_id),
    INDEX idx_daily_aggregates_project_id (project_id),
    INDEX idx_daily_aggregates_metric_type (metric_type)
);
```

### Dashboard Configurations Table
```sql
CREATE TABLE dashboard_configurations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    dashboard_name VARCHAR(255) NOT NULL,
    dashboard_type ENUM('studio_overview', 'project_analytics', 'client_insights', 'engineer_performance', 'custom') NOT NULL,
    layout_config JSON NOT NULL,
    filters JSON NULL,
    is_default BOOLEAN NOT NULL DEFAULT false,
    is_shared BOOLEAN NOT NULL DEFAULT false,
    shared_with JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_dashboard_configs_user_id (user_id),
    INDEX idx_dashboard_configs_type (dashboard_type)
);
```

### Analytics Reports Table
```sql
CREATE TABLE analytics_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('scheduled', 'one_time', 'automated') NOT NULL,
    report_config JSON NOT NULL,
    schedule_config JSON NULL,
    output_format ENUM('pdf', 'excel', 'json', 'csv') NOT NULL DEFAULT 'pdf',
    recipients JSON NULL,
    last_generated_at TIMESTAMP NULL,
    next_generation_at TIMESTAMP NULL,
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_analytics_reports_user_id (user_id),
    INDEX idx_analytics_reports_type (report_type),
    INDEX idx_analytics_reports_next_generation (next_generation_at)
);
```

## Service Architecture

### AnalyticsEventService
```php
<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsEventService
{
    public function trackEvent(
        string $eventType,
        string $eventCategory,
        ?User $user = null,
        ?Project $project = null,
        array $eventData = [],
        ?string $sessionId = null
    ): AnalyticsEvent {
        return AnalyticsEvent::create([
            'user_id' => $user?->id,
            'project_id' => $project?->id,
            'event_type' => $eventType,
            'event_category' => $eventCategory,
            'event_data' => $eventData,
            'session_id' => $sessionId ?? session()->getId(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'referrer' => Request::header('referer'),
            'occurred_at' => now(),
        ]);
    }
    
    public function trackClientInteraction(string $action, Project $project, array $metadata = []): void
    {
        $this->trackEvent(
            "client_{$action}",
            'client_interaction',
            null, // Anonymous for client interactions
            $project,
            array_merge($metadata, [
                'client_email' => $project->client_email,
                'project_workflow' => $project->workflow_type,
            ])
        );
    }
    
    public function trackProjectWorkflow(string $action, Project $project, User $user, array $metadata = []): void
    {
        $this->trackEvent(
            "project_{$action}",
            'project_workflow',
            $user,
            $project,
            array_merge($metadata, [
                'project_workflow' => $project->workflow_type,
                'project_status' => $project->status ?? 'active',
            ])
        );
    }
    
    public function trackFileOperation(string $action, Project $project, User $user, array $fileData = []): void
    {
        $this->trackEvent(
            "file_{$action}",
            'file_operation',
            $user,
            $project,
            array_merge($fileData, [
                'file_type' => $fileData['file_type'] ?? 'unknown',
                'file_size' => $fileData['file_size'] ?? 0,
            ])
        );
    }
    
    public function trackSystemUsage(string $action, User $user, array $metadata = []): void
    {
        $this->trackEvent(
            "system_{$action}",
            'system_usage',
            $user,
            null,
            $metadata
        );
    }
    
    public function trackFinancialEvent(string $action, User $user, array $financialData = []): void
    {
        $this->trackEvent(
            "financial_{$action}",
            'financial',
            $user,
            null,
            $this->sanitizeFinancialData($financialData)
        );
    }
    
    public function getClientListeningSessions(Project $project, Carbon $startDate, Carbon $endDate): array
    {
        $events = AnalyticsEvent::where('project_id', $project->id)
            ->where('event_category', 'client_interaction')
            ->whereIn('event_type', ['client_play', 'client_pause', 'client_seek', 'client_download'])
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->orderBy('occurred_at')
            ->get();
        
        return $this->processSessions($events);
    }
    
    public function getEngineerPerformanceMetrics(User $engineer, Carbon $startDate, Carbon $endDate): array
    {
        $projects = Project::where('user_id', $engineer->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
        
        $totalProjects = $projects->count();
        $completedProjects = $projects->where('status', 'completed')->count();
        
        $avgTurnaroundTime = $this->calculateAverageTurnaroundTime($projects);
        $approvalRate = $this->calculateApprovalRate($projects);
        $clientSatisfaction = $this->calculateClientSatisfaction($projects);
        
        return [
            'total_projects' => $totalProjects,
            'completed_projects' => $completedProjects,
            'completion_rate' => $totalProjects > 0 ? ($completedProjects / $totalProjects) * 100 : 0,
            'avg_turnaround_time_hours' => $avgTurnaroundTime,
            'approval_rate' => $approvalRate,
            'client_satisfaction_score' => $clientSatisfaction,
            'revenue_generated' => $this->calculateRevenueGenerated($projects),
            'active_projects' => $projects->whereIn('status', ['in_progress', 'ready_for_review'])->count(),
        ];
    }
    
    public function getProjectDurationAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $projects = Project::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->with(['pitches' => function($query) {
                $query->where('status', 'completed');
            }])
            ->get();
        
        $durations = [];
        
        foreach ($projects as $project) {
            $completedPitch = $project->pitches->first();
            if ($completedPitch) {
                $duration = $project->created_at->diffInHours($completedPitch->updated_at);
                $durations[] = [
                    'project_id' => $project->id,
                    'workflow_type' => $project->workflow_type,
                    'duration_hours' => $duration,
                    'revision_count' => $this->getRevisionCount($project),
                ];
            }
        }
        
        return [
            'average_duration_hours' => collect($durations)->avg('duration_hours'),
            'median_duration_hours' => $this->calculateMedian(collect($durations)->pluck('duration_hours')->toArray()),
            'by_workflow_type' => collect($durations)->groupBy('workflow_type')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'avg_duration' => $group->avg('duration_hours'),
                    'avg_revisions' => $group->avg('revision_count'),
                ];
            })->toArray(),
            'duration_distribution' => $this->getDurationDistribution($durations),
        ];
    }
    
    protected function processSessions(array $events): array
    {
        $sessions = [];
        $currentSession = null;
        
        foreach ($events as $event) {
            $sessionKey = $event->session_id ?? 'anonymous';
            
            if (!isset($sessions[$sessionKey])) {
                $sessions[$sessionKey] = [
                    'start_time' => $event->occurred_at,
                    'end_time' => $event->occurred_at,
                    'events' => [],
                    'total_listen_time' => 0,
                    'seeks' => 0,
                    'downloads' => 0,
                ];
            }
            
            $sessions[$sessionKey]['events'][] = $event;
            $sessions[$sessionKey]['end_time'] = $event->occurred_at;
            
            if ($event->event_type === 'client_seek') {
                $sessions[$sessionKey]['seeks']++;
            }
            
            if ($event->event_type === 'client_download') {
                $sessions[$sessionKey]['downloads']++;
            }
        }
        
        return array_values($sessions);
    }
    
    protected function calculateAverageTurnaroundTime($projects): float
    {
        $turnaroundTimes = [];
        
        foreach ($projects as $project) {
            $completedPitch = $project->pitches()->where('status', 'completed')->first();
            if ($completedPitch) {
                $turnaroundTimes[] = $project->created_at->diffInHours($completedPitch->updated_at);
            }
        }
        
        return count($turnaroundTimes) > 0 ? array_sum($turnaroundTimes) / count($turnaroundTimes) : 0;
    }
    
    protected function calculateApprovalRate($projects): float
    {
        $totalPitches = 0;
        $approvedPitches = 0;
        
        foreach ($projects as $project) {
            $pitches = $project->pitches;
            $totalPitches += $pitches->count();
            $approvedPitches += $pitches->whereIn('status', ['approved', 'completed'])->count();
        }
        
        return $totalPitches > 0 ? ($approvedPitches / $totalPitches) * 100 : 0;
    }
    
    protected function calculateClientSatisfaction($projects): float
    {
        // Calculate based on revision requests, approval speed, etc.
        $satisfactionScores = [];
        
        foreach ($projects as $project) {
            $revisionCount = $this->getRevisionCount($project);
            $approvalSpeed = $this->getApprovalSpeed($project);
            
            // Simple scoring algorithm - can be made more sophisticated
            $score = max(0, 100 - ($revisionCount * 10) + $approvalSpeed);
            $satisfactionScores[] = min(100, $score);
        }
        
        return count($satisfactionScores) > 0 ? array_sum($satisfactionScores) / count($satisfactionScores) : 0;
    }
    
    protected function calculateRevenueGenerated($projects): float
    {
        return $projects->sum('budget') ?? 0;
    }
    
    protected function getRevisionCount(Project $project): int
    {
        return AnalyticsEvent::where('project_id', $project->id)
            ->where('event_type', 'project_revision_requested')
            ->count();
    }
    
    protected function getApprovalSpeed(Project $project): float
    {
        // Calculate how quickly approvals happen relative to submission
        $approvalEvents = AnalyticsEvent::where('project_id', $project->id)
            ->whereIn('event_type', ['project_approved', 'project_completed'])
            ->first();
        
        if ($approvalEvents) {
            $hoursSinceSubmission = $project->created_at->diffInHours($approvalEvents->occurred_at);
            return max(0, 100 - $hoursSinceSubmission); // Faster approval = higher score
        }
        
        return 0;
    }
    
    protected function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        
        if ($count === 0) return 0;
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        } else {
            return $values[floor($count / 2)];
        }
    }
    
    protected function getDurationDistribution(array $durations): array
    {
        $ranges = [
            '0-24h' => 0,
            '24-48h' => 0,
            '48-72h' => 0,
            '3-7d' => 0,
            '7d+' => 0,
        ];
        
        foreach ($durations as $duration) {
            $hours = $duration['duration_hours'];
            
            if ($hours <= 24) {
                $ranges['0-24h']++;
            } elseif ($hours <= 48) {
                $ranges['24-48h']++;
            } elseif ($hours <= 72) {
                $ranges['48-72h']++;
            } elseif ($hours <= 168) { // 7 days
                $ranges['3-7d']++;
            } else {
                $ranges['7d+']++;
            }
        }
        
        return $ranges;
    }
    
    protected function sanitizeFinancialData(array $data): array
    {
        // Remove or anonymize sensitive financial information
        return array_intersect_key($data, array_flip([
            'transaction_type',
            'amount_range', // Instead of exact amount
            'currency',
            'payment_method_type', // Instead of actual payment method
        ]));
    }
}
```

### AnalyticsDashboardService
```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\DailyAnalyticsAggregate;
use App\Models\DashboardConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AnalyticsDashboardService
{
    public function __construct(
        protected AnalyticsEventService $analyticsEventService
    ) {}
    
    public function getStudioOverviewDashboard(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "studio_overview_{$user->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user, $startDate, $endDate) {
            $projects = Project::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            return [
                'overview_metrics' => $this->getOverviewMetrics($projects, $startDate, $endDate),
                'project_performance' => $this->getProjectPerformance($projects),
                'client_engagement' => $this->getClientEngagementMetrics($projects),
                'revenue_analytics' => $this->getRevenueAnalytics($projects),
                'workflow_efficiency' => $this->getWorkflowEfficiency($projects),
                'trending_data' => $this->getTrendingData($user, $startDate, $endDate),
            ];
        });
    }
    
    public function getProjectAnalyticsDashboard(Project $project): array
    {
        $cacheKey = "project_analytics_{$project->id}";
        
        return Cache::remember($cacheKey, 1800, function () use ($project) {
            return [
                'project_timeline' => $this->getProjectTimeline($project),
                'client_interactions' => $this->getClientInteractions($project),
                'file_activity' => $this->getFileActivity($project),
                'workflow_progression' => $this->getWorkflowProgression($project),
                'engagement_metrics' => $this->getProjectEngagementMetrics($project),
                'performance_indicators' => $this->getProjectPerformanceIndicators($project),
            ];
        });
    }
    
    public function getClientInsightsDashboard(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $projects = Project::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('client_email')
            ->get();
        
        return [
            'client_behavior_patterns' => $this->getClientBehaviorPatterns($projects),
            'listening_analytics' => $this->getListeningAnalytics($projects),
            'feedback_patterns' => $this->getFeedbackPatterns($projects),
            'client_satisfaction' => $this->getClientSatisfactionMetrics($projects),
            'repeat_client_analysis' => $this->getRepeatClientAnalysis($user),
        ];
    }
    
    public function getEngineerPerformanceDashboard(User $user, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'performance_metrics' => $this->analyticsEventService->getEngineerPerformanceMetrics($user, $startDate, $endDate),
            'workload_distribution' => $this->getWorkloadDistribution($user, $startDate, $endDate),
            'skill_analysis' => $this->getSkillAnalysis($user),
            'client_feedback_summary' => $this->getClientFeedbackSummary($user),
            'efficiency_trends' => $this->getEfficiencyTrends($user, $startDate, $endDate),
        ];
    }
    
    public function generateAnalyticsReport(User $user, array $config): array
    {
        $startDate = Carbon::parse($config['start_date']);
        $endDate = Carbon::parse($config['end_date']);
        $reportType = $config['report_type'];
        
        $reportData = match($reportType) {
            'studio_overview' => $this->getStudioOverviewDashboard($user, $startDate, $endDate),
            'client_insights' => $this->getClientInsightsDashboard($user, $startDate, $endDate),
            'engineer_performance' => $this->getEngineerPerformanceDashboard($user, $startDate, $endDate),
            'project_analysis' => $this->getProjectAnalysisReport($user, $startDate, $endDate),
            default => throw new \InvalidArgumentException("Unknown report type: {$reportType}"),
        };
        
        return [
            'report_metadata' => [
                'generated_at' => now(),
                'generated_by' => $user->id,
                'report_type' => $reportType,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'data_privacy_applied' => true,
            ],
            'report_data' => $reportData,
            'executive_summary' => $this->generateExecutiveSummary($reportData, $reportType),
        ];
    }
    
    protected function getOverviewMetrics($projects, Carbon $startDate, Carbon $endDate): array
    {
        $totalProjects = $projects->count();
        $activeProjects = $projects->whereIn('status', ['in_progress', 'ready_for_review'])->count();
        $completedProjects = $projects->where('status', 'completed')->count();
        
        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'completion_rate' => $totalProjects > 0 ? ($completedProjects / $totalProjects) * 100 : 0,
            'avg_project_value' => $projects->avg('budget') ?? 0,
            'total_revenue' => $projects->sum('budget') ?? 0,
            'new_clients' => $this->getNewClientsCount($projects),
            'client_retention_rate' => $this->getClientRetentionRate($projects),
        ];
    }
    
    protected function getProjectPerformance($projects): array
    {
        $performanceData = [];
        
        foreach ($projects as $project) {
            $duration = $this->getProjectDuration($project);
            $revisionCount = $this->analyticsEventService->getRevisionCount($project);
            
            $performanceData[] = [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'workflow_type' => $project->workflow_type,
                'duration_hours' => $duration,
                'revision_count' => $revisionCount,
                'status' => $project->status,
                'budget' => $project->budget,
                'client_satisfaction' => $this->calculateProjectSatisfaction($project),
            ];
        }
        
        return [
            'projects' => $performanceData,
            'averages' => [
                'duration' => collect($performanceData)->avg('duration_hours'),
                'revisions' => collect($performanceData)->avg('revision_count'),
                'satisfaction' => collect($performanceData)->avg('client_satisfaction'),
            ],
            'top_performing' => collect($performanceData)->sortByDesc('client_satisfaction')->take(5)->values()->toArray(),
            'bottlenecks' => $this->identifyBottlenecks($performanceData),
        ];
    }
    
    protected function getClientEngagementMetrics($projects): array
    {
        $engagementData = [];
        
        foreach ($projects as $project) {
            if ($project->client_email) {
                $sessions = $this->analyticsEventService->getClientListeningSessions(
                    $project,
                    $project->created_at,
                    now()
                );
                
                $engagementData[] = [
                    'project_id' => $project->id,
                    'client_email' => $this->anonymizeEmail($project->client_email),
                    'session_count' => count($sessions),
                    'total_listen_time' => collect($sessions)->sum('total_listen_time'),
                    'avg_session_duration' => count($sessions) > 0 ? collect($sessions)->avg(function($session) {
                        return Carbon::parse($session['start_time'])->diffInMinutes($session['end_time']);
                    }) : 0,
                    'downloads' => collect($sessions)->sum('downloads'),
                ];
            }
        }
        
        return [
            'client_engagement' => $engagementData,
            'summary' => [
                'total_sessions' => collect($engagementData)->sum('session_count'),
                'avg_listen_time' => collect($engagementData)->avg('total_listen_time'),
                'most_engaged_clients' => collect($engagementData)->sortByDesc('total_listen_time')->take(5)->values()->toArray(),
            ],
        ];
    }
    
    protected function getRevenueAnalytics($projects): array
    {
        $revenueData = $projects->groupBy(function($project) {
            return $project->created_at->format('Y-m');
        })->map(function($monthProjects) {
            return [
                'project_count' => $monthProjects->count(),
                'total_revenue' => $monthProjects->sum('budget'),
                'avg_project_value' => $monthProjects->avg('budget'),
                'completed_projects' => $monthProjects->where('status', 'completed')->count(),
            ];
        });
        
        $workflowRevenue = $projects->groupBy('workflow_type')->map(function($workflowProjects) {
            return [
                'project_count' => $workflowProjects->count(),
                'total_revenue' => $workflowProjects->sum('budget'),
                'avg_project_value' => $workflowProjects->avg('budget'),
                'completion_rate' => $workflowProjects->where('status', 'completed')->count() / max(1, $workflowProjects->count()) * 100,
            ];
        });
        
        return [
            'monthly_revenue' => $revenueData->toArray(),
            'workflow_revenue' => $workflowRevenue->toArray(),
            'revenue_trends' => $this->calculateRevenueTrends($revenueData),
            'forecasting' => $this->generateRevenueForecasting($revenueData),
        ];
    }
    
    protected function getWorkflowEfficiency($projects): array
    {
        $efficiencyData = $projects->groupBy('workflow_type')->map(function($workflowProjects) {
            $durations = [];
            $approvalRates = [];
            
            foreach ($workflowProjects as $project) {
                $duration = $this->getProjectDuration($project);
                if ($duration > 0) {
                    $durations[] = $duration;
                }
                
                $approvalRates[] = $this->getProjectApprovalRate($project);
            }
            
            return [
                'project_count' => $workflowProjects->count(),
                'avg_duration' => count($durations) > 0 ? array_sum($durations) / count($durations) : 0,
                'median_duration' => $this->calculateMedian($durations),
                'avg_approval_rate' => count($approvalRates) > 0 ? array_sum($approvalRates) / count($approvalRates) : 0,
                'efficiency_score' => $this->calculateEfficiencyScore($durations, $approvalRates),
            ];
        });
        
        return [
            'by_workflow' => $efficiencyData->toArray(),
            'overall_efficiency' => $this->calculateOverallEfficiency($efficiencyData),
            'improvement_opportunities' => $this->identifyImprovementOpportunities($efficiencyData),
        ];
    }
    
    protected function getTrendingData(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $dailyAggregates = DailyAnalyticsAggregate::where('user_id', $user->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('metric_type');
        
        $trends = [];
        
        foreach ($dailyAggregates as $metricType => $aggregates) {
            $values = $aggregates->pluck('metric_value')->toArray();
            $dates = $aggregates->pluck('date')->toArray();
            
            $trends[$metricType] = [
                'data_points' => array_combine($dates, $values),
                'trend_direction' => $this->calculateTrendDirection($values),
                'growth_rate' => $this->calculateGrowthRate($values),
            ];
        }
        
        return $trends;
    }
    
    protected function calculateTrendDirection(array $values): string
    {
        if (count($values) < 2) return 'stable';
        
        $firstHalf = array_slice($values, 0, floor(count($values) / 2));
        $secondHalf = array_slice($values, ceil(count($values) / 2));
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        if ($secondAvg > $firstAvg * 1.1) return 'increasing';
        if ($secondAvg < $firstAvg * 0.9) return 'decreasing';
        
        return 'stable';
    }
    
    protected function calculateGrowthRate(array $values): float
    {
        if (count($values) < 2) return 0;
        
        $firstValue = reset($values);
        $lastValue = end($values);
        
        if ($firstValue == 0) return 0;
        
        return (($lastValue - $firstValue) / $firstValue) * 100;
    }
    
    protected function anonymizeEmail(string $email): string
    {
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1] ?? '';
        
        if (strlen($username) <= 3) {
            $anonymized = str_repeat('*', strlen($username));
        } else {
            $anonymized = substr($username, 0, 2) . str_repeat('*', strlen($username) - 4) . substr($username, -2);
        }
        
        return $anonymized . '@' . $domain;
    }
    
    protected function calculateMedian(array $values): float
    {
        if (empty($values)) return 0;
        
        sort($values);
        $count = count($values);
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        } else {
            return $values[floor($count / 2)];
        }
    }
}
```

## Models

### AnalyticsEvent Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'project_id',
        'event_type',
        'event_category',
        'event_data',
        'session_id',
        'ip_address',
        'user_agent',
        'referrer',
        'occurred_at',
    ];
    
    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
    ];
    
    public $timestamps = false;
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    public function isClientInteraction(): bool
    {
        return $this->event_category === 'client_interaction';
    }
    
    public function isProjectWorkflow(): bool
    {
        return $this->event_category === 'project_workflow';
    }
    
    public function getEventDataAttribute($value): array
    {
        return json_decode($value, true) ?? [];
    }
}
```

### DailyAnalyticsAggregate Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyAnalyticsAggregate extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'date',
        'user_id',
        'project_id',
        'metric_type',
        'metric_value',
        'additional_data',
    ];
    
    protected $casts = [
        'date' => 'date',
        'metric_value' => 'decimal:4',
        'additional_data' => 'array',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
```

### DashboardConfiguration Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardConfiguration extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'dashboard_name',
        'dashboard_type',
        'layout_config',
        'filters',
        'is_default',
        'is_shared',
        'shared_with',
    ];
    
    protected $casts = [
        'layout_config' => 'array',
        'filters' => 'array',
        'shared_with' => 'array',
        'is_default' => 'boolean',
        'is_shared' => 'boolean',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

## UI Implementation

### Analytics Dashboard Livewire Component
```php
<?php

namespace App\Livewire\Analytics;

use App\Services\AnalyticsDashboardService;
use App\Services\AnalyticsEventService;
use Carbon\Carbon;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public $dashboardType = 'studio_overview';
    public $dateRange = '30d';
    public $startDate;
    public $endDate;
    public $selectedMetrics = [];
    public $showExportModal = false;
    public $exportFormat = 'pdf';
    
    public $dashboardData = [];
    public $isLoading = false;
    
    protected $queryString = [
        'dashboardType' => ['except' => 'studio_overview'],
        'dateRange' => ['except' => '30d'],
    ];
    
    public function mount()
    {
        $this->setDateRange();
        $this->loadDashboardData();
    }
    
    public function updatedDashboardType()
    {
        $this->loadDashboardData();
    }
    
    public function updatedDateRange()
    {
        $this->setDateRange();
        $this->loadDashboardData();
    }
    
    public function setCustomDateRange($start, $end)
    {
        $this->startDate = Carbon::parse($start);
        $this->endDate = Carbon::parse($end);
        $this->dateRange = 'custom';
        $this->loadDashboardData();
    }
    
    public function refreshData()
    {
        $this->isLoading = true;
        $this->loadDashboardData();
        $this->isLoading = false;
    }
    
    public function exportDashboard()
    {
        try {
            $dashboardService = app(AnalyticsDashboardService::class);
            
            $config = [
                'report_type' => $this->dashboardType,
                'start_date' => $this->startDate->format('Y-m-d'),
                'end_date' => $this->endDate->format('Y-m-d'),
                'format' => $this->exportFormat,
                'metrics' => $this->selectedMetrics,
            ];
            
            $reportData = $dashboardService->generateAnalyticsReport(auth()->user(), $config);
            
            $fileName = "analytics-{$this->dashboardType}-" . now()->format('Y-m-d');
            
            return match($this->exportFormat) {
                'pdf' => response()->streamDownload(function () use ($reportData) {
                    echo $this->generatePDFReport($reportData);
                }, $fileName . '.pdf', ['Content-Type' => 'application/pdf']),
                'excel' => response()->streamDownload(function () use ($reportData) {
                    echo $this->generateExcelReport($reportData);
                }, $fileName . '.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
                'json' => response()->json($reportData)->download($fileName . '.json'),
                default => response()->json($reportData),
            };
            
        } catch (\Exception $e) {
            session()->flash('error', 'Export failed: ' . $e->getMessage());
        }
    }
    
    protected function setDateRange()
    {
        $this->endDate = now();
        
        $this->startDate = match($this->dateRange) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            'custom' => $this->startDate ?? now()->subDays(30),
            default => now()->subDays(30),
        };
    }
    
    protected function loadDashboardData()
    {
        $this->isLoading = true;
        
        try {
            $dashboardService = app(AnalyticsDashboardService::class);
            
            $this->dashboardData = match($this->dashboardType) {
                'studio_overview' => $dashboardService->getStudioOverviewDashboard(auth()->user(), $this->startDate, $this->endDate),
                'client_insights' => $dashboardService->getClientInsightsDashboard(auth()->user(), $this->startDate, $this->endDate),
                'engineer_performance' => $dashboardService->getEngineerPerformanceDashboard(auth()->user(), $this->startDate, $this->endDate),
                default => [],
            };
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load dashboard data: ' . $e->getMessage());
            $this->dashboardData = [];
        }
        
        $this->isLoading = false;
    }
    
    protected function generatePDFReport(array $reportData): string
    {
        $html = view('exports.analytics-report-pdf', compact('reportData'))->render();
        
        // Use DomPDF or similar
        $pdf = \PDF::loadHTML($html);
        return $pdf->output();
    }
    
    protected function generateExcelReport(array $reportData): string
    {
        // Use PhpSpreadsheet or similar
        // Implementation would create Excel file from report data
        return ''; // Placeholder
    }
    
    public function render()
    {
        return view('livewire.analytics.analytics-dashboard');
    }
}
```

### Analytics Dashboard Blade Template
```blade
{{-- resources/views/livewire/analytics/analytics-dashboard.blade.php --}}
<div class="space-y-6">
    {{-- Header with Controls --}}
    <div class="flex justify-between items-center">
        <flux:heading size="lg">Analytics Dashboard</flux:heading>
        
        <div class="flex items-center gap-4">
            {{-- Dashboard Type Selector --}}
            <flux:select wire:model.live="dashboardType" class="w-48">
                <option value="studio_overview">Studio Overview</option>
                <option value="client_insights">Client Insights</option>
                <option value="engineer_performance">Engineer Performance</option>
            </flux:select>
            
            {{-- Date Range Selector --}}
            <flux:select wire:model.live="dateRange" class="w-32">
                <option value="7d">7 days</option>
                <option value="30d">30 days</option>
                <option value="90d">90 days</option>
                <option value="1y">1 year</option>
            </flux:select>
            
            {{-- Action Buttons --}}
            <flux:button wire:click="refreshData" variant="ghost" :loading="$isLoading">
                <flux:icon name="arrow-path" class="size-4" />
                Refresh
            </flux:button>
            
            <flux:button wire:click="$set('showExportModal', true)" variant="primary">
                <flux:icon name="download" class="size-4" />
                Export
            </flux:button>
        </div>
    </div>
    
    {{-- Loading State --}}
    @if($isLoading)
        <div class="flex items-center justify-center py-12">
            <flux:spinner class="size-8 text-blue-500" />
            <flux:text class="ml-3 text-gray-600">Loading analytics data...</flux:text>
        </div>
    @endif
    
    {{-- Studio Overview Dashboard --}}
    @if($dashboardType === 'studio_overview' && !$isLoading)
        <div class="space-y-6">
            {{-- Overview Metrics Cards --}}
            @if(isset($dashboardData['overview_metrics']))
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <flux:icon name="briefcase" class="size-8 text-blue-500" />
                            </div>
                            <div class="ml-4">
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ $dashboardData['overview_metrics']['total_projects'] }}
                                </div>
                                <div class="text-sm text-gray-600">Total Projects</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <flux:icon name="chart-bar" class="size-8 text-green-500" />
                            </div>
                            <div class="ml-4">
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ number_format($dashboardData['overview_metrics']['completion_rate'], 1) }}%
                                </div>
                                <div class="text-sm text-gray-600">Completion Rate</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <flux:icon name="currency-dollar" class="size-8 text-green-500" />
                            </div>
                            <div class="ml-4">
                                <div class="text-2xl font-bold text-gray-900">
                                    ${{ number_format($dashboardData['overview_metrics']['total_revenue'], 0) }}
                                </div>
                                <div class="text-sm text-gray-600">Total Revenue</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <flux:icon name="users" class="size-8 text-purple-500" />
                            </div>
                            <div class="ml-4">
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ number_format($dashboardData['overview_metrics']['client_retention_rate'], 1) }}%
                                </div>
                                <div class="text-sm text-gray-600">Client Retention</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            {{-- Project Performance Chart --}}
            @if(isset($dashboardData['project_performance']))
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <flux:heading size="md" class="mb-4">Project Performance</flux:heading>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Performance Metrics --}}
                        <div class="space-y-4">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Average Duration</span>
                                <span class="font-semibold">{{ number_format($dashboardData['project_performance']['averages']['duration'], 1) }}h</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Average Revisions</span>
                                <span class="font-semibold">{{ number_format($dashboardData['project_performance']['averages']['revisions'], 1) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Client Satisfaction</span>
                                <span class="font-semibold">{{ number_format($dashboardData['project_performance']['averages']['satisfaction'], 1) }}%</span>
                            </div>
                        </div>
                        
                        {{-- Top Performing Projects --}}
                        <div>
                            <flux:heading size="sm" class="mb-3">Top Performing Projects</flux:heading>
                            <div class="space-y-2">
                                @foreach(array_slice($dashboardData['project_performance']['top_performing'], 0, 5) as $project)
                                    <div class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded">
                                        <span class="text-sm font-medium">{{ Str::limit($project['project_name'], 30) }}</span>
                                        <flux:badge color="green" size="sm">
                                            {{ number_format($project['client_satisfaction'], 1) }}%
                                        </flux:badge>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            {{-- Revenue Analytics --}}
            @if(isset($dashboardData['revenue_analytics']))
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <flux:heading size="md" class="mb-4">Revenue Analytics</flux:heading>
                    
                    {{-- Revenue by Workflow Type --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($dashboardData['revenue_analytics']['workflow_revenue'] as $workflow => $data)
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-lg font-bold text-gray-900">
                                    ${{ number_format($data['total_revenue'], 0) }}
                                </div>
                                <div class="text-sm text-gray-600 capitalize">
                                    {{ str_replace('_', ' ', $workflow) }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $data['project_count'] }} projects
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
    
    {{-- Client Insights Dashboard --}}
    @if($dashboardType === 'client_insights' && !$isLoading)
        <div class="space-y-6">
            {{-- Client Engagement Summary --}}
            @if(isset($dashboardData['listening_analytics']))
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <flux:heading size="sm" class="mb-3">Total Sessions</flux:heading>
                        <div class="text-3xl font-bold text-blue-600">
                            {{ $dashboardData['listening_analytics']['summary']['total_sessions'] ?? 0 }}
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <flux:heading size="sm" class="mb-3">Avg Listen Time</flux:heading>
                        <div class="text-3xl font-bold text-green-600">
                            {{ number_format($dashboardData['listening_analytics']['summary']['avg_listen_time'] ?? 0, 1) }}m
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <flux:heading size="sm" class="mb-3">Engagement Rate</flux:heading>
                        <div class="text-3xl font-bold text-purple-600">
                            {{ number_format($dashboardData['client_satisfaction']['avg_satisfaction'] ?? 0, 1) }}%
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
    
    {{-- Engineer Performance Dashboard --}}
    @if($dashboardType === 'engineer_performance' && !$isLoading)
        <div class="space-y-6">
            {{-- Performance Metrics --}}
            @if(isset($dashboardData['performance_metrics']))
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <flux:heading size="sm" class="mb-3">Projects Completed</flux:heading>
                        <div class="text-3xl font-bold text-blue-600">
                            {{ $dashboardData['performance_metrics']['completed_projects'] }}
                        </div>
                        <div class="text-sm text-gray-600">
                            {{ number_format($dashboardData['performance_metrics']['completion_rate'], 1) }}% rate
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <flux:heading size="sm" class="mb-3">Avg Turnaround</flux:heading>
                        <div class="text-3xl font-bold text-green-600">
                            {{ number_format($dashboardData['performance_metrics']['avg_turnaround_time_hours'], 1) }}h
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <flux:heading size="sm" class="mb-3">Approval Rate</flux:heading>
                        <div class="text-3xl font-bold text-purple-600">
                            {{ number_format($dashboardData['performance_metrics']['approval_rate'], 1) }}%
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <flux:heading size="sm" class="mb-3">Revenue Generated</flux:heading>
                        <div class="text-3xl font-bold text-green-600">
                            ${{ number_format($dashboardData['performance_metrics']['revenue_generated'], 0) }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
    
    {{-- Export Modal --}}
    <flux:modal wire:model="showExportModal" name="export-modal">
        <flux:modal.header>
            <flux:heading size="lg">Export Analytics Report</flux:heading>
        </flux:modal.header>
        
        <flux:modal.body class="space-y-6">
            <flux:field>
                <flux:label>Export Format</flux:label>
                <flux:select wire:model="exportFormat">
                    <option value="pdf">PDF Report</option>
                    <option value="excel">Excel Spreadsheet</option>
                    <option value="json">JSON Data</option>
                </flux:select>
            </flux:field>
            
            <flux:field>
                <flux:label>Include Metrics</flux:label>
                <div class="space-y-2">
                    <flux:checkbox wire:model="selectedMetrics" value="overview">Overview Metrics</flux:checkbox>
                    <flux:checkbox wire:model="selectedMetrics" value="performance">Performance Data</flux:checkbox>
                    <flux:checkbox wire:model="selectedMetrics" value="revenue">Revenue Analytics</flux:checkbox>
                    <flux:checkbox wire:model="selectedMetrics" value="trends">Trend Analysis</flux:checkbox>
                </div>
            </flux:field>
        </flux:modal.body>
        
        <flux:modal.footer>
            <flux:button type="button" wire:click="$set('showExportModal', false)" variant="ghost">
                Cancel
            </flux:button>
            
            <flux:button wire:click="exportDashboard" variant="primary">
                <flux:icon name="download" class="size-4" />
                Export Report
            </flux:button>
        </flux:modal.footer>
    </flux:modal>
</div>
```

## Testing Strategy

### Feature Tests
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\AnalyticsEvent;
use App\Services\AnalyticsEventService;
use App\Services\AnalyticsDashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Project $project;
    protected AnalyticsEventService $analyticsEventService;
    protected AnalyticsDashboardService $dashboardService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->analyticsEventService = app(AnalyticsEventService::class);
        $this->dashboardService = app(AnalyticsDashboardService::class);
    }
    
    public function test_analytics_event_tracking()
    {
        $event = $this->analyticsEventService->trackEvent(
            'client_play',
            'client_interaction',
            null,
            $this->project,
            ['file_id' => 123, 'duration' => 180]
        );
        
        expect($event->event_type)->toBe('client_play');
        expect($event->event_category)->toBe('client_interaction');
        expect($event->project_id)->toBe($this->project->id);
        expect($event->event_data['file_id'])->toBe(123);
        
        $this->assertDatabaseHas('analytics_events', [
            'project_id' => $this->project->id,
            'event_type' => 'client_play',
            'event_category' => 'client_interaction',
        ]);
    }
    
    public function test_client_interaction_tracking()
    {
        $this->analyticsEventService->trackClientInteraction('play', $this->project, [
            'file_type' => 'audio/mp3',
            'duration' => 240,
        ]);
        
        $this->analyticsEventService->trackClientInteraction('pause', $this->project, [
            'position' => 120,
        ]);
        
        $this->analyticsEventService->trackClientInteraction('download', $this->project);
        
        $events = AnalyticsEvent::where('project_id', $this->project->id)
            ->where('event_category', 'client_interaction')
            ->get();
        
        expect($events)->toHaveCount(3);
        expect($events->pluck('event_type')->toArray())->toEqual(['client_play', 'client_pause', 'client_download']);
    }
    
    public function test_engineer_performance_metrics_calculation()
    {
        // Create test projects with different outcomes
        $completedProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'budget' => 1000,
            'created_at' => now()->subDays(5),
        ]);
        
        $activeProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'in_progress',
            'budget' => 1500,
            'created_at' => now()->subDays(2),
        ]);
        
        // Track some workflow events
        $this->analyticsEventService->trackProjectWorkflow('approved', $completedProject, $this->user);
        $this->analyticsEventService->trackProjectWorkflow('revision_requested', $activeProject, $this->user);
        
        $metrics = $this->analyticsEventService->getEngineerPerformanceMetrics(
            $this->user,
            now()->subDays(7),
            now()
        );
        
        expect($metrics['total_projects'])->toBe(2);
        expect($metrics['completed_projects'])->toBe(1);
        expect($metrics['completion_rate'])->toBe(50.0);
        expect($metrics['revenue_generated'])->toBe(2500.0);
    }
    
    public function test_project_duration_analysis()
    {
        // Create projects with known durations
        $fastProject = Project::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subHours(24),
        ]);
        
        $slowProject = Project::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subHours(72),
        ]);
        
        // Mock pitch completion times
        $fastPitch = \App\Models\Pitch::factory()->create([
            'project_id' => $fastProject->id,
            'status' => 'completed',
            'updated_at' => now()->subHours(20),
        ]);
        
        $slowPitch = \App\Models\Pitch::factory()->create([
            'project_id' => $slowProject->id,
            'status' => 'completed',
            'updated_at' => now()->subHours(48),
        ]);
        
        $analysis = $this->analyticsEventService->getProjectDurationAnalysis(
            now()->subDays(7),
            now()
        );
        
        expect($analysis['average_duration_hours'])->toBeGreaterThan(0);
        expect($analysis['duration_distribution'])->toHaveKey('0-24h');
        expect($analysis['duration_distribution'])->toHaveKey('48-72h');
    }
    
    public function test_studio_overview_dashboard()
    {
        // Create test data
        Project::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'budget' => 1000,
        ]);
        
        Project::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'in_progress',
            'budget' => 1500,
        ]);
        
        $dashboard = $this->dashboardService->getStudioOverviewDashboard(
            $this->user,
            now()->subDays(30),
            now()
        );
        
        expect($dashboard)->toHaveKey('overview_metrics');
        expect($dashboard)->toHaveKey('project_performance');
        expect($dashboard)->toHaveKey('revenue_analytics');
        
        expect($dashboard['overview_metrics']['total_projects'])->toBe(8);
        expect($dashboard['overview_metrics']['completed_projects'])->toBe(5);
        expect($dashboard['overview_metrics']['completion_rate'])->toBe(62.5);
        expect($dashboard['overview_metrics']['total_revenue'])->toBe(9500.0);
    }
    
    public function test_client_listening_sessions()
    {
        // Track a listening session
        $sessionId = 'test-session-123';
        
        $this->analyticsEventService->trackEvent('client_play', 'client_interaction', null, $this->project, ['position' => 0], $sessionId);
        $this->analyticsEventService->trackEvent('client_seek', 'client_interaction', null, $this->project, ['position' => 30], $sessionId);
        $this->analyticsEventService->trackEvent('client_pause', 'client_interaction', null, $this->project, ['position' => 60], $sessionId);
        $this->analyticsEventService->trackEvent('client_download', 'client_interaction', null, $this->project, [], $sessionId);
        
        $sessions = $this->analyticsEventService->getClientListeningSessions(
            $this->project,
            now()->subHour(),
            now()
        );
        
        expect($sessions)->toHaveCount(1);
        expect($sessions[0]['seeks'])->toBe(1);
        expect($sessions[0]['downloads'])->toBe(1);
        expect($sessions[0]['events'])->toHaveCount(4);
    }
    
    public function test_analytics_report_generation()
    {
        // Create test data
        Project::factory()->count(3)->create(['user_id' => $this->user->id]);
        
        $config = [
            'report_type' => 'studio_overview',
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'format' => 'json',
        ];
        
        $report = $this->dashboardService->generateAnalyticsReport($this->user, $config);
        
        expect($report)->toHaveKey('report_metadata');
        expect($report)->toHaveKey('report_data');
        expect($report)->toHaveKey('executive_summary');
        
        expect($report['report_metadata']['report_type'])->toBe('studio_overview');
        expect($report['report_metadata']['data_privacy_applied'])->toBeTrue();
    }
    
    public function test_livewire_dashboard_component()
    {
        $this->actingAs($this->user);
        
        Livewire::test(\App\Livewire\Analytics\AnalyticsDashboard::class)
            ->assertSet('dashboardType', 'studio_overview')
            ->assertSet('dateRange', '30d')
            ->set('dashboardType', 'client_insights')
            ->assertSet('dashboardType', 'client_insights')
            ->call('refreshData')
            ->assertHasNoErrors();
    }
    
    public function test_privacy_protection_in_analytics()
    {
        $project = Project::factory()->create([
            'client_email' => 'sensitive@client.com',
        ]);
        
        $this->analyticsEventService->trackClientInteraction('play', $project);
        
        $dashboard = $this->dashboardService->getClientInsightsDashboard(
            $this->user,
            now()->subDays(7),
            now()
        );
        
        // Check that sensitive data is anonymized
        $clientData = $dashboard['client_engagement'][0] ?? [];
        
        if (isset($clientData['client_email'])) {
            expect($clientData['client_email'])->not->toBe('sensitive@client.com');
            expect($clientData['client_email'])->toMatch('/se\*+@client\.com/');
        }
    }
    
    public function test_analytics_event_privacy_sanitization()
    {
        $sensitiveFinancialData = [
            'transaction_id' => 'txn_12345',
            'amount' => 5000.00,
            'payment_method' => 'card_ending_in_4242',
            'transaction_type' => 'payment',
            'currency' => 'USD',
        ];
        
        $this->analyticsEventService->trackFinancialEvent('payment_received', $this->user, $sensitiveFinancialData);
        
        $event = AnalyticsEvent::where('event_type', 'financial_payment_received')->first();
        
        // Sensitive data should be sanitized
        expect($event->event_data)->not->toHaveKey('transaction_id');
        expect($event->event_data)->not->toHaveKey('amount');
        expect($event->event_data)->not->toHaveKey('payment_method');
        
        // Safe data should be preserved
        expect($event->event_data)->toHaveKey('transaction_type');
        expect($event->event_data)->toHaveKey('currency');
    }
}
```

## Implementation Steps

### Phase 1: Analytics Infrastructure (Week 1-2)
1. **Database Schema Setup**
   - Create analytics_events table with proper indexing
   - Create daily_analytics_aggregates for performance
   - Create dashboard_configurations for user preferences
   - Create analytics_reports for scheduled reporting
   - Run migrations and verify data integrity

2. **Service Layer Development**
   - Implement AnalyticsEventService for event tracking
   - Add privacy protection and data sanitization
   - Create AnalyticsDashboardService for dashboard logic
   - Implement caching strategies for performance
   - Add comprehensive validation and error handling

3. **Event Tracking Integration**
   - Add event tracking to existing project workflows
   - Implement client interaction tracking
   - Add file operation tracking
   - Create system usage tracking
   - Integrate with existing Livewire components

### Phase 2: Dashboard Development (Week 3-4)
1. **Core Dashboard UI**
   - Create AnalyticsDashboard Livewire component
   - Implement real-time data loading and caching
   - Add dashboard type switching and filtering
   - Create responsive chart components
   - Add export functionality

2. **Dashboard Views**
   - Build Studio Overview dashboard with key metrics
   - Create Client Insights dashboard with engagement data
   - Implement Engineer Performance dashboard
   - Add customizable dashboard configurations
   - Create mobile-optimized views

3. **Chart Integration**
   - Integrate Chart.js or similar charting library
   - Create reusable chart components
   - Implement interactive data visualization
   - Add chart export capabilities
   - Create trend analysis visualizations

### Phase 3: Advanced Analytics (Week 5-6)
1. **Aggregation System**
   - Implement daily analytics aggregation job
   - Create automated report generation
   - Add trend analysis algorithms
   - Implement forecasting capabilities
   - Create performance optimization

2. **Reporting System**
   - Build PDF report generation
   - Create Excel export functionality
   - Add scheduled report delivery
   - Implement report templates
   - Create executive summary generation

3. **Real-time Features**
   - Add live dashboard updates
   - Implement WebSocket integration
   - Create notification system
   - Add real-time alerts
   - Implement performance monitoring

### Phase 4: Privacy & Performance (Week 7-8)
1. **Privacy Implementation**
   - Implement data anonymization
   - Add GDPR compliance features
   - Create data retention policies
   - Implement consent management
   - Add data export/deletion capabilities

2. **Performance Optimization**
   - Implement aggressive caching strategies
   - Add database query optimization
   - Create index optimization
   - Implement background job processing
   - Add performance monitoring

3. **Testing & Documentation**
   - Write comprehensive feature tests
   - Create unit tests for analytics services
   - Add integration tests for dashboard components
   - Document analytics architecture
   - Create user training materials

## Security Considerations

### Data Privacy
- **Anonymization**: Automatically anonymize sensitive client information
- **Consent Management**: Track and respect user privacy preferences
- **Data Minimization**: Collect only necessary analytics data
- **GDPR Compliance**: Implement right to deletion and data portability

### Access Control
- **Role-Based Access**: Limit analytics access based on user roles
- **Dashboard Permissions**: Control access to sensitive analytics data
- **Export Restrictions**: Limit export capabilities for sensitive data
- **Audit Logging**: Track all analytics access and export operations

### Data Security
- **Encryption**: Encrypt sensitive analytics data at rest
- **Secure Transmission**: Use HTTPS for all analytics API calls
- **Input Validation**: Validate all analytics event data
- **SQL Injection Prevention**: Use parameterized queries for analytics

### Performance Security
- **Rate Limiting**: Prevent analytics API abuse
- **Query Limits**: Limit expensive analytics queries
- **Caching Security**: Ensure cached data doesn't leak sensitive information
- **Background Processing**: Use queues for heavy analytics processing

This implementation provides a comprehensive analytics dashboard that enables studios to make data-driven decisions while maintaining strict privacy and security standards.