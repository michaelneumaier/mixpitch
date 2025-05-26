<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Add new columns for Laravel's notification system
            if (!Schema::hasColumn('notifications', 'notifiable_type')) {
                $table->string('notifiable_type')->nullable();
            }
            
            if (!Schema::hasColumn('notifications', 'notifiable_id')) {
                $table->unsignedBigInteger('notifiable_id')->nullable();
            }
            
            // Add index for the polymorphic relationship
            $table->index(['notifiable_type', 'notifiable_id']);
        });
        
        // Migrate data from user_id, related_id, related_type to notifiable_type and notifiable_id
        if (Schema::hasColumn('notifications', 'user_id')) {
            DB::statement('UPDATE notifications SET notifiable_type = "App\\\\Models\\\\User", notifiable_id = user_id WHERE user_id IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop index first
        Schema::table('notifications', function (Blueprint $table) {
             try {
                // Index name convention: table_column1_column2_index
                $table->dropIndex('notifications_notifiable_type_notifiable_id_index');
            } catch (\Illuminate\Database\QueryException $e) {
                Log::warning("Could not drop notifiable index during migration rollback: " . $e->getMessage());
            }
        });

        // Drop columns separately for SQLite compatibility
        $columns = ['notifiable_type', 'notifiable_id'];
        foreach ($columns as $column) {
            if (Schema::hasColumn('notifications', $column)) {
                Schema::table('notifications', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
