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
        Schema::table('subscription_limits', function (Blueprint $table) {
            $table->dropColumn('file_retention_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_limits', function (Blueprint $table) {
            $table->integer('file_retention_days')->default(30)->after('storage_per_project_gb');
        });
    }
};
