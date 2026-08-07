<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('reddit_username')->nullable()->index();
            $table->string('reddit_user_id')->nullable()->unique();
            $table->timestamp('reddit_account_created_at')->nullable();
            $table->timestamp('reddit_linked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['reddit_user_id']);
            $table->dropIndex(['reddit_username']);
            $table->dropColumn([
                'reddit_username',
                'reddit_user_id',
                'reddit_account_created_at',
                'reddit_linked_at',
            ]);
        });
    }
};
