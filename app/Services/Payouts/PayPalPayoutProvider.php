<?php

namespace App\Services\Payouts;

use App\Models\User;
use PaypalPayoutsSDK\Core\PayPalHttpClient;
use PaypalPayoutsSDK\Core\ProductionEnvironment;
use PaypalPayoutsSDK\Core\SandboxEnvironment;
use PaypalPayoutsSDK\Payouts\PayoutsGetRequest;
use PaypalPayoutsSDK\Payouts\PayoutsPostRequest;

class PayPalPayoutProvider extends AbstractPayoutProvider
{
    private PayPalHttpClient $client;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $clientId = $this->getConfig('client_id');
        $clientSecret = $this->getConfig('client_secret');

        // In testing or development environments, allow mock credentials
        if (app()->environment(['testing', 'local']) && (!$clientId || !$clientSecret)) {
            $clientId = $clientId ?: 'fake_paypal_client_id_for_testing';
            $clientSecret = $clientSecret ?: 'fake_paypal_client_secret_for_testing';
        }

        if (!$clientId || !$clientSecret) {
            throw new \InvalidArgumentException("Missing required configuration: client_id or client_secret");
        }

        // Initialize PayPal client
        $environment = $this->getConfig('environment', 'sandbox') === 'production'
            ? new ProductionEnvironment($clientId, $clientSecret)
            : new SandboxEnvironment($clientId, $clientSecret);

        $this->client = new PayPalHttpClient($environment);
    }

    public function getProviderName(): string
    {
        return 'paypal';
    }

    public function getDisplayName(): string
    {
        return 'PayPal';
    }

    public function createAccount(User $user, array $options = []): array
    {
        // PayPal doesn't require explicit account creation like Stripe
        // We just store the email and validate it when needed
        $paypalEmail = $options['paypal_email'] ?? $user->email;

        // Set the user's PayPal email as their account ID
        $this->setUserAccountId($user, $paypalEmail);

        $this->logActivity('info', 'PayPal account linked', [
            'user_id' => $user->id,
            'paypal_email' => $paypalEmail,
        ]);

        return $this->buildSuccessResponse([
            'account_id' => $paypalEmail,
            'account_email' => $paypalEmail,
        ]);
    }

    public function createOnboardingLink(User $user, array $options = []): array
    {
        // PayPal doesn't require complex onboarding like Stripe
        // Users just need to provide their PayPal email address
        return $this->buildSuccessResponse([
            'url' => null, // No external onboarding required
            'message' => 'Simply provide your PayPal email address to receive payouts',
        ]);
    }

    public function isAccountReadyForPayouts(User $user): bool
    {
        $accountId = $this->getUserAccountId($user);

        // For PayPal, we consider an account ready if we have a valid email
        return ! empty($accountId) && filter_var($accountId, FILTER_VALIDATE_EMAIL);
    }

    public function getDetailedAccountStatus(User $user): array
    {
        $accountId = $this->getUserAccountId($user);

        if (! $accountId) {
            return [
                'status' => 'not_created',
                'status_display' => 'Email Not Provided',
                'status_description' => 'You need to provide your PayPal email address to receive payouts.',
                'charges_enabled' => false,
                'payouts_enabled' => false,
                'details_submitted' => false,
                'requirements' => ['PayPal email address'],
                'next_steps' => ['Enter your PayPal email address'],
                'can_receive_payouts' => false,
                'verification_status' => 'not_started',
            ];
        }

        if (! filter_var($accountId, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'incomplete',
                'status_display' => 'Invalid Email',
                'status_description' => 'The provided email address is not valid.',
                'charges_enabled' => false,
                'payouts_enabled' => false,
                'details_submitted' => false,
                'requirements' => ['Valid PayPal email address'],
                'next_steps' => ['Provide a valid PayPal email address'],
                'can_receive_payouts' => false,
                'verification_status' => 'action_required',
            ];
        }

        // For PayPal, we assume the account is active if we have a valid email
        // In a production environment, you might want to validate the email
        // by sending a test micro-transaction
        return [
            'status' => 'active',
            'status_display' => 'Active',
            'status_description' => 'Your PayPal account is ready to receive payouts.',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
            'requirements' => [],
            'next_steps' => ['Your PayPal account is ready to receive payouts!'],
            'can_receive_payouts' => true,
            'verification_status' => 'verified',
            'account_email' => $accountId,
        ];
    }

    public function processTransfer(User $recipient, float $amount, array $metadata = []): array
    {
        if (! $this->isAccountReadyForPayouts($recipient)) {
            return $this->buildErrorResponse('Recipient PayPal account is not ready for payouts');
        }

        try {
            $recipientEmail = $this->getUserAccountId($recipient);
            $senderBatchId = 'mixpitch_'.time().'_'.$recipient->id;

            $request = new PayoutsPostRequest;
            $request->body = [
                'sender_batch_header' => [
                    'sender_batch_id' => $senderBatchId,
                    'email_subject' => $metadata['email_subject'] ?? 'MixPitch Payout',
                    'email_message' => $metadata['email_message'] ?? 'You have received a payout from MixPitch.',
                ],
                'items' => [[
                    'recipient_type' => 'EMAIL',
                    'amount' => [
                        'value' => number_format($amount, 2, '.', ''),
                        'currency' => $metadata['currency'] ?? 'USD',
                    ],
                    'receiver' => $recipientEmail,
                    'note' => $metadata['description'] ?? 'MixPitch payout',
                    'sender_item_id' => $metadata['payout_schedule_id'] ?? uniqid('payout_'),
                ]],
            ];

            $response = $this->client->execute($request);

            if ($response->statusCode >= 200 && $response->statusCode < 300) {
                $batchId = $response->result->batch_header->payout_batch_id;

                $this->logActivity('info', 'PayPal payout processed successfully', [
                    'batch_id' => $batchId,
                    'recipient_user_id' => $recipient->id,
                    'recipient_email' => $recipientEmail,
                    'amount' => $amount,
                    'metadata' => $metadata,
                ]);

                return $this->buildSuccessResponse([
                    'transfer_id' => $batchId,
                    'batch_id' => $batchId,
                    'sender_batch_id' => $senderBatchId,
                    'batch_status' => $response->result->batch_header->batch_status,
                    'response' => $response->result,
                ]);
            } else {
                throw new \Exception('PayPal API returned status: '.$response->statusCode);
            }

        } catch (\Exception $e) {
            return $this->buildErrorResponse($e->getMessage(), [
                'recipient_user_id' => $recipient->id,
                'amount' => $amount,
                'metadata' => $metadata,
            ]);
        }
    }

    public function reverseTransfer(string $transferId, ?float $amount = null, array $metadata = []): array
    {
        // PayPal doesn't support reversing payouts like Stripe does
        // Once a payout is sent, it cannot be reversed programmatically
        return $this->buildErrorResponse('PayPal does not support payout reversals. Contact PayPal support for assistance.');
    }

    public function getTransfer(string $transferId): array
    {
        try {
            $request = new PayoutsGetRequest($transferId);
            $response = $this->client->execute($request);

            if ($response->statusCode >= 200 && $response->statusCode < 300) {
                return $this->buildSuccessResponse([
                    'transfer' => $response->result,
                    'batch_header' => $response->result->batch_header,
                ]);
            } else {
                throw new \Exception('PayPal API returned status: '.$response->statusCode);
            }

        } catch (\Exception $e) {
            return $this->buildErrorResponse($e->getMessage(), [
                'transfer_id' => $transferId,
            ]);
        }
    }

    public function createLoginLink(User $user): array
    {
        // PayPal doesn't have a dashboard login link like Stripe
        // Direct users to PayPal's main site
        return $this->buildSuccessResponse([
            'url' => 'https://www.paypal.com/signin',
            'message' => 'Login to your PayPal account to manage your settings',
        ]);
    }

    public function getAccountIdField(): string
    {
        return 'paypal_account_id';
    }

    public function getConfigurationRequirements(): array
    {
        return [
            'client_id' => [
                'type' => 'string',
                'required' => true,
                'description' => 'PayPal Client ID',
                'sensitive' => false,
            ],
            'client_secret' => [
                'type' => 'string',
                'required' => true,
                'description' => 'PayPal Client Secret',
                'sensitive' => true,
            ],
            'environment' => [
                'type' => 'select',
                'required' => false,
                'description' => 'PayPal Environment',
                'options' => ['sandbox', 'production'],
                'default' => 'sandbox',
                'sensitive' => false,
            ],
        ];
    }

    public function validateConfiguration(array $config): array
    {
        $errors = [];

        if (empty($config['client_id'])) {
            $errors[] = 'Client ID is required';
        }

        if (empty($config['client_secret'])) {
            $errors[] = 'Client Secret is required';
        }

        if (! empty($config['environment']) && ! in_array($config['environment'], ['sandbox', 'production'])) {
            $errors[] = 'Environment must be either "sandbox" or "production"';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function getSupportedCurrencies(): array
    {
        return [
            'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'SEK', 'NOK', 'DKK',
            'PLN', 'CZK', 'HUF', 'BGN', 'HRK', 'RON', 'ISK', 'HKD', 'SGD', 'TWD',
            'KRW', 'THB', 'MYR', 'INR', 'IDR', 'PHP', 'BRL', 'MXN', 'ILS', 'RUB',
        ];
    }

    public function getSupportedCountries(): array
    {
        return [
            'US', 'CA', 'GB', 'AU', 'AT', 'BE', 'DK', 'FI', 'FR', 'DE', 'HK', 'IE',
            'IT', 'JP', 'LU', 'NL', 'NO', 'PT', 'SG', 'ES', 'SE', 'CH', 'BR', 'MX',
            'IN', 'ID', 'PH', 'TH', 'MY', 'KR', 'TW', 'PL', 'CZ', 'HU', 'BG', 'HR',
            'RO', 'IS', 'IL', 'RU',
        ];
    }

    public function supportsInstantTransfers(): bool
    {
        return false; // PayPal payouts typically take 24-48 hours
    }

    public function getPricingInfo(): array
    {
        return [
            'setup_fee' => 0,
            'transaction_fee_percentage' => 0,
            'transaction_fee_fixed' => 0,
            'currency' => 'USD',
            'description' => 'Free for domestic payouts, varies for international',
            'international_fee' => 'Varies by country',
            'processing_time' => '24-48 hours',
        ];
    }

    /**
     * Validate a PayPal email address by sending a test payout
     * This is optional and can be used to verify email addresses
     */
    public function validateEmail(string $email): array
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => 'Invalid email format',
            ];
        }

        // In a production environment, you might want to send a test micro-transaction
        // For now, we'll just validate the email format
        return [
            'valid' => true,
            'email' => $email,
        ];
    }

    /**
     * Get batch status for a payout
     */
    public function getBatchStatus(string $batchId): array
    {
        try {
            $request = new PayoutsGetRequest($batchId);
            $response = $this->client->execute($request);

            if ($response->statusCode >= 200 && $response->statusCode < 300) {
                $batchHeader = $response->result->batch_header;

                return $this->buildSuccessResponse([
                    'batch_id' => $batchId,
                    'status' => $batchHeader->batch_status,
                    'total_amount' => $batchHeader->amount->value ?? 0,
                    'currency' => $batchHeader->amount->currency ?? 'USD',
                    'time_created' => $batchHeader->time_created ?? null,
                    'time_completed' => $batchHeader->time_completed ?? null,
                    'errors' => $batchHeader->errors ?? [],
                ]);
            } else {
                throw new \Exception('PayPal API returned status: '.$response->statusCode);
            }

        } catch (\Exception $e) {
            return $this->buildErrorResponse($e->getMessage(), [
                'batch_id' => $batchId,
            ]);
        }
    }
}
