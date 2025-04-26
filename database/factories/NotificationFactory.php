<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use App\Models\Pitch; // Example related model
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Basic definition, specific tests should override related_id/type/data
        $relatedModel = Pitch::factory()->create(); // Default to a Pitch, can be overridden

        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement([
                Notification::TYPE_PITCH_SUBMITTED,
                Notification::TYPE_PITCH_STATUS_CHANGE,
                Notification::TYPE_PITCH_COMMENT,
                // Add other common types as needed
            ]),
            'related_id' => $relatedModel->id,
            'related_type' => get_class($relatedModel),
            'data' => fn (array $attributes) => match ($attributes['type']) {
                Notification::TYPE_PITCH_SUBMITTED => [
                    'project_name' => 'Test Project',
                    'submitter_name' => 'Test Submitter'
                ],
                Notification::TYPE_PITCH_STATUS_CHANGE => [
                    'status' => Pitch::STATUS_PENDING,
                    'project_name' => 'Test Project'
                ],
                Notification::TYPE_PITCH_COMMENT => [
                    'commenter_name' => 'Test Commenter',
                    'project_name' => 'Test Project'
                ],
                default => ['info' => $this->faker->sentence],
            },
            'read_at' => null, // Default to unread
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the notification is read.
     */
    public function read(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'read_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'read_at' => null,
            ];
        });
    }

    /**
     * Set the related model for the notification.
     */
    public function relatedTo($model): Factory
    {
        return $this->state(function (array $attributes) use ($model) {
            return [
                'related_id' => $model->id,
                'related_type' => get_class($model),
            ];
        });
    }
} 