<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MigrateUserTagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting migration of user tags...');

        // Define which columns contain tags and their corresponding tag type
        $tagColumns = [
            'skills' => 'skill',
            'equipment' => 'equipment',
            'specialties' => 'specialty',
            // Add 'genres' here if it exists and needs migration
        ];

        // Process users in chunks to avoid memory issues
        User::chunk(100, function ($users) use ($tagColumns) {
            foreach ($users as $user) {
                $tagsToSync = [];

                foreach ($tagColumns as $column => $type) {
                    // Check if the column exists and is not null
                    if (isset($user->{$column})) {
                        // Use the casted array directly (assuming it's cast in the User model)
                        $items = $user->{$column};

                        // Ensure items is an array (double-check)
                        if (is_array($items)) {
                            foreach ($items as $itemName) {
                                // Clean up the item name
                                $itemName = trim($itemName);
                                if (empty($itemName)) {
                                    continue;
                                }

                                // Find or create the tag
                                try {
                                    $tag = Tag::firstOrCreate(
                                        [
                                            'slug' => Str::slug($itemName),
                                            'type' => $type,
                                        ],
                                        [
                                            'name' => $itemName,
                                        ]
                                    );
                                    $tagsToSync[] = $tag->id;
                                } catch (\Exception $e) {
                                    Log::error("Error creating/finding tag '{$itemName}' (Type: {$type}) for User ID {$user->id}: ".$e->getMessage());
                                }
                            }
                        }
                    }
                }

                // Sync the collected tags for the user
                if (! empty($tagsToSync)) {
                    try {
                        $user->tags()->syncWithoutDetaching($tagsToSync); // Use syncWithoutDetaching if running multiple times
                        $this->command->info('Synced '.count($tagsToSync)." tags for User ID {$user->id}");
                    } catch (\Exception $e) {
                        Log::error("Error syncing tags for User ID {$user->id}: ".$e->getMessage());
                    }
                }
            }
        });

        $this->command->info('User tag migration completed.');
    }
}
