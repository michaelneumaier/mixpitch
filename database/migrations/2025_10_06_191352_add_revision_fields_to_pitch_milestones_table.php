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
        Schema::table('pitch_milestones', function (Blueprint $table) {
            // Identify if milestone is auto-generated for revision
            $table->boolean('is_revision_milestone')->default(false)->after('approved_at');
            $table->integer('revision_round_number')->nullable()->after('is_revision_milestone');
            $table->text('revision_request_details')->nullable()->after('revision_round_number');

            $table->index(['is_revision_milestone', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_milestones', function (Blueprint $table) {
            $table->dropIndex(['is_revision_milestone', 'payment_status']);
            $table->dropColumn([
                'is_revision_milestone',
                'revision_round_number',
                'revision_request_details',
            ]);
        });
    }
};
