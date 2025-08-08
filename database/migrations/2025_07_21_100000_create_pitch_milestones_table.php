<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pitch_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pitch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->unsignedInteger('sort_order')->nullable();
            $table->string('status')->default('pending'); // pending, approved, paid, failed
            $table->string('payment_status')->nullable(); // pending, processing, paid, failed, not_required
            $table->string('stripe_invoice_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('payment_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pitch_milestones');
    }
};


