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
        Schema::table('pitch_files', function (Blueprint $table) {
            // Track which revision round this file belongs to
            $table->integer('revision_round')->default(1);
            // Mark if file has been superseded by a newer revision
            $table->boolean('superseded_by_revision')->default(false);

            $table->index(['revision_round', 'superseded_by_revision']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->dropIndex(['revision_round', 'superseded_by_revision']);
            $table->dropColumn([
                'revision_round',
                'superseded_by_revision',
            ]);
        });
    }
};
