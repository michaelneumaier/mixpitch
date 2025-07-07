<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProjectType;
use Illuminate\Support\Facades\DB;

class ProjectTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing project types to avoid duplicates
        DB::table('project_types')->delete();

        $projectTypes = [
            [
                'name' => 'Single',
                'slug' => 'single',
                'description' => 'Individual track project',
                'icon' => 'fas fa-music',
                'color' => 'blue',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Album',
                'slug' => 'album',
                'description' => 'Full album project with multiple tracks',
                'icon' => 'fas fa-compact-disc',
                'color' => 'purple',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Remix',
                'slug' => 'remix',
                'description' => 'Remix of existing track',
                'icon' => 'fas fa-magic',
                'color' => 'pink',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'EP',
                'slug' => 'ep',
                'description' => 'Extended play project (3-6 tracks)',
                'icon' => 'fas fa-layer-group',
                'color' => 'green',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Mixtape',
                'slug' => 'mixtape',
                'description' => 'Mixtape project with multiple tracks',
                'icon' => 'fas fa-tape',
                'color' => 'orange',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Demo',
                'slug' => 'demo',
                'description' => 'Demo recording project',
                'icon' => 'fas fa-microphone',
                'color' => 'gray',
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        // Use DB::table for bulk insert performance
        DB::table('project_types')->insert($projectTypes);

        $this->command->info('Created ' . count($projectTypes) . ' project types.');
    }
}
