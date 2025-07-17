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
        Schema::create('file_upload_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->json('value');
            $table->string('context')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('key');
            $table->index('context');
            $table->unique(['key', 'context']);
        });

        // Seed default configuration values
        DB::table('file_upload_settings')->insert([
            [
                'key' => 'max_file_size_mb',
                'value' => json_encode(500),
                'context' => null,
                'description' => 'Maximum file size in MB for uploads',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'chunk_size_mb',
                'value' => json_encode(5),
                'context' => null,
                'description' => 'Default chunk size in MB for chunked uploads',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_concurrent_uploads',
                'value' => json_encode(3),
                'context' => null,
                'description' => 'Maximum number of concurrent uploads per user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_retry_attempts',
                'value' => json_encode(3),
                'context' => null,
                'description' => 'Maximum number of retry attempts for failed chunks',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_file_size_mb',
                'value' => json_encode(200),
                'context' => 'projects',
                'description' => 'Maximum file size in MB for project uploads',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_file_size_mb',
                'value' => json_encode(100),
                'context' => 'pitches',
                'description' => 'Maximum file size in MB for pitch uploads',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_file_size_mb',
                'value' => json_encode(150),
                'context' => 'client_portals',
                'description' => 'Maximum file size in MB for client portal uploads',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_upload_settings');
    }
};
