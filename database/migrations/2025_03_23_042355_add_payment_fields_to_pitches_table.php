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
        Schema::table('pitches', function (Blueprint $table) {
            $table->string('payment_status')->nullable()->default(null)->after('status');
            $table->string('final_invoice_id')->nullable()->after('payment_status');
            $table->unsignedInteger('payment_amount')->nullable()->after('final_invoice_id');
            $table->timestamp('payment_completed_at')->nullable()->after('payment_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'final_invoice_id',
                'payment_amount',
                'payment_completed_at'
            ]);
        });
    }
};
