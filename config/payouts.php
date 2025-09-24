<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payout Provider
    |--------------------------------------------------------------------------
    |
    | This is the default payout provider that will be used when no specific
    | provider is requested or when the user hasn't set a preference.
    |
    */

    'default' => env('PAYOUT_DEFAULT_PROVIDER', 'stripe'),

    /*
    |--------------------------------------------------------------------------
    | Payout Providers
    |--------------------------------------------------------------------------
    |
    | Configuration for each payout provider. Each provider can be enabled
    | or disabled, and has its own specific configuration options.
    |
    */

    'providers' => [

        'stripe' => [
            'enabled' => env('PAYOUT_STRIPE_ENABLED', true),
            'secret_key' => env('STRIPE_SECRET', env('CASHIER_SECRET')),
            'publishable_key' => env('STRIPE_KEY', env('CASHIER_KEY')),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

        'paypal' => [
            'enabled' => env('PAYPAL_ENABLED', false), // Disabled until partnership approved
            'partner_id' => env('PAYPAL_PARTNER_ID'),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'bn_code' => env('PAYPAL_BN_CODE'),
            'environment' => env('PAYPAL_ENVIRONMENT', 'sandbox'), // 'sandbox' or 'production'
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        ],

        'wise' => [
            'enabled' => env('PAYOUT_WISE_ENABLED', false),
            'api_token' => env('WISE_API_TOKEN'),
            'profile_id' => env('WISE_PROFILE_ID'),
            'environment' => env('WISE_ENVIRONMENT', 'sandbox'), // 'sandbox' or 'live'
        ],

        'dwolla' => [
            'enabled' => env('PAYOUT_DWOLLA_ENABLED', false),
            'key' => env('DWOLLA_KEY'),
            'secret' => env('DWOLLA_SECRET'),
            'environment' => env('DWOLLA_ENVIRONMENT', 'sandbox'), // 'sandbox' or 'production'
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Selection Rules
    |--------------------------------------------------------------------------
    |
    | Rules for automatically selecting providers based on various criteria
    | such as user location, currency, or amount thresholds.
    |
    */

    'selection_rules' => [

        // Minimum amounts for each provider (USD)
        'minimum_amounts' => [
            'stripe' => 0.50,
            'paypal' => 1.00,
            'wise' => 1.00,
            'dwolla' => 1.00,
        ],

        // Maximum amounts for each provider (USD)
        'maximum_amounts' => [
            'stripe' => 999999.99,
            'paypal' => 60000.00,
            'wise' => 1000000.00,
            'dwolla' => 500000.00,
        ],

        // Preferred providers by country
        'country_preferences' => [
            'US' => ['stripe', 'paypal', 'dwolla'],
            'CA' => ['stripe', 'paypal', 'wise'],
            'GB' => ['stripe', 'wise', 'paypal'],
            'EU' => ['wise', 'stripe', 'paypal'],
            'default' => ['stripe', 'paypal'],
        ],

        // Preferred providers by currency
        'currency_preferences' => [
            'USD' => ['stripe', 'paypal', 'dwolla'],
            'EUR' => ['wise', 'stripe', 'paypal'],
            'GBP' => ['wise', 'stripe', 'paypal'],
            'default' => ['stripe', 'paypal'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Fee Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for provider fees and how they should be handled.
    | Note: These are provider processing fees, not platform commissions.
    |
    */

    'fees' => [

        // Whether to absorb provider fees or pass them to users
        'absorb_fees' => env('PAYOUT_ABSORB_FEES', true),

        // Fee structures for each provider (for estimation purposes)
        'structures' => [
            'stripe' => [
                'percentage' => 2.9,
                'fixed' => 0.30,
                'currency' => 'USD',
            ],
            'paypal' => [
                'percentage' => 0,
                'fixed' => 0,
                'currency' => 'USD',
                'note' => 'Free for domestic transfers, varies internationally',
            ],
            'wise' => [
                'percentage' => 0.45,
                'fixed' => 0,
                'currency' => 'USD',
                'note' => 'Varies by currency and destination',
            ],
            'dwolla' => [
                'percentage' => 0,
                'fixed' => 0.25,
                'currency' => 'USD',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for retrying failed payouts across providers.
    |
    */

    'retry' => [

        // Enable automatic retry with different providers
        'enabled' => env('PAYOUT_RETRY_ENABLED', true),

        // Maximum number of retry attempts
        'max_attempts' => env('PAYOUT_RETRY_MAX_ATTEMPTS', 3),

        // Delay between retry attempts (in minutes)
        'delay_minutes' => env('PAYOUT_RETRY_DELAY_MINUTES', 5),

        // Whether to try different providers on retry
        'try_different_providers' => env('PAYOUT_RETRY_DIFFERENT_PROVIDERS', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for notifications related to payouts.
    |
    */

    'notifications' => [

        // Notify users when provider setup is needed
        'setup_reminders' => env('PAYOUT_SETUP_REMINDERS', true),

        // Notify users of payout successes
        'success_notifications' => env('PAYOUT_SUCCESS_NOTIFICATIONS', true),

        // Notify users of payout failures
        'failure_notifications' => env('PAYOUT_FAILURE_NOTIFICATIONS', true),

        // Notify admins of system issues
        'admin_alerts' => env('PAYOUT_ADMIN_ALERTS', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching provider status and capabilities.
    |
    */

    'cache' => [

        // Enable caching of provider capabilities
        'enabled' => env('PAYOUT_CACHE_ENABLED', true),

        // Cache TTL for provider status (in minutes)
        'ttl_minutes' => env('PAYOUT_CACHE_TTL_MINUTES', 60),

        // Cache key prefix
        'key_prefix' => 'payouts',

    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for testing payout functionality.
    |
    */

    'testing' => [

        // Use fake providers during testing
        'use_fake_providers' => env('PAYOUT_USE_FAKE_PROVIDERS', false),

        // Default test amounts
        'test_amounts' => [
            'small' => 1.00,
            'medium' => 50.00,
            'large' => 500.00,
        ],

    ],

];
