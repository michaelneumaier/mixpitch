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
        // Create PayPal onboarding links tracking table
        Schema::create('paypal_onboarding_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tracking_id')->unique();
            $table->text('action_url');
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('merchant_id')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
            $table->index('tracking_id');
        });

        // Add PayPal Commerce specific fields to user_payout_accounts
        Schema::table('user_payout_accounts', function (Blueprint $table) {
            $table->string('paypal_merchant_id')->nullable()->after('metadata');
            $table->string('paypal_onboarding_status')->nullable()->after('paypal_merchant_id');
            $table->json('paypal_permissions')->nullable()->after('paypal_onboarding_status');
            $table->string('paypal_primary_email')->nullable()->after('paypal_permissions');
            $table->boolean('paypal_payments_receivable')->default(false)->after('paypal_primary_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paypal_onboarding_links');

        Schema::table('user_payout_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'paypal_merchant_id',
                'paypal_onboarding_status',
                'paypal_permissions',
                'paypal_primary_email',
                'paypal_payments_receivable',
            ]);
        });
    }
};
