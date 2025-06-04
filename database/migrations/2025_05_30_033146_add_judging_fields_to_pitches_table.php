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
            // For now, we'll keep rank as a string and handle ENUM-like validation in the model
            // This avoids the Doctrine DBAL issues with changing column types to ENUM
            $table->string('rank', 20)->nullable()->change();
            
            // Add new judging fields
            $table->text('judging_notes')->nullable()->after('rank');
            $table->timestamp('placement_finalized_at')->nullable()->after('judging_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            // Revert rank field back to integer (if it was previously)
            $table->integer('rank')->nullable()->change();
            
            // Drop the new fields
            $table->dropColumn(['judging_notes', 'placement_finalized_at']);
        });
    }
};
