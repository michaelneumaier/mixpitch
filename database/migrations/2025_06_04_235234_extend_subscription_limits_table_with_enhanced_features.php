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
        Schema::table('subscription_limits', function (Blueprint $table) {
            // Storage & File Management
            $table->decimal('storage_per_project_gb', 5, 2)->default(1.00)->after('custom_portfolio');
            $table->integer('file_retention_days')->default(30)->after('storage_per_project_gb');
            
            // Business Features  
            $table->decimal('platform_commission_rate', 4, 2)->default(10.00)->after('file_retention_days');
            $table->integer('max_license_templates')->nullable()->after('platform_commission_rate');
            
            // Engagement Features
            $table->integer('monthly_visibility_boosts')->default(0)->after('max_license_templates');
            $table->decimal('reputation_multiplier', 3, 2)->default(1.00)->after('monthly_visibility_boosts');
            $table->integer('max_private_projects_monthly')->nullable()->after('reputation_multiplier');
            
            // Access & Support Features
            $table->boolean('has_client_portal')->default(false)->after('max_private_projects_monthly');
            $table->enum('analytics_level', ['basic', 'track', 'client_earnings'])->default('basic')->after('has_client_portal');
            $table->integer('challenge_early_access_hours')->default(0)->after('analytics_level');
            $table->boolean('has_judge_access')->default(false)->after('challenge_early_access_hours');
            $table->integer('support_sla_hours')->nullable()->after('has_judge_access');
            $table->json('support_channels')->nullable()->after('support_sla_hours');
            $table->string('user_badge', 10)->nullable()->after('support_channels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_limits', function (Blueprint $table) {
            $table->dropColumn([
                'storage_per_project_gb',
                'file_retention_days',
                'platform_commission_rate',
                'max_license_templates',
                'monthly_visibility_boosts',
                'reputation_multiplier',
                'max_private_projects_monthly',
                'has_client_portal',
                'analytics_level',
                'challenge_early_access_hours',
                'has_judge_access',
                'support_sla_hours',
                'support_channels',
                'user_badge'
            ]);
        });
    }
};
