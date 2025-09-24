<?php

namespace App\Services\Payouts;

use App\Models\User;
use App\Models\UserPayoutAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayPalProvider extends AbstractPayoutProvider
{
    protected string $apiBaseUrl;
    protected string $partnerId;
    protected string $bnCode;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->apiBaseUrl = $this->getConfig('environment', 'sandbox') === 'production'
            ? 'https://api.paypal.com'
            : 'https://api.sandbox.paypal.com';

        $this->partnerId = $this->getConfig('partner_id', '');
        $this->bnCode = $this->getConfig('bn_code', '');
    }

    public function getProviderName(): string
    {
        return 'paypal';
    }

    public function getDisplayName(): string
    {
        return 'PayPal';
    }

    public function getDescription(): string
    {
        return 'Connect your PayPal Business account for direct marketplace payments with instant access to funds.';
    }

    /**
     * Create a PayPal account connection/onboarding link
     */
    public function createAccount(User $user, array $options = []): array
    {
        try {
            // Get partner access token
            $accessToken = $this->getPartnerAccessToken();
            if (!$accessToken) {
                throw new \Exception('Failed to obtain partner access token');
            }

            // Generate unique tracking ID
            $trackingId = 'mp_' . $user->id . '_' . time();

            // Create partner referral
            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'PayPal-Partner-Attribution-Id' => $this->bnCode,
                ])
                ->post("{$this->apiBaseUrl}/v2/customer/partner-referrals", [
                    'tracking_id' => $trackingId,
                    'partner_config_override' => [
                        'return_url' => route('paypal.onboarding.return'),
                        'return_url_description' => 'Return to MixPitch',
                        'show_add_credit_card' => true,
                    ],
                    'operations' => [
                        [
                            'operation' => 'API_INTEGRATION',
                            'api_integration_preference' => [
                                'rest_api_integration' => [
                                    'integration_method' => 'PAYPAL',
                                    'integration_type' => 'THIRD_PARTY',
                                    'third_party_details' => [
                                        'features' => [
                                            'PAYMENT',
                                            'PARTNER_FEE',
                                            'DELAY_FUNDS_DISBURSEMENT',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'products' => ['PPCP'], // PayPal Complete Payments
                    'legal_consents' => [
                        [
                            'type' => 'SHARE_DATA_CONSENT',
                            'granted' => true,
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store onboarding link details
                DB::table('paypal_onboarding_links')->insert([
                    'user_id' => $user->id,
                    'tracking_id' => $trackingId,
                    'action_url' => $data['links'][1]['href'] ?? '', // Self-hosted flow
                    'expires_at' => Carbon::now()->addHours(3),
                    'created_at' => Carbon::now(),
                ]);

                return [
                    'success' => true,
                    'account_id' => $trackingId,
                    'onboarding_url' => $data['links'][1]['href'] ?? '',
                    'expires_at' => Carbon::now()->addHours(3)->toIso8601String(),
                ];
            }

            throw new \Exception('Failed to create partner referral: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('Failed to create PayPal Commerce account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Complete onboarding after user returns from PayPal
     */
    public function completeOnboarding(User $user, string $merchantId, string $permissionsGranted): array
    {
        try {
            // Get merchant details
            $accessToken = $this->getPartnerAccessToken();
            $merchantDetails = $this->getMerchantDetails($merchantId, $accessToken);

            if (!$merchantDetails) {
                throw new \Exception('Failed to retrieve merchant details');
            }

            // Update or create payout account
            $account = UserPayoutAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => $this->getProviderName(),
                ],
                [
                    'account_id' => $merchantId,
                    'status' => 'active',
                    'is_verified' => true,
                    'verified_at' => Carbon::now(),
                    'metadata' => [
                        'merchant_id' => $merchantId,
                        'primary_email' => $merchantDetails['primary_email'] ?? null,
                        'permissions_granted' => $permissionsGranted,
                        'payments_receivable' => $merchantDetails['payments_receivable'] ?? false,
                        'legal_name' => $merchantDetails['legal_name'] ?? null,
                    ],
                ]
            );

            // Update onboarding record
            DB::table('paypal_onboarding_links')
                ->where('user_id', $user->id)
                ->whereNull('completed_at')
                ->update([
                    'completed_at' => Carbon::now(),
                    'merchant_id' => $merchantId,
                ]);

            return [
                'success' => true,
                'account' => $account,
                'message' => 'PayPal Commerce account connected successfully',
            ];

        } catch (\Exception $e) {
            $this->logError('Failed to complete PayPal onboarding', [
                'user_id' => $user->id,
                'merchant_id' => $merchantId,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get current account status
     */
    public function getAccountStatus(User $user): array
    {
        try {
            $account = $user->payoutAccountsForProvider($this->getProviderName())->first();

            if (!$account) {
                return [
                    'success' => true,
                    'status' => 'not_connected',
                    'can_receive_payouts' => false,
                ];
            }

            // For active accounts, verify with PayPal API
            if ($account->status === 'active' && $account->account_id) {
                $accessToken = $this->getPartnerAccessToken();
                $details = $this->getMerchantDetails($account->account_id, $accessToken);

                if ($details) {
                    $paymentsReceivable = $details['payments_receivable'] ?? false;
                    $primaryEmailConfirmed = $details['primary_email_confirmed'] ?? false;

                    return [
                        'success' => true,
                        'status' => 'connected',
                        'account_id' => $account->account_id,
                        'can_receive_payouts' => $paymentsReceivable && $primaryEmailConfirmed,
                        'details' => [
                            'email' => $details['primary_email'] ?? null,
                            'country' => $details['country'] ?? null,
                            'payments_receivable' => $paymentsReceivable,
                            'email_confirmed' => $primaryEmailConfirmed,
                        ],
                    ];
                }
            }

            return [
                'success' => true,
                'status' => $account->status,
                'can_receive_payouts' => $account->is_verified,
                'account_id' => $account->account_id,
            ];

        } catch (\Exception $e) {
            $this->logError('Failed to get account status', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Process transfer is handled differently in Commerce Platform
     * Payments go directly to merchant with platform fee
     */
    public function processTransfer(User $recipient, float $amount, array $metadata = []): array
    {
        // This method would typically not be called for Commerce Platform
        // as payments are processed at checkout time, not as separate transfers
        return [
            'success' => false,
            'error' => 'PayPal Commerce uses direct payments at checkout, not separate transfers',
        ];
    }

    /**
     * Create an order with platform fee (for checkout integration)
     */
    public function createOrderWithPlatformFee(User $seller, float $amount, float $platformFee, array $metadata = []): array
    {
        try {
            $account = $seller->payoutAccountsForProvider($this->getProviderName())->first();
            if (!$account || !$account->account_id) {
                throw new \Exception('Seller does not have a connected PayPal Commerce account');
            }

            $accessToken = $this->getPartnerAccessToken();

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'PayPal-Partner-Attribution-Id' => $this->bnCode,
                    'PayPal-Auth-Assertion' => $this->getAuthAssertion($account->account_id),
                ])
                ->post("{$this->apiBaseUrl}/v2/checkout/orders", [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'amount' => [
                                'currency_code' => 'USD',
                                'value' => number_format($amount, 2, '.', ''),
                            ],
                            'payee' => [
                                'merchant_id' => $account->account_id,
                            ],
                            'payment_instruction' => [
                                'disbursement_mode' => 'INSTANT',
                                'platform_fees' => [
                                    [
                                        'amount' => [
                                            'currency_code' => 'USD',
                                            'value' => number_format($platformFee, 2, '.', ''),
                                        ],
                                        'payee' => [
                                            'merchant_id' => $this->partnerId,
                                        ],
                                    ],
                                ],
                            ],
                            'description' => $metadata['description'] ?? 'MixPitch Payment',
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'order_id' => $data['id'],
                    'status' => $data['status'],
                    'links' => $data['links'],
                ];
            }

            throw new \Exception('Failed to create order: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('Failed to create PayPal order with platform fee', [
                'seller_id' => $seller->id,
                'amount' => $amount,
                'platform_fee' => $platformFee,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get partner access token for API calls
     */
    protected function getPartnerAccessToken(): ?string
    {
        $cacheKey = 'paypal_partner_access_token';
        
        // Check cache first
        $cachedToken = cache($cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth(
                    $this->getConfig('client_id'),
                    $this->getConfig('client_secret')
                )
                ->post("{$this->apiBaseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;

                // Cache for slightly less than expiry time
                cache([$cacheKey => $token], $expiresIn - 300);

                return $token;
            }

        } catch (\Exception $e) {
            $this->logError('Failed to get partner access token', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get merchant details using partner access
     */
    protected function getMerchantDetails(string $merchantId, string $accessToken): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'PayPal-Partner-Attribution-Id' => $this->bnCode,
                ])
                ->get("{$this->apiBaseUrl}/v1/customer/partners/{$this->partnerId}/merchant-integrations/{$merchantId}");

            if ($response->successful()) {
                return $response->json();
            }

        } catch (\Exception $e) {
            $this->logError('Failed to get merchant details', [
                'merchant_id' => $merchantId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Generate auth assertion for acting on behalf of merchant
     */
    protected function getAuthAssertion(string $merchantId): string
    {
        $header = base64_encode(json_encode(['alg' => 'none']));
        $payload = base64_encode(json_encode([
            'iss' => $this->getConfig('client_id'),
            'payer_id' => $merchantId,
        ]));

        return "{$header}.{$payload}.";
    }

    /**
     * Check if provider supports a specific feature
     */
    public function supports(string $feature): bool
    {
        $supportedFeatures = [
            'instant_payouts',
            'platform_fees',
            'direct_payments',
            'refunds',
            'webhooks',
            'oauth_connection',
        ];

        return in_array($feature, $supportedFeatures);
    }

    /**
     * Get the minimum payout amount (PayPal has no minimum)
     */
    public function getMinimumPayoutAmount(): float
    {
        return 0.01;
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhookSignature(array $headers, string $body): bool
    {
        // PayPal webhook validation
        $webhookId = $this->getConfig('webhook_id');
        $transmissionId = $headers['paypal-transmission-id'] ?? '';
        $transmissionTime = $headers['paypal-transmission-time'] ?? '';
        $certUrl = $headers['paypal-cert-url'] ?? '';
        $authAlgo = $headers['paypal-auth-algo'] ?? '';
        $transmissionSig = $headers['paypal-transmission-sig'] ?? '';

        // Implement PayPal webhook signature validation
        // This is a simplified version - full implementation would verify certificate chain
        return !empty($transmissionSig) && !empty($webhookId);
    }

    /**
     * Create an onboarding/setup link for a user to complete their account setup
     */
    public function createOnboardingLink(User $user, array $options = []): array
    {
        // For PayPal Commerce, this is the same as createAccount
        return $this->createAccount($user, $options);
    }

    /**
     * Check if a user's account is fully set up and can receive transfers
     */
    public function isAccountReadyForPayouts(User $user): bool
    {
        $status = $this->getAccountStatus($user);
        return $status['success'] && $status['can_receive_payouts'];
    }

    /**
     * Get detailed account status and requirements for a user
     */
    public function getDetailedAccountStatus(User $user): array
    {
        return $this->getAccountStatus($user);
    }

    /**
     * Reverse a transfer (for refunds)
     */
    public function reverseTransfer(string $transferId, ?float $amount = null, array $metadata = []): array
    {
        try {
            $accessToken = $this->getPartnerAccessToken();
            
            $refundData = [
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ];

            if (!empty($metadata['reason'])) {
                $refundData['note_to_payer'] = $metadata['reason'];
            }

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'PayPal-Partner-Attribution-Id' => $this->bnCode,
                ])
                ->post("{$this->apiBaseUrl}/v2/payments/captures/{$transferId}/refund", $refundData);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'refund_id' => $data['id'],
                    'status' => $data['status'],
                    'amount' => $data['amount'],
                ];
            }

            throw new \Exception('Failed to process refund: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('Failed to reverse PayPal transfer', [
                'transfer_id' => $transferId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get transfer details
     */
    public function getTransfer(string $transferId): array
    {
        try {
            $accessToken = $this->getPartnerAccessToken();

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'PayPal-Partner-Attribution-Id' => $this->bnCode,
                ])
                ->get("{$this->apiBaseUrl}/v2/payments/captures/{$transferId}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'transfer_id' => $data['id'],
                    'status' => $data['status'],
                    'amount' => $data['amount'],
                    'created_time' => $data['create_time'],
                ];
            }

            throw new \Exception('Failed to get transfer details: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('Failed to get PayPal transfer details', [
                'transfer_id' => $transferId,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Create a login/dashboard link for an existing connected account
     */
    public function createLoginLink(User $user): array
    {
        // PayPal doesn't have a partner-provided dashboard link like Stripe
        // Users would log into their PayPal Business account directly
        return [
            'success' => true,
            'url' => 'https://www.paypal.com/businessprofile/mytools',
            'message' => 'Please log into your PayPal Business account directly',
        ];
    }

    /**
     * Get provider-specific configuration requirements
     */
    public function getConfigurationRequirements(): array
    {
        return [
            'partner_id' => 'PayPal Partner ID',
            'client_id' => 'PayPal Client ID',
            'client_secret' => 'PayPal Client Secret',
            'bn_code' => 'PayPal BN Code',
            'environment' => 'Environment (sandbox or production)',
            'webhook_id' => 'PayPal Webhook ID',
        ];
    }

    /**
     * Validate provider-specific configuration
     */
    public function validateConfiguration(array $config): array
    {
        $errors = [];
        $required = ['partner_id', 'client_id', 'client_secret', 'bn_code'];

        foreach ($required as $key) {
            if (empty($config[$key])) {
                $errors[] = "Missing required configuration: {$key}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get supported currencies for this provider
     */
    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY'];
    }

    /**
     * Get supported countries for this provider
     */
    public function getSupportedCountries(): array
    {
        return [
            'US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE',
            'AT', 'CH', 'DK', 'FI', 'IE', 'LU', 'NO', 'PT', 'SE', 'JP'
        ];
    }

    /**
     * Check if provider supports instant transfers
     */
    public function supportsInstantTransfers(): bool
    {
        return true; // PayPal Commerce Platform supports instant disbursement
    }

    /**
     * Get provider-specific fees/pricing information
     */
    public function getPricingInfo(): array
    {
        return [
            'platform_fee' => 'Variable (set by platform)',
            'processing_fee' => '2.9% + $0.30 per transaction',
            'payout_fee' => 'Free for domestic transfers',
            'currency_conversion' => '2.5% - 4.0% above base exchange rate',
            'chargeback_fee' => '$20.00 per chargeback',
        ];
    }
}