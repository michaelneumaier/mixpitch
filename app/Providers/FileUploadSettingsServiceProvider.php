<?php

namespace App\Providers;

use App\Services\FileUploadSettingsService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class FileUploadSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FileUploadSettingsService::class, function ($app) {
            return new FileUploadSettingsService();
        });
        
        // Register alias for easier access
        $this->app->alias(FileUploadSettingsService::class, 'file-upload-settings');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set up event listeners for real-time settings updates
        $this->setupEventListeners();
        
        // Warm up cache on application boot (optional, can be disabled for performance)
        if (config('app.env') === 'production') {
            $this->app->booted(function () {
                try {
                    app(FileUploadSettingsService::class)->warmCache();
                } catch (\Exception $e) {
                    Log::warning('Failed to warm file upload settings cache', [
                        'error' => $e->getMessage()
                    ]);
                }
            });
        }
    }
    
    /**
     * Set up event listeners for real-time updates
     */
    protected function setupEventListeners(): void
    {
        // Listen for settings updates
        Event::listen('file-upload-settings.updated', function ($context, $settings) {
            Log::info('File upload settings updated event fired', [
                'context' => $context,
                'settings' => array_keys($settings)
            ]);
            
            // Here you could add additional logic like:
            // - Broadcasting to connected clients via WebSockets
            // - Updating configuration files
            // - Notifying other services
            // - Triggering cache refresh on other servers
        });
        
        // Listen for cache clear events
        Event::listen('file-upload-settings.cache-cleared', function ($context = null) {
            Log::info('File upload settings cache cleared', [
                'context' => $context ?? 'all'
            ]);
        });
        
        // Listen for validation errors
        Event::listen('file-upload-settings.validation-error', function ($context, $errors) {
            Log::warning('File upload settings validation failed', [
                'context' => $context,
                'errors' => $errors
            ]);
        });
    }
}