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
        Schema::create('contest_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('first_place_pitch_id')->nullable()->constrained('pitches')->onDelete('set null');
            $table->foreignId('second_place_pitch_id')->nullable()->constrained('pitches')->onDelete('set null');
            $table->foreignId('third_place_pitch_id')->nullable()->constrained('pitches')->onDelete('set null');
            $table->json('runner_up_pitch_ids')->nullable(); // Array of pitch IDs
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('show_submissions_publicly')->default(true);
            $table->timestamps();
            
            // Ensure each project can only have one contest result
            $table->unique('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_results');
    }
};
