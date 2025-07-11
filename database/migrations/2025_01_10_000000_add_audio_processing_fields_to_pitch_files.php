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
            // Audio processing status
            $table->boolean('audio_processed')->default(false)->after('duration');
            $table->timestamp('audio_processed_at')->nullable()->after('audio_processed');
            
            // Processed file information
            $table->string('processed_file_path')->nullable()->after('audio_processed_at');
            $table->string('processed_file_name')->nullable()->after('processed_file_path');
            $table->unsignedBigInteger('processed_file_size')->nullable()->after('processed_file_name');
            
            // Processing metadata
            $table->boolean('is_transcoded')->default(false)->after('processed_file_size');
            $table->boolean('is_watermarked')->default(false)->after('is_transcoded');
            $table->string('processed_format')->nullable()->after('is_watermarked');
            $table->string('processed_bitrate')->nullable()->after('processed_format');
            $table->json('processing_metadata')->nullable()->after('processed_bitrate');
            
            // Processing error tracking
            $table->text('processing_error')->nullable()->after('processing_metadata');
            
            // Add indexes for performance
            $table->index(['audio_processed', 'audio_processed_at']);
            $table->index(['is_transcoded', 'is_watermarked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->dropIndex(['audio_processed', 'audio_processed_at']);
            $table->dropIndex(['is_transcoded', 'is_watermarked']);
            
            $table->dropColumn([
                'audio_processed',
                'audio_processed_at',
                'processed_file_path',
                'processed_file_name',
                'processed_file_size',
                'is_transcoded',
                'is_watermarked',
                'processed_format',
                'processed_bitrate',
                'processing_metadata',
                'processing_error'
            ]);
        });
    }
}; 