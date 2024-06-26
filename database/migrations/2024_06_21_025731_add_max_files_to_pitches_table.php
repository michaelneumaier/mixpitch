<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('pitches', function (Blueprint $table) {
            $table->integer('max_files')->default(25);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('pitches', function (Blueprint $table) {
            $table->dropColumn('max_files');
        });
    }
};
