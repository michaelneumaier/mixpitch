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
        Schema::create('email_events', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('message_id')->nullable();
            $table->string('event_type'); // sent, delivered, opened, clicked, bounced, complained
            $table->string('email_type')->nullable(); // verification, notification, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Index for quick lookups by email
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_events');
    }
};
