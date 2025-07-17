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
        Schema::table('license_templates', function (Blueprint $table) {
            // Legal and compliance
            $table->json('legal_metadata')->nullable(); // Jurisdiction, version, compliance flags
            $table->string('license_version', 20)->default('1.0');
            $table->timestamp('last_legal_review')->nullable();

            // Advanced categorization
            $table->string('use_case')->nullable(); // sync, samples, collaboration, etc.
            $table->json('industry_tags')->nullable(); // film, tv, advertising, gaming

            // Template relationships
            $table->foreignId('parent_template_id')->nullable()->constrained('license_templates')->onDelete('set null');
            $table->boolean('is_system_template')->default(false);
            $table->boolean('is_public')->default(false); // For marketplace

            // Usage and analytics
            $table->decimal('average_project_value', 10, 2)->nullable();
            $table->json('usage_analytics')->nullable();

            // Approval workflow
            $table->enum('approval_status', ['draft', 'pending', 'approved', 'rejected'])->default('approved');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // Performance indexes
            $table->index(['is_public', 'approval_status']);
            $table->index(['use_case']);
            $table->index(['parent_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_templates', function (Blueprint $table) {
            $table->dropIndex(['is_public', 'approval_status']);
            $table->dropIndex(['use_case']);
            $table->dropIndex(['parent_template_id']);

            $table->dropForeign(['parent_template_id']);
            $table->dropForeign(['approved_by']);

            $table->dropColumn([
                'legal_metadata',
                'license_version',
                'last_legal_review',
                'use_case',
                'industry_tags',
                'parent_template_id',
                'is_system_template',
                'is_public',
                'average_project_value',
                'usage_analytics',
                'approval_status',
                'approved_by',
                'approved_at',
            ]);
        });
    }
};
