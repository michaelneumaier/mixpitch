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
        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_plan', 50)->default('free')->after('role');
            $table->string('subscription_tier', 50)->default('basic')->after('subscription_plan');
            $table->timestamp('plan_started_at')->nullable()->after('subscription_tier');
            $table->integer('monthly_pitch_count')->default(0)->after('plan_started_at');
            $table->date('monthly_pitch_reset_date')->nullable()->after('monthly_pitch_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'subscription_tier', 
                'plan_started_at',
                'monthly_pitch_count',
                'monthly_pitch_reset_date'
            ]);
        });
    }
};
