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
        // First check if the column doesn't already exist
        if (! Schema::hasColumn('pitch_files', 'file_path')) {
            Schema::table('pitch_files', function (Blueprint $table) {
                $table->string('file_path')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop if the column exists
        if (Schema::hasColumn('pitch_files', 'file_path')) {
            Schema::table('pitch_files', function (Blueprint $table) {
                $table->dropColumn('file_path');
            });
        }
    }
};
