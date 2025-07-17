<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('is_published');
        });

        // Set completed_at for projects with status 'completed'
        // Use database-agnostic approach
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE projects SET completed_at = datetime('now') WHERE status = 'completed'");
        } else {
            DB::statement("UPDATE projects SET completed_at = NOW() WHERE status = 'completed'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
