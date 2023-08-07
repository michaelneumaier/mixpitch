<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop the unique index first
            $table->dropUnique('projects_slug_unique');

            // Now drop the column
            $table->dropColumn('slug');
        });
    }


};
