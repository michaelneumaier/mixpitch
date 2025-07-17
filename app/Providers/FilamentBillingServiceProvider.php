<?php

namespace App\Providers;

use App\Filament\Plugins\Billing\Pages\BillingDashboard;
use App\Filament\Plugins\Billing\Pages\InvoiceDetailsPage;
use App\Filament\Plugins\Billing\Pages\SetupCompletePage;
use App\Filament\Plugins\Billing\Widgets\InvoiceListWidget;
use App\Filament\Plugins\Billing\Widgets\OneTimePaymentWidget;
use App\Filament\Plugins\Billing\Widgets\PaymentMethodWidget;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FilamentBillingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Livewire components
        Livewire::component('payment-method-widget', PaymentMethodWidget::class);
        Livewire::component('invoice-list-widget', InvoiceListWidget::class);
        Livewire::component('one-time-payment-widget', OneTimePaymentWidget::class);
        Livewire::component('billing-dashboard', BillingDashboard::class);
        Livewire::component('invoice-details-page', InvoiceDetailsPage::class);
        Livewire::component('setup-complete-page', SetupCompletePage::class);

        // Publish views
        $this->publishes([
            __DIR__.'/../../resources/views/filament' => resource_path('views/filament'),
        ], 'filament-billing-views');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'filament-billing');

        // Register assets
        FilamentAsset::register([
            // Load Stripe.js as a Filament asset
            Js::make('stripe-js', 'https://js.stripe.com/v3/'),
        ]);

        // Register custom icons
        FilamentIcon::register([
            'billing' => 'heroicon-o-credit-card',
            'invoice' => 'heroicon-o-document-text',
            'payment' => 'heroicon-o-banknotes',
        ]);
    }
}
