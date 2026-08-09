<?php

/**
 * Google reCAPTCHA v3 configuration, consumed by App\Services\RecaptchaService.
 * Keys @ www.google.com/recaptcha/admin
 */
return [

    'enabled' => env('RECAPTCHA_ENABLED', true),

    'api_site_key' => env('RECAPTCHA_SITE_KEY', ''),

    'api_secret_key' => env('RECAPTCHA_SECRET_KEY', ''),

    /**
     * Verification request timeout in seconds.
     */
    'timeout' => 10,

    /**
     * Minimum v3 score (0.0 - 1.0) required to pass. Null accepts any score
     * Google marks successful.
     */
    'min_score' => env('RECAPTCHA_MIN_SCORE'),
];
