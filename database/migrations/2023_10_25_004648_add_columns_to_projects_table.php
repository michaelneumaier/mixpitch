<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('artist_name')->nullable()->default('');
            $table->string('project_type')->default('');
            $table->json('collaboration_type')->default('[]');
            $table->integer('budget')->default(0);
            $table->date('deadline')->nullable();
            $table->string('preview_track')->nullable()->default('');
            $table->text('notes')->nullable()->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = 'projects';
        $columns = [
            'artist_name',
            'project_type',
            'collaboration_type',
            'budget',
            'deadline',
            'preview_track',
            'notes',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
}
