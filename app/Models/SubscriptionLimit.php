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
    ];

    protected $casts = [
        'max_projects_owned' => 'integer',
        'max_active_pitches' => 'integer',
        'max_monthly_pitches' => 'integer',
        'storage_per_project_mb' => 'integer',
        'priority_support' => 'boolean',
        'custom_portfolio' => 'boolean',
    ];

    public static function getPlanLimits(string $planName, string $planTier): ?self
    {
        return self::where('plan_name', $planName)
            ->where('plan_tier', $planTier)
            ->first();
    }
}
