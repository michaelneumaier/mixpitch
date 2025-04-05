<?php

namespace Database\Factories;

use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PitchSnapshot>
 */
class PitchSnapshotFactory extends Factory
{
    protected $model = PitchSnapshot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Default state requires associated Pitch, Project, and User
        // These should ideally be provided when calling the factory, 
        // e.g., PitchSnapshot::factory()->for($pitch)->create();
        $pitch = Pitch::factory()->create(); // Create a default pitch if none provided

        return [
            'pitch_id' => $pitch->id,
            'project_id' => $pitch->project_id,
            'user_id' => $pitch->user_id,
            'status' => PitchSnapshot::STATUS_PENDING, // Default status
            'snapshot_data' => [ // Example snapshot data
                'version' => $this->faker->numberBetween(1, 5), // Add version number
                'comment' => $this->faker->sentence,
                'file_ids' => [], // Add empty file_ids array
                'files' => [
                    ['name' => $this->faker->word . '.mp3', 'path' => 'dummy/path1.mp3', 'size' => $this->faker->numberBetween(100000, 5000000)],
                    ['name' => $this->faker->word . '.wav', 'path' => 'dummy/path2.wav', 'size' => $this->faker->numberBetween(5000000, 20000000)],
                ]
            ],
        ];
    }
} 