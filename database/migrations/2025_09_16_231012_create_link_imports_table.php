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
        Schema::create('link_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('source_url', 2000);
            $table->string('source_domain');
            $table->json('detected_files'); // Array of file metadata from link analysis
            $table->json('imported_files')->nullable(); // Cache of successfully imported ProjectFile IDs
            $table->string('status'); // pending, analyzing, importing, completed, failed
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional info like file counts, sizes, progress
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index('source_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_imports');
    }
};
