<?php

namespace App\Filament\Plugins\Billing\Pages;

use App\Filament\Plugins\Billing\Widgets\RecentTransactionsWidget;
use App\Filament\Plugins\Billing\Widgets\RevenueOverviewWidget;
use App\Filament\Plugins\Billing\Widgets\TopCustomersWidget;
use App\Filament\Plugins\Billing\Widgets\UserBillingStatusWidget;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class BillingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $view = 'filament.pages.billing.dashboard';

    protected static ?string $navigationLabel = 'Billing Overview';

    protected static ?string $title = 'Billing Administration';

    protected static ?string $slug = 'billing';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationGroup = 'Administration';

    /**
     * Only show this page to users with billing administration permissions
     */
    public static function canAccess(): bool
    {
        return Auth::user()->can('manage_billing') || Auth::user()->hasRole('admin');
    }

    // Define the widgets that will be shown
    protected function getHeaderWidgets(): array
    {
        return [
            RevenueOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            UserBillingStatusWidget::class,
            RecentTransactionsWidget::class,
            TopCustomersWidget::class,
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Billing Administration' => '#',
        ];
    }
}
