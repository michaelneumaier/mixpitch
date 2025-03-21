<?php

namespace App\Filament\Widgets;

use App\Models\EmailEvent;
use App\Models\EmailSuppression;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EmailStats extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected function getStats(): array
    {
        // Total emails sent
        $totalSent = EmailEvent::where('event_type', 'sent')->count();
        
        // Last 24 hours emails
        $last24Hours = EmailEvent::where('event_type', 'sent')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
        
        // Last 7 days trend (daily count of sent emails for the last 7 days)
        $dailyCounts = EmailEvent::where('event_type', 'sent')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->pluck('count')
            ->toArray();
        
        // Ensure the array has 7 elements (fill with 0 for missing days)
        $trend = array_pad($dailyCounts, 7, 0);
        
        // Calculate delivery rate
        $delivered = EmailEvent::where('event_type', 'delivered')->count();
        $deliveryRate = $totalSent > 0 ? round(($delivered / $totalSent) * 100) : 0;
        
        // Calculate open rate from delivered emails
        $opened = EmailEvent::where('event_type', 'opened')->count();
        $openRate = $delivered > 0 ? round(($opened / $delivered) * 100) : 0;
        
        // Calculate click rate from opened emails
        $clicked = EmailEvent::where('event_type', 'clicked')->count();
        $clickRate = $opened > 0 ? round(($clicked / $opened) * 100) : 0;
        
        // Count bounces and complaints
        $bounces = EmailSuppression::where('type', 'bounce')->count();
        $complaints = EmailSuppression::where('type', 'complaint')->count();
        $bounceRate = $totalSent > 0 ? round(($bounces / $totalSent) * 100, 2) : 0;
        
        // Last 7 days bounces trend
        $bounceTrend = EmailSuppression::where('type', 'bounce')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->pluck('count')
            ->toArray();
            
        // Ensure the array has 7 elements
        $bounceTrend = array_pad($bounceTrend, 7, 0);
        
        return [
            Stat::make('Total Emails Sent', number_format($totalSent))
                ->description($last24Hours . ' in last 24 hours')
                ->descriptionIcon('heroicon-m-envelope')
                ->chart($trend)
                ->color('primary'),
                
            Stat::make('Delivery Rate', $deliveryRate . '%')
                ->description($delivered . ' emails delivered')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart([$deliveryRate, $deliveryRate, $deliveryRate, $deliveryRate, $deliveryRate])
                ->color('success'),
                
            Stat::make('Open Rate', $openRate . '%')
                ->description($opened . ' emails opened')
                ->descriptionIcon('heroicon-m-eye')
                ->chart([$openRate, $openRate, $openRate, $openRate, $openRate])
                ->color('info'),
                
            Stat::make('Click Rate', $clickRate . '%')
                ->description($clicked . ' emails clicked')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->chart([$clickRate, $clickRate, $clickRate, $clickRate, $clickRate])
                ->color('warning'),
                
            Stat::make('Bounce Rate', $bounceRate . '%')
                ->description($bounces . ' bounces, ' . $complaints . ' complaints')
                ->descriptionIcon('heroicon-m-x-circle')
                ->chart($bounceTrend)
                ->color($bounceRate > 5 ? 'danger' : ($bounceRate > 2 ? 'warning' : 'success')),
        ];
    }
}
