<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'name' => $this->faker->name(),
            'company' => $this->faker->optional(0.7)->company(),
            'phone' => $this->faker->optional(0.5)->phoneNumber(),
            'timezone' => $this->faker->randomElement(['UTC', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'Asia/Tokyo']),
            'preferences' => [
                'communication' => $this->faker->randomElement(['email', 'phone', 'both']),
                'file_formats' => $this->faker->randomElements(['mp3', 'wav', 'aiff'], $this->faker->numberBetween(1, 3)),
                'feedback_style' => $this->faker->randomElement(['detailed', 'concise', 'visual']),
            ],
            'notes' => $this->faker->optional(0.6)->paragraph(),
            'tags' => $this->faker->optional(0.4)->randomElements(['VIP', 'Regular', 'New', 'Hip-Hop', 'Pop', 'Electronic'], $this->faker->numberBetween(1, 3)),
            'status' => $this->faker->randomElement([Client::STATUS_ACTIVE, Client::STATUS_INACTIVE, Client::STATUS_BLOCKED]),
            'last_contacted_at' => $this->faker->optional(0.8)->dateTimeBetween('-30 days', 'now'),
            'total_spent' => $this->faker->randomFloat(2, 0, 10000),
            'total_projects' => $this->faker->numberBetween(0, 20),
        ];
    }

    /**
     * Indicate that the client is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Client::STATUS_ACTIVE,
        ]);
    }

    /**
     * Indicate that the client is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Client::STATUS_INACTIVE,
        ]);
    }

    /**
     * Indicate that the client is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Client::STATUS_BLOCKED,
        ]);
    }

    /**
     * Indicate that the client is a VIP.
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'tags' => ['VIP', 'Premium'],
            'total_spent' => $this->faker->randomFloat(2, 5000, 50000),
            'total_projects' => $this->faker->numberBetween(10, 50),
            'notes' => 'VIP client - Priority support and expedited delivery.',
        ]);
    }

    /**
     * Indicate that the client needs follow-up.
     */
    public function needsFollowUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_contacted_at' => $this->faker->dateTimeBetween('-60 days', '-20 days'),
            'status' => Client::STATUS_ACTIVE,
        ]);
    }

    /**
     * Indicate that the client is new (recently contacted).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_contacted_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'total_projects' => $this->faker->numberBetween(1, 3),
            'total_spent' => $this->faker->randomFloat(2, 100, 1000),
        ]);
    }
}
