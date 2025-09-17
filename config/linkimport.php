<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Domains
    |--------------------------------------------------------------------------
    |
    | Domains that are allowed for link importing. Only links from these
    | domains will be processed for security reasons.
    |
    */
    'allowed_domains' => [
        'wetransfer.com',
        'we.tl',
        'drive.google.com',
        'dropbox.com',
        'db.tt',
        '1drv.ms',
        'onedrive.live.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration to prevent abuse of the link import feature.
    |
    */
    'rate_limits' => [
        'per_project_per_hour' => env('LINK_IMPORT_PER_PROJECT_PER_HOUR', 5),
        'per_user_per_hour' => env('LINK_IMPORT_PER_USER_PER_HOUR', 10),
        'per_user_per_day' => env('LINK_IMPORT_PER_USER_PER_DAY', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration for link import processing.
    |
    */
    'security' => [
        'max_file_size' => env('LINK_IMPORT_MAX_FILE_SIZE', 500 * 1024 * 1024), // 500MB
        'scan_with_clamav' => env('LINK_IMPORT_SCAN_ENABLED', true),
        'max_redirects' => env('LINK_IMPORT_MAX_REDIRECTS', 3),
        'timeout_seconds' => env('LINK_IMPORT_TIMEOUT', 60),
        'allowed_mime_types' => [
            'audio/mpeg',
            'audio/wav',
            'audio/flac',
            'audio/aiff',
            'audio/x-aiff',
            'audio/mp4',
            'audio/m4a',
            'application/pdf',
            'application/zip',
            'application/x-zip-compressed',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Remote Fetch Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the edge worker that handles remote file fetching
    | and streaming to S3 to avoid routing large files through app servers.
    |
    */
    'remote_fetch_worker' => [
        'endpoint' => env('LINK_IMPORT_WORKER_URL'),
        'chunk_size' => env('LINK_IMPORT_CHUNK_SIZE', 5 * 1024 * 1024), // 5MB parts by default
        'presign_ttl' => env('LINK_IMPORT_PRESIGN_TTL', 900), // 15 minutes in seconds
        'enabled' => env('LINK_IMPORT_WORKER_ENABLED', false), // Disabled until worker is deployed
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Settings related to link processing and file downloading.
    |
    */
    'processing' => [
        'user_agent' => 'MixPitch-LinkImporter/1.0',
        'max_files_per_link' => env('LINK_IMPORT_MAX_FILES_PER_LINK', 20),
        'max_total_size_per_import' => env('LINK_IMPORT_MAX_TOTAL_SIZE', 1024 * 1024 * 1024), // 1GB
        'retry_attempts' => env('LINK_IMPORT_RETRY_ATTEMPTS', 3),
        'retry_delay_seconds' => env('LINK_IMPORT_RETRY_DELAY', 5),
        'streaming_threshold_mb' => env('LINK_IMPORT_STREAMING_THRESHOLD_MB', 50), // Files larger than 50MB use streaming
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for notifications related to link imports.
    |
    */
    'notifications' => [
        'notify_on_completion' => env('LINK_IMPORT_NOTIFY_COMPLETION', true),
        'notify_on_failure' => env('LINK_IMPORT_NOTIFY_FAILURE', true),
        'include_progress_updates' => env('LINK_IMPORT_PROGRESS_UPDATES', true),
    ],
];
