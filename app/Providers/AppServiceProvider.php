<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the custom Livewire component to override Jetstream defaults
        \Livewire\Livewire::component('profile.update-profile-information-form', \App\Http\Livewire\Profile\UpdateProfileInformationForm::class);

        // Register Billing widget Livewire components
        Livewire::component('top-customers-widget', \App\Filament\Plugins\Billing\Widgets\TopCustomersWidget::class);
        Livewire::component('revenue-overview-widget', \App\Filament\Plugins\Billing\Widgets\RevenueOverviewWidget::class);
        Livewire::component('user-billing-status-widget', \App\Filament\Plugins\Billing\Widgets\UserBillingStatusWidget::class);
        Livewire::component('recent-transactions-widget', \App\Filament\Plugins\Billing\Widgets\RecentTransactionsWidget::class);
        Livewire::component('invoice-list-widget', \App\Filament\Plugins\Billing\Widgets\InvoiceListWidget::class);
        Livewire::component('payment-method-widget', \App\Filament\Plugins\Billing\Widgets\PaymentMethodWidget::class);
        Livewire::component('one-time-payment-widget', \App\Filament\Plugins\Billing\Widgets\OneTimePaymentWidget::class);
    }
}
