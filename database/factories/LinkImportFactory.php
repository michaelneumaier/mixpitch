<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LinkImport>
 */
class LinkImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\Project::factory(),
            'user_id' => \App\Models\User::factory(),
            'source_url' => 'https://wetransfer.com/downloads/'.$this->faker->uuid,
            'source_domain' => 'wetransfer.com',
            'detected_files' => [],
            'imported_files' => [],
            'status' => \App\Models\LinkImport::STATUS_PENDING,
            'error_message' => null,
            'metadata' => [],
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
