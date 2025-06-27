<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Illuminate\Support\Facades\Blade;
use App\Services\PitchWorkflowService;
use App\Services\NotificationService;
use App\View\Components\DateTimeFixed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Explicitly bind PitchWorkflowService - Laravel should normally auto-resolve this,
        // but let's try forcing it to debug the controller DI issue.
        $this->app->bind(PitchWorkflowService::class, function ($app) {
            // Manually resolve NotificationService first (which should also be auto-resolvable)
            $notificationService = $app->make(NotificationService::class);
            return new PitchWorkflowService($notificationService);
        });
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

        // Add the ReCaptchaV3 facade as a blade directive
        Blade::directive('recaptchav3', function ($action) {
            return "<?php echo app('recaptcha')->htmlScriptTagJsApi(['action' => $action]); ?>";
        });

        Blade::component('datetime', DateTimeFixed::class);
    }
}
