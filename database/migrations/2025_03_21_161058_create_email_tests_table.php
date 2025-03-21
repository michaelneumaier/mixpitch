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
        Schema::create('email_tests', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email');
            $table->string('subject')->nullable();
            $table->string('template')->default('emails.test');
            $table->json('content_variables')->nullable();
            $table->string('status')->default('pending');
            $table->json('result')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_tests');
    }
};
