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
        // For SQLite, we need to recreate the table to modify the enum values
        Schema::table('pitches', function (Blueprint $table) {
            // Add a boolean column to indicate if the pitch is inactive
            $table->boolean('is_inactive')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            $table->dropColumn('is_inactive');
        });
    }
};
