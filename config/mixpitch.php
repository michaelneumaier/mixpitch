<?php

// config/mixpitch.php - Legacy configuration, migrated to config/business.php
// This file is kept for backward compatibility
return [
    /*
    |--------------------------------------------------------------------------
    | Client Portal Settings (DEPRECATED)
    |--------------------------------------------------------------------------
    |
    | These settings have been moved to config/business.php
    | This file is maintained for backward compatibility only.
    |
    */
    'client_portal_link_expiry_days' => config('business.client_portal_link_expiry_days', 7),

    /*
    |--------------------------------------------------------------------------
    | Other MixPitch Settings
    |--------------------------------------------------------------------------
    |
    | Add other application-specific configuration values here as needed.
    |
    */
    // 'example_setting' => env('EXAMPLE_SETTING', 'default_value'),
];
