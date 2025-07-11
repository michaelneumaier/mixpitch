<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Audio Processing Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for audio processing, including
    | watermarking, transcoding, and supported formats.
    |
    */

    'supported_formats' => [
        'mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac'
    ],

    'target_format' => 'mp3',
    'target_bitrate' => '192k',

    /*
    |--------------------------------------------------------------------------
    | Watermarking Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for audio watermarking based on project workflow type.
    | Watermarking is applied to protect intellectual property during review
    | phases before final approval and payment.
    |
    */

    'watermarking' => [
        'enabled' => true,
        
        // Workflow types that require watermarking
        'workflows' => [
            'standard' => true,
            'contest' => false,
            'direct_hire' => false,
            'client_management' => false,
        ],
        
        // Default watermark settings
        'default_settings' => [
            'type' => 'periodic_tone',
            'frequency' => 1000,      // 1kHz tone
            'volume' => 0.5,          // 50% volume - very audible
            'duration' => 0.8,        // 800ms - duration of each watermark burst
            'interval' => 20,         // Every 20 seconds
        ],
        
        // Advanced watermark settings for different noise types
        'noise_types' => [
            'sine' => [
                'description' => 'Pure sine wave tone',
                'command_pattern' => 'sine=frequency={frequency}:duration={duration}',
            ],
            'white_noise' => [
                'description' => 'White noise across all frequencies',
                'command_pattern' => 'anoisesrc=color=white:sample_rate=44100:amplitude=0.5',
            ],
            'pink_noise' => [
                'description' => 'Pink noise with 1/f frequency distribution',
                'command_pattern' => 'anoisesrc=color=pink:sample_rate=44100:amplitude=0.5',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS Lambda Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AWS Lambda audio processing functions.
    | These functions handle transcoding and watermarking in the cloud.
    |
    */

    'aws_lambda' => [
        'enabled' => env('AWS_LAMBDA_AUDIO_ENABLED', true),
        'url' => env('AWS_LAMBDA_AUDIO_PROCESSOR_URL'),
        'timeout' => 300, // 5 minutes
        'retry_attempts' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Local FFmpeg Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for local FFmpeg processing as a fallback when AWS Lambda
    | is not available or configured.
    |
    */

    'ffmpeg' => [
        'enabled' => true,
        'path' => env('FFMPEG_PATH', 'ffmpeg'),
        'timeout' => 300, // 5 minutes
        'memory_limit' => '512M',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for processed file storage and management.
    |
    */

    'storage' => [
        'processed_files_path' => 'pitches/{pitch_id}/processed/',
        'temp_files_path' => 'temp/audio/',
        'cleanup_after_days' => 30,
        'cleanup_temp_files' => env('AUDIO_CLEANUP_TEMP_FILES', true),
        'cleanup_delay_minutes' => env('AUDIO_CLEANUP_DELAY_MINUTES', 60), // Wait 1 hour before cleanup
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for audio file security and access control.
    |
    */

    'security' => [
        'signed_url_expiration' => 3600,    // 1 hour
        'download_url_expiration' => 900,   // 15 minutes
        'streaming_url_expiration' => 7200, // 2 hours
        'max_file_size' => 100 * 1024 * 1024, // 100MB
    ],

]; 