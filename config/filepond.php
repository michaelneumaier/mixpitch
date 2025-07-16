<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FilePond Permanent Disk
    |--------------------------------------------------------------------------
    |
    | Set the FilePond default disk to be used for permanent file storage.
    |
    */
    'disk' => env('FILEPOND_DISK', 's3'),

    /*
    |--------------------------------------------------------------------------
    | FilePond Temporary Disk
    |--------------------------------------------------------------------------
    |
    | Set the FilePond temporary disk and folder name to be used for temporary
    | storage. This disk will be used for temporary file storage and cleared
    | upon running the "artisan filepond:clear" command. It is recommended to
    | use local disk for temporary storage when you want to take advantage of
    | controller level validation. However, we're configuring for S3/R2.
    |
    */
    'temp_disk' => env('FILEPOND_TEMP_DISK', 's3'),
    'temp_folder' => env('FILEPOND_TEMP_FOLDER', 'filepond/temp'),

    /*
    |--------------------------------------------------------------------------
    | FilePond Routes Middleware
    |--------------------------------------------------------------------------
    |
    | Default middleware for FilePond routes.
    |
    */
    'middleware' => [
        'web', 'auth',
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Delete FilePond Model
    |--------------------------------------------------------------------------
    |
    | Determine whether to enable or disable soft delete in FilePond model.
    |
    */
    'soft_delete' => true,

    /*
    |--------------------------------------------------------------------------
    | File Delete After (Minutes)
    |--------------------------------------------------------------------------
    |
    | Set the minutes after which the FilePond temporary storage files will be
    | deleted while running 'artisan filepond:clear' command.
    |
    */
    'expiration' => 30,

    /*
    |--------------------------------------------------------------------------
    | FilePond Controller
    |--------------------------------------------------------------------------
    |
    | FilePond controller determines how the requests from FilePond library is
    | processed.
    |
    */
    'controller' => App\Http\Controllers\S3FilepondController::class,

    /*
    |--------------------------------------------------------------------------
    | FilePond Model
    |--------------------------------------------------------------------------
    |
    | Set the filepond model to be used by the package. Make sure you extend
    | the custom model with "RahulHaque\Filepond\Models\Filepond" model.
    |
    */
    'model' => RahulHaque\Filepond\Models\Filepond::class,

    /*
    |--------------------------------------------------------------------------
    | Global Validation Rules
    |--------------------------------------------------------------------------
    |
    | Set the default validation for filepond's ./process route. In other words
    | temporary file upload validation.
    |
    */
    'validation_rules' => [
        'required',
        'file',
        'max:200000', // 200MB for large audio files
    ],

    /*
    |--------------------------------------------------------------------------
    | FilePond Server Paths
    |--------------------------------------------------------------------------
    |
    | Configure url for each of the FilePond actions.
    | See details - https://pqina.nl/filepond/docs/patterns/api/server/
    |
    */
    'server' => [
        'url' => env('FILEPOND_URL', '/filepond'),
    ],
];