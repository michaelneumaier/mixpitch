<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    |
    | This configuration defines the allowed file types for uploads in the
    | application. These can be configured globally and per context.
    |
    */

    'allowed_types' => [
        'audio/*',
        'video/*',
        'application/pdf',
        'image/*',
        'application/zip',
    ],

    /*
    |--------------------------------------------------------------------------
    | Context-Specific File Types
    |--------------------------------------------------------------------------
    |
    | Define specific file types allowed for different upload contexts.
    | If not defined, the global allowed_types will be used.
    |
    */

    'contexts' => [
        'global' => [
            'audio/*',
            'video/*',
            'application/pdf',
            'image/*',
            'application/zip',
        ],

        'projects' => [
            'audio/*',
            'video/*',
            'application/pdf',
            'image/*',
            'application/zip',
        ],

        'pitches' => [
            'audio/*',
            'video/*',
            'application/pdf',
            'image/*',
        ],

        'client_portals' => [
            'audio/*',
            'video/*',
            'application/pdf',
            'image/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MIME Type Definitions
    |--------------------------------------------------------------------------
    |
    | Detailed MIME type definitions for better file type detection.
    |
    */

    'mime_types' => [
        'audio' => [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/wave',
            'audio/x-wav',
            'audio/ogg',
            'audio/aac',
            'audio/m4a',
            'audio/mp4',
            'audio/flac',
            'audio/x-flac',
            'audio/webm',
        ],

        'video' => [
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'video/x-msvideo',
            'video/avi',
            'video/webm',
            'video/ogg',
            'video/3gpp',
            'video/x-flv',
            'video/x-ms-wmv',
        ],

        'image' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp',
            'image/tiff',
        ],

        'document' => [
            'application/pdf',
        ],

        'archive' => [
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Type Display Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for how different file types are displayed in the UI.
    |
    */

    'display' => [
        'icons' => [
            'audio' => [
                'class' => 'fas fa-music',
                'color' => 'text-gray-500',
                'flux_icon' => 'musical-note',
            ],
            'video' => [
                'class' => 'fas fa-video',
                'color' => 'text-purple-500',
                'flux_icon' => 'play',
            ],
            'image' => [
                'class' => 'fas fa-file-image',
                'color' => 'text-blue-500',
                'flux_icon' => 'photo',
            ],
            'pdf' => [
                'class' => 'fas fa-file-pdf',
                'color' => 'text-red-500',
                'flux_icon' => 'document-text',
            ],
            'archive' => [
                'class' => 'fas fa-file-archive',
                'color' => 'text-orange-500',
                'flux_icon' => 'archive-box',
            ],
            'default' => [
                'class' => 'fas fa-file-alt',
                'color' => 'text-gray-500',
                'flux_icon' => 'document',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for file processing jobs and capabilities.
    |
    */

    'processing' => [
        'audio' => [
            'waveform_generation' => true,
            'duration_extraction' => true,
        ],
        'video' => [
            'thumbnail_generation' => true,
            'duration_extraction' => true,
            'metadata_extraction' => true,
        ],
        'image' => [
            'thumbnail_generation' => false, // Images are displayed directly
            'metadata_extraction' => true,
        ],
    ],
];
