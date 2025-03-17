<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MixController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PitchController;
use App\Http\Controllers\PitchFileController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\UserProfileController;
use App\Livewire\CreateProject;
use App\Livewire\ManageProject;
use App\Livewire\Pitch\Snapshot\ShowSnapshot;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('home');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
});

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');


Route::middleware(['auth'])->group(function () {
    Route::post('/projects/store', [ProjectController::class, 'storeProject'])->name('projects.store');
    Route::get('/projects/upload', [ProjectController::class, 'createProject'])->name('projects.upload');
    Route::get('/projects/{project}/step2', [ProjectController::class, 'createStep2'])->name('projects.createStep2');
    Route::post('/projects/{project}/step2', [ProjectController::class, 'storeStep2'])->name('projects.storeStep2');
    //Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

    // Route::get('/create-project', function () {
    //     return view('livewire.create-project');
    // });

    Route::get('/create-project', CreateProject::class)->name('projects.create');
    Route::get('/edit-project/{project}', CreateProject::class)->name('projects.edit');
    Route::get('/manage-project/{project}', ManageProject::class)->name('projects.manage');


    Route::delete('projects/{project}/files/{file}', [ProjectController::class, 'deleteFile'])->name('projects.deleteFile');

    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::get('/projects/{project}/download', [ProjectController::class, 'download'])->name('projects.download');

    Route::get('/projects/{project}/mixes/create', [MixController::class, 'create'])->name('mixes.create');
    Route::post('/projects/{project}/mixes', [MixController::class, 'store'])->name('mixes.store');
    Route::patch('/mixes/{mix}/rate', [MixController::class, 'rate'])->name('mixes.rate');

    Route::resource('/pitches', PitchController::class);
    Route::get('/pitches/create/{project}', [PitchController::class, 'create'])->name('pitches.create');
    Route::post('/pitches/{pitch}/status', [PitchController::class, 'updateStatus'])->name('pitches.updateStatus');

    // Special route for pitch deletion to handle the Livewire redirect approach
    Route::get('/pitches/{pitch}/delete-confirmed', [PitchController::class, 'destroyConfirmed'])->name('pitches.destroyConfirmed');

    // Make sure snapshot routes come after other specific routes to avoid conflicts
    Route::get('/pitches/{pitch}/latest-snapshot', [PitchController::class, 'showLatestSnapshot'])->name('pitches.showLatestSnapshot');
    Route::get('/pitches/{pitch}/{pitchSnapshot}', ShowSnapshot::class)->name('pitches.showSnapshot');

    // New routes for non-Livewire pitch status changes
    Route::get('/pitch/{pitch}/change-status/{direction}/{newStatus?}', [App\Http\Controllers\PitchStatusController::class, 'changeStatus'])
        ->name('pitch.changeStatus')
        ->middleware('auth');
    Route::post('/pitch/{pitch}/approve-snapshot/{snapshot}', [App\Http\Controllers\PitchStatusController::class, 'approveSnapshot'])
        ->name('pitch.approveSnapshot')
        ->middleware('auth');
    Route::post('/pitch/{pitch}/deny-snapshot/{snapshot}', [App\Http\Controllers\PitchStatusController::class, 'denySnapshot'])
        ->name('pitch.denySnapshot')
        ->middleware('auth');
    Route::post('/pitch/{pitch}/request-changes/{snapshot}', [App\Http\Controllers\PitchStatusController::class, 'requestChanges'])
        ->name('pitch.requestChanges')
        ->middleware('auth');

    Route::get('/pitch-files/{file}', [PitchFileController::class, 'show'])->name('pitch-files.show');
    Route::get('/pitch-files/download/{file}', [PitchFileController::class, 'download'])
        ->name('pitch-files.download')
        ->middleware('auth');
});

// User Profile Routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/profile/edit', function () {
        return view('user-profile.edit-livewire');
    })->name('profile.edit');

    Route::get('/@{username}', [UserProfileController::class, 'show'])->name('profile.show');
});

// About and Pricing Pages
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

// Audio Processor Test Routes
Route::get('/test-audio-processor', [App\Http\Controllers\TestAudioProcessorController::class, 'index'])->middleware('auth');
Route::get('/test-audio-processor/test/{file_id}', [App\Http\Controllers\TestAudioProcessorController::class, 'testEndpoint'])->middleware('auth');
Route::post('/test-audio-processor/upload', [App\Http\Controllers\TestAudioProcessorController::class, 'uploadTest'])->middleware('auth');

// Simple Lambda test
Route::get('/test-lambda-direct', function () {
    try {
        $lambdaUrl = config('services.aws.lambda_audio_processor_url');

        if (empty($lambdaUrl)) {
            return response()->json([
                'success' => false,
                'error' => 'Lambda URL is not configured'
            ]);
        }

        // Append /waveform if it's not already there
        if (!str_ends_with($lambdaUrl, '/waveform')) {
            $lambdaUrl .= '/waveform';
        }

        // First try a direct GET request to test connectivity
        $getResponse = \Illuminate\Support\Facades\Http::timeout(10)
            ->withOptions([
                'debug' => true,
                'verify' => false
            ])
            ->get($lambdaUrl);

        // Now try a POST with minimal data
        $postResponse = \Illuminate\Support\Facades\Http::timeout(10)
            ->withOptions([
                'debug' => true,
                'verify' => false
            ])
            ->post($lambdaUrl, [
                'test' => true
            ]);

        return response()->json([
            'lambda_url' => $lambdaUrl,
            'get_status' => $getResponse->status(),
            'get_body' => $getResponse->body(),
            'post_status' => $postResponse->status(),
            'post_body' => $postResponse->body()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->middleware('auth');

// Test Lambda with a specific file
Route::get('/test-lambda-with-file/{file_id?}', function ($fileId = null) {
    try {
        // Get a file to test with
        if ($fileId) {
            $file = \App\Models\PitchFile::findOrFail($fileId);
        } else {
            // Get the first audio file
            $file = \App\Models\PitchFile::whereRaw("LOWER(file_path) LIKE '%.mp3' OR LOWER(file_path) LIKE '%.wav'")->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'error' => 'No audio files found'
                ]);
            }
        }

        // Get file URL
        $fileUrl = $file->fullFilePath;

        if (empty($fileUrl)) {
            return response()->json([
                'success' => false,
                'error' => 'Could not generate S3 URL for file'
            ]);
        }

        // Properly encode the URL - ensure spaces are encoded as %20
        $encodedFileUrl = str_replace(' ', '%20', $fileUrl);

        // Get Lambda URL
        $lambdaUrl = config('services.aws.lambda_audio_processor_url');

        if (empty($lambdaUrl)) {
            return response()->json([
                'success' => false,
                'error' => 'Lambda URL is not configured'
            ]);
        }

        // Append /waveform if it's not already there
        if (!str_ends_with($lambdaUrl, '/waveform')) {
            $lambdaUrl .= '/waveform';
        }

        // Try different variations of the request

        // Regular request
        $response1 = \Illuminate\Support\Facades\Http::timeout(60)
            ->withOptions([
                'debug' => true,
                'verify' => false
            ])
            ->post($lambdaUrl, [
                'file_url' => $encodedFileUrl,
                'peaks_count' => 200
            ]);

        // Try with JSON content type
        $response2 = \Illuminate\Support\Facades\Http::timeout(60)
            ->withOptions([
                'debug' => true,
                'verify' => false
            ])
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->post($lambdaUrl, [
                'file_url' => $encodedFileUrl,
                'peaks_count' => 200
            ]);

        // Try with URL encoded as query param
        $response3 = \Illuminate\Support\Facades\Http::timeout(60)
            ->withOptions([
                'debug' => true,
                'verify' => false
            ])
            ->post($lambdaUrl . '?file_url=' . urlencode($encodedFileUrl) . '&peaks_count=200');

        return response()->json([
            'file_id' => $file->id,
            'file_name' => $file->file_name,
            'file_path' => $file->file_path,
            'file_url' => $fileUrl,
            'encoded_file_url' => $encodedFileUrl,
            'lambda_url' => $lambdaUrl,
            'regular_request' => [
                'status' => $response1->status(),
                'body' => $response1->body()
            ],
            'json_request' => [
                'status' => $response2->status(),
                'body' => $response2->body()
            ],
            'url_params_request' => [
                'status' => $response3->status(),
                'body' => $response3->body()
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->middleware('auth');

// Test Lambda with various URL formats
Route::get('/test-lambda-url-formats/{file_id?}', function ($fileId = null) {
    try {
        // Get a file to test with
        if ($fileId) {
            $file = \App\Models\PitchFile::findOrFail($fileId);
        } else {
            // Get the first audio file
            $file = \App\Models\PitchFile::whereRaw("LOWER(file_path) LIKE '%.mp3' OR LOWER(file_path) LIKE '%.wav'")->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'error' => 'No audio files found'
                ]);
            }
        }

        // Get file URL and create variations
        $originalUrl = $file->fullFilePath;

        if (empty($originalUrl)) {
            return response()->json([
                'success' => false,
                'error' => 'Could not generate S3 URL for file'
            ]);
        }

        // Pre-encode the URL to ensure spaces are handled correctly
        $safeUrl = str_replace(' ', '%20', $originalUrl);

        // Generate URL variations to test
        $urlVariations = [
            'original' => $originalUrl,
            'safe_url' => $safeUrl,
            'encoded' => urlencode($safeUrl),
            'double_encoded' => urlencode(urlencode($safeUrl)),
            'spaces_to_plus' => str_replace(' ', '+', $originalUrl),
            'spaces_to_percent20' => str_replace(' ', '%20', $originalUrl),
            'lowercase' => strtolower($safeUrl),
            'no_query_params' => preg_replace('/\?.*/', '', $safeUrl),
            'escaped_quotes' => str_replace('"', '\"', $safeUrl)
        ];

        // Get Lambda URL
        $lambdaUrl = config('services.aws.lambda_audio_processor_url');

        if (empty($lambdaUrl)) {
            return response()->json([
                'success' => false,
                'error' => 'Lambda URL is not configured'
            ]);
        }

        // Append /waveform if it's not already there
        if (!str_ends_with($lambdaUrl, '/waveform')) {
            $lambdaUrl .= '/waveform';
        }

        // Test each URL variation
        $results = [];

        foreach ($urlVariations as $type => $url) {
            try {
                // Send request with this URL variation
                $response = \Illuminate\Support\Facades\Http::timeout(30)
                    ->withOptions([
                        'verify' => false
                    ])
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ])
                    ->post($lambdaUrl, [
                        'file_url' => $url,
                        'peaks_count' => 200
                    ]);

                $results[$type] = [
                    'status' => $response->status(),
                    'success' => $response->successful(),
                    'url' => $url,
                    'response_excerpt' => substr($response->body(), 0, 200) . (strlen($response->body()) > 200 ? '...' : '')
                ];

                // If successful, mark this one
                if ($response->successful()) {
                    $results[$type]['works'] = true;
                }
            } catch (\Exception $e) {
                $results[$type] = [
                    'status' => 'exception',
                    'success' => false,
                    'url' => $url,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'file_id' => $file->id,
            'file_name' => $file->file_name,
            'file_path' => $file->file_path,
            'file_url_variations' => $results,
            'lambda_url' => $lambdaUrl
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->middleware('auth');
// Secure file download routes
Route::get('/download/pitch-file/{id}', [App\Http\Controllers\FileDownloadController::class, 'downloadPitchFile'])
    ->name('download.pitch-file')
    ->middleware('auth');

Route::get('/download/project-file/{id}', [App\Http\Controllers\FileDownloadController::class, 'downloadProjectFile'])
    ->name('download.project-file')
    ->middleware('auth');
Route::get('/test-s3', function () {
    try {
        // Test if we can list files in the S3 bucket
        $files = Storage::disk('s3')->files('test-directory');

        // Try to create a small test file
        $result = Storage::disk('s3')->put('test-file-' . time() . '.txt', 'Hello S3 Test');

        return [
            'connection' => 'success',
            'can_list_files' => true,
            'can_write_file' => $result,
            'files_found' => count($files)
        ];
    } catch (\Exception $e) {
        return [
            'connection' => 'failed',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
});

Route::get('/check-s3-config', function () {
    return [
        's3_disk_config' => [
            'driver' => config('filesystems.disks.s3.driver'),
            'region' => config('filesystems.disks.s3.region'),
            'bucket' => config('filesystems.disks.s3.bucket'),
            'url' => config('filesystems.disks.s3.url'),
            'endpoint' => config('filesystems.disks.s3.endpoint'),
            'use_path_style_endpoint' => config('filesystems.disks.s3.use_path_style_endpoint'),
            'throw' => config('filesystems.disks.s3.throw'),
            'visibility' => config('filesystems.disks.s3.visibility'),
            // Don't include key and secret for security reasons
        ],
        'default_filesystem' => config('filesystems.default'),
        'livewire_upload_disk' => config('livewire.temporary_file_upload.disk'),
        'flysystem_version' => \Composer\InstalledVersions::getVersion('league/flysystem-aws-s3-v3'),
        'aws_sdk_version' => \Composer\InstalledVersions::getVersion('aws/aws-sdk-php'),
    ];
});
