<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add workflow_type column
            $table->string('workflow_type')->default('standard')->after('user_id');
            $table->index('workflow_type');

            // Add target_producer_id for Direct Hire
            $table->foreignId('target_producer_id')->nullable()->constrained('users')->onDelete('set null')->after('workflow_type');

            // Add client_email and client_name for Client Management
            $table->string('client_email')->nullable()->after('target_producer_id');
            $table->string('client_name')->nullable()->after('client_email');
            $table->index('client_email');

            // Add Contest Prize Fields
            $table->decimal('prize_amount', 10, 2)->nullable()->after('client_name');
            $table->string('prize_currency', 3)->nullable()->after('prize_amount');

            // Add Contest Deadline Fields
            $table->dateTime('submission_deadline')->nullable()->after('prize_currency');
            $table->dateTime('judging_deadline')->nullable()->after('submission_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Define columns and indexes to drop
        $columns = ['workflow_type', 'target_producer_id', 'client_email', 'client_name', 'prize_amount', 'prize_currency', 'submission_deadline', 'judging_deadline'];
        $indexes = ['workflow_type', 'client_email']; // Indexes that need explicit dropping

        // NOTE: SQLite does not support dropping foreign keys.
        // The target_producer_id foreign key is implicitly removed when the column is dropped.

        // Drop Indexes first in separate blocks
        foreach ($indexes as $index) {
            if (Schema::hasColumn('projects', $index)) { // Check column existence as proxy for index
                Schema::table('projects', function (Blueprint $table) use ($index) {
                    try {
                        // Index name usually follows convention: table_column_index
                        $table->dropIndex('projects_' . $index . '_index');
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Log if index drop fails (e.g., non-standard name or already dropped)
                        Log::warning("Could not drop index for {$index} during migration rollback: " . $e->getMessage());
                    }
                });
            }
        }

        // Drop Columns in separate blocks
        foreach ($columns as $column) {
            if (Schema::hasColumn('projects', $column)) {
                Schema::table('projects', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
