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
        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // "Single", "Album", "Remix"
            $table->string('slug', 50)->unique(); // "single", "album", "remix" 
            $table->string('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true); // Allow disabling types
            $table->integer('sort_order')->default(0); // Control display order
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_types');
    }
};
