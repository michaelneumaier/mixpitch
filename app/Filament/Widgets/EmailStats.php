<?php

namespace App\Filament\Widgets;

use App\Models\EmailAudit;
use App\Models\EmailEvent;
use App\Models\EmailSuppression;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EmailStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Get today's date for comparison
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $lastWeek = now()->subWeek()->startOfWeek();
        
        // Get email stats
        $totalEmails = EmailAudit::count();
        $todayEmails = EmailAudit::where('created_at', '>=', $today)->count();
        $yesterdayEmails = EmailAudit::where('created_at', '>=', $yesterday)
            ->where('created_at', '<', $today)
            ->count();
        
        // Calculate percentage change
        $emailPercentChange = $yesterdayEmails > 0 
            ? round((($todayEmails - $yesterdayEmails) / $yesterdayEmails) * 100, 1) 
            : 0;
        
        // Get status breakdown
        $statusBreakdown = EmailAudit::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        
        // Get suppression stats
        $totalSuppressions = EmailSuppression::count();
        $thisWeekSuppressions = EmailSuppression::where('created_at', '>=', $thisWeek)->count();
        $lastWeekSuppressions = EmailSuppression::where('created_at', '>=', $lastWeek)
            ->where('created_at', '<', $thisWeek)
            ->count();
        
        // Calculate percentage change
        $suppressionPercentChange = $lastWeekSuppressions > 0 
            ? round((($thisWeekSuppressions - $lastWeekSuppressions) / $lastWeekSuppressions) * 100, 1) 
            : 0;
        
        // Get bounce rate
        $bouncedEmails = $statusBreakdown['bounced'] ?? 0;
        $sentEmails = $statusBreakdown['sent'] ?? 0;
        $queuedEmails = $statusBreakdown['queued'] ?? 0;
        
        $bounceRate = ($sentEmails + $queuedEmails) > 0 
            ? round(($bouncedEmails / ($sentEmails + $queuedEmails)) * 100, 1) 
            : 0;
        
        return [
            Stat::make('Total Emails', $totalEmails)
                ->description($todayEmails . ' today (' . $emailPercentChange . '% from yesterday)')
                ->descriptionIcon($emailPercentChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($emailPercentChange >= 0 ? 'success' : 'danger'),
            
            Stat::make('Suppressed Emails', $totalSuppressions)
                ->description($thisWeekSuppressions . ' this week (' . $suppressionPercentChange . '% from last week)')
                ->descriptionIcon($suppressionPercentChange <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($suppressionPercentChange <= 0 ? 'success' : 'danger'),
            
            Stat::make('Bounce Rate', $bounceRate . '%')
                ->description('Based on sent and queued emails')
                ->descriptionIcon($bounceRate <= 5 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($bounceRate <= 5 ? 'success' : ($bounceRate <= 10 ? 'warning' : 'danger')),
        ];
    }
}
