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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('username_locked')->default(false)->after('username');
            $table->json('skills')->nullable()->after('social_links');
            $table->json('equipment')->nullable()->after('skills');
            $table->json('specialties')->nullable()->after('equipment');
            $table->text('featured_work')->nullable()->after('specialties');
            $table->string('headline')->nullable()->after('bio');
            $table->string('portfolio_layout')->default('standard')->after('featured_work');
            $table->boolean('profile_completed')->default(false)->after('portfolio_layout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username_locked',
                'skills',
                'equipment',
                'specialties',
                'featured_work',
                'headline',
                'portfolio_layout',
                'profile_completed'
            ]);
        });
    }
};
