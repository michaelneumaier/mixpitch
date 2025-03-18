<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Project;
use App\Models\Pitch;
use Carbon\Carbon;

class ProjectStats extends BaseWidget
{
    protected static ?int $sort = 19;
    
    protected function getStats(): array
    {
        // Get counts for different project statuses
        $draftProjects = Project::where('status', 'draft')->count();
        $inProgressProjects = Project::where('status', 'in_progress')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        
        // Get counts for pitches by status
        $openPitches = Pitch::where('status', 'open')->count();
        $acceptedPitches = Pitch::where('status', 'accepted')->count();
        
        // Get activity trends for the last 7 days
        $lastWeekProjects = Project::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count')
            ->toArray();
            
        $lastWeekPitches = Pitch::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count')
            ->toArray();
            
        return [
            Stat::make('Projects by Status', Project::count())
                ->description('Total projects in the system')
                ->descriptionIcon('heroicon-m-folder')
                ->chart([
                    $draftProjects, 
                    $inProgressProjects, 
                    $completedProjects
                ])
                ->color('success'),
                
            Stat::make('Active Projects', Project::where('status', 'in_progress')->count())
                ->description('Projects currently in progress')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($lastWeekProjects)
                ->color('primary'),
                
            Stat::make('Pitches by Status', Pitch::count())
                ->description("$openPitches open, $acceptedPitches accepted")
                ->descriptionIcon('heroicon-m-musical-note')
                ->chart($lastWeekPitches)
                ->color('warning'),
        ];
    }
}
