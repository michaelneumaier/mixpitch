<?php

namespace App\Filament\Plugins;

use App\Filament\Plugins\Billing\Pages\BillingDashboard;
use App\Filament\Plugins\Billing\Pages\InvoiceDetailsPage;
use App\Filament\Plugins\Billing\Pages\SetupCompletePage;
use Filament\Contracts\Plugin;
use Filament\Panel;

class BillingPlugin implements Plugin
{
    public function getId(): string
    {
        return 'billing';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                BillingDashboard::class,
                InvoiceDetailsPage::class,
                SetupCompletePage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Register navigation items
        $panel->navigationItems([
            \Filament\Navigation\NavigationItem::make('Billing')
                ->url(BillingDashboard::getUrl())
                ->icon('heroicon-o-credit-card')
                ->group('Accounting')
                ->sort(3),
        ]);
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
