<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserVerificationStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $unverifiedUsers = $totalUsers - $verifiedUsers;

        $validEmails = User::where('email_valid', true)->count();
        $invalidEmails = User::where('email_valid', false)->count();

        $percentVerified = $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100) : 0;
        $percentValid = $totalUsers > 0 ? round(($validEmails / $totalUsers) * 100) : 0;

        return [
            Stat::make('Total Users', $totalUsers)
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->chart([7, 2, 10, 3, 15, 4, $totalUsers])
                ->color('gray'),

            Stat::make('Verified Users', $verifiedUsers)
                ->description("$percentVerified% of total users")
                ->descriptionIcon('heroicon-m-check-badge')
                ->chart([2, 4, 6, 8, 10, 12, $verifiedUsers])
                ->color('success'),

            Stat::make('Unverified Users', $unverifiedUsers)
                ->description('Pending verification')
                ->descriptionIcon('heroicon-m-envelope')
                ->chart([5, 4, 3, 2, 1, 0, $unverifiedUsers])
                ->color($unverifiedUsers > 0 ? 'warning' : 'success'),

            Stat::make('Valid Emails', $validEmails)
                ->description("$percentValid% of total users")
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart([3, 5, 7, 9, 11, 13, $validEmails])
                ->color('success'),

            Stat::make('Invalid Emails', $invalidEmails)
                ->description('Bounced or reported')
                ->descriptionIcon('heroicon-m-x-circle')
                ->chart([0, 1, 0, 2, 1, 0, $invalidEmails])
                ->color($invalidEmails > 0 ? 'danger' : 'success'),
        ];
    }
}
