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
        Schema::table('pitch_file_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('user_id');
            $table->foreign('parent_id')->references('id')->on('pitch_file_comments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_file_comments', function (Blueprint $table) {
            // NOTE: SQLite does not support dropping foreign keys.
            // We only drop the column.
            if (Schema::hasColumn('pitch_file_comments', 'parent_id')) {
                $table->dropColumn('parent_id');
            }
        });
    }
};
