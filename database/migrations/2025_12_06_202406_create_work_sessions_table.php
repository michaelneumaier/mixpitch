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
        Schema::create('work_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pitch_id')->constrained()->cascadeOnDelete();

            // Session timing
            $table->string('status', 20)->default('active'); // active, paused, ended
            $table->timestamp('started_at');
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('total_duration_seconds')->default(0);

            // Session details
            $table->text('notes')->nullable();
            $table->boolean('is_visible_to_client')->default(true);
            $table->boolean('focus_mode')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['pitch_id', 'status']);
            $table->index(['pitch_id', 'started_at']);
        });

        // Add presence settings to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('presence_visibility', 20)->default('full')->after('remember_token');
            // full = show all details, summary = just online/offline, minimal = hide presence
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_sessions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('presence_visibility');
        });
    }
};
