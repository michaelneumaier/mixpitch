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
        Schema::create('imported_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_import_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_file_id')->constrained()->onDelete('cascade');
            $table->string('source_filename');
            $table->string('source_url', 2000);
            $table->bigInteger('size_bytes');
            $table->string('mime_type');
            $table->timestamp('imported_at');
            $table->timestamps();

            $table->index(['link_import_id', 'imported_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imported_files');
    }
};
