<?php

namespace App\Services;

use App\Models\FileUploadSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class FileUploadSettingsService
{
    const CACHE_TTL = 3600; // 1 hour
    const CACHE_PREFIX = 'file_upload_settings';
    
    /**
     * Get settings for a specific context with enhanced caching
     */
    public function getSettings(string $context = FileUploadSetting::CONTEXT_GLOBAL): array
    {
        $cacheKey = $this->getCacheKey($context);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($context) {
            return $this->loadSettingsFromDatabase($context);
        });
    }
    
    /**
     * Get a specific setting value with caching
     */
    public function getSetting(string $key, string $context = FileUploadSetting::CONTEXT_GLOBAL)
    {
        $settings = $this->getSettings($context);
        return $settings[$key] ?? FileUploadSetting::DEFAULT_VALUES[$key] ?? null;
    }
    
    /**
     * Update settings with immediate cache invalidation
     */
    public function updateSettings(array $settings, string $context = FileUploadSetting::CONTEXT_GLOBAL): bool
    {
        try {
            // Validate all settings first
            foreach ($settings as $key => $value) {
                FileUploadSetting::validateSetting($key, $value);
            }

            // Update settings in database
            foreach ($settings as $key => $value) {
                FileUploadSetting::updateOrCreate(
                    ['key' => $key, 'context' => $context],
                    ['value' => $value]
                );
            }

            // Invalidate cache immediately
            $this->invalidateCache($context);
            
            // Fire event for real-time updates
            Event::dispatch('file-upload-settings.updated', [
                'context' => $context,
                'settings' => $settings
            ]);
            
            Log::info("File upload settings updated via service", [
                'context' => $context,
                'settings' => array_keys($settings)
            ]);

            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to update file upload settings via service", [
                'context' => $context,
                'settings' => $settings,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Update a single setting with immediate cache invalidation
     */
    public function updateSetting(string $key, $value, string $context = FileUploadSetting::CONTEXT_GLOBAL): bool
    {
        return $this->updateSettings([$key => $value], $context);
    }
    
    /**
     * Get all settings for all contexts (useful for admin interfaces)
     */
    public function getAllSettings(): array
    {
        $allSettings = [];
        
        foreach (FileUploadSetting::getValidContexts() as $context) {
            $allSettings[$context] = $this->getSettings($context);
        }
        
        return $allSettings;
    }
    
    /**
     * Warm up the cache for all contexts
     */
    public function warmCache(): void
    {
        foreach (FileUploadSetting::getValidContexts() as $context) {
            $this->getSettings($context);
        }
        
        Log::info("File upload settings cache warmed up");
    }
    
    /**
     * Clear all settings cache
     */
    public function clearAllCache(): void
    {
        foreach (FileUploadSetting::getValidContexts() as $context) {
            $this->invalidateCache($context);
        }
        
        Log::info("All file upload settings cache cleared");
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $stats = [
            'cached_contexts' => 0,
            'cache_keys' => [],
            'cache_sizes' => []
        ];
        
        foreach (FileUploadSetting::getValidContexts() as $context) {
            $cacheKey = $this->getCacheKey($context);
            
            if (Cache::has($cacheKey)) {
                $stats['cached_contexts']++;
                $stats['cache_keys'][] = $cacheKey;
                
                // Get approximate cache size (this is an estimate)
                $data = Cache::get($cacheKey);
                $stats['cache_sizes'][$context] = strlen(serialize($data));
            }
        }
        
        return $stats;
    }
    
    /**
     * Validate settings configuration for a context
     */
    public function validateContextSettings(string $context, array $settings): array
    {
        $errors = [];
        
        foreach ($settings as $key => $value) {
            try {
                FileUploadSetting::validateSetting($key, $value);
            } catch (\Exception $e) {
                $errors[$key] = $e->getMessage();
            }
        }
        
        return $errors;
    }
    
    /**
     * Get recommended settings for a context based on usage patterns
     */
    public function getRecommendedSettings(string $context): array
    {
        // This could be enhanced with actual usage analytics
        return FileUploadSetting::getContextDefaults($context);
    }
    
    /**
     * Check if settings are using defaults or have been customized
     */
    public function getSettingsStatus(): array
    {
        $status = [];
        
        foreach (FileUploadSetting::getValidContexts() as $context) {
            $customSettings = FileUploadSetting::where('context', $context)->count();
            $status[$context] = [
                'has_custom_settings' => $customSettings > 0,
                'custom_settings_count' => $customSettings,
                'using_defaults' => $customSettings === 0
            ];
        }
        
        return $status;
    }
    
    /**
     * Load settings from database with inheritance logic
     */
    protected function loadSettingsFromDatabase(string $context): array
    {
        $settings = [];
        
        // Get context-specific settings
        $contextSettings = FileUploadSetting::where('context', $context)->pluck('value', 'key')->toArray();
        
        // Get global settings as fallback (if not global context)
        $globalSettings = [];
        if ($context !== FileUploadSetting::CONTEXT_GLOBAL) {
            $globalSettings = FileUploadSetting::where('context', FileUploadSetting::CONTEXT_GLOBAL)
                ->pluck('value', 'key')->toArray();
        }
        
        // Merge with defaults, prioritizing context > global > defaults
        foreach (FileUploadSetting::DEFAULT_VALUES as $key => $defaultValue) {
            if (isset($contextSettings[$key])) {
                $settings[$key] = $contextSettings[$key];
            } elseif (isset($globalSettings[$key])) {
                $settings[$key] = $globalSettings[$key];
            } else {
                $settings[$key] = $defaultValue;
            }
        }
        
        return $settings;
    }
    
    /**
     * Get cache key for a context
     */
    protected function getCacheKey(string $context): string
    {
        return self::CACHE_PREFIX . "_{$context}";
    }
    
    /**
     * Invalidate cache for a specific context
     */
    protected function invalidateCache(string $context): void
    {
        $cacheKey = $this->getCacheKey($context);
        Cache::forget($cacheKey);
        
        // Also invalidate dependent contexts if this is global
        if ($context === FileUploadSetting::CONTEXT_GLOBAL) {
            foreach (FileUploadSetting::getValidContexts() as $ctx) {
                if ($ctx !== FileUploadSetting::CONTEXT_GLOBAL) {
                    Cache::forget($this->getCacheKey($ctx));
                }
            }
        }
    }
}