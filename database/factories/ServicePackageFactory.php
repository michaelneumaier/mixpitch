<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServicePackage>
 */
class ServicePackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 20, 500),
            'currency' => 'USD',
            'deliverables' => $this->faker->paragraph(),
            'revisions_included' => $this->faker->numberBetween(0, 3),
            'estimated_delivery_days' => $this->faker->numberBetween(1, 14),
            'requirements_prompt' => $this->faker->paragraph(),
            'is_published' => false,
        ];
    }

    /**
     * Indicate that the service package is published.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function published()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_published' => true,
            ];
        });
    }
}
