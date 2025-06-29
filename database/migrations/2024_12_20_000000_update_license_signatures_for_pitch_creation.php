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
        Schema::table('license_signatures', function (Blueprint $table) {
            // Add revoked_by field (only field that's missing)
            $table->unsignedBigInteger('revoked_by')->nullable()->after('revoked_at');
            
            // Add additional indexes for better performance
            $table->index(['project_id', 'status']);
            $table->index(['user_id', 'status']);
            
            // Foreign key for revoked_by
            $table->foreign('revoked_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_signatures', function (Blueprint $table) {
            $table->dropForeign(['revoked_by']);
            $table->dropIndex(['project_id', 'status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn(['revoked_by']);
        });
    }
}; 