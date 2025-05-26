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
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('budget');
            $table->timestamp('published_at')->nullable()->after('is_published');
        });
        
        // Set is_published based on existing status
        DB::statement('UPDATE projects SET is_published = CASE WHEN status = "unpublished" THEN 0 ELSE 1 END');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = ['is_published', 'published_at'];
        // Drop columns separately for SQLite
        foreach ($columns as $column) {
            if (Schema::hasColumn('projects', $column)) {
                Schema::table('projects', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
