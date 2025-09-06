<?php

use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\SesWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::middleware(['auth:sanctum', \App\Http\Middleware\ZapierRateLimit::class])->prefix('zapier')->group(function () {
    // Authentication test endpoint
    Route::get('/auth/test', [\App\Http\Controllers\Api\Zapier\ZapierApiKeyController::class, 'test']);

    // Trigger endpoints
    Route::get('/triggers/clients/new', [\App\Http\Controllers\Api\Zapier\NewClientTrigger::class, 'poll']);
    Route::get('/triggers/clients/updated', [\App\Http\Controllers\Api\Zapier\ClientUpdatedTrigger::class, 'poll']);
    Route::get('/triggers/reminders/due', [\App\Http\Controllers\Api\Zapier\ClientReminderDueTrigger::class, 'poll']);
    Route::get('/triggers/projects/client-created', [\App\Http\Controllers\Api\Zapier\ClientProjectCreatedTrigger::class, 'poll']);
    Route::get('/triggers/projects/status-changed', [\App\Http\Controllers\Api\Zapier\ClientProjectStatusTrigger::class, 'poll']);

    // Phase 3 - Analytics & Advanced Features
    Route::get('/triggers/revenue/analytics', [\App\Http\Controllers\Api\Zapier\RevenueAnalyticsTrigger::class, 'poll']);
    Route::get('/triggers/communications/log', [\App\Http\Controllers\Api\Zapier\ClientCommunicationLogTrigger::class, 'poll']);

    // Action endpoints
    Route::post('/actions/clients/create', [\App\Http\Controllers\Api\Zapier\CreateClientAction::class, 'create']);
    Route::post('/actions/clients/update', [\App\Http\Controllers\Api\Zapier\UpdateClientAction::class, 'update']);
    Route::post('/actions/clients/bulk-update', [\App\Http\Controllers\Api\Zapier\BulkUpdateClientsAction::class, 'update']);
    Route::post('/actions/reminders/create', [\App\Http\Controllers\Api\Zapier\AddClientReminderAction::class, 'create']);
    Route::post('/actions/projects/create', [\App\Http\Controllers\Api\Zapier\CreateProjectAction::class, 'create']);

    // Search endpoints
    Route::get('/searches/clients', [\App\Http\Controllers\Api\Zapier\FindClientSearch::class, 'search']);
    Route::get('/searches/projects', [\App\Http\Controllers\Api\Zapier\FindClientProjectsSearch::class, 'search']);

    // Webhook management endpoints
    Route::post('/webhooks/register', [\App\Http\Controllers\Api\Zapier\WebhookController::class, 'register']);
    Route::post('/webhooks/deregister', [\App\Http\Controllers\Api\Zapier\WebhookController::class, 'deregister']);
    Route::get('/webhooks/list', [\App\Http\Controllers\Api\Zapier\WebhookController::class, 'list']);
    Route::post('/webhooks/test', [\App\Http\Controllers\Api\Zapier\WebhookController::class, 'test']);
    Route::get('/webhooks/event-types', [\App\Http\Controllers\Api\Zapier\WebhookController::class, 'eventTypes']);

    // Usage analytics and monitoring endpoints
    Route::get('/usage/rate-limit-status', [\App\Http\Controllers\Api\Zapier\UsageAnalyticsController::class, 'rateLimitStatus']);
    Route::get('/usage/stats', [\App\Http\Controllers\Api\Zapier\UsageAnalyticsController::class, 'usageStats']);
    Route::get('/usage/quota-status', [\App\Http\Controllers\Api\Zapier\UsageAnalyticsController::class, 'quotaStatus']);
    Route::get('/usage/report', [\App\Http\Controllers\Api\Zapier\UsageAnalyticsController::class, 'usageReport']);
    Route::get('/admin/system-analytics', [\App\Http\Controllers\Api\Zapier\UsageAnalyticsController::class, 'systemAnalytics']);
});

// Health check endpoint (no auth or rate limiting required)
Route::get('/zapier/health', [\App\Http\Controllers\Api\Zapier\UsageAnalyticsController::class, 'healthCheck']);
