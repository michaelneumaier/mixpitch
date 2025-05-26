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
        // Drop columns separately for SQLite (dropForeign is not supported)
        $columns = ['user_id', 'project_id']; // Drop user_id first potentially?
        foreach ($columns as $column) {
            if (Schema::hasColumn('pitch_snapshots', $column)) {
                Schema::table('pitch_snapshots', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
