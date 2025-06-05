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
        Schema::create('license_templates', function (Blueprint $table) {
            $table->id();
            
            // User relationship
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Template details
            $table->string('name', 100); // Template name
            $table->text('content'); // License text content
            $table->boolean('is_default')->default(false); // Is this a default template for the user
            $table->boolean('is_active')->default(true); // Can this template be used
            
            // License terms as structured data
            $table->json('terms')->nullable(); // Commercial use, attribution, etc.
            
            // Template metadata
            $table->string('category', 50)->nullable(); // music, sound-design, mixing, etc.
            $table->text('description')->nullable(); // Description of when to use this template
            $table->json('usage_stats')->nullable(); // Track how often this template is used
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'is_default']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_templates');
    }
};
