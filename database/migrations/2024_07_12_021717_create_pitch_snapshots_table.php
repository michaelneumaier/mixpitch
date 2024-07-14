<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('pitch_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pitch_id')->constrained()->onDelete('cascade');
            $table->json('snapshot_data'); // To store the snapshot details as JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pitch_snapshots');
    }
};
