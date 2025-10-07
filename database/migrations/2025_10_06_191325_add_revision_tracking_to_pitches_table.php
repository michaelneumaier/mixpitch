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
        Schema::table('pitches', function (Blueprint $table) {
            // Revision tracking
            $table->integer('included_revisions')->default(2)->after('payment_status');
            $table->decimal('additional_revision_price', 10, 2)->nullable()->after('included_revisions');
            $table->integer('revisions_used')->default(0)->after('additional_revision_price');
            $table->text('revision_scope_guidelines')->nullable()->after('revisions_used');

            $table->index(['revisions_used', 'included_revisions']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            $table->dropIndex(['revisions_used', 'included_revisions']);
            $table->dropColumn([
                'included_revisions',
                'additional_revision_price',
                'revisions_used',
                'revision_scope_guidelines',
            ]);
        });
    }
};
