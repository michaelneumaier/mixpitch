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
        Schema::table('pitch_file_comments', function (Blueprint $table) {
            $table->string('client_email')->nullable()->after('user_id');
            $table->boolean('is_client_comment')->default(false)->after('is_resolved');
            $table->index(['pitch_file_id', 'is_client_comment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_file_comments', function (Blueprint $table) {
            $table->dropIndex(['pitch_file_id', 'is_client_comment']);
            $table->dropColumn(['client_email', 'is_client_comment']);
        });
    }
};
