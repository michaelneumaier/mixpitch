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
        Schema::table('pitch_milestones', function (Blueprint $table) {
            // Link revision milestones to the snapshot that triggered their creation
            // This enables snapshot-based file access control for paid revisions
            $table->foreignId('pitch_snapshot_id')->nullable()->after('pitch_id')->constrained('pitch_snapshots')->nullOnDelete();

            $table->index('pitch_snapshot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_milestones', function (Blueprint $table) {
            $table->dropForeign(['pitch_snapshot_id']);
            $table->dropIndex(['pitch_snapshot_id']);
            $table->dropColumn('pitch_snapshot_id');
        });
    }
};
