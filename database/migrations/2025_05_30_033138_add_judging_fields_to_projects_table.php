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
            $table->timestamp('judging_finalized_at')->nullable()->after('submission_deadline');
            $table->boolean('show_submissions_publicly')->default(true)->after('judging_finalized_at');
            $table->text('judging_notes')->nullable()->after('show_submissions_publicly');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['judging_finalized_at', 'show_submissions_publicly', 'judging_notes']);
        });
    }
};
