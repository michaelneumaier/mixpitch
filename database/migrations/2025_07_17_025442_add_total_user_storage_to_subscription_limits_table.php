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
            $table->decimal('total_user_storage_gb', 10, 2)->default(10.0)->after('storage_per_project_gb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_limits', function (Blueprint $table) {
            $table->dropColumn('total_user_storage_gb');
        });
    }
};
