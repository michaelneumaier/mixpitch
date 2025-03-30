<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectFile>
 */
class ProjectFileFactory extends Factory
{
    protected $model = ProjectFile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $storagePath = 'projects/' . $this->faker->uuid() . '.pdf';
        
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'file_name' => $this->faker->word() . '.pdf',
            'original_file_name' => $this->faker->word() . '.pdf',
            'mime_type' => $this->faker->mimeType(),
            'storage_path' => $storagePath,
            'file_path' => $storagePath,
            'size' => $this->faker->numberBetween(1000, 5000000),
            'is_preview_track' => false,
        ];
    }
} 