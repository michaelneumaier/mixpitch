<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPayoutAccount;
use App\Services\Payouts\PayoutProviderRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutAccountManagementService
{
    protected PayoutProviderRegistry $providerRegistry;

    public function __construct(PayoutProviderRegistry $providerRegistry)
    {
        $this->providerRegistry = $providerRegistry;
    }

    /**
     * Set up a payout account for a user with a specific provider
     */
    public function setupPayoutAccount(User $user, string $providerName, array $accountData = []): array
    {
        Log::info('Setting up payout account', [
            'user_id' => $user->id,
            'provider' => $providerName,
        ]);

        $provider = $this->providerRegistry->get($providerName);
        if (! $provider) {
            return [
                'success' => false,
                'error' => "Provider '{$providerName}' not found",
            ];
        }

        DB::beginTransaction();
        try {
            // Create account with the provider
            $accountResult = $provider->createAccount($user, $accountData);
            if (! $accountResult['success']) {
                DB::rollBack();

                return $accountResult;
            }

            $accountId = $accountResult['account_id'];

            // Check if user already has this account
            $existingAccount = UserPayoutAccount::where('user_id', $user->id)
                ->where('provider', $providerName)
                ->where('account_id', $accountId)
                ->first();

            if ($existingAccount) {
                DB::rollBack();

                return [
                    'success' => false,
                    'error' => 'Account already exists for this provider',
                    'account' => $existingAccount,
                ];
            }

            // Create UserPayoutAccount record
            $payoutAccount = UserPayoutAccount::create([
                'user_id' => $user->id,
                'provider' => $providerName,
                'account_id' => $accountId,
                'status' => UserPayoutAccount::STATUS_PENDING,
                'is_primary' => $this->shouldBecomePrimary($user, $providerName),
                'is_verified' => false,
                'account_data' => $accountResult,
                'created_by' => 'user',
                'setup_completed_at' => now(),
            ]);

            // Update user's preferred payout method if this is their first account
            if (! $user->preferred_payout_method || $user->preferred_payout_method === 'stripe') {
                $user->update(['preferred_payout_method' => $providerName]);
            }

            DB::commit();

            Log::info('Payout account setup successful', [
                'user_id' => $user->id,
                'provider' => $providerName,
                'account_id' => $accountId,
                'payout_account_id' => $payoutAccount->id,
            ]);

            return [
                'success' => true,
                'account' => $payoutAccount,
                'provider_response' => $accountResult,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to setup payout account', [
                'user_id' => $user->id,
                'provider' => $providerName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Switch user's preferred payout provider
     */
    public function switchPreferredProvider(User $user, string $providerName): array
    {
        $provider = $this->providerRegistry->get($providerName);
        if (! $provider) {
            return [
                'success' => false,
                'error' => "Provider '{$providerName}' not found",
            ];
        }

        // Check if user has a verified account with this provider
        if (! $user->hasVerifiedPayoutAccount($providerName)) {
            return [
                'success' => false,
                'error' => "No verified account found for provider '{$providerName}'",
                'requires_setup' => true,
            ];
        }

        $user->update(['preferred_payout_method' => $providerName]);

        // Mark this provider's account as primary
        $account = $user->primaryPayoutAccountForProvider($providerName);
        if ($account) {
            $account->markAsPrimary();
        }

        Log::info('Switched preferred payout provider', [
            'user_id' => $user->id,
            'new_provider' => $providerName,
        ]);

        return [
            'success' => true,
            'new_provider' => $providerName,
            'account' => $account,
        ];
    }

    /**
     * Get onboarding link for a provider
     */
    public function getOnboardingLink(User $user, string $providerName, array $options = []): array
    {
        $provider = $this->providerRegistry->get($providerName);
        if (! $provider) {
            return [
                'success' => false,
                'error' => "Provider '{$providerName}' not found",
            ];
        }

        return $provider->createOnboardingLink($user, $options);
    }

    /**
     * Refresh account status for a provider
     */
    public function refreshAccountStatus(User $user, string $providerName): array
    {
        $provider = $this->providerRegistry->get($providerName);
        if (! $provider) {
            return [
                'success' => false,
                'error' => "Provider '{$providerName}' not found",
            ];
        }

        $status = $provider->getDetailedAccountStatus($user);

        // Update UserPayoutAccount record if it exists
        $payoutAccount = $user->primaryPayoutAccountForProvider($providerName);
        if ($payoutAccount) {
            $newStatus = match ($status['status']) {
                'active' => UserPayoutAccount::STATUS_ACTIVE,
                'restricted' => UserPayoutAccount::STATUS_RESTRICTED,
                'incomplete' => UserPayoutAccount::STATUS_INCOMPLETE,
                'under_review' => UserPayoutAccount::STATUS_UNDER_REVIEW,
                default => UserPayoutAccount::STATUS_PENDING,
            };

            $payoutAccount->update([
                'status' => $newStatus,
                'is_verified' => $status['can_receive_payouts'] ?? false,
                'verified_at' => $status['can_receive_payouts'] ? now() : null,
                'account_data' => $status,
                'last_status_check' => now(),
            ]);
        }

        return [
            'success' => true,
            'status' => $status,
            'account' => $payoutAccount,
        ];
    }

    /**
     * Get available providers for a user
     */
    public function getAvailableProviders(User $user): array
    {
        $availableProviders = $this->providerRegistry->getAvailableProvidersForUser($user);

        return $availableProviders->map(function ($providerData) use ($user) {
            $providerName = $providerData['name'];
            $provider = $providerData['provider'];

            // Get user's account for this provider
            $userAccount = $user->primaryPayoutAccountForProvider($providerName);

            return [
                'name' => $providerName,
                'display_name' => $provider->getDisplayName(),
                'is_configured' => $userAccount !== null,
                'is_ready' => $userAccount && $userAccount->isReadyForPayouts(),
                'is_preferred' => $user->preferred_payout_method === $providerName,
                'pricing' => $provider->getPricingInfo(),
                'supports_instant' => $provider->supportsInstantTransfers(),
                'supported_currencies' => $provider->getSupportedCurrencies(),
                'account' => $userAccount,
                'status' => $userAccount ? $provider->getDetailedAccountStatus($user) : null,
            ];
        })->toArray();
    }

    /**
     * Remove a payout account
     */
    public function removePayoutAccount(User $user, string $providerName): array
    {
        $payoutAccount = $user->primaryPayoutAccountForProvider($providerName);
        if (! $payoutAccount) {
            return [
                'success' => false,
                'error' => 'No account found for this provider',
            ];
        }

        // Check if this is the user's preferred provider
        $wasPreferred = $user->preferred_payout_method === $providerName;

        $payoutAccount->delete();

        // If this was their preferred provider, switch to another available provider
        if ($wasPreferred) {
            $otherAccount = $user->payoutAccounts()->where('is_verified', true)->first();
            if ($otherAccount) {
                $user->update(['preferred_payout_method' => $otherAccount->provider]);
            } else {
                $user->update(['preferred_payout_method' => 'stripe']); // Default fallback
            }
        }

        Log::info('Payout account removed', [
            'user_id' => $user->id,
            'provider' => $providerName,
            'was_preferred' => $wasPreferred,
        ]);

        return [
            'success' => true,
            'was_preferred' => $wasPreferred,
            'new_preferred' => $user->fresh()->preferred_payout_method,
        ];
    }

    /**
     * Get comprehensive payout account summary for a user
     */
    public function getAccountSummary(User $user): array
    {
        $accounts = $this->getAvailableProviders($user);
        $stats = $this->getPayoutStats($user);

        return [
            'user_id' => $user->id,
            'preferred_provider' => $user->preferred_payout_method,
            'total_accounts' => count(array_filter($accounts, fn ($a) => $a['is_configured'])),
            'verified_accounts' => count(array_filter($accounts, fn ($a) => $a['is_ready'])),
            'available_providers' => $accounts,
            'payout_stats' => $stats,
            'recommendations' => $this->getProviderRecommendations($user, $accounts),
        ];
    }

    /**
     * Get payout statistics for a user
     */
    protected function getPayoutStats(User $user): array
    {
        $payouts = $user->payoutSchedules();

        return [
            'total_payouts' => $payouts->count(),
            'total_amount' => $payouts->sum('net_amount'),
            'successful_payouts' => $payouts->where('status', 'completed')->count(),
            'failed_payouts' => $payouts->where('status', 'failed')->count(),
            'pending_payouts' => $payouts->where('status', 'scheduled')->count(),
            'by_provider' => $payouts->selectRaw('payout_provider, count(*) as count, sum(net_amount) as total')
                ->groupBy('payout_provider')
                ->get()
                ->keyBy('payout_provider'),
        ];
    }

    /**
     * Get provider recommendations for a user
     */
    protected function getProviderRecommendations(User $user, array $accounts): array
    {
        $recommendations = [];

        // Recommend PayPal if user doesn't have it and is in supported country
        $hasPayPal = collect($accounts)->where('name', 'paypal')->where('is_configured', true)->isNotEmpty();
        if (! $hasPayPal) {
            $recommendations[] = [
                'type' => 'setup_provider',
                'provider' => 'paypal',
                'title' => 'Set up PayPal',
                'description' => 'PayPal offers free domestic transfers and wider global reach.',
                'priority' => 'medium',
            ];
        }

        // Recommend completing setup for incomplete accounts
        $incompleteAccounts = collect($accounts)->where('is_configured', true)->where('is_ready', false);
        foreach ($incompleteAccounts as $account) {
            $recommendations[] = [
                'type' => 'complete_setup',
                'provider' => $account['name'],
                'title' => "Complete {$account['display_name']} setup",
                'description' => 'Finish setting up your account to receive payouts.',
                'priority' => 'high',
            ];
        }

        return $recommendations;
    }

    /**
     * Determine if this should become the primary account
     */
    protected function shouldBecomePrimary(User $user, string $providerName): bool
    {
        // If user has no accounts for this provider, make it primary
        return ! $user->payoutAccountsForProvider($providerName)->exists();
    }
}
