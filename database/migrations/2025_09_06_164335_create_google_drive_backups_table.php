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
        Schema::create('google_drive_backups', function (Blueprint $table) {
            $table->id();
            
            // User who performed the backup
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Source file information
            $table->morphs('file'); // file_id, file_type (ProjectFile, PitchFile)
            $table->string('original_file_name');
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('file_hash')->nullable(); // For detecting file changes
            
            // Google Drive information
            $table->string('google_drive_file_id');
            $table->string('google_drive_folder_id');
            $table->string('google_drive_folder_name');
            $table->text('google_drive_file_path')->nullable(); // Full path in Google Drive
            
            // Project context (if applicable)
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('project_name')->nullable();
            
            // Backup metadata
            $table->enum('status', ['pending', 'completed', 'failed', 'deleted'])->default('pending');
            $table->timestamp('backed_up_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional backup metadata
            
            // Versioning
            $table->integer('version')->default(1);
            $table->boolean('is_latest_version')->default(true);
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['file_id', 'file_type']);
            $table->index(['project_id']);
            $table->index(['google_drive_file_id']);
            $table->index(['backed_up_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_drive_backups');
    }
};
