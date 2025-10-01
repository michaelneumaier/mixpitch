<?php

namespace App\Services\Payouts;

use App\Contracts\PayoutProviderInterface;
use App\Models\User;
use Illuminate\Support\Collection;

class PayoutProviderRegistry
{
    protected array $providers = [];

    protected array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Register a payout provider
     */
    public function register(string $name, PayoutProviderInterface $provider): void
    {
        $this->providers[$name] = $provider;
    }

    /**
     * Get a specific provider by name
     */
    public function get(string $name): ?PayoutProviderInterface
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * Get all registered providers
     */
    public function all(): array
    {
        return $this->providers;
    }

    /**
     * Get provider names
     */
    public function getProviderNames(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Check if a provider is registered
     */
    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    /**
     * Get available providers for a user based on their location/preferences
     */
    public function getAvailableProvidersForUser(User $user): Collection
    {
        $availableProviders = collect();

        foreach ($this->providers as $name => $provider) {
            // Check if provider is enabled in configuration
            if (! $this->isProviderEnabled($name)) {
                continue;
            }

            // Check if provider supports user's country
            $userCountry = $this->getUserCountry($user);
            if (! in_array($userCountry, $provider->getSupportedCountries())) {
                continue;
            }

            $availableProviders->put($name, [
                'name' => $name,
                'display_name' => $provider->getDisplayName(),
                'provider' => $provider,
                'is_configured' => $this->hasAccountId($user, $provider),
                'is_ready' => $this->hasAccountId($user, $provider) && $provider->isAccountReadyForPayouts($user),
                'pricing' => $provider->getPricingInfo(),
                'supports_instant' => $provider->supportsInstantTransfers(),
                'supported_currencies' => $provider->getSupportedCurrencies(),
            ]);
        }

        return $availableProviders;
    }

    /**
     * Get the user's preferred provider or the default
     */
    public function getPreferredProvider(User $user): ?PayoutProviderInterface
    {
        // Check user's preferred provider
        if ($user->preferred_payout_method && $this->has($user->preferred_payout_method)) {
            $provider = $this->get($user->preferred_payout_method);
            if ($provider && $provider->isAccountReadyForPayouts($user)) {
                return $provider;
            }
        }

        // Fallback to first available ready provider
        $availableProviders = $this->getAvailableProvidersForUser($user);

        foreach ($availableProviders as $providerData) {
            if ($providerData['is_ready']) {
                return $providerData['provider'];
            }
        }

        // Last resort: return default provider (Stripe)
        return $this->get('stripe');
    }

    /**
     * Get provider for a specific payout (used when processing existing payouts)
     */
    public function getProviderForPayout(string $providerName): ?PayoutProviderInterface
    {
        return $this->get($providerName);
    }

    /**
     * Check if provider is enabled in configuration
     */
    protected function isProviderEnabled(string $name): bool
    {
        return $this->config['providers'][$name]['enabled'] ?? false;
    }

    /**
     * Get user's country (implement based on your user model)
     */
    protected function getUserCountry(User $user): string
    {
        // For now, default to US. You might want to implement
        // location detection or add a country field to users table
        return 'US';
    }

    /**
     * Check if user has account ID for provider
     */
    protected function hasAccountId(User $user, PayoutProviderInterface $provider): bool
    {
        $accountId = $provider->getUserAccountId($user);

        return ! empty($accountId);
    }

    /**
     * Get providers that support a specific currency
     */
    public function getProvidersByCurrency(string $currency): Collection
    {
        return collect($this->providers)->filter(function ($provider) use ($currency) {
            return in_array(strtoupper($currency), $provider->getSupportedCurrencies());
        });
    }

    /**
     * Get providers that support a specific country
     */
    public function getProvidersByCountry(string $country): Collection
    {
        return collect($this->providers)->filter(function ($provider) use ($country) {
            return in_array(strtoupper($country), $provider->getSupportedCountries());
        });
    }

    /**
     * Get system health status for all providers
     */
    public function getHealthStatus(): array
    {
        $status = [];

        foreach ($this->providers as $name => $provider) {
            try {
                // You could implement a health check method in providers
                $status[$name] = [
                    'status' => 'healthy',
                    'enabled' => $this->isProviderEnabled($name),
                    'last_check' => now(),
                ];
            } catch (\Exception $e) {
                $status[$name] = [
                    'status' => 'unhealthy',
                    'enabled' => $this->isProviderEnabled($name),
                    'error' => $e->getMessage(),
                    'last_check' => now(),
                ];
            }
        }

        return $status;
    }
}
