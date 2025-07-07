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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_package_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_user_id')->constrained('users')->onDelete('cascade'); // Buyer
            $table->foreignId('producer_user_id')->constrained('users')->onDelete('cascade'); // Seller (denormalized for easier lookup)
            $table->string('status'); // e.g., pending_requirements, in_progress, completed
            $table->decimal('price', 10, 2); // Price at time of order
            $table->string('currency', 3);
            $table->string('payment_status')->default('pending'); // e.g., pending, paid, failed, refunded
            $table->unsignedBigInteger('invoice_id')->nullable(); // Will add foreign key constraint later
            $table->text('requirements_submitted')->nullable(); // Client's input
            $table->unsignedInteger('revision_count')->default(0);
            $table->timestamp('delivered_at')->nullable(); // Added this missing column
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable(); // If implementing cancellations
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('client_user_id');
            $table->index('producer_user_id');
            $table->index('payment_status');
            $table->index('invoice_id'); // Add index for performance
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
}; 