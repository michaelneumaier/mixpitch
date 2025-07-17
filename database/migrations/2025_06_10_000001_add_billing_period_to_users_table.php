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
            $table->string('billing_period', 20)->default('monthly')->after('subscription_tier');
            $table->decimal('subscription_price', 8, 2)->nullable()->after('billing_period');
            $table->string('subscription_currency', 3)->default('USD')->after('subscription_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['billing_period', 'subscription_price', 'subscription_currency']);
        });
    }
};
