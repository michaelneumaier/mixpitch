<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PayoutHoldSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'default_days',
        'workflow_days',
        'business_days_only',
        'processing_time',
        'minimum_hold_hours',
        'allow_admin_bypass',
        'require_bypass_reason',
        'log_bypasses',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'default_days' => 'integer',
        'workflow_days' => 'array',
        'business_days_only' => 'boolean',
        'processing_time' => 'datetime:H:i',
        'minimum_hold_hours' => 'integer',
        'allow_admin_bypass' => 'boolean',
        'require_bypass_reason' => 'boolean',
        'log_bypasses' => 'boolean',
    ];

    /**
     * Get the current hold period settings (singleton pattern)
     */
    public static function current(): self
    {
        return Cache::remember('payout_hold_settings', 3600, function () {
            return static::first() ?? static::create(static::getDefaultSettings());
        });
    }

    /**
     * Get default settings based on configuration
     */
    public static function getDefaultSettings(): array
    {
        return [
            'enabled' => config('business.payout_hold_settings.enabled', true),
            'default_days' => config('business.payout_hold_settings.default_days', 1),
            'workflow_days' => config('business.payout_hold_settings.workflow_specific', [
                'standard' => 1,
                'contest' => 0,
                'client_management' => 0,
            ]),
            'business_days_only' => config('business.payout_hold_settings.business_days_only', true),
            'processing_time' => config('business.payout_hold_settings.processing_time', '09:00'),
            'minimum_hold_hours' => config('business.payout_hold_settings.minimum_hold_hours', 0),
            'allow_admin_bypass' => config('business.admin_overrides.allow_bypass', true),
            'require_bypass_reason' => config('business.admin_overrides.require_reason', true),
            'log_bypasses' => config('business.admin_overrides.log_bypasses', true),
        ];
    }

    /**
     * Get hold days for a specific workflow type
     */
    public function getHoldDaysForWorkflow(string $workflowType): int
    {
        if (!$this->enabled) {
            return 0;
        }

        $workflowDays = $this->workflow_days ?? [];
        return $workflowDays[$workflowType] ?? $this->default_days;
    }

    /**
     * Clear the settings cache when model is updated
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('payout_hold_settings');
        });

        static::deleted(function () {
            Cache::forget('payout_hold_settings');
        });
    }

    /**
     * Validation rules for the model
     */
    public static function validationRules(): array
    {
        return [
            'enabled' => 'boolean',
            'default_days' => 'integer|min:0|max:30',
            'workflow_days' => 'array',
            'workflow_days.standard' => 'integer|min:0|max:30',
            'workflow_days.contest' => 'integer|min:0|max:30',
            'workflow_days.client_management' => 'integer|min:0|max:30',
            'business_days_only' => 'boolean',
            'processing_time' => 'date_format:H:i',
            'minimum_hold_hours' => 'integer|min:0|max:168', // Max 1 week
            'allow_admin_bypass' => 'boolean',
            'require_bypass_reason' => 'boolean',
            'log_bypasses' => 'boolean',
        ];
    }
} 