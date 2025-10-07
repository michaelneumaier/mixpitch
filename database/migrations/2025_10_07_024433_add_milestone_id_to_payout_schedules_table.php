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
        Schema::table('payout_schedules', function (Blueprint $table) {
            // Add milestone_id column to track milestone-based payouts
            $table->unsignedBigInteger('pitch_milestone_id')->nullable()->after('pitch_id');

            // Add index for faster queries
            $table->index('pitch_milestone_id');

            // Add foreign key constraint
            $table->foreign('pitch_milestone_id')
                ->references('id')
                ->on('pitch_milestones')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payout_schedules', function (Blueprint $table) {
            $table->dropForeign(['pitch_milestone_id']);
            $table->dropIndex(['pitch_milestone_id']);
            $table->dropColumn('pitch_milestone_id');
        });
    }
};
