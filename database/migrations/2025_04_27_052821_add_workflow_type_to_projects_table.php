<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Check if the column doesn't already exist
            if (!Schema::hasColumn('projects', 'workflow_type')) {
                $table->string('workflow_type')->default('standard')->after('user_id');
                $table->index('workflow_type');
            }
            
            // Check if target_producer_id doesn't already exist
            if (!Schema::hasColumn('projects', 'target_producer_id')) {
                $table->foreignId('target_producer_id')->nullable()->constrained('users')->onDelete('set null')->after('workflow_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop target_producer_id first
        if (Schema::hasColumn('projects', 'target_producer_id')) {
            Schema::table('projects', function (Blueprint $table) {
                // NOTE: SQLite does not support dropping foreign keys directly.
                // We will only drop the column, which implicitly removes the constraint
                // when using SQLite. This might behave differently on other databases.
                $table->dropColumn('target_producer_id');
            });
        }
        
        // Drop workflow_type separately
        if (Schema::hasColumn('projects', 'workflow_type')) {
            Schema::table('projects', function (Blueprint $table) {
                // Attempt to drop index first (might fail if non-existent, but usually safe)
                try {
                    $table->dropIndex(['workflow_type']);
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::warning("Could not drop index for workflow_type during migration rollback: " . $e->getMessage());
                }
                $table->dropColumn('workflow_type');
            });
        }
    }
};
