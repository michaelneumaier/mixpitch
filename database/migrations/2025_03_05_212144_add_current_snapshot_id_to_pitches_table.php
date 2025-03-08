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
            $table->unsignedBigInteger('current_snapshot_id')->nullable();
            $table->foreign('current_snapshot_id')->references('id')->on('pitch_snapshots')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            $table->dropForeign(['current_snapshot_id']);
            $table->dropColumn('current_snapshot_id');
        });
    }
};
