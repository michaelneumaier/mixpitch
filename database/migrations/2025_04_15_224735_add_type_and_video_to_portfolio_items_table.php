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
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->string('type')->default('audio')->after('user_id')->comment('Type of portfolio item (audio, youtube)');
            $table->text('video_url')->nullable()->after('description')->comment('Full URL for video embeds');
            $table->string('video_id')->nullable()->after('video_url')->comment('Extracted video ID for embedding');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->dropColumn(['type', 'video_url', 'video_id']);
        });
    }
};
