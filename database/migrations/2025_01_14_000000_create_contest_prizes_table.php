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
        if (!Schema::hasTable('contest_prizes')) {
            Schema::create('contest_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->enum('placement', ['1st', '2nd', '3rd', 'runner_up']);
            $table->enum('prize_type', ['cash', 'other']);
            
            // Cash prize fields
            $table->decimal('cash_amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            // Other prize fields
            $table->string('prize_title')->nullable();
            $table->text('prize_description')->nullable();
            $table->decimal('prize_value_estimate', 10, 2)->nullable(); // Optional estimated value
            
            $table->timestamps();
            
            // Ensure only one prize per placement per project
            $table->unique(['project_id', 'placement']);
            
            // Add indexes for better performance
            $table->index(['project_id', 'prize_type']);
            $table->index('placement');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_prizes');
    }
};
