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
        Schema::create('taggables', function (Blueprint $table) {
            $table->unsignedBigInteger('tag_id');
            $table->unsignedBigInteger('taggable_id');
            $table->string('taggable_type');

            // Define foreign key constraint for tag_id
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');

            // Create indexes for efficient lookups
            $table->index(['taggable_id', 'taggable_type']);
            $table->primary(['tag_id', 'taggable_id', 'taggable_type']); // Composite primary key
            
            // No timestamps needed for a typical pivot table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
