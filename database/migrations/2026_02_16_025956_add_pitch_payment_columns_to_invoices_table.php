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
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'pitch_id')) {
                $table->foreignId('pitch_id')->nullable()->after('order_id')->constrained()->onDelete('cascade');
            }
            if (! Schema::hasColumn('invoices', 'description')) {
                $table->string('description')->nullable()->after('currency');
            }
            if (! Schema::hasColumn('invoices', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id')->nullable()->after('stripe_invoice_id');
            }
            if (! Schema::hasColumn('invoices', 'stripe_checkout_session_id')) {
                $table->string('stripe_checkout_session_id')->nullable()->after('stripe_payment_intent_id');
            }
            if (! Schema::hasColumn('invoices', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['pitch_id']);
            $table->dropColumn(['pitch_id', 'description', 'stripe_payment_intent_id', 'stripe_checkout_session_id', 'deleted_at']);
        });
    }
};
