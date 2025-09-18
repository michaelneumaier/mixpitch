<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Zapier Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for MixPitch's Zapier integration including rate limits,
    | webhook settings, and feature flags.
    |
    */

    'enabled' => env('ZAPIER_INTEGRATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */

    'webhook_timeout' => env('ZAPIER_WEBHOOK_TIMEOUT', 30), // seconds
    'webhook_retry_attempts' => env('ZAPIER_WEBHOOK_RETRY_ATTEMPTS', 3),
    'webhook_retry_delay' => env('ZAPIER_WEBHOOK_RETRY_DELAY', 60), // seconds

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for different types of Zapier API requests.
    | Values are per user, per time window.
    |
    */

    'rate_limiting' => [
        'enabled' => env('ZAPIER_RATE_LIMITING_ENABLED', true),
    ],

    'rate_limits' => [
        'per_minute' => env('ZAPIER_RATE_LIMIT_PER_MINUTE', 60),
        'per_hour' => env('ZAPIER_RATE_LIMIT_PER_HOUR', 1000),
        'per_day' => env('ZAPIER_RATE_LIMIT_PER_DAY', 10000),
        'per_endpoint_per_minute' => env('ZAPIER_RATE_LIMIT_PER_ENDPOINT_PER_MINUTE', 20),

        // Legacy settings for backward compatibility
        'triggers' => env('ZAPIER_TRIGGER_RATE_LIMIT', 100), // per 15 minutes
        'actions' => env('ZAPIER_ACTION_RATE_LIMIT', 60),    // per minute
        'webhooks' => env('ZAPIER_WEBHOOK_RATE_LIMIT', 1000), // per hour
        'searches' => env('ZAPIER_SEARCH_RATE_LIMIT', 30),   // per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Portal Configuration
    |--------------------------------------------------------------------------
    */

    'client_portal_expiry_days' => env('CLIENT_PORTAL_EXPIRY_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | API Token Configuration
    |--------------------------------------------------------------------------
    */

    'api_token_name' => 'Zapier Integration',
    'api_token_abilities' => [
        'zapier-client-management',
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Event Types
    |--------------------------------------------------------------------------
    |
    | Event types that can trigger webhooks to Zapier
    |
    */

    'webhook_events' => [
        'client_approved' => 'Client Approved Project',
        'client_commented' => 'Client Added Comment',
        'client_requested_revisions' => 'Client Requested Revisions',
        'project_created' => 'Project Created',
        'project_completed' => 'Project Completed',
        'producer_commented' => 'Producer Added Comment',
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Logging
    |--------------------------------------------------------------------------
    */

    'log_usage' => env('ZAPIER_LOG_USAGE', true),
    'log_retention_days' => env('ZAPIER_LOG_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'features' => [
        'webhooks_enabled' => env('ZAPIER_WEBHOOKS_ENABLED', true),
        'usage_tracking_enabled' => env('ZAPIER_USAGE_TRACKING_ENABLED', true),
        'bulk_operations_enabled' => env('ZAPIER_BULK_OPERATIONS_ENABLED', false),
    ],
];
