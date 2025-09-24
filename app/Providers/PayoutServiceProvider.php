<?php

namespace App\Providers;

use App\Services\Payouts\PayoutProviderRegistry;
use App\Services\Payouts\PayPalPayoutProvider;
use App\Services\Payouts\StripePayoutProvider;
use Illuminate\Support\ServiceProvider;

class PayoutServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the payout provider registry as a singleton
        $this->app->singleton(PayoutProviderRegistry::class, function ($app) {
            $config = config('payouts', []);

            return new PayoutProviderRegistry($config);
        });

        // Register individual providers
        $this->registerStripeProvider();
        $this->registerPayPalProvider();

        // Add future providers here
        // $this->registerWiseProvider();
        // $this->registerDwollaProvider();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register all providers with the registry
        $registry = $this->app->make(PayoutProviderRegistry::class);

        // Register Stripe provider
        if (config('payouts.providers.stripe.enabled', true)) {
            $stripeProvider = $this->app->make(StripePayoutProvider::class);
            $registry->register('stripe', $stripeProvider);
        }

        // Register PayPal provider
        if (config('payouts.providers.paypal.enabled', false)) {
            $paypalProvider = $this->app->make(PayPalPayoutProvider::class);
            $registry->register('paypal', $paypalProvider);
        }

        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/payouts.php' => config_path('payouts.php'),
        ], 'payouts-config');
    }

    /**
     * Register Stripe payout provider
     */
    protected function registerStripeProvider(): void
    {
        $this->app->bind(StripePayoutProvider::class, function ($app) {
            $config = config('payouts.providers.stripe', []);

            // Merge with Cashier configuration for backwards compatibility
            $config = array_merge($config, [
                'secret_key' => $config['secret_key'] ?? config('cashier.secret'),
                'publishable_key' => $config['publishable_key'] ?? config('cashier.key'),
            ]);

            return new StripePayoutProvider($config);
        });
    }

    /**
     * Register PayPal payout provider
     */
    protected function registerPayPalProvider(): void
    {
        $this->app->bind(PayPalPayoutProvider::class, function ($app) {
            $config = config('payouts.providers.paypal', []);

            return new PayPalPayoutProvider($config);
        });
    }

    /**
     * Register Wise payout provider (future implementation)
     */
    protected function registerWiseProvider(): void
    {
        // Implementation for future Wise provider
        // $this->app->bind(WisePayoutProvider::class, function ($app) {
        //     $config = config('payouts.providers.wise', []);
        //     return new WisePayoutProvider($config);
        // });
    }

    /**
     * Register Dwolla payout provider (future implementation)
     */
    protected function registerDwollaProvider(): void
    {
        // Implementation for future Dwolla provider
        // $this->app->bind(DwollaPayoutProvider::class, function ($app) {
        //     $config = config('payouts.providers.dwolla', []);
        //     return new DwollaPayoutProvider($config);
        // });
    }
}
