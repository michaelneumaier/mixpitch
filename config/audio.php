<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audio Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for audio processing features including transcoding,
    | watermarking, and waveform generation.
    |
    */

    'processing' => [
        /*
        |--------------------------------------------------------------------------
        | Processing Method
        |--------------------------------------------------------------------------
        |
        | Choose the primary method for audio processing:
        | - 'lambda' for AWS Lambda processing
        | - 'local' for local FFmpeg processing
        | - 'auto' to automatically choose based on availability
        |
        */
        'method' => env('AUDIO_PROCESSING_METHOD', 'auto'),

        /*
        |--------------------------------------------------------------------------
        | Use AWS Lambda
        |--------------------------------------------------------------------------
        |
        | Whether to use AWS Lambda for audio processing when available.
        | If false, will fall back to local processing.
        |
        */
        'use_lambda' => env('AUDIO_USE_LAMBDA', true),

        /*
        |--------------------------------------------------------------------------
        | Processing Timeout
        |--------------------------------------------------------------------------
        |
        | Maximum time (in seconds) to allow for audio processing.
        | Lambda: 300 seconds (5 minutes)
        | Local: 600 seconds (10 minutes)
        |
        */
        'timeout' => [
            'lambda' => env('AUDIO_LAMBDA_TIMEOUT', 300),
            'local' => env('AUDIO_LOCAL_TIMEOUT', 600),
        ],

        /*
        |--------------------------------------------------------------------------
        | Retry Configuration
        |--------------------------------------------------------------------------
        |
        | Number of retry attempts for failed audio processing jobs.
        |
        */
        'retries' => env('AUDIO_PROCESSING_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transcoding Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for audio transcoding functionality.
    |
    */
    'transcoding' => [
        /*
        |--------------------------------------------------------------------------
        | Enabled
        |--------------------------------------------------------------------------
        |
        | Whether to enable audio transcoding for submissions.
        |
        */
        'enabled' => env('AUDIO_TRANSCODING_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Supported Input Formats
        |--------------------------------------------------------------------------
        |
        | List of audio formats that can be transcoded.
        |
        */
        'supported_formats' => ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac'],

        /*
        |--------------------------------------------------------------------------
        | Target Format
        |--------------------------------------------------------------------------
        |
        | The target format for transcoded audio files.
        |
        */
        'target_format' => env('AUDIO_TARGET_FORMAT', 'mp3'),

        /*
        |--------------------------------------------------------------------------
        | Quality Settings
        |--------------------------------------------------------------------------
        |
        | Quality settings for transcoded audio.
        |
        */
        'quality' => [
            'bitrate' => env('AUDIO_TARGET_BITRATE', '192k'),
            'sample_rate' => env('AUDIO_TARGET_SAMPLE_RATE', '44100'),
            'channels' => env('AUDIO_TARGET_CHANNELS', '2'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Workflow Types
        |--------------------------------------------------------------------------
        |
        | Which workflow types should have transcoding enabled.
        |
        */
        'workflows' => [
            'standard' => env('AUDIO_TRANSCODING_STANDARD', true),
            'contest' => env('AUDIO_TRANSCODING_CONTEST', false),
            'direct_hire' => env('AUDIO_TRANSCODING_DIRECT_HIRE', false),
            'client_management' => env('AUDIO_TRANSCODING_CLIENT_MANAGEMENT', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Watermarking Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for audio watermarking functionality.
    |
    */
    'watermarking' => [
        /*
        |--------------------------------------------------------------------------
        | Enabled
        |--------------------------------------------------------------------------
        |
        | Whether to enable audio watermarking for submissions.
        |
        */
        'enabled' => env('AUDIO_WATERMARKING_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Watermark Type
        |--------------------------------------------------------------------------
        |
        | Type of watermark to apply:
        | - 'tone' for audio tone watermark
        | - 'noise' for noise-based watermark
        | - 'spectral' for spectral watermark (requires advanced processing)
        |
        */
        'type' => env('AUDIO_WATERMARK_TYPE', 'tone'),

        /*
        |--------------------------------------------------------------------------
        | Watermark Settings
        |--------------------------------------------------------------------------
        |
        | Configuration for watermark generation.
        |
        */
        'settings' => [
            'frequency' => env('AUDIO_WATERMARK_FREQUENCY', 1000), // Hz
            'volume' => env('AUDIO_WATERMARK_VOLUME', 0.1), // 0.0 to 1.0
            'duration' => env('AUDIO_WATERMARK_DURATION', 0.5), // seconds
            'interval' => env('AUDIO_WATERMARK_INTERVAL', 30), // seconds
        ],

        /*
        |--------------------------------------------------------------------------
        | Workflow Types
        |--------------------------------------------------------------------------
        |
        | Which workflow types should have watermarking enabled.
        |
        */
        'workflows' => [
            'standard' => env('AUDIO_WATERMARKING_STANDARD', true),
            'contest' => env('AUDIO_WATERMARKING_CONTEST', false),
            'direct_hire' => env('AUDIO_WATERMARKING_DIRECT_HIRE', false),
            'client_management' => env('AUDIO_WATERMARKING_CLIENT_MANAGEMENT', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for processed audio file storage.
    |
    */
    'storage' => [
        /*
        |--------------------------------------------------------------------------
        | Processed Files Directory
        |--------------------------------------------------------------------------
        |
        | Directory where processed audio files are stored.
        |
        */
        'processed_directory' => env('AUDIO_PROCESSED_DIRECTORY', 'processed'),

        /*
        |--------------------------------------------------------------------------
        | Cleanup Policy
        |--------------------------------------------------------------------------
        |
        | Whether to clean up temporary files after processing.
        |
        */
        'cleanup_temp_files' => env('AUDIO_CLEANUP_TEMP_FILES', true),

        /*
        |--------------------------------------------------------------------------
        | Keep Original Files
        |--------------------------------------------------------------------------
        |
        | Whether to keep original files after processing.
        |
        */
        'keep_original_files' => env('AUDIO_KEEP_ORIGINAL_FILES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Waveform Generation
    |--------------------------------------------------------------------------
    |
    | Settings for audio waveform generation (existing functionality).
    |
    */
    'waveform' => [
        /*
        |--------------------------------------------------------------------------
        | Enabled
        |--------------------------------------------------------------------------
        |
        | Whether waveform generation is enabled.
        |
        */
        'enabled' => env('AUDIO_WAVEFORM_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Data Points
        |--------------------------------------------------------------------------
        |
        | Number of data points to generate for waveform visualization.
        |
        */
        'peaks_count' => env('AUDIO_WAVEFORM_PEAKS', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for optimizing processing costs.
    |
    */
    'cost_optimization' => [
        /*
        |--------------------------------------------------------------------------
        | Processing Limits
        |--------------------------------------------------------------------------
        |
        | Limits to prevent excessive processing costs.
        |
        */
        'max_file_size' => env('AUDIO_MAX_FILE_SIZE', 200 * 1024 * 1024), // 200MB
        'max_duration' => env('AUDIO_MAX_DURATION', 600), // 10 minutes
        'max_files_per_pitch' => env('AUDIO_MAX_FILES_PER_PITCH', 10),

        /*
        |--------------------------------------------------------------------------
        | Processing Schedule
        |--------------------------------------------------------------------------
        |
        | Whether to process files immediately or queue for later processing.
        |
        */
        'immediate_processing' => env('AUDIO_IMMEDIATE_PROCESSING', true),
        'queue_processing' => env('AUDIO_QUEUE_PROCESSING', true),
    ],
]; 