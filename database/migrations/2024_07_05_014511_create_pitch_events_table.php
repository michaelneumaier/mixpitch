<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('pitch_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pitch_id')->constrained()->onDelete('cascade');
            $table->string('event_type');
            $table->string('status')->nullable();
            $table->text('comment')->nullable();
            $table->integer('rating')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pitch_events');
    }
};
