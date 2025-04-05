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
        Schema::dropIfExists('tracks');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If needed, recreate the table structure here based on the old migration
        // Schema::create('tracks', function (Blueprint $table) {
        //     $table->id();
        //     // Add other columns from the original migration if necessary
        //     $table->timestamps();
        // });
        // For now, we assume no rollback is needed for this cleanup migration.
    }
};
