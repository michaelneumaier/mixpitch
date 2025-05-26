<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Removes the direct tag columns from the users table as we're now using
     * the polymorphic taggables relationship exclusively.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // $table->dropColumn(['skills', 'equipment', 'specialties']);
        });
    }

    /**
     * Reverse the migrations.
     * Adds back the direct tag columns to the users table if needed.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('skills')->nullable()->after('social_links');
            $table->json('equipment')->nullable()->after('skills');
            $table->json('specialties')->nullable()->after('equipment');
        });
    }
};
