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
        Schema::table('project_files', function (Blueprint $table) {
            // Add duration column for audio files (nullable as not all project files are audio)
            $table->float('duration')->nullable()->after('mime_type')
                ->comment('Duration in seconds for audio files');
            
            // Add index for faster queries on audio files with duration
            $table->index(['duration']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex(['duration']);
            
            // Drop the column
            $table->dropColumn('duration');
        });
    }
};
