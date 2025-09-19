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
        Schema::create('file_comments', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to any commentable model (PitchFile, ProjectFile, etc.)
            $table->morphs('commentable'); // Creates commentable_type and commentable_id

            // User who made the comment (nullable for client comments)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // Parent comment for threading/replies
            $table->unsignedBigInteger('parent_id')->nullable();

            // Comment content
            $table->text('comment');

            // Timestamp in the audio file where comment is placed (in seconds)
            $table->float('timestamp')->comment('Position in seconds where the comment is placed on the audio file');

            // Status
            $table->boolean('resolved')->default(false)->comment('Whether the comment has been addressed/resolved');

            // Client comment fields
            $table->string('client_email')->nullable()->comment('Email for client comments when user is not authenticated');
            $table->boolean('is_client_comment')->default(false)->comment('Flag to identify comments made by external clients');

            $table->timestamps();

            // Indexes for performance
            $table->index(['commentable_type', 'commentable_id', 'timestamp'], 'file_comments_commentable_timestamp_index');
            $table->index(['commentable_type', 'commentable_id'], 'file_comments_commentable_index');
            $table->index(['parent_id']);
            $table->index(['user_id']);
            $table->index(['is_client_comment']);

            // Foreign key for parent comment (self-referencing)
            $table->foreign('parent_id')->references('id')->on('file_comments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_comments');
    }
};
