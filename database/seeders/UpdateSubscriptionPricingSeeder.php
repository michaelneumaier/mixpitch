<?php

namespace Database\Seeders;

use App\Models\SubscriptionLimit;
use Illuminate\Database\Seeder;

class UpdateSubscriptionPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update Free plan
        SubscriptionLimit::updateOrCreate(
            [
                'plan_name' => 'free',
                'plan_tier' => 'basic',
            ],
            [
                'display_name' => 'Free',
                'description' => 'Perfect for getting started with music collaboration',
                'is_most_popular' => false,
                'monthly_price' => 0,
                'yearly_price' => 0,
                'yearly_savings' => 0,
                'max_projects_owned' => 1,
                'max_active_pitches' => 3,
                'total_user_storage_gb' => 10,
                'platform_commission_rate' => 10.0,
                'max_license_templates' => 0, // 0 means presets only
                'analytics_level' => 'basic',
                'reputation_multiplier' => 1.0,
                'has_client_portal' => false,
                'challenge_early_access_hours' => 0,
                'has_judge_access' => false,
                'support_channels' => ['forum'],
                'support_sla_hours' => null,
                'user_badge' => null,
            ]
        );

        // Update Pro Artist plan
        SubscriptionLimit::updateOrCreate(
            [
                'plan_name' => 'pro',
                'plan_tier' => 'artist',
            ],
            [
                'display_name' => 'Pro Artist',
                'description' => 'For professional music creators',
                'is_most_popular' => true, // Mark as most popular
                'monthly_price' => 6.99,
                'yearly_price' => 69.99,
                'yearly_savings' => 13.89,
                'max_projects_owned' => null, // null means unlimited
                'max_active_pitches' => null,
                'total_user_storage_gb' => 50,
                'platform_commission_rate' => 8.0,
                'max_license_templates' => null, // unlimited custom
                'analytics_level' => 'track',
                'reputation_multiplier' => 1.0,
                'has_client_portal' => false,
                'challenge_early_access_hours' => 24,
                'has_judge_access' => false,
                'support_channels' => ['email'],
                'support_sla_hours' => 48,
                'user_badge' => '🔷',
            ]
        );

        // Update Pro Engineer plan
        SubscriptionLimit::updateOrCreate(
            [
                'plan_name' => 'pro',
                'plan_tier' => 'engineer',
            ],
            [
                'display_name' => 'Pro Engineer',
                'description' => 'Advanced tools for audio engineers',
                'is_most_popular' => false,
                'monthly_price' => 9.99,
                'yearly_price' => 99.99,
                'yearly_savings' => 19.89,
                'max_projects_owned' => null,
                'max_active_pitches' => null,
                'total_user_storage_gb' => 200,
                'platform_commission_rate' => 6.0,
                'max_license_templates' => null,
                'analytics_level' => 'client_earnings',
                'reputation_multiplier' => 1.25,
                'has_client_portal' => true,
                'challenge_early_access_hours' => 24,
                'has_judge_access' => true,
                'support_channels' => ['email', 'chat'],
                'support_sla_hours' => 24,
                'user_badge' => '🔶',
            ]
        );

        $this->command->info('Subscription pricing data has been updated successfully!');
    }
}
