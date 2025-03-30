<?php
namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'genre' => $this->faker->randomElement(['Pop', 'Rock', 'Hip Hop', 'Jazz', 'Country']), // Updated to include valid genres
            'artist_name' => $this->faker->name,
            'project_type' => $this->faker->randomElement(['Mixing', 'Mastering', 'Production', 'Songwriting']), // Example project types
            'collaboration_type' => [$this->faker->randomElement(['Vocalist', 'Instrumentalist', 'Producer', 'Songwriter'])], // Example collab types
            'budget' => $this->faker->numberBetween(100, 5000),
            'status' => Project::STATUS_UNPUBLISHED, // Default status
            'deadline' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
            'total_storage_used' => 0,
            // Add other necessary fields from your Project model if needed
        ];
    }

    /**
     * Indicate that the project is published.
     */
    public function published(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Project::STATUS_OPEN, // Or relevant published status like OPEN
                'published_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the project is completed.
     */
    public function completed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Project::STATUS_COMPLETED,
                'completed_at' => now(),
            ];
        });
    }
} 