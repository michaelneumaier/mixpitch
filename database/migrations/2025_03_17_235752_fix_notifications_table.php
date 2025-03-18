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
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['notifiable_type', 'notifiable_id']);
            $table->dropColumn('notifiable_type');
            $table->dropColumn('notifiable_id');
        });
    }
};
