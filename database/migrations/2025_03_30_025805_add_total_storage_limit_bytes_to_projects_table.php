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
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'total_storage_limit_bytes')) {
                $table->unsignedBigInteger('total_storage_limit_bytes')->default(100 * 1024 * 1024); // Default 100MB
            }
        });

        Schema::table('pitches', function (Blueprint $table) {
            if (! Schema::hasColumn('pitches', 'total_storage_limit_bytes')) {
                $table->unsignedBigInteger('total_storage_limit_bytes')->default(50 * 1024 * 1024); // Default 50MB
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'total_storage_limit_bytes')) {
                $table->dropColumn('total_storage_limit_bytes');
            }
        });

        Schema::table('pitches', function (Blueprint $table) {
            if (Schema::hasColumn('pitches', 'total_storage_limit_bytes')) {
                $table->dropColumn('total_storage_limit_bytes');
            }
        });
    }
};
