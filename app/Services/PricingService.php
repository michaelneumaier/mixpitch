<?php

namespace App\Services;

use App\Models\SubscriptionLimit;
use Illuminate\Support\Collection;

class PricingService
{
    /**
     * Get all subscription plans with their features for the pricing page
     */
    public function getAllPlansForPricing(): Collection
    {
        $plans = SubscriptionLimit::orderByRaw("
            CASE 
                WHEN plan_name = 'free' THEN 1
                WHEN plan_name = 'pro' AND plan_tier = 'artist' THEN 2
                WHEN plan_name = 'pro' AND plan_tier = 'engineer' THEN 3
                ELSE 4
            END
        ")->get();

        return $plans->map(function ($plan) {
            return [
                'id' => $plan->id,
                'plan_name' => $plan->plan_name,
                'plan_tier' => $plan->plan_tier,
                'display_name' => $plan->display_name ?: $this->getDefaultDisplayName($plan),
                'description' => $plan->description ?: $this->getDefaultDescription($plan),
                'is_most_popular' => $plan->is_most_popular,
                'monthly_price' => $plan->monthly_price,
                'yearly_price' => $plan->yearly_price,
                'yearly_savings' => $plan->yearly_savings,
                'badge' => $plan->user_badge,
                'features' => $this->getFormattedFeatures($plan),
                'limits' => $plan,
            ];
        });
    }

    /**
     * Get formatted features list for a plan
     */
    protected function getFormattedFeatures(SubscriptionLimit $plan): array
    {
        $features = [];

        // Projects
        if ($plan->max_projects_owned === null) {
            $features[] = 'Unlimited Projects';
        } else {
            $features[] = $plan->max_projects_owned.' Project'.($plan->max_projects_owned !== 1 ? 's' : '');
        }

        // Pitches
        if ($plan->max_active_pitches === null) {
            $features[] = 'Unlimited Pitches';
        } else {
            $features[] = $plan->max_active_pitches.' Active Pitches';
        }

        // Storage
        if ($plan->total_user_storage_gb) {
            $features[] = intval($plan->total_user_storage_gb).'GB Total Storage';
        }

        // Commission
        $features[] = intval($plan->platform_commission_rate).'% Commission Rate';

        // License Templates
        if ($plan->max_license_templates === null) {
            $features[] = 'Unlimited License Templates';
        } elseif ($plan->max_license_templates > 0) {
            $features[] = $plan->max_license_templates.' License '.
                         ($plan->max_license_templates === 1 ? 'Template' : 'Templates');
        } else {
            $features[] = '3 License Presets'; // Default for free plan
        }

        // Analytics
        $analyticsText = match ($plan->analytics_level) {
            'track' => 'Track-level Analytics',
            'client_earnings' => 'Client & Earnings Analytics',
            default => 'Basic Analytics',
        };
        $features[] = $analyticsText;

        // Reputation Multiplier (only if > 1)
        if ($plan->reputation_multiplier > 1) {
            $features[] = $plan->reputation_multiplier.'× Reputation Multiplier';
        }

        // Client Portal
        if ($plan->has_client_portal) {
            $features[] = 'Client Portal Access';
        }

        // Challenge Access
        if ($plan->challenge_early_access_hours > 0) {
            $accessText = $plan->challenge_early_access_hours.'h Early Challenge Access';
            if ($plan->has_judge_access) {
                $accessText .= ' + Judge';
            }
            $features[] = $accessText;
        }

        // Support
        $supportText = $this->getSupportText($plan);
        if ($supportText) {
            $features[] = $supportText;
        }

        return $features;
    }

    /**
     * Get support text based on channels and SLA
     */
    protected function getSupportText(SubscriptionLimit $plan): ?string
    {
        $channels = $plan->support_channels ?? [];

        if (empty($channels)) {
            return null;
        }

        $supportParts = [];

        if (in_array('email', $channels) && in_array('chat', $channels)) {
            $supportParts[] = 'Email & Chat Support';
        } elseif (in_array('email', $channels)) {
            $supportParts[] = 'Email Support';
        } elseif (in_array('forum', $channels)) {
            $supportParts[] = 'Forum Support';
        }

        if (! empty($supportParts) && $plan->support_sla_hours) {
            $supportParts[0] .= ' ('.$plan->support_sla_hours.'h SLA)';
        }

        return $supportParts[0] ?? null;
    }

    /**
     * Get default display name if not set
     */
    protected function getDefaultDisplayName(SubscriptionLimit $plan): string
    {
        if ($plan->plan_name === 'free') {
            return 'Free';
        }

        return ucfirst($plan->plan_name).' '.ucfirst($plan->plan_tier);
    }

    /**
     * Get default description if not set
     */
    protected function getDefaultDescription(SubscriptionLimit $plan): string
    {
        return match ($plan->plan_name.'_'.$plan->plan_tier) {
            'free_basic' => 'Perfect for getting started with music collaboration',
            'pro_artist' => 'For professional music creators',
            'pro_engineer' => 'Advanced tools for audio engineers',
            default => '',
        };
    }

    /**
     * Calculate discount percentage
     */
    public function getYearlyDiscountPercentage(): int
    {
        // This could be made dynamic if needed
        return 17;
    }
}
