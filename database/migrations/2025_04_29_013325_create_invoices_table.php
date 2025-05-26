<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Invoice;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User who owns the invoice (usually the client)
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade'); // Link to order if applicable
            // Add foreignId for pitch_id if invoices can be linked to pitches too
            // $table->foreignId('pitch_id')->nullable()->constrained()->onDelete('cascade');

            $table->string('stripe_invoice_id')->nullable()->unique(); // Stripe's Invoice ID
            $table->string('invoice_number')->unique()->nullable(); // Your internal invoice number
            $table->string('status')->default('pending'); // Use the actual string value for default
            $table->integer('amount'); // Amount in cents
            $table->string('currency', 3);
            $table->timestamp('due_date')->nullable(); // **** ADDED THIS LINE ****
            $table->timestamp('paid_at')->nullable();
            $table->string('pdf_url')->nullable(); // Link to Stripe hosted PDF
            $table->json('metadata')->nullable(); // Store extra info
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
