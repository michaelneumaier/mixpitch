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
        Schema::create('pitch_file_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pitch_file_id')->constrained('pitch_files')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('comment');
            $table->float('timestamp')->comment('Timestamp in seconds where the comment is placed on the audio file');
            $table->boolean('resolved')->default(false)->comment('Whether the comment has been addressed/resolved');
            $table->timestamps();

            // Index for faster queries
            $table->index(['pitch_file_id', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pitch_file_comments');
    }
};
