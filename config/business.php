<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payout Hold Period Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payout hold periods across different workflow types.
    | These settings control when payouts are released to producers.
    |
    */
    'payout_hold_settings' => [
        // Master enable/disable switch for hold periods
        'enabled' => env('PAYOUT_HOLD_ENABLED', true),
        
        // Default hold period in days (used as fallback)
        'default_days' => env('PAYOUT_HOLD_DEFAULT_DAYS', 1),
        
        // Workflow-specific hold periods (in days)
        'workflow_specific' => [
            'standard' => env('PAYOUT_HOLD_STANDARD_DAYS', 1),
            'contest' => env('PAYOUT_HOLD_CONTEST_DAYS', 0),
            'client_management' => env('PAYOUT_HOLD_CLIENT_MGMT_DAYS', 0),
        ],
        
        // Whether to count only business days (Mon-Fri)
        'business_days_only' => env('PAYOUT_HOLD_BUSINESS_DAYS_ONLY', true),
        
        // Time of day when payouts are processed (24-hour format)
        'processing_time' => env('PAYOUT_PROCESSING_TIME', '09:00'),
        
        // Minimum hold period in hours (applied even when disabled for safety)
        'minimum_hold_hours' => env('PAYOUT_MINIMUM_HOLD_HOURS', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Override Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for administrative overrides of hold periods.
    |
    */
    'admin_overrides' => [
        // Allow administrators to bypass hold periods
        'allow_bypass' => env('ALLOW_PAYOUT_HOLD_BYPASS', true),
        
        // Require reason when bypassing hold periods
        'require_reason' => env('REQUIRE_BYPASS_REASON', true),
        
        // Log all bypass actions for audit trail
        'log_bypasses' => env('LOG_PAYOUT_BYPASSES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Portal Settings
    |--------------------------------------------------------------------------
    |
    | Configuration values related to the client management portal feature.
    |
    */
    'client_portal_link_expiry_days' => env('CLIENT_PORTAL_LINK_EXPIRY_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Other Business Settings
    |--------------------------------------------------------------------------
    |
    | Additional application-specific configuration values.
    |
    */
    // Future business settings can be added here
]; 