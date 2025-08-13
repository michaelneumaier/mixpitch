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
            if (! Schema::hasColumn('pitches', 'watermarking_enabled')) {
                $column = $table->boolean('watermarking_enabled')->default(false);

                if (Schema::hasColumn('pitches', 'payment_hold_released_at')) {
                    $column->after('payment_hold_released_at');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            if (Schema::hasColumn('pitches', 'watermarking_enabled')) {
                $table->dropColumn('watermarking_enabled');
            }
        });
    }
};
