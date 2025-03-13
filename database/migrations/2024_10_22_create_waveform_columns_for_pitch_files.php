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
            $table->longText('waveform_peaks')->nullable();
            $table->boolean('waveform_processed')->default(false);
            $table->timestamp('waveform_processed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->dropColumn(['waveform_peaks', 'waveform_processed', 'waveform_processed_at']);
        });
    }
}; 