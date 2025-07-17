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
            // Marketplace publishing fields
            $table->string('marketplace_title', 150)->nullable()->after('description');
            $table->text('marketplace_description')->nullable()->after('marketplace_title');
            $table->text('submission_notes')->nullable()->after('marketplace_description');
            $table->timestamp('submitted_for_approval_at')->nullable()->after('submission_notes');
            $table->text('rejection_reason')->nullable()->after('submitted_for_approval_at');
            $table->boolean('marketplace_featured')->default(false)->after('rejection_reason');

            // Analytics fields
            $table->unsignedInteger('view_count')->default(0)->after('marketplace_featured');
            $table->unsignedInteger('fork_count')->default(0)->after('view_count');

            // Indexes for performance
            $table->index(['is_public', 'marketplace_featured']);
            $table->index(['view_count']);
            $table->index(['fork_count']);
            $table->index(['submitted_for_approval_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_templates', function (Blueprint $table) {
            $table->dropIndex(['is_public', 'marketplace_featured']);
            $table->dropIndex(['view_count']);
            $table->dropIndex(['fork_count']);
            $table->dropIndex(['submitted_for_approval_at']);

            $table->dropColumn([
                'marketplace_title',
                'marketplace_description',
                'submission_notes',
                'submitted_for_approval_at',
                'rejection_reason',
                'marketplace_featured',
                'view_count',
                'fork_count',
            ]);
        });
    }
};
