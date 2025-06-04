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
        'pro_artist' => env('STRIPE_PRICE_PRO_ARTIST', 'price_1234567890'),
        'pro_engineer' => env('STRIPE_PRICE_PRO_ENGINEER', 'price_0987654321'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan Features
    |--------------------------------------------------------------------------
    |
    | Define the features available for each plan.
    | This is used for display purposes in the UI.
    |
    */
    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'features' => [
                '1 Project',
                '3 Active Pitches',
                '100MB Storage per Project',
                'Basic Support',
            ],
        ],
        'pro_artist' => [
            'name' => 'Pro Artist',
            'price' => 29,
            'features' => [
                'Unlimited Projects',
                'Unlimited Active Pitches',
                '500MB Storage per Project',
                'Priority Support',
                'Custom Portfolio',
                'Project Prioritization',
            ],
        ],
        'pro_engineer' => [
            'name' => 'Pro Engineer',
            'price' => 19,
            'features' => [
                'Unlimited Projects',
                'Unlimited Active Pitches',
                '5 Monthly Pitches',
                '500MB Storage per Project',
                'Priority Support',
                'Custom Portfolio',
            ],
        ],
    ],
]; 