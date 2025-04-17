<?php

namespace Database\Factories;

use App\Models\PortfolioItem;
use App\Models\User;
// use App\Models\Project; // No longer needed for this factory
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
        // Use the model constants for types
        $itemType = $this->faker->randomElement([PortfolioItem::TYPE_AUDIO, PortfolioItem::TYPE_YOUTUBE]);
        
        $data = [
            'user_id' => User::factory(), // Associate with a user factory by default
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'item_type' => $itemType,
            'display_order' => $this->faker->unique()->randomNumber(3), // Ensure display_order is unique for testing sorting
            'is_public' => $this->faker->boolean(80), // 80% chance of being public
            
            // Initialize type-specific fields to null
            'file_path' => null,
            'file_name' => null,
            'original_filename' => null,
            'mime_type' => null,
            'file_size' => null,
            'video_url' => null,
            'video_id' => null,
        ];

        // Add type-specific data
        if ($itemType === PortfolioItem::TYPE_AUDIO) {
            // Use a placeholder path consistent with the component logic if possible
            $userIdPlaceholder = 1; // Placeholder, might need the actual user ID later if path includes it
            $fileName = Str::random(10) . '.mp3';
            $data['file_path'] = "portfolio-audio/{$userIdPlaceholder}/{$fileName}";
            $data['file_name'] = $fileName;
            $data['original_filename'] = 'test_audio.mp3';
            $data['mime_type'] = 'audio/mpeg';
            $data['file_size'] = $this->faker->numberBetween(100000, 5000000); // Random size
        } elseif ($itemType === PortfolioItem::TYPE_YOUTUBE) {
            $videoId = Str::random(11); // Fake YouTube ID
            $data['video_url'] = 'https://www.youtube.com/watch?v=' . $videoId;
            $data['video_id'] = $videoId; // Model's boot method should also handle this
        }

        return $data;
    }
}
