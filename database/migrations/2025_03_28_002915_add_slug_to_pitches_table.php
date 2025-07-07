<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the slug column already exists
        if (!Schema::hasColumn('pitches', 'slug')) {
            Schema::table('pitches', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('id');
            });
        }
        
        // Add the unique constraint - Laravel will handle checking if it exists
        // We'll use a try-catch to handle cases where the index might already exist
        try {
            Schema::table('pitches', function (Blueprint $table) {
                // Create a composite unique index on slug and project_id
                // This ensures slugs are unique within each project
                $table->unique(['project_id', 'slug'], 'project_pitch_slug_unique');
            });
        } catch (\Exception $e) {
            // Index might already exist, which is fine
            // We can safely ignore this error
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            // Try to drop the unique constraint
            try {
                $table->dropUnique('project_pitch_slug_unique');
            } catch (\Exception $e) {
                // Index might not exist, which is fine
            }
            
            // Only drop the column if it exists
            if (Schema::hasColumn('pitches', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};
