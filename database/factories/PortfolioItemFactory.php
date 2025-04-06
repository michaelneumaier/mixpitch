<?php

namespace Database\Factories;

use App\Models\PortfolioItem;
use App\Models\User;
use App\Models\Project; // Import Project model if linking projects
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PortfolioItem>
 */
class PortfolioItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PortfolioItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $itemType = $this->faker->randomElement(['audio_upload', 'external_link', 'mixpitch_project_link']);
        $data = [
            'user_id' => User::factory(), // Associate with a user factory by default
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'item_type' => $itemType,
            'file_path' => null,
            'external_url' => null,
            'linked_project_id' => null,
            'display_order' => $this->faker->unique()->randomNumber(3),
            'is_public' => $this->faker->boolean(80), // 80% chance of being public
        ];

        // Add type-specific data
        if ($itemType === 'audio_upload') {
            // For testing, we might not generate actual files here, 
            // but maybe a placeholder path
            $data['file_path'] = 'portfolio-audio/test/' . Str::random(10) . '.mp3';
        } elseif ($itemType === 'external_link') {
            $data['external_url'] = $this->faker->url;
        } elseif ($itemType === 'mixpitch_project_link') {
            // Optionally create/link a real project
            // $data['linked_project_id'] = Project::factory(); 
            // Or leave null/set a specific ID during test setup
        }

        return $data;
    }
}
