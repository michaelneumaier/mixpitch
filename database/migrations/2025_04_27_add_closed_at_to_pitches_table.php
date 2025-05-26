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
            if (!Schema::hasColumn('pitches', 'closed_at')) {
                $table->timestamp('closed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            if (Schema::hasColumn('pitches', 'closed_at')) {
                $table->dropColumn('closed_at');
            }
        });
    }
}; 