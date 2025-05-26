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
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Producer owning the package
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->text('deliverables')->nullable(); // What the client receives
            $table->unsignedInteger('revisions_included')->default(0);
            $table->unsignedInteger('estimated_delivery_days')->nullable();
            $table->text('requirements_prompt')->nullable(); // Instructions for the client
            $table->boolean('is_published')->default(false);
            // Consider: $table->string('category')->nullable(); $table->index('category');
            $table->timestamps();
            $table->softDeletes(); // Optional: allow soft deletion
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
}; 