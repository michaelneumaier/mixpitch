<?php

namespace Database\Factories;

use App\Models\Mix;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MixFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Mix::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            // 'name' => $this->faker->words(3, true) . ' Mix', // Remove name
            'description' => $this->faker->sentence(),
            // 'file_path' => 'mixes/' . $this->faker->uuid . '.mp3', // Remove file_path
            // 'file_name' => $this->faker->word . '.mp3', // Remove file_name
            'mix_file_path' => 'mixes/' . $this->faker->uuid . '.mp3', // Add mix_file_path
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'rating' => $this->faker->optional()->numberBetween(1, 5), // Add rating
            // 'order' => $this->faker->numberBetween(1, 10), // Remove order
            // 'duration' => $this->faker->randomFloat(2, 60, 300), // Remove duration
            // Add other necessary fields and default values
        ];
    }
} 