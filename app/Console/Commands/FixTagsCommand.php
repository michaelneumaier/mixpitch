<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;

class FixTagsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-tags {--user=} {--reset} {--diagnose}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose and fix tag relationships for users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting tag diagnosis...');
        
        // Get all users or just one if specified
        $userId = $this->option('user');
        if ($userId) {
            $users = User::where('id', $userId)->get();
            if ($users->isEmpty()) {
                $this->error("User with ID {$userId} not found");
                return 1;
            }
        } else {
            $users = User::all();
        }
        
        $this->info('Found ' . $users->count() . ' users to process');
        
        // Diagnose mode just shows information
        if ($this->option('diagnose')) {
            $this->diagnoseTags($users);
            return 0;
        }
        
        // Reset option clears all user tags before setting them
        $reset = $this->option('reset');
        if ($reset) {
            if (!$this->confirm('This will RESET ALL TAGS for the specified users. Are you sure?')) {
                $this->info('Operation cancelled');
                return 0;
            }
        }
        
        // Process each user
        foreach ($users as $user) {
            $this->info("Processing user: {$user->username} (ID: {$user->id})");
            
            // Show current tags
            $currentTags = $user->tags()->get();
            $this->info("  Current tags: " . $currentTags->count());
            foreach ($currentTags as $tag) {
                $this->line("    - {$tag->name} ({$tag->type})");
            }
            
            // Reset if requested
            if ($reset) {
                $this->info("  Resetting all tags for user...");
                $user->tags()->detach();
                $this->info("  Tags reset");
            }
            
            // Fix tags by ensuring default tags are assigned
            $this->info("  Checking for missing tags...");
            
            // Get the available tags
            $skillTags = Tag::where('type', 'skill')->get();
            $equipmentTags = Tag::where('type', 'equipment')->get();
            $specialtyTags = Tag::where('type', 'specialty')->get();
            
            // Assign one of each type if they don't have any
            $updated = false;
            
            if (!$user->tags()->where('type', 'skill')->exists() && $skillTags->isNotEmpty()) {
                $user->tags()->attach($skillTags->first()->id);
                $this->info("  Added skill tag: {$skillTags->first()->name}");
                $updated = true;
            }
            
            if (!$user->tags()->where('type', 'equipment')->exists() && $equipmentTags->isNotEmpty()) {
                $user->tags()->attach($equipmentTags->first()->id);
                $this->info("  Added equipment tag: {$equipmentTags->first()->name}");
                $updated = true;
            }
            
            if (!$user->tags()->where('type', 'specialty')->exists() && $specialtyTags->isNotEmpty()) {
                $user->tags()->attach($specialtyTags->first()->id);
                $this->info("  Added specialty tag: {$specialtyTags->first()->name}");
                $updated = true;
            }
            
            // Verify the result
            $user->load('tags'); // Refresh relationship
            $finalTags = $user->tags;
            
            $this->info("  Final tags: " . $finalTags->count());
            foreach ($finalTags as $tag) {
                $this->line("    - {$tag->name} ({$tag->type})");
            }
            
            if ($updated) {
                $this->info("  Tags updated for {$user->username}");
            } else {
                $this->info("  No changes needed for {$user->username}");
            }
            
            $this->newLine();
        }
        
        $this->info('Tag fix operation completed');
        return 0;
    }
    
    /**
     * Diagnose tag issues without making changes
     */
    protected function diagnoseTags($users)
    {
        $this->info('DIAGNOSTIC INFORMATION:');
        $this->newLine();
        
        // Check tags table
        $tagCount = Tag::count();
        $this->info("Tags in database: {$tagCount}");
        if ($tagCount == 0) {
            $this->error("No tags found in the database! You need to create tags first.");
            return;
        }
        
        Tag::get()->each(function($tag) {
            $this->line("  - ID {$tag->id}: {$tag->name} ({$tag->type})");
        });
        $this->newLine();
        
        // Check taggables table
        $taggablesCount = DB::table('taggables')->count();
        $this->info("Entries in taggables table: {$taggablesCount}");
        
        if ($taggablesCount > 0) {
            $tagRelationships = DB::table('taggables')
                ->join('tags', 'taggables.tag_id', '=', 'tags.id')
                ->select('taggables.taggable_id', 'taggables.taggable_type', 'tags.name', 'tags.type')
                ->get();
                
            $grouped = $tagRelationships->groupBy('taggable_id');
            
            foreach ($grouped as $userId => $userTags) {
                $user = User::find($userId);
                $username = $user ? $user->username : "Unknown (ID: {$userId})";
                $this->line("  - {$username} has {$userTags->count()} tags:");
                
                foreach ($userTags as $tag) {
                    $this->line("    * {$tag->name} ({$tag->type})");
                }
            }
        } else {
            $this->warn("No tag relationships found in the taggables table!");
        }
        $this->newLine();
        
        // Check each user's tags
        $this->info("User tag counts from relationship:");
        foreach ($users as $user) {
            $count = $user->tags()->count();
            $this->line("  - {$user->username} (ID: {$user->id}): {$count} tags");
            
            if ($count > 0) {
                $user->tags()->get()->each(function($tag) {
                    $this->line("    * {$tag->name} ({$tag->type})");
                });
            }
        }
    }
}
