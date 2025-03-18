<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 20;
    
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Users registered on the platform')
                ->descriptionIcon('heroicon-m-user')
                ->chart(User::query()
                    ->selectRaw('count(*) as count')
                    ->selectRaw('DATE(created_at) as date')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->groupBy('date')
                    ->pluck('count')
                    ->toArray())
                ->color('primary'),
                
            Stat::make('Total Projects', Project::count())
                ->description('Projects created')
                ->descriptionIcon('heroicon-m-folder')
                ->chart(Project::query()
                    ->selectRaw('count(*) as count')
                    ->selectRaw('DATE(created_at) as date')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->groupBy('date')
                    ->pluck('count')
                    ->toArray())
                ->color('success'),
                
            Stat::make('Total Pitches', Pitch::count())
                ->description('Pitches submitted')
                ->descriptionIcon('heroicon-m-musical-note')
                ->chart(Pitch::query()
                    ->selectRaw('count(*) as count')
                    ->selectRaw('DATE(created_at) as date')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->groupBy('date')
                    ->pluck('count')
                    ->toArray())
                ->color('warning'),
        ];
    }
}
