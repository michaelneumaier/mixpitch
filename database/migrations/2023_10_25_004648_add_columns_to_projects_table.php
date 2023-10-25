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
            $table->json('collaboration_type')->default('');
            $table->integer('budget')->default('');
            $table->date('deadline')->default('');
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
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('artist_name');
            $table->dropColumn('project_type');
            $table->dropColumn('collaboration_type');
            $table->dropColumn('budget');
            $table->dropColumn('deadline');
            $table->dropColumn('preview_track');
            $table->dropColumn('notes');
        });
    }
}
