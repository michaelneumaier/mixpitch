<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the is_username_locked column exists and remove it
        if (Schema::hasColumn('users', 'is_username_locked')) {
            // First, copy any data from is_username_locked to username_locked if needed
            DB::statement('UPDATE users SET username_locked = is_username_locked WHERE username_locked = 0 AND is_username_locked = 1');

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_username_locked');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the column if needed for rollback
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_username_locked')->default(false);
        });
    }
};
