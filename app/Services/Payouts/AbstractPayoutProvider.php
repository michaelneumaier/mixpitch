<?php

namespace App\Services\Payouts;

use App\Contracts\PayoutProviderInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;

abstract class AbstractPayoutProvider implements PayoutProviderInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Log provider-specific activities
     */
    protected function logActivity(string $level, string $message, array $context = []): void
    {
        $context['provider'] = $this->getProviderName();

        Log::log($level, $message, $context);
    }

    /**
     * Format currency amount for display
     */
    protected function formatAmount(float $amount, string $currency = 'USD'): string
    {
        return number_format($amount, 2).' '.strtoupper($currency);
    }

    /**
     * Convert amount to provider-specific format (e.g., cents for Stripe)
     */
    protected function convertAmountToProviderFormat(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Convert amount from provider-specific format
     */
    protected function convertAmountFromProviderFormat(int $amount): float
    {
        return $amount / 100;
    }

    /**
     * Get provider account identifier field name for User model
     */
    public function getAccountIdField(): string
    {
        return $this->getProviderName().'_account_id';
    }

    /**
     * Get user's account ID for this provider
     */
    public function getUserAccountId(User $user): ?string
    {
        $field = $this->getAccountIdField();

        return $user->{$field} ?? null;
    }

    /**
     * Set user's account ID for this provider
     */
    public function setUserAccountId(User $user, string $accountId): void
    {
        $field = $this->getAccountIdField();

        $user->update([$field => $accountId]);
    }

    /**
     * Build standardized error response
     */
    protected function buildErrorResponse(string $error, array $context = []): array
    {
        $this->logActivity('error', $error, $context);

        return [
            'success' => false,
            'error' => $error,
            'provider' => $this->getProviderName(),
            'context' => $context,
        ];
    }

    /**
     * Build standardized success response
     */
    protected function buildSuccessResponse(array $data = []): array
    {
        return array_merge([
            'success' => true,
            'provider' => $this->getProviderName(),
        ], $data);
    }

    /**
     * Validate required configuration keys
     */
    protected function validateRequiredConfig(array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (empty($this->config[$key])) {
                throw new \InvalidArgumentException("Missing required configuration: {$key}");
            }
        }

        return true;
    }

    /**
     * Get configuration value with fallback
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Default implementation - can be overridden by providers
     */
    public function getSupportedCurrencies(): array
    {
        return ['USD'];
    }

    /**
     * Default implementation - can be overridden by providers
     */
    public function getSupportedCountries(): array
    {
        return ['US'];
    }

    /**
     * Default implementation - can be overridden by providers
     */
    public function supportsInstantTransfers(): bool
    {
        return false;
    }

    /**
     * Default implementation - providers should override with specific pricing
     */
    public function getPricingInfo(): array
    {
        return [
            'setup_fee' => 0,
            'transaction_fee_percentage' => 0,
            'transaction_fee_fixed' => 0,
            'currency' => 'USD',
            'description' => 'Contact provider for pricing information',
        ];
    }

    /**
     * Default configuration requirements - providers should override
     */
    public function getConfigurationRequirements(): array
    {
        return [];
    }

    /**
     * Default configuration validation - providers should override
     */
    public function validateConfiguration(array $config): array
    {
        return [
            'valid' => true,
            'errors' => [],
        ];
    }
}
