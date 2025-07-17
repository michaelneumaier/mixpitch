<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeConnectService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    /**
     * Create a Stripe Express account for a user
     */
    public function createExpressAccount(User $user): array
    {
        try {
            $account = $this->stripe->accounts->create([
                'type' => 'express',
                'country' => 'US', // Default, can be customized based on user location
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_type' => 'individual', // Most producers will be individuals
                'metadata' => [
                    'user_id' => $user->id,
                    'created_via' => 'mixpitch_platform',
                    'user_name' => $user->name,
                ],
            ]);

            // Update user with Stripe account ID
            $user->update([
                'stripe_account_id' => $account->id,
            ]);

            Log::info('Stripe Connect account created', [
                'user_id' => $user->id,
                'stripe_account_id' => $account->id,
            ]);

            return [
                'success' => true,
                'account_id' => $account->id,
                'account' => $account,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe Connect account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create an onboarding link for a user to complete their Stripe Connect setup
     */
    public function createOnboardingLink(User $user): array
    {
        try {
            // Create account if it doesn't exist
            if (! $user->stripe_account_id) {
                $result = $this->createExpressAccount($user);
                if (! $result['success']) {
                    return $result;
                }
            }

            $accountLink = $this->stripe->accountLinks->create([
                'account' => $user->stripe_account_id,
                'refresh_url' => route('stripe.connect.refresh'),
                'return_url' => route('stripe.connect.return'),
                'type' => 'account_onboarding',
            ]);

            return [
                'success' => true,
                'url' => $accountLink->url,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Failed to create onboarding link', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if a user's Stripe Connect account is fully set up and can receive transfers
     */
    public function isAccountReadyForPayouts(User $user): bool
    {
        if (! $user->stripe_account_id) {
            return false;
        }

        try {
            $account = $this->stripe->accounts->retrieve($user->stripe_account_id);

            return $account->charges_enabled &&
                   $account->payouts_enabled &&
                   $account->details_submitted;

        } catch (ApiErrorException $e) {
            Log::error('Failed to check account status', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get detailed account status and requirements for a user
     */
    public function getDetailedAccountStatus(User $user): array
    {
        if (! $user->stripe_account_id) {
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
            $account = $this->stripe->accounts->retrieve($user->stripe_account_id);

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
            Log::error('Failed to get detailed account status', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

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

    /**
     * Determine detailed account status based on Stripe account object
     */
    private function determineDetailedAccountStatus($account): array
    {
        // Check for disabled reasons first
        if ($account->requirements->disabled_reason) {
            return $this->getDisabledStatus($account->requirements->disabled_reason);
        }

        // Check if account is under review
        if ($account->requirements->disabled_reason === 'under_review') {
            return [
                'key' => 'under_review',
                'display' => 'Under Review',
                'description' => 'Your account is being reviewed by Stripe. This process typically takes 1-2 business days.',
            ];
        }

        // Check if details haven't been submitted
        if (! $account->details_submitted) {
            return [
                'key' => 'incomplete',
                'display' => 'Setup Incomplete',
                'description' => 'You need to complete your account setup by providing required information.',
            ];
        }

        // Check for pending verification
        if (! empty($account->requirements->pending_verification)) {
            return [
                'key' => 'pending_verification',
                'display' => 'Verification Pending',
                'description' => 'Stripe is currently verifying your information. This process can take 1-7 business days.',
            ];
        }

        // Check for past due requirements
        if (! empty($account->requirements->past_due)) {
            return [
                'key' => 'past_due',
                'display' => 'Action Required',
                'description' => 'Your account has restrictions due to missing information. Please provide the required details immediately.',
            ];
        }

        // Check for currently due requirements
        if (! empty($account->requirements->currently_due)) {
            return [
                'key' => 'action_required',
                'display' => 'Information Required',
                'description' => 'Additional information is needed to complete your account verification.',
            ];
        }

        // Check capabilities
        if (! $account->charges_enabled || ! $account->payouts_enabled) {
            return [
                'key' => 'restricted',
                'display' => 'Account Restricted',
                'description' => 'Your account has restrictions. Please contact support or complete the required actions.',
            ];
        }

        // Account is fully active
        return [
            'key' => 'active',
            'display' => 'Active',
            'description' => 'Your account is fully set up and ready to receive payouts.',
        ];
    }

    /**
     * Get disabled status information
     */
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

    /**
     * Parse requirements into user-friendly format
     */
    private function parseRequirements($requirements): array
    {
        $parsed = [
            'currently_due' => [],
            'eventually_due' => [],
            'past_due' => [],
            'pending_verification' => [],
            'errors' => [],
        ];

        // Parse currently due requirements
        foreach ($requirements->currently_due ?? [] as $requirement) {
            $parsed['currently_due'][] = $this->formatRequirement($requirement);
        }

        // Parse eventually due requirements
        foreach ($requirements->eventually_due ?? [] as $requirement) {
            $parsed['eventually_due'][] = $this->formatRequirement($requirement);
        }

        // Parse past due requirements
        foreach ($requirements->past_due ?? [] as $requirement) {
            $parsed['past_due'][] = $this->formatRequirement($requirement);
        }

        // Parse pending verification
        foreach ($requirements->pending_verification ?? [] as $requirement) {
            $parsed['pending_verification'][] = $this->formatRequirement($requirement);
        }

        // Parse errors
        foreach ($requirements->errors ?? [] as $error) {
            $parsed['errors'][] = [
                'requirement' => $this->formatRequirement($error->requirement),
                'code' => $error->code,
                'reason' => $error->reason,
            ];
        }

        return $parsed;
    }

    /**
     * Format requirement into user-friendly text
     */
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

        // Handle person-specific requirements (e.g., person_abc123.verification.document)
        if (preg_match('/^person_[a-zA-Z0-9]+\.(.+)$/', $requirement, $matches)) {
            $baseRequirement = 'individual.'.$matches[1];

            return $requirementMap[$baseRequirement] ?? 'Additional person information';
        }

        return $requirementMap[$requirement] ?? ucwords(str_replace(['_', '.'], ' ', $requirement));
    }

    /**
     * Generate next steps based on account status
     */
    private function generateNextSteps($account, array $requirements): array
    {
        $steps = [];

        // If account is not submitted, primary step is to complete onboarding
        if (! $account->details_submitted) {
            $steps[] = 'Complete your account setup by clicking "Set Up Stripe Account" below.';

            return $steps;
        }

        // Handle past due requirements
        if (! empty($requirements['past_due'])) {
            $steps[] = 'Urgent: Provide the following past due information immediately to restore your account:';
            foreach ($requirements['past_due'] as $req) {
                $steps[] = "• $req";
            }

            return $steps;
        }

        // Handle currently due requirements
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

        // Handle pending verification
        if (! empty($requirements['pending_verification'])) {
            $steps[] = 'Stripe is currently verifying your information. No action is required.';
            $steps[] = 'Verification typically takes 1-7 business days.';

            return $steps;
        }

        // Handle errors
        if (! empty($requirements['errors'])) {
            $steps[] = 'Please correct the following issues:';
            foreach ($requirements['errors'] as $error) {
                $steps[] = "• {$error['requirement']}: {$error['reason']}";
            }

            return $steps;
        }

        // Account restrictions
        if (! $account->charges_enabled || ! $account->payouts_enabled) {
            $steps[] = 'Your account has restrictions. Please contact Stripe support for assistance.';

            return $steps;
        }

        // Account is active
        $steps[] = 'Your account is fully set up and ready to receive payouts!';

        return $steps;
    }

    /**
     * Get verification status
     */
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

    /**
     * Process a transfer to a connected account
     */
    public function processTransfer(User $recipient, float $amount, array $metadata = []): array
    {
        if (! $this->isAccountReadyForPayouts($recipient)) {
            return [
                'success' => false,
                'error' => 'Recipient account is not ready for payouts',
            ];
        }

        try {
            // Convert dollars to cents
            $amountCents = (int) round($amount * 100);

            $transfer = $this->stripe->transfers->create([
                'amount' => $amountCents,
                'currency' => 'usd',
                'destination' => $recipient->stripe_account_id,
                'description' => $metadata['description'] ?? 'MixPitch payout',
                'metadata' => array_merge([
                    'recipient_user_id' => $recipient->id,
                    'processed_at' => Carbon::now()->toISOString(),
                ], $metadata),
            ]);

            Log::info('Stripe transfer processed successfully', [
                'transfer_id' => $transfer->id,
                'recipient_user_id' => $recipient->id,
                'amount' => $amount,
                'metadata' => $metadata,
            ]);

            return [
                'success' => true,
                'transfer_id' => $transfer->id,
                'transfer' => $transfer,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Failed to process Stripe transfer', [
                'recipient_user_id' => $recipient->id,
                'amount' => $amount,
                'metadata' => $metadata,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reverse a transfer (for refunds)
     */
    public function reverseTransfer(string $transferId, ?float $amount = null, array $metadata = []): array
    {
        try {
            $reverseParams = [
                'metadata' => array_merge([
                    'reversed_at' => Carbon::now()->toISOString(),
                ], $metadata),
            ];

            // If partial amount specified, add it
            if ($amount !== null) {
                $reverseParams['amount'] = (int) round($amount * 100);
            }

            $reversal = $this->stripe->transfers->createReversal($transferId, $reverseParams);

            Log::info('Stripe transfer reversed successfully', [
                'transfer_id' => $transferId,
                'reversal_id' => $reversal->id,
                'amount' => $amount,
                'metadata' => $metadata,
            ]);

            return [
                'success' => true,
                'reversal_id' => $reversal->id,
                'reversal' => $reversal,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Failed to reverse Stripe transfer', [
                'transfer_id' => $transferId,
                'amount' => $amount,
                'metadata' => $metadata,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get transfer details
     */
    public function getTransfer(string $transferId): array
    {
        try {
            $transfer = $this->stripe->transfers->retrieve($transferId);

            return [
                'success' => true,
                'transfer' => $transfer,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve transfer', [
                'transfer_id' => $transferId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a login link for an existing connected account
     */
    public function createLoginLink(User $user): array
    {
        if (! $user->stripe_account_id) {
            return [
                'success' => false,
                'error' => 'No Stripe account found for user',
            ];
        }

        try {
            $loginLink = $this->stripe->accounts->createLoginLink($user->stripe_account_id);

            return [
                'success' => true,
                'url' => $loginLink->url,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Failed to create login link', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
