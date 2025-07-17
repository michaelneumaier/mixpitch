<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create payout hold settings table for dynamic configuration
        Schema::create('payout_hold_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->integer('default_days')->default(1);
            $table->json('workflow_days'); // {"standard": 1, "contest": 0, "client_management": 0}
            $table->boolean('business_days_only')->default(true);
            $table->time('processing_time')->default('09:00:00');
            $table->integer('minimum_hold_hours')->default(0);
            $table->boolean('allow_admin_bypass')->default(true);
            $table->boolean('require_bypass_reason')->default(true);
            $table->boolean('log_bypasses')->default(true);
            $table->timestamps();

            // Create an index for faster lookups
            $table->index('enabled');
        });

        // Add bypass tracking fields to existing payout_schedules table
        Schema::table('payout_schedules', function (Blueprint $table) {
            $table->boolean('hold_bypassed')->default(false)->after('hold_release_date');
            $table->text('bypass_reason')->nullable()->after('hold_bypassed');
            $table->unsignedBigInteger('bypass_admin_id')->nullable()->after('bypass_reason');
            $table->timestamp('bypassed_at')->nullable()->after('bypass_admin_id');

            // Add foreign key constraint for bypass admin
            $table->foreign('bypass_admin_id')->references('id')->on('users')->onDelete('set null');

            // Add indexes for performance
            $table->index('hold_bypassed');
            $table->index('bypassed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove bypass tracking fields from payout_schedules table
        Schema::table('payout_schedules', function (Blueprint $table) {
            $table->dropForeign(['bypass_admin_id']);
            $table->dropIndex(['hold_bypassed']);
            $table->dropIndex(['bypassed_at']);
            $table->dropColumn([
                'hold_bypassed',
                'bypass_reason',
                'bypass_admin_id',
                'bypassed_at',
            ]);
        });

        // Drop payout hold settings table
        Schema::dropIfExists('payout_hold_settings');
    }
};
