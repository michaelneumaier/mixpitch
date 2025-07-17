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
            // Check if column doesn't exist
            if (! Schema::hasColumn('pitches', 'title')) {
                $table->string('title')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            if (Schema::hasColumn('pitches', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};
