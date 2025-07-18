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
        Schema::create('feedback_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Producer who created template (null for system templates)
            $table->string('name'); // Template name
            $table->text('description')->nullable(); // Template description
            $table->json('questions'); // Array of questions with types and options
            $table->string('category')->default('general'); // Category (mixing, mastering, general, etc.)
            $table->boolean('is_default')->default(false); // System default template
            $table->boolean('is_active')->default(true); // Template is active/available
            $table->integer('usage_count')->default(0); // How many times this template has been used
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['category', 'is_default']);
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_templates');
    }
};
