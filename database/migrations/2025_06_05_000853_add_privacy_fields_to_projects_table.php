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
        Schema::table('projects', function (Blueprint $table) {
            // Privacy settings
            $table->boolean('is_private')->default(false)->after('is_published');
            $table->timestamp('privacy_set_at')->nullable()->after('is_private');

            // Enhanced visibility options
            $table->enum('visibility_level', ['public', 'unlisted', 'private', 'invite_only'])
                ->default('public')
                ->after('privacy_set_at');

            // Private project metadata
            $table->json('privacy_settings')->nullable()->after('visibility_level');
            $table->string('access_code', 32)->nullable()->after('privacy_settings');

            // Monthly tracking for subscription limits
            $table->string('privacy_month_year', 7)->nullable()->after('access_code');

            // Indexes for performance
            $table->index(['is_private', 'is_published']);
            $table->index(['visibility_level', 'status']);
            $table->index(['user_id', 'is_private']);
            $table->index(['privacy_month_year', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['is_private', 'is_published']);
            $table->dropIndex(['visibility_level', 'status']);
            $table->dropIndex(['user_id', 'is_private']);
            $table->dropIndex(['privacy_month_year', 'user_id']);

            $table->dropColumn([
                'is_private',
                'privacy_set_at',
                'visibility_level',
                'privacy_settings',
                'access_code',
                'privacy_month_year',
            ]);
        });
    }
};
