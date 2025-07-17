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
        Schema::create('license_signatures', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('license_template_id')->nullable()->constrained()->onDelete('set null');

            // Signature data
            $table->string('signature_text')->nullable(); // Typed name signature
            $table->text('signature_data')->nullable(); // Digital signature canvas data
            $table->string('signature_method')->default('text'); // text, canvas, electronic

            // Legal and audit information
            $table->string('ip_address', 45); // IPv6 compatible
            $table->text('user_agent')->nullable();
            $table->text('agreement_hash'); // Hash of the agreement content at time of signing
            $table->json('metadata')->nullable(); // Additional signing context

            // Verification and compliance
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');

            // Legal status
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active');
            $table->text('revocation_reason')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'user_id']);
            $table->index(['license_template_id']);
            $table->index(['status']);
            $table->index(['created_at']); // For audit queries
            $table->index(['project_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_signatures');
    }
};
