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
            $table->foreignId('project_id')->constrained()->after('pitch_id');
            $table->foreignId('user_id')->constrained('users')->after('project_id');
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
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
