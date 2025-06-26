<?php

namespace App\Filament\Widgets;

use App\Models\PayoutSchedule;
use App\Models\PayoutHoldSetting;
use App\Services\PayoutHoldService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class HoldPeriodStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $holdService = app(PayoutHoldService::class);
        $settings = PayoutHoldSetting::current();
        
        // Basic counts
        $totalScheduled = PayoutSchedule::where('status', 'scheduled')->count();
        $inHoldPeriod = PayoutSchedule::where('status', 'scheduled')
            ->where('hold_release_date', '>', now())
            ->count();
        $readyForRelease = PayoutSchedule::where('status', 'scheduled')
            ->where('hold_release_date', '<=', now())
            ->count();
        $totalBypassed = PayoutSchedule::where('hold_bypassed', true)->count();
        
        // Amount calculations
        $holdAmount = PayoutSchedule::where('status', 'scheduled')
            ->where('hold_release_date', '>', now())
            ->sum('net_amount');
        $readyAmount = PayoutSchedule::where('status', 'scheduled')
            ->where('hold_release_date', '<=', now())
            ->sum('net_amount');

        $stats = [
            Stat::make('Hold Period Status', $settings->enabled ? 'Active' : 'Disabled')
                ->description($settings->enabled ? 'Hold periods are being enforced' : 'All payouts process immediately')
                ->icon($settings->enabled ? 'heroicon-o-shield-check' : 'heroicon-o-shield-exclamation')
                ->color($settings->enabled ? 'success' : 'warning')
                ->url('/admin/payout-hold-settings')
                ->extraAttributes(['title' => 'Click to configure hold period settings']),

            Stat::make('In Hold Period', $inHoldPeriod)
                ->description('Payouts waiting for hold release')
                ->descriptionIcon('heroicon-o-clock')
                ->chart($this->getHoldTrendData())
                ->color('warning'),

            Stat::make('Ready for Release', $readyForRelease)
                ->description('Payouts ready to process')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->url('/admin/payout-schedules?tab=ready')
                ->extraAttributes(['title' => 'Click to view ready payouts']),

            Stat::make('Hold Amount', '$' . number_format($holdAmount, 2))
                ->description('Total amount in hold')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info'),
        ];

        // Add bypass stats only for admins
        if ($holdService->canBypassHold(Auth::user())) {
            $stats[] = Stat::make('Bypassed This Month', $this->getBypassedThisMonth())
                ->description('Admin bypasses this month')
                ->descriptionIcon('heroicon-o-shield-exclamation')
                ->color('warning')
                ->url(url('/admin/payout-schedules?tableFilters[bypassed_holds][isActive]=true'));
        }

        return $stats;
    }

    protected function getHoldTrendData(): array
    {
        // Get the last 7 days of hold period data
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = PayoutSchedule::where('status', 'scheduled')
                ->where('hold_release_date', '>', $date->startOfDay())
                ->where('hold_release_date', '<=', $date->endOfDay())
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    protected function getBypassedThisMonth(): int
    {
        return PayoutSchedule::where('hold_bypassed', true)
            ->whereMonth('bypassed_at', now()->month)
            ->whereYear('bypassed_at', now()->year)
            ->count();
    }

    public function getPollingInterval(): ?string
    {
        return '30s'; // Refresh every 30 seconds
    }
}
