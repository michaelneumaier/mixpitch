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
        Schema::table('pitch_snapshots', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('snapshot_data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pitch_snapshots', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
