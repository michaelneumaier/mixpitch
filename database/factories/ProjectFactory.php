<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
// use App\Models\Genre; // Remove this import
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
        // Get a random genre ID (assuming genres exist)
        // $genreId = \App\Models\Genre::inRandomOrder()->first()?->id ?? 1; // Remove this

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(3), // Use 'name' based on model
            'description' => $this->faker->paragraph,
            'genre' => $this->faker->randomElement(['Pop', 'Rock', 'Hip Hop', 'Jazz', 'Country']), // Use string genre
            // 'genre_id' => $genreId, // Remove this
            'artist_name' => $this->faker->name,
            'project_type' => $this->faker->randomElement(['single', 'ep', 'album']), // Example project subtypes
            'collaboration_type' => [$this->faker->randomElement(['Mixing', 'Mastering', 'Production', 'Songwriting'])], // Array format
            'budget' => $this->faker->randomElement([0, $this->faker->numberBetween(50, 2000)]),
            'status' => Project::STATUS_UNPUBLISHED,
            'deadline' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
            'total_storage_used' => 0,
            'is_published' => false,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD, // Default to standard

            // Default nulls for conditional fields
            'target_producer_id' => null,
            'client_email' => null,
            'client_name' => null,
            'prize_amount' => null,
            'prize_currency' => null,
            'submission_deadline' => null,
            'judging_deadline' => null,
        ];
    }

    /**
     * Indicate that the project is published.
     */
    public function published(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Project::STATUS_OPEN,
                'published_at' => now(),
                'is_published' => true, // Ensure this aligns with status
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

    /**
     * Configure the project for a specific workflow type.
     */
    public function configureWorkflow(string $workflowType, array $options = []): Factory
    {
        return $this->state(function (array $attributes) use ($workflowType, $options) {
            $state = ['workflow_type' => $workflowType];
            switch ($workflowType) {
                case Project::WORKFLOW_TYPE_CONTEST:
                    $state['submission_deadline'] = $options['submission_deadline'] ?? now()->addDays(14);
                    $state['judging_deadline'] = $options['judging_deadline'] ?? now()->addDays(21);
                    $state['prize_amount'] = $options['prize_amount'] ?? $this->faker->numberBetween(100, 1000);
                    $state['prize_currency'] = $options['prize_currency'] ?? Project::DEFAULT_CURRENCY;
                    break;
                case Project::WORKFLOW_TYPE_DIRECT_HIRE:
                    $state['target_producer_id'] = $options['target_producer_id'] ?? User::factory()->create()->id; // Create/use provided producer
                    break;
                case Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT:
                    $state['client_email'] = $options['client_email'] ?? $this->faker->unique()->safeEmail();
                    $state['client_name'] = $options['client_name'] ?? $this->faker->name();
                    break;
            }

            return $state;
        });
    }
}
