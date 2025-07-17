<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we're handling the status in the application code
        // No database changes needed for enum-like fields in SQLite

        // However, we need to update any existing inactive pitches to have the closed status
        DB::table('pitches')
            ->where('is_inactive', true)
            ->update(['status' => 'closed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes needed for the down migration
    }
};
