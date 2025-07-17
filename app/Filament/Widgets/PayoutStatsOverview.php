<?php

namespace App\Filament\Widgets;

use App\Models\PayoutSchedule;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PayoutStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Current period calculations
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // Total amounts by status
        $totalScheduled = PayoutSchedule::where('status', 'scheduled')->sum('net_amount');
        $totalCompleted = PayoutSchedule::where('status', 'completed')->sum('net_amount');
        $totalFailed = PayoutSchedule::where('status', 'failed')->sum('net_amount');

        // Ready for release
        $readyCount = PayoutSchedule::where('status', 'scheduled')
            ->where('hold_release_date', '<=', now())
            ->count();
        $readyAmount = PayoutSchedule::where('status', 'scheduled')
            ->where('hold_release_date', '<=', now())
            ->sum('net_amount');

        // Monthly comparison
        $thisMonthCompleted = PayoutSchedule::where('status', 'completed')
            ->where('completed_at', '>=', $thisMonth)
            ->sum('net_amount');
        $lastMonthCompleted = PayoutSchedule::where('status', 'completed')
            ->where('completed_at', '>=', $lastMonth)
            ->where('completed_at', '<', $thisMonth)
            ->sum('net_amount');

        $monthlyGrowth = $lastMonthCompleted > 0
            ? round((($thisMonthCompleted - $lastMonthCompleted) / $lastMonthCompleted) * 100, 1)
            : ($thisMonthCompleted > 0 ? 100 : 0);

        // Success rate
        $totalPayouts = PayoutSchedule::count();
        $successfulPayouts = PayoutSchedule::whereIn('status', ['completed'])->count();
        $successRate = $totalPayouts > 0 ? round(($successfulPayouts / $totalPayouts) * 100, 1) : 0;

        // Recent activity chart (last 14 days)
        $payoutChart = PayoutSchedule::query()
            ->selectRaw('DATE(created_at) as date, count(*) as count, sum(net_amount) as amount')
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        // Commission metrics
        $totalCommissionEarned = PayoutSchedule::where('status', 'completed')
            ->sum('commission_amount');
        $pendingCommission = PayoutSchedule::where('status', 'scheduled')
            ->sum('commission_amount');

        return [
            Stat::make('Ready for Release', $readyCount.' payouts')
                ->description('$'.number_format($readyAmount, 2).' total amount')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($readyCount > 0 ? 'success' : 'gray')
                ->extraAttributes([
                    'class' => $readyCount > 0 ? 'animate-pulse' : '',
                ]),

            Stat::make('Monthly Payouts', '$'.number_format($thisMonthCompleted, 2))
                ->description('vs last month ('.($monthlyGrowth >= 0 ? '+' : '').$monthlyGrowth.'%)')
                ->descriptionIcon($monthlyGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyGrowth >= 0 ? 'success' : 'danger')
                ->chart($payoutChart),

            Stat::make('Success Rate', $successRate.'%')
                ->description($successfulPayouts.' of '.$totalPayouts.' payouts successful')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($successRate >= 95 ? 'success' : ($successRate >= 85 ? 'warning' : 'danger')),

            Stat::make('Total Scheduled', '$'.number_format($totalScheduled, 2))
                ->description(PayoutSchedule::where('status', 'scheduled')->count().' pending payouts')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Commission Earned', '$'.number_format($totalCommissionEarned, 2))
                ->description('$'.number_format($pendingCommission, 2).' pending')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),

            Stat::make('Failed Payouts', '$'.number_format($totalFailed, 2))
                ->description(PayoutSchedule::where('status', 'failed')->count().' failed transactions')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($totalFailed > 0 ? 'danger' : 'success'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    public function getPollingInterval(): ?string
    {
        return '30s'; // Refresh every 30 seconds
    }
}
