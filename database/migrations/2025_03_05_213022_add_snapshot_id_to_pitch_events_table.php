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
        Schema::table('pitch_events', function (Blueprint $table) {
            $table->foreignId('snapshot_id')->nullable()->after('pitch_id')->constrained('pitch_snapshots')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_events', function (Blueprint $table) {
            // NOTE: SQLite does not support dropping foreign keys.
            // We only drop the column.
            if (Schema::hasColumn('pitch_events', 'snapshot_id')) {
                $table->dropColumn('snapshot_id');
            }
        });
    }
};
