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
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->unsignedBigInteger('size')->nullable()->after('file_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }
};
