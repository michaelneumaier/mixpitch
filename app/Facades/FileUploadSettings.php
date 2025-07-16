<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getSettings(string $context = 'global')
 * @method static mixed getSetting(string $key, string $context = 'global')
 * @method static bool updateSettings(array $settings, string $context = 'global')
 * @method static bool updateSetting(string $key, $value, string $context = 'global')
 * @method static array getAllSettings()
 * @method static void warmCache()
 * @method static void clearAllCache()
 * @method static array getCacheStats()
 * @method static array validateContextSettings(string $context, array $settings)
 * @method static array getRecommendedSettings(string $context)
 * @method static array getSettingsStatus()
 */
class FileUploadSettings extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'file-upload-settings';
    }
}