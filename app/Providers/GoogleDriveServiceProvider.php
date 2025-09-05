<?php

namespace App\Providers;

use App\Services\GoogleDriveService;
use Google\Client as GoogleClient;
use Illuminate\Support\ServiceProvider;

class GoogleDriveServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(GoogleDriveService::class, function ($app) {
            return new GoogleDriveService(
                $app->make(\App\Services\FileValidationService::class),
                $app->make(\App\Services\UserStorageService::class)
            );
        });

        // Ensure Google Client is available
        $this->app->bind(GoogleClient::class, function () {
            return new GoogleClient();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Force autoload the Google Client classes
        if (class_exists(GoogleClient::class)) {
            // Google Client is available
        }
    }
}