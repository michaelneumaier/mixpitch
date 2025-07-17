<?php

namespace Database\Seeders;

use App\Models\SubscriptionLimit;
use Illuminate\Database\Seeder;

class UpdateSubscriptionLimitsWithUserStorageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update Free tier: 10GB total
        SubscriptionLimit::where('plan_name', 'free')
            ->where('plan_tier', 'basic')
            ->update(['total_user_storage_gb' => 10.0]);

        // Update Pro Artist tier: 50GB total
        SubscriptionLimit::where('plan_name', 'pro')
            ->where('plan_tier', 'artist')
            ->update(['total_user_storage_gb' => 50.0]);

        // Update Pro Engineer tier: 200GB total
        SubscriptionLimit::where('plan_name', 'pro')
            ->where('plan_tier', 'engineer')
            ->update(['total_user_storage_gb' => 200.0]);

        $this->command->info('Updated subscription limits with new user storage allocations:');
        $this->command->info('- Free: 10GB');
        $this->command->info('- Pro Artist: 50GB');
        $this->command->info('- Pro Engineer: 200GB');
    }
}
