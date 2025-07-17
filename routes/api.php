<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SesWebhookController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\ChunkUploadController;
use App\Http\Controllers\CustomUppyS3MultipartController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// SES webhook endpoint
Route::post('/webhooks/ses', [SesWebhookController::class, 'handle'])
    ->name('webhooks.ses')
    ->middleware('throttle:60,1');


Route::middleware('auth:sanctum')->group(function () {
    // License preview routes
    Route::get('/licenses/{license}/preview', [LicenseController::class, 'preview']);
    
    // Upload settings routes
    Route::get('/upload-settings/{context?}', [\App\Http\Controllers\Api\UploadSettingsController::class, 'getSettings']);
    Route::post('/upload-settings/for-model', [\App\Http\Controllers\Api\UploadSettingsController::class, 'getSettingsForModel']);
    Route::post('/upload-settings/test', [\App\Http\Controllers\Api\UploadSettingsController::class, 'testSettings']);

    // Presigned URL upload routes
    Route::post('/presigned-upload/generate', [\App\Http\Controllers\Api\PresignedUploadController::class, 'generatePresignedUrl'])
        ->middleware('upload.validate:auto');
    Route::post('/presigned-upload/complete', [\App\Http\Controllers\Api\PresignedUploadController::class, 'completeUpload']);
});
