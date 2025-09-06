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

    // Zapier API key management routes
    Route::prefix('zapier-keys')->group(function () {
        Route::post('/generate', [\App\Http\Controllers\Api\Zapier\ZapierApiKeyController::class, 'generate']);
        Route::delete('/revoke', [\App\Http\Controllers\Api\Zapier\ZapierApiKeyController::class, 'revoke']);
        Route::get('/status', [\App\Http\Controllers\Api\Zapier\ZapierApiKeyController::class, 'status']);
    });
});

// Zapier integration routes (authenticated with Zapier-specific token abilities)
Route::middleware(['auth:sanctum'])->prefix('zapier')->group(function () {
    // Authentication test endpoint
    Route::get('/auth/test', [\App\Http\Controllers\Api\Zapier\ZapierApiKeyController::class, 'test']);

    // Trigger endpoints
    Route::get('/triggers/clients/new', [\App\Http\Controllers\Api\Zapier\NewClientTrigger::class, 'poll']);

    // Action endpoints  
    Route::post('/actions/clients/create', [\App\Http\Controllers\Api\Zapier\CreateClientAction::class, 'create']);
});
