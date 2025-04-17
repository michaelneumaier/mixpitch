<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Import DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('original_file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('storage_path')->nullable();
            $table->boolean('is_preview_track')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = 'project_files';
        $connection = Schema::getConnection()->getName();

        if ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off;');
        }

        // Separate dropColumn calls for SQLite compatibility
        // Drop foreign key first if exists
        if (Schema::hasColumn($tableName, 'user_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                 // For broader compatibility, let's just drop the column. SQLite handles FK constraints differently.
                 $table->dropColumn('user_id');
            });
        }
        
        // Drop other columns individually
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
        if (Schema::hasColumn($tableName, 'is_preview_track')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('is_preview_track');
            });
        }

        if ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=on;');
        }
    }
};
