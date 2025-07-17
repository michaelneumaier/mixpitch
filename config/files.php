<?php

// config/files.php

return [
    /*
    |--------------------------------------------------------------------------
    | Maximum File Sizes
    |--------------------------------------------------------------------------
    |
    | Define the maximum allowed file sizes in bytes for different upload types.
    | Provides a central place to manage file size restrictions.
    |
    */

    'max_project_file_size' => env('MAX_PROJECT_FILE_SIZE_BYTES', 200 * 1024 * 1024), // Default 200MB for audio files

    'max_pitch_file_size' => env('MAX_PITCH_FILE_SIZE_BYTES', 200 * 1024 * 1024), // Default 200MB for audio files

    // Add other file size limits as needed
];
