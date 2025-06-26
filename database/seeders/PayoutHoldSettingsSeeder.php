<?php

namespace Database\Seeders;

use App\Models\PayoutHoldSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PayoutHoldSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only create if no settings exist
        if (PayoutHoldSetting::count() === 0) {
            PayoutHoldSetting::create([
                'enabled' => true,
                'default_days' => 1,
                'workflow_days' => [
                    'standard' => 1,           // Standard projects: 1 day hold
                    'contest' => 0,            // Contests: immediate payout
                    'client_management' => 0,   // Client management: immediate payout
                ],
                'business_days_only' => true,
                'processing_time' => '09:00:00',
                'minimum_hold_hours' => 0,
                'allow_admin_bypass' => true,
                'require_bypass_reason' => true,
                'log_bypasses' => true,
            ]);

            $this->command->info('Default payout hold settings created successfully.');
        } else {
            $this->command->info('Payout hold settings already exist. Skipping seeder.');
        }
    }
}
