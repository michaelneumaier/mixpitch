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
        Schema::table('project_types', function (Blueprint $table) {
            $table->string('icon', 100)->nullable()->after('description'); // FontAwesome icon class
            $table->string('color', 50)->default('blue')->after('icon'); // Tailwind color name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_types', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color']);
        });
    }
};
