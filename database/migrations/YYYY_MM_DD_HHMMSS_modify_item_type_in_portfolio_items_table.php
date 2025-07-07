<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing data if necessary
        DB::table('portfolio_items')->where('item_type', 'audio_upload')->update(['item_type' => 'audio']);
        // Add similar updates for other old types if needed

        // Modify the item_type column
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->string('item_type')->default('audio')->comment('Type: audio, youtube')->change();
        });
        
        // Drop columns one by one for SQLite compatibility
        if (Schema::hasColumn('portfolio_items', 'external_url')) {
             Schema::table('portfolio_items', function (Blueprint $table) {
                $table->dropColumn('external_url');
            });
        }
        if (Schema::hasColumn('portfolio_items', 'linked_project_id')) {
             // Handle foreign key constraint removal based on database type
             if (DB::getDriverName() !== 'sqlite') {
                 // MySQL/PostgreSQL: Drop foreign key constraint first
                 Schema::table('portfolio_items', function (Blueprint $table) {
                     $table->dropForeign(['linked_project_id']);
                 });
             }
             // SQLite: Can drop column directly (foreign keys are handled differently)
             
             // Then drop the column
             Schema::table('portfolio_items', function (Blueprint $table) {
                 $table->dropColumn('linked_project_id');
             });
        }
        if (Schema::hasColumn('portfolio_items', 'type')) {
             Schema::table('portfolio_items', function (Blueprint $table) {
                // Drop the mistakenly added 'type' column
                 $table->dropColumn('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Note: Reversing this perfectly might be complex.
     * This down method provides a basic rollback and needs similar SQLite adjustments.
     */
    public function down(): void
    {
        // Update data back first
        DB::table('portfolio_items')->where('item_type', 'audio')->update(['item_type' => 'audio_upload']);

        // Revert column type change
        Schema::table('portfolio_items', function (Blueprint $table) {
             $table->string('item_type')->comment('Original enum attempt')->change(); // Simple string revert
        });

        // Re-add dropped columns one by one
        if (!Schema::hasColumn('portfolio_items', 'external_url')) {
            Schema::table('portfolio_items', function (Blueprint $table) {
                 $table->string('external_url')->nullable();
             });
        }
        if (!Schema::hasColumn('portfolio_items', 'linked_project_id')) {
             Schema::table('portfolio_items', function (Blueprint $table) {
                 $table->foreignId('linked_project_id')->nullable()->constrained('projects')->onDelete('set null');
             });
        }
         if (!Schema::hasColumn('portfolio_items', 'type')) {
            Schema::table('portfolio_items', function (Blueprint $table) {
                // Re-add the mistaken 'type' column
                 $table->string('type')->nullable(); 
             });
        }
    }
}; 