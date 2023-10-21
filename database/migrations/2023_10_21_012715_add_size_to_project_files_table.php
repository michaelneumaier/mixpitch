<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->bigInteger('size')->after('file_path')->nullable();
        });
    }

    public function down()
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }

};
