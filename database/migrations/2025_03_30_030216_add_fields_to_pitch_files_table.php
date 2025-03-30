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
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->dropColumn('original_file_name');
            $table->dropColumn('mime_type');
            $table->dropColumn('storage_path');
        });
    }
};
