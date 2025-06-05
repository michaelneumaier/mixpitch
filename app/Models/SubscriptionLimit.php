<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_name',
        'plan_tier',
        'max_projects_owned',
        'max_active_pitches',
        'max_monthly_pitches',
        'storage_per_project_mb',
        'priority_support',
        'custom_portfolio',
        // Enhanced features
        'storage_per_project_gb',
        'file_retention_days',
        'platform_commission_rate',
        'max_license_templates',
        'monthly_visibility_boosts',
        'reputation_multiplier',
        'max_private_projects_monthly',
        'has_client_portal',
        'analytics_level',
        'challenge_early_access_hours',
        'has_judge_access',
        'support_sla_hours',
        'support_channels',
        'user_badge'
    ];

    protected $casts = [
        'max_projects_owned' => 'integer',
        'max_active_pitches' => 'integer',
        'max_monthly_pitches' => 'integer',
        'storage_per_project_mb' => 'integer',
        'priority_support' => 'boolean',
        'custom_portfolio' => 'boolean',
        // Enhanced features casts
        'storage_per_project_gb' => 'decimal:2',
        'file_retention_days' => 'integer',
        'platform_commission_rate' => 'decimal:2',
        'max_license_templates' => 'integer',
        'monthly_visibility_boosts' => 'integer',
        'reputation_multiplier' => 'decimal:2',
        'max_private_projects_monthly' => 'integer',
        'has_client_portal' => 'boolean',
        'challenge_early_access_hours' => 'integer',
        'has_judge_access' => 'boolean',
        'support_sla_hours' => 'integer',
        'support_channels' => 'array',
    ];

    public static function getPlanLimits(string $planName, string $planTier): ?self
    {
        return self::where('plan_name', $planName)
            ->where('plan_tier', $planTier)
            ->first();
    }
}
