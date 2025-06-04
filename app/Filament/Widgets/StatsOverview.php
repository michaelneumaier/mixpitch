<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\PitchResource;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 20;
    
    protected function getStats(): array
    {
        // Calculate growth metrics
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $lastWeek = now()->subWeek()->startOfWeek();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        
        // User metrics
        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $newUsersThisWeek = User::where('created_at', '>=', $thisWeek)->count();
        $newUsersLastWeek = User::where('created_at', '>=', $lastWeek)
            ->where('created_at', '<', $thisWeek)->count();
        
        $userGrowth = $newUsersLastWeek > 0 
            ? round((($newUsersThisWeek - $newUsersLastWeek) / $newUsersLastWeek) * 100, 1)
            : ($newUsersThisWeek > 0 ? 100 : 0);
        
        // Project metrics
        $totalProjects = Project::count();
        $publishedProjects = Project::where('is_published', true)->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $activeProjects = Project::whereIn('status', ['in_progress', 'pending_review'])->count();
        
        // Pitch metrics
        $totalPitches = Pitch::count();
        $approvedPitches = Pitch::where('status', 'approved')->count();
        $completedPitches = Pitch::where('status', 'completed')->count();
        $successRate = $totalPitches > 0 ? round((($approvedPitches + $completedPitches) / $totalPitches) * 100, 1) : 0;
        
        // Recent activity charts (last 7 days)
        $userChart = User::query()
            ->selectRaw('count(*) as count')
            ->selectRaw('DATE(created_at) as date')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
            
        $projectChart = Project::query()
            ->selectRaw('count(*) as count')
            ->selectRaw('DATE(created_at) as date')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
            
        $pitchChart = Pitch::query()
            ->selectRaw('count(*) as count')
            ->selectRaw('DATE(created_at) as date')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
        
        return [
            Stat::make('Total Users', $totalUsers)
                ->description($newUsersThisWeek . ' new this week (' . ($userGrowth >= 0 ? '+' : '') . $userGrowth . '%)')
                ->descriptionIcon($userGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($userGrowth >= 0 ? 'success' : 'danger')
                ->chart($userChart)
                ->url(UserResource::getUrl()),
                
            Stat::make('Active Projects', $activeProjects)
                ->description($publishedProjects . ' published • ' . $completedProjects . ' completed')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('primary')
                ->chart($projectChart)
                ->url(ProjectResource::getUrl()),
                
            Stat::make('Pitch Success Rate', $successRate . '%')
                ->description($totalPitches . ' total pitches • ' . ($approvedPitches + $completedPitches) . ' successful')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($successRate >= 60 ? 'success' : ($successRate >= 40 ? 'warning' : 'danger'))
                ->chart($pitchChart)
                ->url(PitchResource::getUrl()),
                
            Stat::make('Platform Health', $this->calculateHealthScore())
                ->description($this->getHealthDescription())
                ->descriptionIcon('heroicon-m-heart')
                ->color($this->getHealthColor())
                ->chart($this->getPlatformHealthChart()),
        ];
    }
    
    private function calculateHealthScore(): string
    {
        $verificationRate = User::count() > 0 ? (User::whereNotNull('email_verified_at')->count() / User::count()) * 100 : 100;
        $projectCompletionRate = Project::count() > 0 ? (Project::where('status', 'completed')->count() / Project::count()) * 100 : 100;
        $pitchSuccessRate = Pitch::count() > 0 ? ((Pitch::where('status', 'approved')->count() + Pitch::where('status', 'completed')->count()) / Pitch::count()) * 100 : 100;
        
        $healthScore = round(($verificationRate + $projectCompletionRate + $pitchSuccessRate) / 3, 1);
        
        return $healthScore . '%';
    }
    
    private function getHealthDescription(): string
    {
        $healthScore = (float) str_replace('%', '', $this->calculateHealthScore());
        
        if ($healthScore >= 80) return 'Excellent platform health';
        if ($healthScore >= 60) return 'Good platform health';
        if ($healthScore >= 40) return 'Needs attention';
        return 'Requires immediate action';
    }
    
    private function getHealthColor(): string
    {
        $healthScore = (float) str_replace('%', '', $this->calculateHealthScore());
        
        if ($healthScore >= 80) return 'success';
        if ($healthScore >= 60) return 'warning';
        return 'danger';
    }
    
    private function getPlatformHealthScore(): string
    {
        $verificationRate = User::count() > 0 ? (User::whereNotNull('email_verified_at')->count() / User::count()) * 100 : 100;
        $projectCompletionRate = Project::count() > 0 ? (Project::where('status', 'completed')->count() / Project::count()) * 100 : 100;
        $pitchSuccessRate = Pitch::count() > 0 ? ((Pitch::where('status', 'approved')->count() + Pitch::where('status', 'completed')->count()) / Pitch::count()) * 100 : 100;
        
        $healthScore = round(($verificationRate + $projectCompletionRate + $pitchSuccessRate) / 3, 1);
        
        return $healthScore . '%';
    }
    
    private function getPlatformHealthDescription(): string
    {
        $healthScore = (float) str_replace('%', '', $this->getPlatformHealthScore());
        
        if ($healthScore >= 80) return 'Excellent platform health';
        if ($healthScore >= 60) return 'Good platform health';
        if ($healthScore >= 40) return 'Needs attention';
        return 'Requires immediate action';
    }
    
    private function getPlatformHealthColor(): string
    {
        $healthScore = (float) str_replace('%', '', $this->getPlatformHealthScore());
        
        if ($healthScore >= 80) return 'success';
        if ($healthScore >= 60) return 'warning';
        return 'danger';
    }
    
    private function getPlatformHealthChart(): array
    {
        // Implementation of getPlatformHealthChart method
        // This method should return an array representing the platform health chart
        // For example, you can use a line chart to represent the health over time
        return [];
    }
}
