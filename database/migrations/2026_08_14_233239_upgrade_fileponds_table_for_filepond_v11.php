<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * rahulhaque/laravel-filepond v11 renamed the mimetypes column to mimetype
     * and requires metadata, upload_id, and upload_tags columns for its native
     * S3 multipart upload driver.
     */
    public function up(): void
    {
        Schema::table('fileponds', function (Blueprint $table) {
            $table->renameColumn('mimetypes', 'mimetype');
        });

        Schema::table('fileponds', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('mimetype');
            $table->text('upload_id')->nullable()->after('metadata');
            $table->json('upload_tags')->nullable()->after('upload_id');
        });
    }

    public function down(): void
    {
        Schema::table('fileponds', function (Blueprint $table) {
            $table->dropColumn(['metadata', 'upload_id', 'upload_tags']);
        });

        Schema::table('fileponds', function (Blueprint $table) {
            $table->renameColumn('mimetype', 'mimetypes');
        });
    }
};
