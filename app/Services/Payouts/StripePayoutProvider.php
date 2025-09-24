<?php

namespace App\Services\Payouts;

use App\Models\User;
use Carbon\Carbon;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripePayoutProvider extends AbstractPayoutProvider
{
    private StripeClient $stripe;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $secretKey = $this->getConfig('secret_key');

        // In testing environments, allow mock secret key
        if (app()->environment('testing') && ! $secretKey) {
            $secretKey = 'sk_test_fake_key_for_testing';
        }

        if (! $secretKey) {
            throw new \InvalidArgumentException('Missing required configuration: secret_key');
        }

        $this->stripe = new StripeClient($secretKey);
    }

    public function getProviderName(): string
    {
        return 'stripe';
    }

    public function getDisplayName(): string
    {
        return 'Stripe Connect';
    }

    public function createAccount(User $user, array $options = []): array
    {
        try {
            $account = $this->stripe->accounts->create([
                'type' => 'express',
                'country' => $options['country'] ?? 'US',
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_type' => $options['business_type'] ?? 'individual',
                'metadata' => [
                    'user_id' => $user->id,
                    'created_via' => 'mixpitch_platform',
                    'user_name' => $user->name,
                ],
            ]);

            // Update user with Stripe account ID
            $this->setUserAccountId($user, $account->id);

            $this->logActivity('info', 'Stripe Connect account created', [
                'user_id' => $user->id,
                'stripe_account_id' => $account->id,
            ]);

            return $this->buildSuccessResponse([
                'account_id' => $account->id,
                'account' => $account,
            ]);

        } catch (ApiErrorException $e) {
            return $this->buildErrorResponse($e->getMessage(), [
                'user_id' => $user->id,
            ]);
        }
    }

    public function createOnboardingLink(User $user, array $options = []): array
    {
        try {
            // Create account if it doesn't exist
            $accountId = $this->getUserAccountId($user);
            if (! $accountId) {
                $result = $this->createAccount($user, $options);
                if (! $result['success']) {
                    return $result;
                }
                $accountId = $result['account_id'];
            }

            $accountLink = $this->stripe->accountLinks->create([
                'account' => $accountId,
                'refresh_url' => $options['refresh_url'] ?? route('stripe.connect.refresh'),
                'return_url' => $options['return_url'] ?? route('stripe.connect.return'),
                'type' => 'account_onboarding',
            ]);

            return $this->buildSuccessResponse([
                'url' => $accountLink->url,
            ]);

        } catch (ApiErrorException $e) {
            return $this->buildErrorResponse($e->getMessage(), [
                'user_id' => $user->id,
                'stripe_account_id' => $this->getUserAccountId($user),
            ]);
        }
    }

    public function isAccountReadyForPayouts(User $user): bool
    {
        $accountId = $this->getUserAccountId($user);
        if (! $accountId) {
            return false;
        }

        try {
            $account = $this->stripe->accounts->retrieve($accountId);

            return $account->charges_enabled &&
                   $account->payouts_enabled &&
                   $account->details_submitted;

        } catch (ApiErrorException $e) {
            $this->logActivity('error', 'Failed to check account status', [
                'user_id' => $user->id,
                'stripe_account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getDetailedAccountStatus(User $user): array
    {
        $accountId = $this->getUserAccountId($user);
        if (! $accountId) {
            return [
                'status' => 'not_created',
                'status_display' => 'Account Not Created',
                'status_description' => 'You need to create a Stripe Connect account to receive payouts.',
                'charges_enabled' => false,
                'payouts_enabled' => false,
                'details_submitted' => false,
                'requirements' => [],
                'next_steps' => ['Create your Stripe Connect account to start receiving payouts.'],
                'can_receive_payouts' => false,
                'verification_status' => 'not_started',
            ];
        }

        try {
            $account = $this->stripe->accounts->retrieve($accountId);

            $status = $this->determineDetailedAccountStatus($account);
            $requirements = $this->parseRequirements($account->requirements);
            $nextSteps = $this->generateNextSteps($account, $requirements);

            return [
                'status' => $status['key'],
                'status_display' => $status['display'],
                'status_description' => $status['description'],
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,
                'requirements' => $requirements,
                'next_steps' => $nextSteps,
                'can_receive_payouts' => $account->payouts_enabled && empty($account->requirements->currently_due),
                'verification_status' => $this->getVerificationStatus($account),
                'account' => $account,
                'deadline' => $account->requirements->current_deadline ? Carbon::createFromTimestamp($account->requirements->current_deadline) : null,
            ];

        } catch (ApiErrorException $e) {
            return [
                'status' => 'error',
                'status_display' => 'Error',
                'status_description' => 'Unable to retrieve account status. Please try again later.',
                'error' => $e->getMessage(),
                'can_receive_payouts' => false,
                'verification_status' => 'error',
            ];
        }
    }

    public function processTransfer(User $recipient, float $amount, array $metadata = []): array
    {
        if (! $this->isAccountReadyForPayouts($recipient)) {
            return $this->buildErrorResponse('Recipient account is not ready for payouts');
        }

        try {
            $amountCents = $this->convertAmountToProviderFormat($amount);

            $transfer = $this->stripe->transfers->create([
                'amount' => $amountCents,
                'currency' => 'usd',
                'destination' => $this->getUserAccountId($recipient),
                'description' => $metadata['description'] ?? 'MixPitch payout',
                'metadata' => array_merge([
                    'recipient_user_id' => $recipient->id,
                    'processed_at' => Carbon::now()->toISOString(),
                ], $metadata),
            ]);

            $this->logActivity('info', 'Stripe transfer processed successfully', [
                'transfer_id' => $transfer->id,
                'recipient_user_id' => $recipient->id,
                'amount' => $amount,
                'metadata' => $metadata,
            ]);

            return $this->buildSuccessResponse([
                'transfer_id' => $transfer->id,
                'transfer' => $transfer,
            ]);

        } catch (ApiErrorException $e) {
            return $this->buildErrorResponse($e->getMessage(), [
                'recipient_user_id' => $recipient->id,
                'amount' => $amount,
                'metadata' => $metadata,
            ]);
        }
    }

    public function reverseTransfer(string $transferId, ?float $amount = null, array $metadata = []): array
    {
        try {
            $reverseParams = [
                'metadata' => array_merge([
                    'reversed_at' => Carbon::now()->toISOString(),
                ], $metadata),
            ];

            if ($amount !== null) {
                $reverseParams['amount'] = $this->convertAmountToProviderFormat($amount);
            }

            $reversal = $this->stripe->transfers->createReversal($transferId, $reverseParams);

            $this->logActivity('info', 'Stripe transfer reversed successfully', [
                'transfer_id' => $transferId,
                'reversal_id' => $reversal->id,
                'amount' => $amount,
                'metadata' => $metadata,
            ]);

            return $this->buildSuccessResponse([
                'reversal_id' => $reversal->id,
                'reversal' => $reversal,
            ]);

        } catch (ApiErrorException $e) {
            return $this->buildErrorResponse($e->getMessage(), [
                'transfer_id' => $transferId,
                'amount' => $amount,
                'metadata' => $metadata,
            ]);
        }
    }

    public function getTransfer(string $transferId): array
    {
        try {
            $transfer = $this->stripe->transfers->retrieve($transferId);

            return $this->buildSuccessResponse([
                'transfer' => $transfer,
            ]);

        } catch (ApiErrorException $e) {
            return $this->buildErrorResponse($e->getMessage(), [
                'transfer_id' => $transferId,
            ]);
        }
    }

    public function createLoginLink(User $user): array
    {
        $accountId = $this->getUserAccountId($user);
        if (! $accountId) {
            return $this->buildErrorResponse('No Stripe account found for user');
        }

        try {
            $loginLink = $this->stripe->accounts->createLoginLink($accountId);

            return $this->buildSuccessResponse([
                'url' => $loginLink->url,
            ]);

        } catch (ApiErrorException $e) {
            return $this->buildErrorResponse($e->getMessage(), [
                'user_id' => $user->id,
                'stripe_account_id' => $accountId,
            ]);
        }
    }

    public function getConfigurationRequirements(): array
    {
        return [
            'secret_key' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Stripe Secret Key (sk_...)',
                'sensitive' => true,
            ],
            'publishable_key' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Stripe Publishable Key (pk_...)',
                'sensitive' => false,
            ],
        ];
    }

    public function validateConfiguration(array $config): array
    {
        $errors = [];

        if (empty($config['secret_key'])) {
            $errors[] = 'Secret key is required';
        } elseif (! str_starts_with($config['secret_key'], 'sk_')) {
            $errors[] = 'Secret key must start with sk_';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY'];
    }

    public function getSupportedCountries(): array
    {
        return ['US', 'CA', 'GB', 'AU', 'AT', 'BE', 'DK', 'FI', 'FR', 'DE', 'HK', 'IE', 'IT', 'JP', 'LU', 'NL', 'NO', 'PT', 'SG', 'ES', 'SE', 'CH'];
    }

    public function supportsInstantTransfers(): bool
    {
        return true;
    }

    public function getPricingInfo(): array
    {
        return [
            'setup_fee' => 0,
            'transaction_fee_percentage' => 2.9,
            'transaction_fee_fixed' => 0.30,
            'currency' => 'USD',
            'description' => '2.9% + $0.30 per transaction',
            'instant_transfer_fee' => 1.5,
            'instant_transfer_description' => '1.5% for instant transfers',
        ];
    }

    // Private methods from original StripeConnectService...

    private function determineDetailedAccountStatus($account): array
    {
        if ($account->requirements->disabled_reason) {
            return $this->getDisabledStatus($account->requirements->disabled_reason);
        }

        if ($account->requirements->disabled_reason === 'under_review') {
            return [
                'key' => 'under_review',
                'display' => 'Under Review',
                'description' => 'Your account is being reviewed by Stripe. This process typically takes 1-2 business days.',
            ];
        }

        if (! $account->details_submitted) {
            return [
                'key' => 'incomplete',
                'display' => 'Setup Incomplete',
                'description' => 'You need to complete your account setup by providing required information.',
            ];
        }

        if (! empty($account->requirements->pending_verification)) {
            return [
                'key' => 'pending_verification',
                'display' => 'Verification Pending',
                'description' => 'Stripe is currently verifying your information. This process can take 1-7 business days.',
            ];
        }

        if (! empty($account->requirements->past_due)) {
            return [
                'key' => 'past_due',
                'display' => 'Action Required',
                'description' => 'Your account has restrictions due to missing information. Please provide the required details immediately.',
            ];
        }

        if (! empty($account->requirements->currently_due)) {
            return [
                'key' => 'action_required',
                'display' => 'Information Required',
                'description' => 'Additional information is needed to complete your account verification.',
            ];
        }

        if (! $account->charges_enabled || ! $account->payouts_enabled) {
            return [
                'key' => 'restricted',
                'display' => 'Account Restricted',
                'description' => 'Your account has restrictions. Please contact support or complete the required actions.',
            ];
        }

        return [
            'key' => 'active',
            'display' => 'Active',
            'description' => 'Your account is fully set up and ready to receive payouts.',
        ];
    }

    private function getDisabledStatus(string $disabledReason): array
    {
        $statusMap = [
            'requirements.past_due' => [
                'key' => 'past_due',
                'display' => 'Past Due',
                'description' => 'Required information was not provided by the deadline. Please update your account immediately.',
            ],
            'requirements.pending_verification' => [
                'key' => 'pending_verification',
                'display' => 'Verification Pending',
                'description' => 'Stripe is currently verifying your information. No action is required at this time.',
            ],
            'rejected.fraud' => [
                'key' => 'rejected_fraud',
                'display' => 'Account Rejected',
                'description' => 'Your account was rejected due to suspected fraud or illegal activity.',
            ],
            'rejected.terms_of_service' => [
                'key' => 'rejected_tos',
                'display' => 'Terms Violation',
                'description' => 'Your account was rejected due to suspected terms of service violations.',
            ],
            'rejected.listed' => [
                'key' => 'rejected_listed',
                'display' => 'Account Rejected',
                'description' => 'Your account was rejected because it appears on a prohibited persons or companies list.',
            ],
            'under_review' => [
                'key' => 'under_review',
                'display' => 'Under Review',
                'description' => 'Your account is under review by Stripe. This process typically takes 1-2 business days.',
            ],
            'listed' => [
                'key' => 'under_investigation',
                'display' => 'Under Investigation',
                'description' => 'Your account is being investigated. Stripe will either reject or reinstate the account accordingly.',
            ],
        ];

        return $statusMap[$disabledReason] ?? [
            'key' => 'restricted',
            'display' => 'Account Restricted',
            'description' => 'Your account has restrictions. Please contact support for more information.',
        ];
    }

    private function parseRequirements($requirements): array
    {
        $parsed = [
            'currently_due' => [],
            'eventually_due' => [],
            'past_due' => [],
            'pending_verification' => [],
            'errors' => [],
        ];

        foreach ($requirements->currently_due ?? [] as $requirement) {
            $parsed['currently_due'][] = $this->formatRequirement($requirement);
        }

        foreach ($requirements->eventually_due ?? [] as $requirement) {
            $parsed['eventually_due'][] = $this->formatRequirement($requirement);
        }

        foreach ($requirements->past_due ?? [] as $requirement) {
            $parsed['past_due'][] = $this->formatRequirement($requirement);
        }

        foreach ($requirements->pending_verification ?? [] as $requirement) {
            $parsed['pending_verification'][] = $this->formatRequirement($requirement);
        }

        foreach ($requirements->errors ?? [] as $error) {
            $parsed['errors'][] = [
                'requirement' => $this->formatRequirement($error->requirement),
                'code' => $error->code,
                'reason' => $error->reason,
            ];
        }

        return $parsed;
    }

    private function formatRequirement(string $requirement): string
    {
        $requirementMap = [
            'individual.verification.document' => 'Identity document (ID or passport)',
            'individual.verification.additional_document' => 'Address verification document',
            'company.verification.document' => 'Business verification document',
            'external_account' => 'Bank account information',
            'tos_acceptance.date' => 'Terms of service acceptance',
            'tos_acceptance.ip' => 'Terms of service acceptance',
            'individual.dob.day' => 'Date of birth',
            'individual.dob.month' => 'Date of birth',
            'individual.dob.year' => 'Date of birth',
            'individual.ssn_last_4' => 'Social Security Number (last 4 digits)',
            'individual.id_number' => 'Full Social Security Number',
            'individual.address.line1' => 'Street address',
            'individual.address.city' => 'City',
            'individual.address.state' => 'State',
            'individual.address.postal_code' => 'ZIP code',
            'individual.first_name' => 'First name',
            'individual.last_name' => 'Last name',
            'individual.email' => 'Email address',
            'individual.phone' => 'Phone number',
            'company.name' => 'Business name',
            'company.tax_id' => 'Tax ID (EIN)',
            'company.address.line1' => 'Business address',
            'company.address.city' => 'Business city',
            'company.address.state' => 'Business state',
            'company.address.postal_code' => 'Business ZIP code',
            'business_profile.url' => 'Business website',
            'business_profile.mcc' => 'Business category',
            'business_profile.product_description' => 'Business description',
        ];

        if (preg_match('/^person_[a-zA-Z0-9]+\.(.+)$/', $requirement, $matches)) {
            $baseRequirement = 'individual.'.$matches[1];

            return $requirementMap[$baseRequirement] ?? 'Additional person information';
        }

        return $requirementMap[$requirement] ?? ucwords(str_replace(['_', '.'], ' ', $requirement));
    }

    private function generateNextSteps($account, array $requirements): array
    {
        $steps = [];

        if (! $account->details_submitted) {
            $steps[] = 'Complete your account setup by clicking "Set Up Stripe Account" below.';

            return $steps;
        }

        if (! empty($requirements['past_due'])) {
            $steps[] = 'Urgent: Provide the following past due information immediately to restore your account:';
            foreach ($requirements['past_due'] as $req) {
                $steps[] = "• $req";
            }

            return $steps;
        }

        if (! empty($requirements['currently_due'])) {
            $deadline = $account->requirements->current_deadline ?
                Carbon::createFromTimestamp($account->requirements->current_deadline)->format('M j, Y') :
                'soon';

            $steps[] = "Provide the following information by $deadline:";
            foreach ($requirements['currently_due'] as $req) {
                $steps[] = "• $req";
            }

            return $steps;
        }

        if (! empty($requirements['pending_verification'])) {
            $steps[] = 'Stripe is currently verifying your information. No action is required.';
            $steps[] = 'Verification typically takes 1-7 business days.';

            return $steps;
        }

        if (! empty($requirements['errors'])) {
            $steps[] = 'Please correct the following issues:';
            foreach ($requirements['errors'] as $error) {
                $steps[] = "• {$error['requirement']}: {$error['reason']}";
            }

            return $steps;
        }

        if (! $account->charges_enabled || ! $account->payouts_enabled) {
            $steps[] = 'Your account has restrictions. Please contact Stripe support for assistance.';

            return $steps;
        }

        $steps[] = 'Your account is fully set up and ready to receive payouts!';

        return $steps;
    }

    private function getVerificationStatus($account): string
    {
        if (! $account->details_submitted) {
            return 'not_started';
        }

        if (! empty($account->requirements->past_due)) {
            return 'past_due';
        }

        if (! empty($account->requirements->currently_due)) {
            return 'action_required';
        }

        if (! empty($account->requirements->pending_verification)) {
            return 'pending';
        }

        if ($account->charges_enabled && $account->payouts_enabled) {
            return 'verified';
        }

        return 'restricted';
    }
}
