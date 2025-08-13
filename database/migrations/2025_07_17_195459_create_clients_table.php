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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Producer who owns this client
            $table->string('email'); // Client's email address
            $table->string('name')->nullable(); // Client's name
            $table->string('company')->nullable(); // Client's company
            $table->string('phone')->nullable(); // Client's phone number
            $table->string('timezone')->default('UTC'); // Client's timezone
            $table->json('preferences')->nullable(); // Client preferences (communication, file formats, etc.)
            $table->text('notes')->nullable(); // Producer's notes about this client
            $table->json('tags')->nullable(); // Tags for organizing clients
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active'); // Client status
            $table->timestamp('last_contacted_at')->nullable(); // Last communication date
            $table->decimal('total_spent', 10, 2)->default(0); // Total amount spent by this client
            $table->integer('total_projects')->default(0); // Total number of projects for this client
            $table->timestamps();

            // Ensure unique client per producer
            $table->unique(['user_id', 'email']);

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'last_contacted_at']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
