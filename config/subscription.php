<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Price IDs
    |--------------------------------------------------------------------------
    |
    | These are the Stripe Price IDs for each subscription plan.
    | You'll need to create these in your Stripe dashboard and update
    | these values with the actual price IDs.
    |
    */
    'stripe_prices' => [
        'pro_artist_monthly' => env('STRIPE_PRICE_PRO_ARTIST_MONTHLY', 'price_1234567890'),
        'pro_artist_yearly' => env('STRIPE_PRICE_PRO_ARTIST_YEARLY', 'price_1234567891'),
        'pro_engineer_monthly' => env('STRIPE_PRICE_PRO_ENGINEER_MONTHLY', 'price_0987654321'),
        'pro_engineer_yearly' => env('STRIPE_PRICE_PRO_ENGINEER_YEARLY', 'price_0987654322'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan Features & Pricing
    |--------------------------------------------------------------------------
    |
    | Define the features and pricing available for each plan.
    | This is used for display purposes in the UI.
    |
    */
    'plans' => [
        'free' => [
            'name' => 'Free',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'description' => 'Perfect for getting started',
            'features' => [
                '1 Project',
                '3 Active Pitches',
                '1GB Storage per Project',
                'Basic Support',
                '3 License Templates',
                '10% Commission Rate',
            ],
        ],
        'pro_artist' => [
            'name' => 'Pro Artist',
            'monthly_price' => 6.99,
            'yearly_price' => 69.99, // ~17% discount (10 months for 12)
            'yearly_savings' => 13.89,
            'description' => 'For professional music creators',
            'badge' => '🔷',
            'features' => [
                'Unlimited Projects',
                'Unlimited Active Pitches',
                '5GB Storage per Project',
                'Priority Support',
                'Custom License Templates',
                '8% Commission Rate',
                '4 Visibility Boosts/month',
                '2 Private Projects/month',
                'Track-level Analytics',
                '24h Early Challenge Access',
            ],
        ],
        'pro_engineer' => [
            'name' => 'Pro Engineer',
            'monthly_price' => 9.99,
            'yearly_price' => 99.99, // ~17% discount (10 months for 12)
            'yearly_savings' => 19.89,
            'description' => 'Advanced tools for audio engineers',
            'badge' => '🔶',
            'features' => [
                'Unlimited Projects',
                'Unlimited Active Pitches',
                '10GB Storage per Project',
                'Priority Support (24h SLA)',
                'Unlimited License Templates',
                '6% Commission Rate',
                '1.25× Reputation Multiplier',
                '1 Visibility Boost/month',
                'Unlimited Private Projects',
                'Client Portal Access',
                'Client & Earnings Analytics',
                '24h Early Challenge Access + Judge',
                'Email & Chat Support',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing Periods
    |--------------------------------------------------------------------------
    |
    | Available billing periods and their display information.
    |
    */
    'billing_periods' => [
        'monthly' => [
            'name' => 'Monthly',
            'description' => 'Billed monthly',
            'interval' => 'month',
            'interval_count' => 1,
        ],
        'yearly' => [
            'name' => 'Yearly',
            'description' => 'Billed annually',
            'interval' => 'year', 
            'interval_count' => 1,
            'discount_text' => 'Save 17%',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for various subscription features.
    |
    */
    'features' => [
        'visibility_boosts' => [
            'duration_hours' => 72,
            'ranking_multiplier' => 2.0,
        ],
        'analytics' => [
            'retention_days' => 365,
        ],
        'billing' => [
            'grace_period_days' => 3,
            'retry_period_days' => 14,
        ],
    ],
]; 