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
            // Change the column to be nullable
            // Ensure the foreign key constraint exists before modifying
            // This assumes the foreign key is named pitch_events_created_by_foreign
            // May need adjustment based on actual constraint name if different
            // $table->dropForeign(['created_by']); // Drop FK if necessary (depends on DB)
            $table->foreignId('created_by')->nullable()->change();
            // Re-add FK if dropped
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_events', function (Blueprint $table) {
            // Revert the column to not nullable
            // Note: This might fail if there are existing NULL values.
            // Consider adding logic to handle NULLs before reverting if needed.
            // $table->dropForeign(['created_by']); // Drop FK if necessary
            $table->foreignId('created_by')->nullable(false)->change();
            // Re-add FK if dropped
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade'); // Or original onDelete action
        });
    }
};
