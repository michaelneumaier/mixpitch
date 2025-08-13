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
        // Only run this migration on MySQL databases
        if (DB::connection()->getDriverName() === 'mysql') {
            // Change the deadline column from DATE to DATETIME
            DB::statement('ALTER TABLE projects MODIFY COLUMN deadline DATETIME NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run this migration on MySQL databases
        if (DB::connection()->getDriverName() === 'mysql') {
            // Revert the deadline column back to DATE
            DB::statement('ALTER TABLE projects MODIFY COLUMN deadline DATE NULL');
        }
    }
};
