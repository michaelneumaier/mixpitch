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
            // Pricing fields
            $table->decimal('monthly_price', 10, 2)->default(0)->after('user_badge');
            $table->decimal('yearly_price', 10, 2)->default(0)->after('monthly_price');
            $table->decimal('yearly_savings', 10, 2)->default(0)->after('yearly_price');
            
            // Display fields
            $table->string('display_name', 100)->nullable()->after('plan_tier');
            $table->text('description')->nullable()->after('display_name');
            $table->boolean('is_most_popular')->default(false)->after('description');
        });
        
        // Handle deprecated columns separately for SQLite compatibility
        if (Schema::hasColumn('subscription_limits', 'monthly_visibility_boosts')) {
            Schema::table('subscription_limits', function (Blueprint $table) {
                $table->dropColumn('monthly_visibility_boosts');
            });
        }
        
        if (Schema::hasColumn('subscription_limits', 'max_private_projects_monthly')) {
            Schema::table('subscription_limits', function (Blueprint $table) {
                $table->dropColumn('max_private_projects_monthly');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_limits', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_price',
                'yearly_price',
                'yearly_savings',
                'display_name',
                'description',
                'is_most_popular'
            ]);
            
            // Re-add deprecated fields
            $table->integer('monthly_visibility_boosts')->default(0);
            $table->integer('max_private_projects_monthly')->nullable();
        });
    }
};
