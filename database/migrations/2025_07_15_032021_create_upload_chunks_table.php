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
        Schema::create('upload_chunks', function (Blueprint $table) {
            $table->id();
            $table->uuid('upload_session_id');
            $table->unsignedInteger('chunk_index');
            $table->string('chunk_hash');
            $table->string('storage_path');
            $table->unsignedBigInteger('size');
            $table->enum('status', ['pending', 'uploaded', 'verified', 'failed'])->default('pending');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('upload_session_id')->references('id')->on('upload_sessions')->onDelete('cascade');

            // Indexes for performance
            $table->index('upload_session_id');
            $table->index('chunk_index');
            $table->index('status');

            // Unique constraint to prevent duplicate chunks
            $table->unique(['upload_session_id', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_chunks');
    }
};
