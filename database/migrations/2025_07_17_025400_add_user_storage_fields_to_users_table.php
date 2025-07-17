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
            $table->bigInteger('total_storage_used')->default(0)->after('subscription_currency');
            $table->decimal('storage_limit_override_gb', 10, 2)->nullable()->after('total_storage_used');
            
            // Add index for performance
            $table->index('total_storage_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['total_storage_used']);
            $table->dropColumn(['total_storage_used', 'storage_limit_override_gb']);
        });
    }
};
