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
        
        // Check if the unique constraint exists
        $indexExists = collect(DB::select("PRAGMA index_list('pitches')"))->pluck('name')->contains('project_pitch_slug_unique');
        
        if (!$indexExists) {
            Schema::table('pitches', function (Blueprint $table) {
                // Create a composite unique index on slug and project_id
                // This ensures slugs are unique within each project
                $table->unique(['project_id', 'slug'], 'project_pitch_slug_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            // Remove the unique constraint if it exists
            if (Schema::hasTable('pitches')) {
                $indexExists = collect(DB::select("PRAGMA index_list('pitches')"))->pluck('name')->contains('project_pitch_slug_unique');
                if ($indexExists) {
                    $table->dropUnique('project_pitch_slug_unique');
                }
            }
            
            // Only drop the column if it exists
            if (Schema::hasColumn('pitches', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};
