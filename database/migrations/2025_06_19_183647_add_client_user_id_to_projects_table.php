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
            $table->unsignedBigInteger('client_user_id')->nullable()->after('client_email');
            $table->foreign('client_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['client_user_id', 'workflow_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['client_user_id']);
            $table->dropIndex(['client_user_id', 'workflow_type']);
            $table->dropColumn('client_user_id');
        });
    }
};
