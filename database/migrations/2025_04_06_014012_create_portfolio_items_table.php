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
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Link to users table
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('item_type', ['audio_upload', 'external_link', 'mixpitch_project_link']);
            $table->string('file_path')->nullable(); // For audio_upload type
            $table->string('external_url')->nullable(); // For external_link type
            $table->foreignId('linked_project_id')->nullable()->constrained('projects')->onDelete('set null'); // Link to projects table
            $table->integer('display_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
    }
};
