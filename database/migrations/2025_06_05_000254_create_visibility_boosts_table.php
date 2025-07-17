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
        Schema::create('visibility_boosts', function (Blueprint $table) {
            $table->id();

            // User and target relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('pitch_id')->nullable()->constrained()->onDelete('cascade');

            // Boost details
            $table->enum('boost_type', ['project', 'pitch', 'profile'])->default('project');
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');

            // Boost effectiveness tracking
            $table->integer('views_before')->default(0); // Views before boost
            $table->integer('views_during')->default(0); // Views gained during boost
            $table->decimal('ranking_multiplier', 3, 2)->default(2.0); // Ranking boost multiplier

            // Monthly tracking for limits
            $table->string('month_year', 7); // Format: '2024-12'

            // Metadata
            $table->json('metadata')->nullable(); // Additional boost configuration

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'month_year']);
            $table->index(['project_id', 'status']);
            $table->index(['pitch_id', 'status']);
            $table->index(['expires_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visibility_boosts');
    }
};
