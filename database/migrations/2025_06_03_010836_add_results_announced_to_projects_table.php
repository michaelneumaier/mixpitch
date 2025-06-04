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
            $table->timestamp('results_announced_at')->nullable()->after('judging_finalized_at');
            $table->foreignId('results_announced_by')->nullable()->constrained('users')->onDelete('set null')->after('results_announced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['results_announced_by']);
            $table->dropColumn(['results_announced_at', 'results_announced_by']);
        });
    }
};
