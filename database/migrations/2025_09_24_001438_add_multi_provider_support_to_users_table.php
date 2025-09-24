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
            // Add new payout provider fields
            $table->string('preferred_payout_method')->default('stripe')->after('stripe_account_id');
            $table->string('paypal_account_id')->nullable()->after('preferred_payout_method');
            $table->string('paypal_merchant_id')->nullable()->after('paypal_account_id');
            $table->string('wise_account_id')->nullable()->after('paypal_merchant_id');
            $table->string('dwolla_account_id')->nullable()->after('wise_account_id');

            // Add indexes for performance
            $table->index('preferred_payout_method');
            $table->index('paypal_account_id');
            $table->index('wise_account_id');
            $table->index('dwolla_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['preferred_payout_method']);
            $table->dropIndex(['paypal_account_id']);
            $table->dropIndex(['wise_account_id']);
            $table->dropIndex(['dwolla_account_id']);

            $table->dropColumn([
                'preferred_payout_method',
                'paypal_account_id',
                'paypal_merchant_id',
                'wise_account_id',
                'dwolla_account_id',
            ]);
        });
    }
};
