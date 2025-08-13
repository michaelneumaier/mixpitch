<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->timestamp('due_at');
            $table->timestamp('snooze_until')->nullable();
            $table->string('status')->default('pending'); // pending|completed|snoozed
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'client_id', 'status']);
            $table->index(['due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_reminders');
    }
};
