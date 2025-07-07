<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing tags to avoid duplicates
        DB::table('tags')->delete();

        $tags = [
            // Skills
            ['name' => 'Mixing', 'type' => 'skill'],
            ['name' => 'Mastering', 'type' => 'skill'],
            ['name' => 'Audio Editing', 'type' => 'skill'],
            ['name' => 'Vocal Tuning', 'type' => 'skill'],
            ['name' => 'Sound Design', 'type' => 'skill'],
            ['name' => 'Beat Making', 'type' => 'skill'],
            ['name' => 'Production', 'type' => 'skill'],
            ['name' => 'Arrangement', 'type' => 'skill'],
            ['name' => 'Composition', 'type' => 'skill'],
            ['name' => 'Songwriting', 'type' => 'skill'],
            ['name' => 'Audio Restoration', 'type' => 'skill'],
            ['name' => 'Post-Production Audio', 'type' => 'skill'],
            ['name' => 'Live Sound Engineering', 'type' => 'skill'],
            ['name' => 'Podcast Editing', 'type' => 'skill'],
            ['name' => 'Audio Forensics', 'type' => 'skill'],
            ['name' => 'Foley Artistry', 'type' => 'skill'],
            ['name' => 'Audio Programming', 'type' => 'skill'],
            ['name' => 'Music Supervision', 'type' => 'skill'],
            ['name' => 'ADR Recording', 'type' => 'skill'],
            ['name' => 'Field Recording', 'type' => 'skill'],
            ['name' => 'DJing', 'type' => 'skill'],
            ['name' => 'Remixing', 'type' => 'skill'],

            // Equipment
            ['name' => 'Pro Tools', 'type' => 'equipment'],
            ['name' => 'Logic Pro X', 'type' => 'equipment'],
            ['name' => 'Ableton Live', 'type' => 'equipment'],
            ['name' => 'FL Studio', 'type' => 'equipment'],
            ['name' => 'Cubase', 'type' => 'equipment'],
            ['name' => 'Reaper', 'type' => 'equipment'],
            ['name' => 'Studio One', 'type' => 'equipment'],
            ['name' => 'Universal Audio Apollo', 'type' => 'equipment'],
            ['name' => 'Focusrite Scarlett', 'type' => 'equipment'],
            ['name' => 'Neumann U87', 'type' => 'equipment'],
            ['name' => 'Shure SM7B', 'type' => 'equipment'],
            ['name' => 'Yamaha NS-10', 'type' => 'equipment'],
            ['name' => 'Genelec Monitors', 'type' => 'equipment'],
            ['name' => 'API Console', 'type' => 'equipment'],
            ['name' => 'SSL Console', 'type' => 'equipment'],
            ['name' => 'Neve Console', 'type' => 'equipment'],
            ['name' => 'Maschine', 'type' => 'equipment'],
            ['name' => 'MPC', 'type' => 'equipment'],
            ['name' => 'Analog Synthesizers', 'type' => 'equipment'],
            ['name' => 'Guitar Pedals', 'type' => 'equipment'],
            ['name' => 'Outboard Gear (Compressors, EQs)', 'type' => 'equipment'],
            ['name' => 'Acoustic Treatment', 'type' => 'equipment'],
            ['name' => 'iZotope RX', 'type' => 'equipment'],
            ['name' => 'Waves Plugins', 'type' => 'equipment'],
            ['name' => 'FabFilter Plugins', 'type' => 'equipment'],
            ['name' => 'Soundtoys Plugins', 'type' => 'equipment'],

            // Specialties (Genres/Styles)
            ['name' => 'Pop', 'type' => 'specialty'],
            ['name' => 'Rock', 'type' => 'specialty'],
            ['name' => 'Hip Hop', 'type' => 'specialty'],
            ['name' => 'R&B', 'type' => 'specialty'],
            ['name' => 'Electronic', 'type' => 'specialty'],
            ['name' => 'EDM', 'type' => 'specialty'],
            ['name' => 'House', 'type' => 'specialty'],
            ['name' => 'Techno', 'type' => 'specialty'],
            ['name' => 'Jazz', 'type' => 'specialty'],
            ['name' => 'Blues', 'type' => 'specialty'],
            ['name' => 'Country', 'type' => 'specialty'],
            ['name' => 'Folk', 'type' => 'specialty'],
            ['name' => 'Metal', 'type' => 'specialty'],
            ['name' => 'Punk', 'type' => 'specialty'],
            ['name' => 'Classical', 'type' => 'specialty'],
            ['name' => 'Orchestral', 'type' => 'specialty'],
            ['name' => 'Film Score', 'type' => 'specialty'],
            ['name' => 'Game Audio', 'type' => 'specialty'],
            ['name' => 'Reggae', 'type' => 'specialty'],
            ['name' => 'Funk', 'type' => 'specialty'],
            ['name' => 'Soul', 'type' => 'specialty'],
            ['name' => 'Gospel', 'type' => 'specialty'],
            ['name' => 'Latin', 'type' => 'specialty'],
            ['name' => 'World Music', 'type' => 'specialty'],
            ['name' => 'Ambient', 'type' => 'specialty'],

        ];

        foreach ($tags as $tagData) {
            Tag::create($tagData);
        }
    }
}
