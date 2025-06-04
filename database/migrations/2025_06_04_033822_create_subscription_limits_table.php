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
        Schema::create('subscription_limits', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name', 50);
            $table->string('plan_tier', 50);
            $table->integer('max_projects_owned')->nullable(); // NULL = unlimited
            $table->integer('max_active_pitches')->nullable(); // NULL = unlimited
            $table->integer('max_monthly_pitches')->nullable(); // For Pro Engineer
            $table->integer('storage_per_project_mb')->default(100);
            $table->boolean('priority_support')->default(false);
            $table->boolean('custom_portfolio')->default(false);
            $table->timestamps();

            $table->unique(['plan_name', 'plan_tier'], 'unique_plan_tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_limits');
    }
};
