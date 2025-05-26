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
            $table->timestamp('completion_date')->nullable();
            $table->text('completion_feedback')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = ['completion_date', 'completion_feedback'];
        // Drop columns separately for SQLite
        foreach ($columns as $column) {
            if (Schema::hasColumn('pitches', $column)) {
                Schema::table('pitches', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
