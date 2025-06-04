<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\EmailAudit;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Analytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationGroup = 'Analytics';
    
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.pages.analytics';
    
    public static function getNavigationLabel(): string
    {
        return 'Platform Analytics';
    }
    
    public function getTitle(): string
    {
        return 'Platform Analytics';
    }
    
    public function getSubheading(): ?string
    {
        return 'Comprehensive insights into platform performance and user engagement';
    }
    
    public static function canAccess(): bool
    {
        return auth()->user()->can('view-analytics') || auth()->user()->hasRole('admin');
    }
    
    public function getViewData(): array
    {
        $timeRanges = [
            'today' => ['start' => now()->startOfDay(), 'label' => 'Today'],
            'yesterday' => ['start' => now()->subDay()->startOfDay(), 'end' => now()->startOfDay(), 'label' => 'Yesterday'],
            'this_week' => ['start' => now()->startOfWeek(), 'label' => 'This Week'],
            'last_week' => ['start' => now()->subWeek()->startOfWeek(), 'end' => now()->startOfWeek(), 'label' => 'Last Week'],
            'this_month' => ['start' => now()->startOfMonth(), 'label' => 'This Month'],
            'last_month' => ['start' => now()->subMonth()->startOfMonth(), 'end' => now()->startOfMonth(), 'label' => 'Last Month'],
            'this_year' => ['start' => now()->startOfYear(), 'label' => 'This Year'],
        ];
        
        // User Analytics
        $userMetrics = $this->getUserMetrics($timeRanges);
        
        // Project Analytics
        $projectMetrics = $this->getProjectMetrics($timeRanges);
        
        // Pitch Analytics
        $pitchMetrics = $this->getPitchMetrics($timeRanges);
        
        // Financial Analytics
        $financialMetrics = $this->getFinancialMetrics($timeRanges);
        
        // Email Analytics
        $emailMetrics = $this->getEmailMetrics($timeRanges);
        
        // Growth Trends (last 30 days)
        $growthTrends = $this->getGrowthTrends();
        
        return [
            'userMetrics' => $userMetrics,
            'projectMetrics' => $projectMetrics,
            'pitchMetrics' => $pitchMetrics,
            'financialMetrics' => $financialMetrics,
            'emailMetrics' => $emailMetrics,
            'growthTrends' => $growthTrends,
            'topPerformers' => $this->getTopPerformers(),
        ];
    }
    
    private function getUserMetrics(array $timeRanges): array
    {
        $metrics = [];
        
        foreach ($timeRanges as $key => $range) {
            $query = User::where('created_at', '>=', $range['start']);
            if (isset($range['end'])) {
                $query->where('created_at', '<', $range['end']);
            }
            $metrics[$key] = $query->count();
        }
        
        return array_merge($metrics, [
            'total' => User::count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'verification_rate' => User::count() > 0 ? round((User::whereNotNull('email_verified_at')->count() / User::count()) * 100, 1) : 0,
            'with_projects' => User::has('projects')->count(),
            'with_pitches' => User::has('pitches')->count(),
        ]);
    }
    
    private function getProjectMetrics(array $timeRanges): array
    {
        $metrics = [];
        
        foreach ($timeRanges as $key => $range) {
            $query = Project::where('created_at', '>=', $range['start']);
            if (isset($range['end'])) {
                $query->where('created_at', '<', $range['end']);
            }
            $metrics[$key] = $query->count();
        }
        
        return array_merge($metrics, [
            'total' => Project::count(),
            'published' => Project::where('is_published', true)->count(),
            'completed' => Project::where('status', 'completed')->count(),
            'with_budget' => Project::where('budget', '>', 0)->count(),
            'avg_budget' => Project::where('budget', '>', 0)->avg('budget') ?: 0,
            'completion_rate' => Project::count() > 0 ? round((Project::where('status', 'completed')->count() / Project::count()) * 100, 1) : 0,
        ]);
    }
    
    private function getPitchMetrics(array $timeRanges): array
    {
        $metrics = [];
        
        foreach ($timeRanges as $key => $range) {
            $query = Pitch::where('created_at', '>=', $range['start']);
            if (isset($range['end'])) {
                $query->where('created_at', '<', $range['end']);
            }
            $metrics[$key] = $query->count();
        }
        
        return array_merge($metrics, [
            'total' => Pitch::count(),
            'approved' => Pitch::where('status', 'approved')->count(),
            'completed' => Pitch::where('status', 'completed')->count(),
            'success_rate' => Pitch::count() > 0 ? round(((Pitch::where('status', 'approved')->count() + Pitch::where('status', 'completed')->count()) / Pitch::count()) * 100, 1) : 0,
            'avg_per_project' => Project::count() > 0 ? round(Pitch::count() / Project::count(), 1) : 0,
        ]);
    }
    
    private function getFinancialMetrics(array $timeRanges): array
    {
        $completed = Project::where('status', 'completed')->where('budget', '>', 0);
        
        return [
            'total_value' => Project::where('budget', '>', 0)->sum('budget'),
            'completed_revenue' => $completed->sum('budget'),
            'this_month_revenue' => (clone $completed)->where('completed_at', '>=', now()->startOfMonth())->sum('budget'),
            'this_year_revenue' => (clone $completed)->where('completed_at', '>=', now()->startOfYear())->sum('budget'),
            'avg_project_value' => Project::where('budget', '>', 0)->avg('budget') ?: 0,
            'conversion_rate' => Project::count() > 0 ? round((Project::where('budget', '>', 0)->count() / Project::count()) * 100, 1) : 0,
        ];
    }
    
    private function getEmailMetrics(array $timeRanges): array
    {
        if (!class_exists(EmailAudit::class)) {
            return ['total' => 0, 'today' => 0, 'this_week' => 0, 'this_month' => 0];
        }
        
        return [
            'total' => EmailAudit::count(),
            'today' => EmailAudit::where('created_at', '>=', now()->startOfDay())->count(),
            'this_week' => EmailAudit::where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => EmailAudit::where('created_at', '>=', now()->startOfMonth())->count(),
            'bounce_rate' => $this->calculateBounceRate(),
        ];
    }
    
    private function calculateBounceRate(): float
    {
        if (!class_exists(EmailAudit::class)) {
            return 0;
        }
        
        $total = EmailAudit::whereIn('status', ['sent', 'bounced'])->count();
        $bounced = EmailAudit::where('status', 'bounced')->count();
        
        return $total > 0 ? round(($bounced / $total) * 100, 1) : 0;
    }
    
    private function getGrowthTrends(): array
    {
        $days = collect(range(29, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('M j'),
                'users' => User::whereDate('created_at', $date)->count(),
                'projects' => Project::whereDate('created_at', $date)->count(),
                'pitches' => Pitch::whereDate('created_at', $date)->count(),
            ];
        });
        
        return $days->toArray();
    }
    
    private function getTopPerformers(): array
    {
        return [
            'top_users_by_projects' => User::withCount('projects')
                ->orderBy('projects_count', 'desc')
                ->limit(10)
                ->get(),
            'top_users_by_pitches' => User::withCount('pitches')
                ->orderBy('pitches_count', 'desc')
                ->limit(10)
                ->get(),
            'most_valuable_projects' => Project::where('budget', '>', 0)
                ->orderBy('budget', 'desc')
                ->limit(10)
                ->with('user')
                ->get(),
            'most_pitched_projects' => Project::withCount('pitches')
                ->orderBy('pitches_count', 'desc')
                ->limit(10)
                ->with('user')
                ->get(),
        ];
    }
} 