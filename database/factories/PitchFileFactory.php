<?php

namespace Database\Factories;

use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PitchFile>
 */
class PitchFileFactory extends Factory
{
    protected $model = PitchFile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $storagePath = 'pitches/' . $this->faker->uuid() . '.mp3';
        
        return [
            'pitch_id' => Pitch::factory(),
            'user_id' => User::factory(),
            'file_name' => $this->faker->word() . '.mp3',
            'original_file_name' => $this->faker->word() . '.mp3',
            'mime_type' => $this->faker->mimeType(),
            'storage_path' => $storagePath,
            'file_path' => $storagePath,
            'size' => $this->faker->numberBetween(1000, 5000000),
        ];
    }
} 