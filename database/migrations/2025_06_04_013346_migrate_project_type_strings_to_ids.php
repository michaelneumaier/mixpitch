<?php

use App\Models\ProjectType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create mapping from old string values to new project type slugs
        $mappings = [
            'single' => 'single',
            'album' => 'album',
            'ep' => 'ep',
            'remix' => 'remix',
            'mixtape' => 'mixtape',
            'demo' => 'demo',
            // Add any other values found in existing data
        ];

        foreach ($mappings as $oldValue => $slug) {
            // Find the project type by slug
            $projectType = ProjectType::where('slug', $slug)->first();

            if ($projectType) {
                // Update all projects with this string value
                $updatedCount = DB::table('projects')
                    ->where('project_type', $oldValue)
                    ->update(['project_type_id' => $projectType->id]);

                if ($updatedCount > 0 && app()->environment() !== 'testing') {
                    echo "Migrated projects with project_type '{$oldValue}' to project_type_id {$projectType->id}\n";
                }
            } else {
                // Only show warnings in non-testing environments
                if (app()->environment() !== 'testing') {
                    echo "Warning: Could not find project type with slug '{$slug}'\n";
                }
            }
        }

        // Handle any projects with empty or null project_type - set to 'single' as default
        $defaultProjectType = ProjectType::where('slug', 'single')->first();
        if ($defaultProjectType) {
            $updatedCount = DB::table('projects')
                ->whereNull('project_type_id')
                ->where(function ($query) {
                    $query->whereNull('project_type')
                        ->orWhere('project_type', '')
                        ->orWhere('project_type', 'single'); // Catch 'single' values that might not have been updated above
                })
                ->update(['project_type_id' => $defaultProjectType->id]);

            if ($updatedCount > 0 && app()->environment() !== 'testing') {
                echo "Set {$updatedCount} projects with empty/null project_type to default 'single'\n";
            }
        }

        // Report any unmapped values
        $unmappedProjects = DB::table('projects')
            ->whereNull('project_type_id')
            ->whereNotNull('project_type')
            ->where('project_type', '!=', '')
            ->select('project_type', DB::raw('count(*) as count'))
            ->groupBy('project_type')
            ->get();

        if ($unmappedProjects->count() > 0 && app()->environment() !== 'testing') {
            echo "Warning: Found projects with unmapped project_type values:\n";
            foreach ($unmappedProjects as $unmapped) {
                echo "  - '{$unmapped->project_type}': {$unmapped->count} projects\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear all project_type_id values to reverse the migration
        DB::table('projects')->update(['project_type_id' => null]);

        if (app()->environment() !== 'testing') {
            echo "Cleared all project_type_id values\n";
        }
    }
};
