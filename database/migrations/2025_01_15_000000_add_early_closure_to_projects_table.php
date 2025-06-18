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
            // Early closure functionality
            $table->timestamp('submissions_closed_early_at')->nullable()->after('submission_deadline');
            $table->foreignId('submissions_closed_early_by')->nullable()->constrained('users')->onDelete('set null')->after('submissions_closed_early_at');
            $table->text('early_closure_reason')->nullable()->after('submissions_closed_early_by');
            
            // Index for performance
            $table->index(['workflow_type', 'submissions_closed_early_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['submissions_closed_early_by']);
            $table->dropIndex(['workflow_type', 'submissions_closed_early_at']);
            $table->dropColumn(['submissions_closed_early_at', 'submissions_closed_early_by', 'early_closure_reason']);
        });
    }
}; 