<?php

namespace App\Contracts;

use App\Models\User;

interface PayoutProviderInterface
{
    /**
     * Get the provider name/identifier
     */
    public function getProviderName(): string;

    /**
     * Get display name for the provider
     */
    public function getDisplayName(): string;

    /**
     * Create a payout account for a user
     */
    public function createAccount(User $user, array $options = []): array;

    /**
     * Create an onboarding/setup link for a user to complete their account setup
     */
    public function createOnboardingLink(User $user, array $options = []): array;

    /**
     * Check if a user's account is fully set up and can receive transfers
     */
    public function isAccountReadyForPayouts(User $user): bool;

    /**
     * Get detailed account status and requirements for a user
     */
    public function getDetailedAccountStatus(User $user): array;

    /**
     * Process a transfer to a connected account
     */
    public function processTransfer(User $recipient, float $amount, array $metadata = []): array;

    /**
     * Reverse a transfer (for refunds)
     */
    public function reverseTransfer(string $transferId, ?float $amount = null, array $metadata = []): array;

    /**
     * Get transfer details
     */
    public function getTransfer(string $transferId): array;

    /**
     * Create a login/dashboard link for an existing connected account
     */
    public function createLoginLink(User $user): array;

    /**
     * Get provider-specific configuration requirements
     */
    public function getConfigurationRequirements(): array;

    /**
     * Validate provider-specific configuration
     */
    public function validateConfiguration(array $config): array;

    /**
     * Get supported currencies for this provider
     */
    public function getSupportedCurrencies(): array;

    /**
     * Get supported countries for this provider
     */
    public function getSupportedCountries(): array;

    /**
     * Check if provider supports instant transfers
     */
    public function supportsInstantTransfers(): bool;

    /**
     * Get provider-specific fees/pricing information
     */
    public function getPricingInfo(): array;
}
