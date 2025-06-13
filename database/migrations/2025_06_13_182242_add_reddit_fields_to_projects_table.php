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
            $table->string('reddit_post_id')->nullable()->after('slug');
            $table->string('reddit_permalink')->nullable()->after('reddit_post_id');
            $table->timestamp('reddit_posted_at')->nullable()->after('reddit_permalink');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['reddit_post_id', 'reddit_permalink', 'reddit_posted_at']);
        });
    }
};
