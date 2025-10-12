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
            // Parent file ID for version tracking (null for root/original files)
            $table->foreignId('parent_file_id')
                ->nullable()
                ->after('pitch_id')
                ->constrained('pitch_files')
                ->onDelete('cascade');

            // Version number within file family (1 for original, 2+ for new versions)
            $table->integer('file_version_number')
                ->default(1)
                ->after('parent_file_id');

            // Indexes for efficient version queries
            $table->index(['parent_file_id', 'file_version_number']);
            $table->index(['pitch_id', 'parent_file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->dropIndex(['pitch_id', 'parent_file_id']);
            $table->dropIndex(['parent_file_id', 'file_version_number']);
            $table->dropForeign(['parent_file_id']);
            $table->dropColumn(['parent_file_id', 'file_version_number']);
        });
    }
};
