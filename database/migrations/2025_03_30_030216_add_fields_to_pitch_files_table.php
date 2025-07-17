<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->string('original_file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('storage_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = 'pitch_files';
        $connection = Schema::getConnection()->getName();

        if ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off;');
        }

        // Separate dropColumn calls for SQLite compatibility
        // Check if column exists before attempting to drop
        if (Schema::hasColumn($tableName, 'original_file_name')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('original_file_name');
            });
        }

        if (Schema::hasColumn($tableName, 'mime_type')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('mime_type');
            });
        }

        if (Schema::hasColumn($tableName, 'storage_path')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('storage_path');
            });
        }

        if ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=on;');
        }
    }
};
