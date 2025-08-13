<?php

namespace Database\Seeders;

use App\Models\SubscriptionLimit;
use Illuminate\Database\Seeder;

class CompleteSubscriptionLimitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            // Free Plan (Basic Tier)
            [
                'plan_name' => 'free',
                'plan_tier' => 'basic',
                'display_name' => 'Free',
                'description' => 'Perfect for getting started with music collaboration',
                'is_most_popular' => false,
                'monthly_price' => 0,
                'yearly_price' => 0,
                'yearly_savings' => 0,
                'max_projects_owned' => 1,
                'max_active_pitches' => 3,
                'max_monthly_pitches' => null,
                'storage_per_project_mb' => 100, // Legacy field (keeping for compatibility)
                'storage_per_project_gb' => 1.0,
                'total_user_storage_gb' => 10.0,
                'platform_commission_rate' => 10.0,
                'max_license_templates' => 0, // 0 means presets only
                'reputation_multiplier' => 1.0,
                'has_client_portal' => false,
                'analytics_level' => 'basic',
                'challenge_early_access_hours' => 0,
                'has_judge_access' => false,
                'support_sla_hours' => null,
                'support_channels' => ['forum'],
                'user_badge' => null,
                'priority_support' => false,
                'custom_portfolio' => false,
            ],

            // Pro Artist Plan
            [
                'plan_name' => 'pro',
                'plan_tier' => 'artist',
                'display_name' => 'Pro Artist',
                'description' => 'For professional music creators',
                'is_most_popular' => true,
                'monthly_price' => 6.99,
                'yearly_price' => 69.99,
                'yearly_savings' => 13.89,
                'max_projects_owned' => null, // unlimited
                'max_active_pitches' => null, // unlimited
                'max_monthly_pitches' => null,
                'storage_per_project_mb' => 5120, // Legacy field (5GB in MB)
                'storage_per_project_gb' => 5.0,
                'total_user_storage_gb' => 50.0,
                'platform_commission_rate' => 8.0,
                'max_license_templates' => null, // unlimited custom templates
                'reputation_multiplier' => 1.0,
                'has_client_portal' => false,
                'analytics_level' => 'track',
                'challenge_early_access_hours' => 24,
                'has_judge_access' => false,
                'support_sla_hours' => 48,
                'support_channels' => ['email'],
                'user_badge' => '🔷',
                'priority_support' => true,
                'custom_portfolio' => true,
            ],

            // Pro Engineer Plan
            [
                'plan_name' => 'pro',
                'plan_tier' => 'engineer',
                'display_name' => 'Pro Engineer',
                'description' => 'Advanced tools for audio engineers',
                'is_most_popular' => false,
                'monthly_price' => 9.99,
                'yearly_price' => 99.99,
                'yearly_savings' => 19.89,
                'max_projects_owned' => null, // unlimited
                'max_active_pitches' => null, // unlimited
                'max_monthly_pitches' => null,
                'storage_per_project_mb' => 10240, // Legacy field (10GB in MB)
                'storage_per_project_gb' => 10.0,
                'total_user_storage_gb' => 200.0,
                'platform_commission_rate' => 6.0,
                'max_license_templates' => null, // unlimited custom templates
                'reputation_multiplier' => 1.25,
                'has_client_portal' => true,
                'analytics_level' => 'client_earnings',
                'challenge_early_access_hours' => 24,
                'has_judge_access' => true,
                'support_sla_hours' => 24,
                'support_channels' => ['email', 'chat'],
                'user_badge' => '🔶',
                'priority_support' => true,
                'custom_portfolio' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionLimit::updateOrCreate(
                [
                    'plan_name' => $plan['plan_name'],
                    'plan_tier' => $plan['plan_tier'],
                ],
                $plan
            );
        }

        $this->command->info('✅ Subscription limits seeded successfully!');
        $this->command->info('📊 Plans created:');
        $this->command->info('   • Free ($0/mo) - 10GB storage, 10% commission');
        $this->command->info('   • Pro Artist ($6.99/mo) - 50GB storage, 8% commission, track analytics');
        $this->command->info('   • Pro Engineer ($9.99/mo) - 200GB storage, 6% commission, client portal');
    }
}
