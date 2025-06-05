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
        Schema::table('projects', function (Blueprint $table) {
            // License assignment
            $table->foreignId('license_template_id')->nullable()->constrained()->onDelete('set null');
            $table->json('custom_license_terms')->nullable(); // Project-specific overrides
            $table->text('license_notes')->nullable();
            
            // License status and workflow
            $table->enum('license_status', ['pending', 'active', 'expired', 'revoked'])->default('pending');
            $table->timestamp('license_signed_at')->nullable();
            $table->string('license_signature_ip', 45)->nullable(); // IPv6 compatible
            $table->boolean('requires_license_agreement')->default(true);
            
            // License metadata
            $table->string('license_jurisdiction', 10)->default('US'); // US, EU, UK, etc.
            $table->text('license_content_hash')->nullable(); // For integrity verification
            
            // Indexes for performance
            $table->index(['license_status']);
            $table->index(['license_template_id', 'license_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['license_status']);
            $table->dropIndex(['license_template_id', 'license_status']);
            
            $table->dropForeign(['license_template_id']);
            
            $table->dropColumn([
                'license_template_id',
                'custom_license_terms',
                'license_notes',
                'license_status',
                'license_signed_at',
                'license_signature_ip',
                'requires_license_agreement',
                'license_jurisdiction',
                'license_content_hash',
            ]);
        });
    }
}; 