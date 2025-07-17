<?php

namespace App\Console\Commands;

use App\Models\FileUploadSetting;
use App\Services\FileUploadSettingsService;
use Illuminate\Console\Command;

class ManageFileUploadSettings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'upload-settings:manage 
                            {action : The action to perform (list, get, set, clear-cache, warm-cache, reset, status)}
                            {--context=global : The context to operate on}
                            {--key= : The setting key}
                            {--value= : The setting value}';

    /**
     * The console command description.
     */
    protected $description = 'Manage file upload settings from the command line';

    /**
     * Execute the console command.
     */
    public function handle(FileUploadSettingsService $service): int
    {
        $action = $this->argument('action');
        $context = $this->option('context');
        $key = $this->option('key');
        $value = $this->option('value');

        switch ($action) {
            case 'list':
                return $this->listSettings($service, $context);

            case 'get':
                return $this->getSetting($service, $key, $context);

            case 'set':
                return $this->setSetting($service, $key, $value, $context);

            case 'clear-cache':
                return $this->clearCache($service);

            case 'warm-cache':
                return $this->warmCache($service);

            case 'reset':
                return $this->resetSettings($context);

            case 'status':
                return $this->showStatus($service);

            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: list, get, set, clear-cache, warm-cache, reset, status');

                return 1;
        }
    }

    protected function listSettings(FileUploadSettingsService $service, string $context): int
    {
        $this->info("File Upload Settings for context: {$context}");
        $this->line('');

        $settings = $service->getSettings($context);

        $headers = ['Setting', 'Value', 'Type'];
        $rows = [];

        foreach ($settings as $key => $value) {
            $schema = FileUploadSetting::getSettingsSchema();
            $type = $schema[$key]['type'] ?? 'unknown';
            $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;

            $rows[] = [$key, $displayValue, $type];
        }

        $this->table($headers, $rows);

        return 0;
    }

    protected function getSetting(FileUploadSettingsService $service, ?string $key, string $context): int
    {
        if (! $key) {
            $this->error('Key is required for get action. Use --key=setting_name');

            return 1;
        }

        $value = $service->getSetting($key, $context);

        if ($value === null) {
            $this->error("Setting '{$key}' not found in context '{$context}'");

            return 1;
        }

        $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        $this->info("Setting '{$key}' in context '{$context}': {$displayValue}");

        return 0;
    }

    protected function setSetting(FileUploadSettingsService $service, ?string $key, ?string $value, string $context): int
    {
        if (! $key || $value === null) {
            $this->error('Both key and value are required for set action. Use --key=setting_name --value=setting_value');

            return 1;
        }

        // Convert string values to appropriate types
        $convertedValue = $this->convertValue($key, $value);

        try {
            $success = $service->updateSetting($key, $convertedValue, $context);

            if ($success) {
                $this->info("Setting '{$key}' updated successfully in context '{$context}'");

                return 0;
            } else {
                $this->error("Failed to update setting '{$key}' in context '{$context}'");

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error updating setting: {$e->getMessage()}");

            return 1;
        }
    }

    protected function clearCache(FileUploadSettingsService $service): int
    {
        $service->clearAllCache();
        $this->info('File upload settings cache cleared successfully');

        return 0;
    }

    protected function warmCache(FileUploadSettingsService $service): int
    {
        $service->warmCache();
        $this->info('File upload settings cache warmed up successfully');

        return 0;
    }

    protected function resetSettings(string $context): int
    {
        if (! $this->confirm("Are you sure you want to reset all settings for context '{$context}' to defaults?")) {
            $this->info('Reset cancelled');

            return 0;
        }

        try {
            FileUploadSetting::resetToDefaults($context);
            $this->info("Settings for context '{$context}' reset to defaults successfully");

            return 0;
        } catch (\Exception $e) {
            $this->error("Error resetting settings: {$e->getMessage()}");

            return 1;
        }
    }

    protected function showStatus(FileUploadSettingsService $service): int
    {
        $this->info('File Upload Settings Status');
        $this->line('');

        // Show cache statistics
        $cacheStats = $service->getCacheStats();
        $this->info("Cached Contexts: {$cacheStats['cached_contexts']}");

        if (! empty($cacheStats['cache_sizes'])) {
            $this->line('Cache Sizes:');
            foreach ($cacheStats['cache_sizes'] as $context => $size) {
                $this->line("  {$context}: ".number_format($size).' bytes');
            }
        }

        $this->line('');

        // Show settings status
        $status = $service->getSettingsStatus();
        $headers = ['Context', 'Custom Settings', 'Using Defaults'];
        $rows = [];

        foreach ($status as $context => $info) {
            $rows[] = [
                $context,
                $info['custom_settings_count'],
                $info['using_defaults'] ? 'Yes' : 'No',
            ];
        }

        $this->table($headers, $rows);

        return 0;
    }

    protected function convertValue(string $key, string $value)
    {
        $schema = FileUploadSetting::getSettingsSchema();
        $type = $schema[$key]['type'] ?? 'string';

        switch ($type) {
            case 'boolean':
                return in_array(strtolower($value), ['true', '1', 'yes', 'on']);

            case 'integer':
                return (int) $value;

            case 'float':
                return (float) $value;

            default:
                return $value;
        }
    }
}
