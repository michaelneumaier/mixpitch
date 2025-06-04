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
            // Add the foreign key column after the existing project_type column
            $table->foreignId('project_type_id')->nullable()->after('project_type');
            
            // Add the foreign key constraint
            $table->foreign('project_type_id')->references('id')->on('project_types')->onDelete('set null');
            
            // Add index for performance
            $table->index('project_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['project_type_id']);
            
            // Drop the index
            $table->dropIndex(['project_type_id']);
            
            // Drop the column
            $table->dropColumn('project_type_id');
        });
    }
};
