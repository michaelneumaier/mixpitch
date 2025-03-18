<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserActivity extends ChartWidget
{
    protected static ?string $heading = 'Monthly Activity';
    
    protected static ?string $maxHeight = '300px';
    
    protected static ?int $sort = 18;
    
    public ?string $filter = 'users';
    
    protected function getFilters(): ?array
    {
        return [
            'users' => 'User Registrations',
            'projects' => 'Projects Created',
            'pitches' => 'Pitches Created',
        ];
    }

    protected function getData(): array
    {
        $months = collect(range(0, 11))->map(function ($i) {
            return Carbon::now()->subMonths($i)->format('M');
        })->reverse()->toArray();
        
        $data = match ($this->filter) {
            'users' => $this->getUserData(),
            'projects' => $this->getProjectData(),
            'pitches' => $this->getPitchData(),
            default => $this->getUserData(),
        };
        
        return [
            'datasets' => [
                [
                    'label' => $this->getFilters()[$this->filter],
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)', // blue
                        'rgba(16, 185, 129, 0.8)', // green
                        'rgba(251, 191, 36, 0.8)', // yellow
                        'rgba(239, 68, 68, 0.8)', // red
                        'rgba(139, 92, 246, 0.8)', // purple
                        'rgba(236, 72, 153, 0.8)', // pink
                        'rgba(248, 113, 113, 0.8)', // light red
                        'rgba(52, 211, 153, 0.8)', // light green
                        'rgba(96, 165, 250, 0.8)', // light blue
                        'rgba(232, 121, 249, 0.8)', // light purple
                        'rgba(244, 114, 182, 0.8)', // light pink
                        'rgba(251, 146, 60, 0.8)', // orange
                    ],
                ],
            ],
            'labels' => $months,
        ];
    }
    
    protected function getUserData(): array
    {
        $counts = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $count = User::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $counts[] = $count;
        }
        
        return $counts;
    }
    
    protected function getProjectData(): array
    {
        $counts = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $count = Project::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $counts[] = $count;
        }
        
        return $counts;
    }
    
    protected function getPitchData(): array
    {
        $counts = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $count = Pitch::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $counts[] = $count;
        }
        
        return $counts;
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
