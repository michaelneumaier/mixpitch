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
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('file_path')->comment('Stored file name for audio');
            $table->string('original_filename')->nullable()->after('file_name')->comment('Original uploaded file name for audio');
            $table->string('mime_type')->nullable()->after('original_filename')->comment('MIME type for audio file');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type')->comment('File size in bytes for audio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->dropColumn(['file_name', 'original_filename', 'mime_type', 'file_size']);
        });
    }
};
