<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Raise the free plan's active project limit from 1 to 3 so casual
     * users are not immediately hit with an upgrade banner after
     * creating a single project. Pro tiers remain unlimited.
     */
    public function up(): void
    {
        DB::table('subscription_limits')
            ->where('plan_name', 'free')
            ->where('plan_tier', 'basic')
            ->where('max_projects_owned', 1)
            ->update(['max_projects_owned' => 3]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('subscription_limits')
            ->where('plan_name', 'free')
            ->where('plan_tier', 'basic')
            ->where('max_projects_owned', 3)
            ->update(['max_projects_owned' => 1]);
    }
};
