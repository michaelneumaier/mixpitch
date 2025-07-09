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
        Schema::table('pitches', function (Blueprint $table) {
            $table->boolean('audio_processed')->default(false)->after('waveform_processed_at');
            $table->timestamp('audio_processed_at')->nullable()->after('audio_processed');
            $table->json('audio_processing_results')->nullable()->after('audio_processed_at');
            $table->text('audio_processing_error')->nullable()->after('audio_processing_results');
            $table->index(['audio_processed', 'audio_processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            $table->dropIndex(['audio_processed', 'audio_processed_at']);
            $table->dropColumn([
                'audio_processed',
                'audio_processed_at',
                'audio_processing_results',
                'audio_processing_error'
            ]);
        });
    }
};
