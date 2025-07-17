<?php

use App\Models\Pitch;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the slug column exists
        if (! Schema::hasColumn('pitches', 'slug')) {
            return;
        }

        // Get all existing pitches that don't have a slug yet, including potentially soft-deleted ones during migration
        $pitches = Pitch::withTrashed()->whereNull('slug')->with('user')->get();

        foreach ($pitches as $pitch) {
            // Skip if user doesn't exist
            if (! $pitch->user) {
                continue;
            }

            // Generate a base slug from the username
            $username = $pitch->user->username ?? 'user-'.$pitch->user_id;
            $baseSlug = Str::slug($username);

            // Check if the slug already exists for the same project
            $existingCount = DB::table('pitches')
                ->where('project_id', $pitch->project_id)
                ->where('slug', $baseSlug)
                ->where('id', '!=', $pitch->id)
                ->count();

            // If slug already exists, make it unique by adding a counter
            $finalSlug = $existingCount > 0 ? $baseSlug.'-'.($existingCount + 1) : $baseSlug;

            // Update the pitch
            DB::table('pitches')
                ->where('id', $pitch->id)
                ->update(['slug' => $finalSlug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not required as this is just data population
        // and the slug column will be dropped by its own migration
    }
};
