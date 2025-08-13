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
        'display_name',
        'description',
        'is_most_popular',
        'max_projects_owned',
        'max_active_pitches',
        'max_monthly_pitches',
        'storage_per_project_mb',
        'priority_support',
        'custom_portfolio',
        // Enhanced features
        'storage_per_project_gb',
        'total_user_storage_gb',
        'platform_commission_rate',
        'max_license_templates',
        'reputation_multiplier',
        'has_client_portal',
        'analytics_level',
        'challenge_early_access_hours',
        'has_judge_access',
        'support_sla_hours',
        'support_channels',
        'user_badge',
        // Pricing
        'monthly_price',
        'yearly_price',
        'yearly_savings',
    ];

    protected $casts = [
        'max_projects_owned' => 'integer',
        'max_active_pitches' => 'integer',
        'max_monthly_pitches' => 'integer',
        'storage_per_project_mb' => 'integer',
        'priority_support' => 'boolean',
        'custom_portfolio' => 'boolean',
        'is_most_popular' => 'boolean',
        // Enhanced features casts
        'storage_per_project_gb' => 'decimal:2',
        'total_user_storage_gb' => 'decimal:2',
        'platform_commission_rate' => 'decimal:2',
        'max_license_templates' => 'integer',
        'reputation_multiplier' => 'decimal:2',
        'has_client_portal' => 'boolean',
        'challenge_early_access_hours' => 'integer',
        'has_judge_access' => 'boolean',
        'support_sla_hours' => 'integer',
        'support_channels' => 'array',
        // Pricing casts
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'yearly_savings' => 'decimal:2',
    ];

    public static function getPlanLimits(string $planName, string $planTier): ?self
    {
        return self::where('plan_name', $planName)
            ->where('plan_tier', $planTier)
            ->first();
    }
}
