<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SubscriptionLimit;

class SubscriptionLimitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $limits = [
            [
                'plan_name' => 'free',
                'plan_tier' => 'basic',
                'max_projects_owned' => 1,
                'max_active_pitches' => 3,
                'max_monthly_pitches' => null,
                'storage_per_project_mb' => 100,
                'priority_support' => false,
                'custom_portfolio' => false,
            ],
            [
                'plan_name' => 'pro',
                'plan_tier' => 'artist',
                'max_projects_owned' => null, // unlimited
                'max_active_pitches' => null, // unlimited
                'max_monthly_pitches' => null,
                'storage_per_project_mb' => 500,
                'priority_support' => true,
                'custom_portfolio' => true,
            ],
            [
                'plan_name' => 'pro',
                'plan_tier' => 'engineer',
                'max_projects_owned' => null, // unlimited
                'max_active_pitches' => null, // unlimited
                'max_monthly_pitches' => 5,
                'storage_per_project_mb' => 500,
                'priority_support' => true,
                'custom_portfolio' => true,
            ],
        ];

        foreach ($limits as $limit) {
            SubscriptionLimit::updateOrCreate(
                [
                    'plan_name' => $limit['plan_name'],
                    'plan_tier' => $limit['plan_tier'],
                ],
                $limit
            );
        }
    }
}
