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
        Schema::create('user_monthly_limits', function (Blueprint $table) {
            $table->id();
            
            // User and time period
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('month_year', 7); // Format: '2024-12'
            
            // Usage tracking
            $table->integer('visibility_boosts_used')->default(0);
            $table->integer('private_projects_created')->default(0);
            $table->integer('license_templates_created')->default(0);
            
            // Additional tracking (extensible)
            $table->json('additional_usage')->nullable(); // For future features
            
            // Reset tracking
            $table->timestamp('last_reset_at')->nullable();
            $table->boolean('auto_reset_enabled')->default(true);
            
            $table->timestamps();
            
            // Unique constraint to prevent duplicates
            $table->unique(['user_id', 'month_year']);
            
            // Indexes
            $table->index(['user_id', 'month_year']);
            $table->index('month_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_monthly_limits');
    }
};
