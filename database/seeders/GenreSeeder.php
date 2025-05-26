<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Genre; // Import Genre model
use Illuminate\Support\Facades\DB; // Import DB facade

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Basic list of genres
        $genres = [
            ['name' => 'Pop', 'slug' => 'pop'],
            ['name' => 'Rock', 'slug' => 'rock'],
            ['name' => 'Hip Hop', 'slug' => 'hip-hop'],
            ['name' => 'Electronic', 'slug' => 'electronic'],
            ['name' => 'R&B', 'slug' => 'rnb'],
            ['name' => 'Country', 'slug' => 'country'],
            ['name' => 'Jazz', 'slug' => 'jazz'],
            ['name' => 'Classical', 'slug' => 'classical'],
            ['name' => 'Metal', 'slug' => 'metal'],
            ['name' => 'Blues', 'slug' => 'blues'],
        ];

        // Use DB::table for potential performance or to avoid observer triggers if Genre model has them
        DB::table('genres')->insert($genres);

        // Or use the model if observers are not an issue:
        // foreach ($genres as $genreData) {
        //     Genre::create($genreData);
        // }
    }
}
