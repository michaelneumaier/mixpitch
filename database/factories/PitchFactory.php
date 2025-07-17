<?php

namespace Database\Factories;

use App\Models\Pitch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pitch>
 */
class PitchFactory extends Factory
{
    protected $model = Pitch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'project_id' => \App\Models\Project::factory(),
            'status' => \App\Models\Pitch::STATUS_PENDING,
            'max_files' => 25,
            'is_inactive' => false,
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Pitch $pitch) {
            // Ensure the slug is set based on the ID after creation
            // This bypasses potential timing issues with the Sluggable trait in tests
            if (empty($pitch->slug)) {
                $pitch->slug = $pitch->id;
                $pitch->saveQuietly(); // Save without triggering events again
            }
        });
    }
}
