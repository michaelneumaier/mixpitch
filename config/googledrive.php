<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Drive Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Drive integration including OAuth settings,
    | file handling preferences, and security options.
    |
    */

    'oauth' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => '/integrations/google-drive/callback',
        'scopes' => [
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive.readonly',
        ],
    ],

    'file_handling' => [
        'max_file_size_mb' => env('GOOGLE_DRIVE_MAX_FILE_SIZE_MB', 500),
        'allowed_mime_types' => [
            'audio/mpeg',
            'audio/wav',
            'audio/mp3',
            'audio/x-wav',
            'audio/aac',
            'audio/flac',
            'audio/ogg',
        ],
        'download_timeout_seconds' => env('GOOGLE_DRIVE_DOWNLOAD_TIMEOUT', 300),
        'chunk_size_mb' => env('GOOGLE_DRIVE_CHUNK_SIZE_MB', 10),
    ],

    'security' => [
        'token_encryption_key' => env('GOOGLE_DRIVE_TOKEN_ENCRYPTION_KEY'),
        'token_expiry_buffer_minutes' => env('GOOGLE_DRIVE_TOKEN_EXPIRY_BUFFER', 5),
        'max_requests_per_minute' => env('GOOGLE_DRIVE_RATE_LIMIT', 100),
    ],

    'ui' => [
        'files_per_page' => env('GOOGLE_DRIVE_FILES_PER_PAGE', 50),
        'enable_folder_browsing' => env('GOOGLE_DRIVE_ENABLE_FOLDERS', true),
        'show_file_previews' => env('GOOGLE_DRIVE_SHOW_PREVIEWS', true),
    ],
];
