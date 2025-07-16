<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FileUploadSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'context',
        'description'
    ];

    protected $casts = [
        'value' => 'json'
    ];

    // Setting keys
    const MAX_FILE_SIZE_MB = 'max_file_size_mb';
    const CHUNK_SIZE_MB = 'chunk_size_mb';
    const MAX_CONCURRENT_UPLOADS = 'max_concurrent_uploads';
    const MAX_RETRY_ATTEMPTS = 'max_retry_attempts';
    const ENABLE_CHUNKING = 'enable_chunking';
    const SESSION_TIMEOUT_HOURS = 'session_timeout_hours';

    // Context types
    const CONTEXT_GLOBAL = 'global';
    const CONTEXT_PROJECTS = 'projects';
    const CONTEXT_PITCHES = 'pitches';
    const CONTEXT_CLIENT_PORTALS = 'client_portals';

    // Default values
    const DEFAULT_VALUES = [
        self::MAX_FILE_SIZE_MB => 500,
        self::CHUNK_SIZE_MB => 5,
        self::MAX_CONCURRENT_UPLOADS => 3,
        self::MAX_RETRY_ATTEMPTS => 3,
        self::ENABLE_CHUNKING => true,
        self::SESSION_TIMEOUT_HOURS => 24
    ];

    // Validation rules for each setting
    const VALIDATION_RULES = [
        self::MAX_FILE_SIZE_MB => 'integer|min:1|max:2048',
        self::CHUNK_SIZE_MB => 'integer|min:1|max:50',
        self::MAX_CONCURRENT_UPLOADS => 'integer|min:1|max:10',
        self::MAX_RETRY_ATTEMPTS => 'integer|min:1|max:5',
        self::ENABLE_CHUNKING => 'boolean',
        self::SESSION_TIMEOUT_HOURS => 'integer|min:1|max:168'
    ];

    /**
     * Get all settings for a specific context
     */
    public static function getSettings(string $context = self::CONTEXT_GLOBAL): array
    {
        $cacheKey = "file_upload_settings_{$context}";
        
        return Cache::remember($cacheKey, 3600, function () use ($context) {
            $settings = [];
            
            // Get context-specific settings
            $contextSettings = self::where('context', $context)->pluck('value', 'key')->toArray();
            
            // Get global settings as fallback
            $globalSettings = [];
            if ($context !== self::CONTEXT_GLOBAL) {
                $globalSettings = self::where('context', self::CONTEXT_GLOBAL)->pluck('value', 'key')->toArray();
            }
            
            // Merge with defaults, prioritizing context > global > defaults
            foreach (self::DEFAULT_VALUES as $key => $defaultValue) {
                if (isset($contextSettings[$key])) {
                    $settings[$key] = $contextSettings[$key];
                } elseif (isset($globalSettings[$key])) {
                    $settings[$key] = $globalSettings[$key];
                } else {
                    $settings[$key] = $defaultValue;
                }
            }
            
            return $settings;
        });
    }

    /**
     * Get a specific setting value
     */
    public static function getSetting(string $key, string $context = self::CONTEXT_GLOBAL)
    {
        $settings = self::getSettings($context);
        return $settings[$key] ?? self::DEFAULT_VALUES[$key] ?? null;
    }

    /**
     * Update multiple settings for a context
     */
    public static function updateSettings(array $settings, string $context = self::CONTEXT_GLOBAL): bool
    {
        try {
            // Validate all settings first
            foreach ($settings as $key => $value) {
                self::validateSetting($key, $value);
            }

            // Update each setting
            foreach ($settings as $key => $value) {
                self::updateOrCreate(
                    ['key' => $key, 'context' => $context],
                    ['value' => $value]
                );
            }

            // Clear cache
            self::clearSettingsCache($context);
            
            Log::info("File upload settings updated", [
                'context' => $context,
                'settings' => array_keys($settings)
            ]);

            return true;
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions so tests can catch them
            throw $e;
        } catch (\Exception $e) {
            Log::error("Failed to update file upload settings", [
                'context' => $context,
                'settings' => $settings,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update a single setting
     */
    public static function updateSetting(string $key, $value, string $context = self::CONTEXT_GLOBAL): bool
    {
        return self::updateSettings([$key => $value], $context);
    }

    /**
     * Validate a setting value
     */
    public static function validateSetting(string $key, $value): void
    {
        if (!isset(self::VALIDATION_RULES[$key])) {
            throw new \InvalidArgumentException("Unknown setting key: {$key}");
        }

        $validator = Validator::make(
            [$key => $value],
            [$key => self::VALIDATION_RULES[$key]]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate context
     */
    public static function validateContext(string $context): bool
    {
        return in_array($context, self::getValidContexts());
    }

    /**
     * Get all valid contexts
     */
    public static function getValidContexts(): array
    {
        return [
            self::CONTEXT_GLOBAL,
            self::CONTEXT_PROJECTS,
            self::CONTEXT_PITCHES,
            self::CONTEXT_CLIENT_PORTALS
        ];
    }

    /**
     * Get all valid setting keys
     */
    public static function getValidKeys(): array
    {
        return array_keys(self::DEFAULT_VALUES);
    }

    /**
     * Clear settings cache for a context
     */
    public static function clearSettingsCache(?string $context = null): void
    {
        if ($context) {
            Cache::forget("file_upload_settings_{$context}");
        } else {
            // Clear all contexts
            foreach (self::getValidContexts() as $ctx) {
                Cache::forget("file_upload_settings_{$ctx}");
            }
        }
    }

    /**
     * Reset settings to defaults for a context
     */
    public static function resetToDefaults(string $context = self::CONTEXT_GLOBAL): bool
    {
        try {
            self::where('context', $context)->delete();
            self::clearSettingsCache($context);
            
            Log::info("File upload settings reset to defaults", ['context' => $context]);
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to reset file upload settings", [
                'context' => $context,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get settings with their descriptions and validation rules
     */
    public static function getSettingsSchema(): array
    {
        return [
            self::MAX_FILE_SIZE_MB => [
                'description' => 'Maximum file size in megabytes',
                'validation' => self::VALIDATION_RULES[self::MAX_FILE_SIZE_MB],
                'default' => self::DEFAULT_VALUES[self::MAX_FILE_SIZE_MB],
                'type' => 'integer'
            ],
            self::CHUNK_SIZE_MB => [
                'description' => 'Chunk size for file uploads in megabytes',
                'validation' => self::VALIDATION_RULES[self::CHUNK_SIZE_MB],
                'default' => self::DEFAULT_VALUES[self::CHUNK_SIZE_MB],
                'type' => 'integer'
            ],
            self::MAX_CONCURRENT_UPLOADS => [
                'description' => 'Maximum number of concurrent uploads',
                'validation' => self::VALIDATION_RULES[self::MAX_CONCURRENT_UPLOADS],
                'default' => self::DEFAULT_VALUES[self::MAX_CONCURRENT_UPLOADS],
                'type' => 'integer'
            ],
            self::MAX_RETRY_ATTEMPTS => [
                'description' => 'Maximum retry attempts for failed uploads',
                'validation' => self::VALIDATION_RULES[self::MAX_RETRY_ATTEMPTS],
                'default' => self::DEFAULT_VALUES[self::MAX_RETRY_ATTEMPTS],
                'type' => 'integer'
            ],
            self::ENABLE_CHUNKING => [
                'description' => 'Enable chunked uploads for large files',
                'validation' => self::VALIDATION_RULES[self::ENABLE_CHUNKING],
                'default' => self::DEFAULT_VALUES[self::ENABLE_CHUNKING],
                'type' => 'boolean'
            ],
            self::SESSION_TIMEOUT_HOURS => [
                'description' => 'Upload session timeout in hours',
                'validation' => self::VALIDATION_RULES[self::SESSION_TIMEOUT_HOURS],
                'default' => self::DEFAULT_VALUES[self::SESSION_TIMEOUT_HOURS],
                'type' => 'integer'
            ]
        ];
    }

    /**
     * Get context-specific validation rules for different upload contexts
     */
    public static function getContextValidationRules(string $context): array
    {
        $baseRules = self::VALIDATION_RULES;
        
        // Context-specific validation adjustments
        switch ($context) {
            case self::CONTEXT_PROJECTS:
                // Projects might need larger file sizes for full tracks
                $baseRules[self::MAX_FILE_SIZE_MB] = 'integer|min:1|max:2048';
                break;
                
            case self::CONTEXT_PITCHES:
                // Pitches typically have smaller file size limits
                $baseRules[self::MAX_FILE_SIZE_MB] = 'integer|min:1|max:1024';
                break;
                
            case self::CONTEXT_CLIENT_PORTALS:
                // Client portals might have more restrictive limits
                $baseRules[self::MAX_FILE_SIZE_MB] = 'integer|min:1|max:500';
                $baseRules[self::MAX_CONCURRENT_UPLOADS] = 'integer|min:1|max:5';
                break;
        }
        
        return $baseRules;
    }

    /**
     * Get recommended default values for different contexts
     */
    public static function getContextDefaults(string $context): array
    {
        $defaults = self::DEFAULT_VALUES;
        
        // Context-specific default adjustments
        switch ($context) {
            case self::CONTEXT_PROJECTS:
                $defaults[self::MAX_FILE_SIZE_MB] = 1000; // 1GB for full tracks
                $defaults[self::CHUNK_SIZE_MB] = 10; // Larger chunks for big files
                break;
                
            case self::CONTEXT_PITCHES:
                $defaults[self::MAX_FILE_SIZE_MB] = 200; // 200MB for pitch demos
                $defaults[self::CHUNK_SIZE_MB] = 5; // Standard chunk size
                break;
                
            case self::CONTEXT_CLIENT_PORTALS:
                $defaults[self::MAX_FILE_SIZE_MB] = 100; // 100MB for client uploads
                $defaults[self::MAX_CONCURRENT_UPLOADS] = 2; // Fewer concurrent uploads
                break;
        }
        
        return $defaults;
    }

    /**
     * Scope for settings by context
     */
    public function scopeForContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    /**
     * Scope for settings by key
     */
    public function scopeForKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Clear cache when settings are updated
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            self::clearSettingsCache($setting->context);
        });

        static::deleted(function ($setting) {
            self::clearSettingsCache($setting->context);
        });
    }
}