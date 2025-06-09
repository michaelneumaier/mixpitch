<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'options' => [
            'ConfigurationSetName' => env('SES_CONFIGURATION_SET', 'MixPitch-Email-Tracking'),
        ],
    ],

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-2'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'lambda_audio_processor_url' => rtrim(env('AWS_LAMBDA_AUDIO_PROCESSOR_URL'), '/'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'cloudflare' => [
        'waveform_worker_url' => rtrim(env('CLOUDFLARE_WAVEFORM_WORKER_URL'), '/'),
        'worker_token' => env('CLOUDFLARE_WORKER_TOKEN'),
        'r2_bucket' => env('CLOUDFLARE_R2_BUCKET'),
        'r2_endpoint' => env('CLOUDFLARE_R2_ENDPOINT'),
        'r2_access_key' => env('CLOUDFLARE_R2_ACCESS_KEY'),
        'r2_secret_key' => env('CLOUDFLARE_R2_SECRET_KEY'),
    ],

];
