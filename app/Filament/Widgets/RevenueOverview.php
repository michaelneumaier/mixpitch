<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Project;
use App\Models\Pitch;
use Illuminate\Support\Facades\DB;

class RevenueOverview extends BaseWidget
{
    protected static ?int $sort = 21;
    
    protected static ?string $pollingInterval = '60s';
    
    public static function canView(): bool
    {
        return auth()->user()->can('view-financial-data') || auth()->user()->hasRole('admin');
    }
    
    protected function getStats(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $thisYear = now()->startOfYear();
        
        // Calculate total project values
        $totalProjectValue = Project::where('budget', '>', 0)->sum('budget');
        $thisMonthValue = Project::where('budget', '>', 0)
            ->where('created_at', '>=', $thisMonth)
            ->sum('budget');
        $lastMonthValue = Project::where('budget', '>', 0)
            ->where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $thisMonth)
            ->sum('budget');
            
        // Calculate average project value
        $paidProjectsCount = Project::where('budget', '>', 0)->count();
        $avgProjectValue = $paidProjectsCount > 0 ? $totalProjectValue / $paidProjectsCount : 0;
        
        // Calculate completed project revenue
        $completedRevenue = Project::where('status', 'completed')
            ->where('budget', '>', 0)
            ->sum('budget');
            
        // Calculate this year's revenue
        $thisYearRevenue = Project::where('status', 'completed')
            ->where('budget', '>', 0)
            ->where('completed_at', '>=', $thisYear)
            ->sum('budget');
            
        // Monthly growth calculation
        $monthlyGrowth = $lastMonthValue > 0 
            ? round((($thisMonthValue - $lastMonthValue) / $lastMonthValue) * 100, 1)
            : ($thisMonthValue > 0 ? 100 : 0);
            
        // Revenue trend chart (last 12 months) - database agnostic approach
        $revenueData = Project::where('status', 'completed')
            ->where('budget', '>', 0)
            ->where('completed_at', '>=', now()->subMonths(12))
            ->whereNotNull('completed_at')
            ->get(['budget', 'completed_at']);
            
        // Group by month in PHP to avoid database-specific functions
        $monthlyRevenue = [];
        foreach ($revenueData as $project) {
            $month = $project->completed_at->format('Y-m');
            if (!isset($monthlyRevenue[$month])) {
                $monthlyRevenue[$month] = 0;
            }
            $monthlyRevenue[$month] += $project->budget;
        }
        
        // Get last 12 months and fill missing months with 0
        $revenueChart = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $revenueChart[] = $monthlyRevenue[$month] ?? 0;
        }
        
        return [
            Stat::make('Total Platform Value', '$' . number_format($totalProjectValue))
                ->description('$' . number_format($thisMonthValue) . ' this month (' . ($monthlyGrowth >= 0 ? '+' : '') . $monthlyGrowth . '%)')
                ->descriptionIcon($monthlyGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyGrowth >= 0 ? 'success' : 'danger')
                ->chart($revenueChart),
                
            Stat::make('Completed Revenue', '$' . number_format($completedRevenue))
                ->description('$' . number_format($thisYearRevenue) . ' this year')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Avg. Project Value', '$' . number_format($avgProjectValue))
                ->description($paidProjectsCount . ' paid projects total')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
                
            Stat::make('Conversion Rate', $this->getConversionRate() . '%')
                ->description($this->getConversionDescription())
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($this->getConversionColor()),
        ];
    }
    
    private function getConversionRate(): string
    {
        $totalProjects = Project::count();
        $paidProjects = Project::where('budget', '>', 0)->count();
        
        if ($totalProjects === 0) return '0';
        
        return number_format(($paidProjects / $totalProjects) * 100, 1);
    }
    
    private function getConversionDescription(): string
    {
        $paidProjects = Project::where('budget', '>', 0)->count();
        $totalProjects = Project::count();
        
        return $paidProjects . ' of ' . $totalProjects . ' projects are paid';
    }
    
    private function getConversionColor(): string
    {
        $rate = (float) $this->getConversionRate();
        
        if ($rate >= 50) return 'success';
        if ($rate >= 25) return 'warning';
        return 'danger';
    }
} 