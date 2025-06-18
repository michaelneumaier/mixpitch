# Advanced Reddit Bot Implementation Plan

## Overview

This document outlines the implementation of advanced Reddit bot features for MixPitch, extending beyond basic project posting to include user control, automation, and lifecycle management.

## Features to Implement

### 1. User Control Features
- **Delete Reddit Posts**: Allow users to remove posts from Reddit
- **Edit Post Updates**: Update post content when project details change
- **Manual Status Updates**: Let users manually update post status/content
- **Post Performance Analytics**: Show Reddit engagement metrics

### 2. Automated Features
- **Status Change Detection**: Auto-update posts when project status changes
- **Deadline Updates**: Update posts when deadlines are modified
- **Completion Notifications**: Add completion status to posts
- **Budget Changes**: Reflect budget updates in posts

### 3. Enhanced Database Schema
- Track additional Reddit metadata
- Store edit history and automation preferences
- Monitor post performance metrics

## Phase 1: Enhanced Database Schema

### 1.1 Migration: Extended Reddit Fields
```php
<?php
// database/migrations/YYYY_MM_DD_HHMMSS_add_advanced_reddit_fields_to_projects.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            // Reddit automation preferences
            $table->boolean('reddit_auto_update')->default(true);
            $table->boolean('reddit_auto_status_update')->default(true);
            $table->boolean('reddit_auto_deadline_update')->default(true);
            
            // Reddit post analytics
            $table->integer('reddit_upvotes')->nullable();
            $table->integer('reddit_downvotes')->nullable();
            $table->integer('reddit_comments_count')->nullable();
            $table->timestamp('reddit_last_updated_at')->nullable();
            $table->timestamp('reddit_analytics_updated_at')->nullable();
            
            // Reddit post management
            $table->json('reddit_edit_history')->nullable();
            $table->string('reddit_flair_id')->nullable();
            $table->string('reddit_flair_text')->nullable();
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'reddit_auto_update',
                'reddit_auto_status_update', 
                'reddit_auto_deadline_update',
                'reddit_upvotes',
                'reddit_downvotes',
                'reddit_comments_count',
                'reddit_last_updated_at',
                'reddit_analytics_updated_at',
                'reddit_edit_history',
                'reddit_flair_id',
                'reddit_flair_text'
            ]);
        });
    }
};
```

### 1.2 Model Updates
Add methods to `app/Models/Project.php`:
```php
/**
 * Check if Reddit auto-updates are enabled
 */
public function shouldAutoUpdateReddit(): bool
{
    return $this->reddit_auto_update && $this->hasBeenPostedToReddit();
}

/**
 * Get Reddit edit history
 */
public function getRedditEditHistory(): array
{
    return json_decode($this->reddit_edit_history, true) ?? [];
}

/**
 * Add entry to Reddit edit history
 */
public function addRedditEditHistory(string $action, array $data = []): void
{
    $history = $this->getRedditEditHistory();
    $history[] = [
        'action' => $action,
        'data' => $data,
        'timestamp' => now()->toISOString(),
        'user_id' => auth()->id()
    ];
    
    // Keep only last 50 entries
    if (count($history) > 50) {
        $history = array_slice($history, -50);
    }
    
    $this->update(['reddit_edit_history' => json_encode($history)]);
}

/**
 * Update Reddit analytics
 */
public function updateRedditAnalytics(array $analytics): void
{
    $this->update([
        'reddit_upvotes' => $analytics['ups'] ?? null,
        'reddit_downvotes' => $analytics['downs'] ?? null,
        'reddit_comments_count' => $analytics['num_comments'] ?? null,
        'reddit_analytics_updated_at' => now()
    ]);
}
```

## Phase 2: Enhanced Reddit Service

### 2.1 Extended RedditService Methods
Add to `app/Services/RedditService.php`:
```php
/**
 * Delete a Reddit post
 */
public function deletePost(string $postId): bool
{
    $token = $this->getAccessToken();
    
    $response = Http::withHeaders([
        'Authorization' => "bearer {$token}",
        'User-Agent' => config('services.reddit.user_agent'),
    ])
    ->asForm()
    ->post("{$this->baseUrl}/api/del", [
        'id' => "t3_{$postId}", // t3_ prefix for posts
    ]);

    if (!$response->successful()) {
        Log::error('Reddit post deletion failed', [
            'post_id' => $postId,
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        return false;
    }

    return true;
}

/**
 * Edit a Reddit text post
 */
public function editPost(string $postId, Project $project): bool
{
    $token = $this->getAccessToken();
    $text = $this->formatText($project);
    
    $response = Http::withHeaders([
        'Authorization' => "bearer {$token}",
        'User-Agent' => config('services.reddit.user_agent'),
    ])
    ->asForm()
    ->post("{$this->baseUrl}/api/editusertext", [
        'thing_id' => "t3_{$postId}",
        'text' => $text,
    ]);

    if (!$response->successful()) {
        Log::error('Reddit post edit failed', [
            'post_id' => $postId,
            'project_id' => $project->id,
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        return false;
    }

    return true;
}

/**
 * Add comment to Reddit post
 */
public function addComment(string $postId, string $comment): ?string
{
    $token = $this->getAccessToken();
    
    $response = Http::withHeaders([
        'Authorization' => "bearer {$token}",
        'User-Agent' => config('services.reddit.user_agent'),
    ])
    ->asForm()
    ->post("{$this->baseUrl}/api/comment", [
        'parent' => "t3_{$postId}",
        'text' => $comment,
    ]);

    if (!$response->successful()) {
        Log::error('Reddit comment failed', [
            'post_id' => $postId,
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        return null;
    }

    $data = $response->json();
    return $data['json']['data']['things'][0]['data']['id'] ?? null;
}

/**
 * Get post analytics
 */
public function getPostAnalytics(string $postId): ?array
{
    $token = $this->getAccessToken();
    
    $response = Http::withHeaders([
        'Authorization' => "bearer {$token}",
        'User-Agent' => config('services.reddit.user_agent'),
    ])
    ->get("{$this->baseUrl}/api/info", [
        'id' => "t3_{$postId}",
    ]);

    if (!$response->successful()) {
        Log::error('Reddit analytics fetch failed', [
            'post_id' => $postId,
            'status' => $response->status()
        ]);
        return null;
    }

    $data = $response->json();
    return $data['data']['children'][0]['data'] ?? null;
}

/**
 * Set post flair
 */
public function setPostFlair(string $postId, string $flairId): bool
{
    $token = $this->getAccessToken();
    
    $response = Http::withHeaders([
        'Authorization' => "bearer {$token}",
        'User-Agent' => config('services.reddit.user_agent'),
    ])
    ->asForm()
    ->post("{$this->baseUrl}/api/selectflair", [
        'link' => "t3_{$postId}",
        'flair_template_id' => $flairId,
    ]);

    return $response->successful();
}

/**
 * Enhanced text formatting with status
 */
private function formatTextWithStatus(Project $project): string
{
    $text = "**{$project->title}**\n\n";
    
    // Status badge
    $statusBadge = $this->getStatusBadge($project);
    if ($statusBadge) {
        $text .= "{$statusBadge}\n\n";
    }
    
    $text .= $project->description . "\n\n";
    $text .= "**💰 Budget:** " . ($project->budget_display ?: 'Not specified') . "\n\n";
    
    if ($project->genre) {
        $text .= "**🎵 Genre:** {$project->genre}\n\n";
    }
    
    if ($project->artist_name) {
        $text .= "**🎤 Artist:** {$project->artist_name}\n\n";
    }
    
    // Deadlines
    $deadlines = $this->formatDeadlines($project);
    if (!empty($deadlines)) {
        $text .= implode("\n\n", $deadlines) . "\n\n";
    }
    
    // Status-specific information
    $text .= $this->getStatusSpecificText($project);
    
    $projectUrl = route('projects.show', $project);
    $text .= "**[📝 View Full Project Details & Apply]({$projectUrl})**\n\n";
    $text .= "*Posted via [MixPitch.io](https://mixpitch.io) - Connect artists with music producers*";
    
    return $text;
}

private function getStatusBadge(Project $project): ?string
{
    return match ($project->status) {
        'open' => '🟢 **OPEN** - Accepting applications',
        'in_progress' => '🟡 **IN PROGRESS** - Work in progress',
        'completed' => '✅ **COMPLETED** - Project finished',
        default => null
    };
}

private function getStatusSpecificText(Project $project): string
{
    return match ($project->status) {
        'open' => "Ready to collaborate? Apply now!\n\n",
        'in_progress' => "This project is currently being worked on. Check back for updates!\n\n",
        'completed' => "This project has been completed successfully. Thanks to everyone who participated!\n\n",
        default => ""
    };
}
```

## Phase 3: Background Jobs

### 3.1 Delete Reddit Post Job
```php
<?php
// app/Jobs/DeleteRedditPost.php

namespace App\Jobs;

use App\Models\Project;
use App\Services\RedditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteRedditPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 300; // 5 minutes

    public function __construct(
        public Project $project,
        public string $reason = 'User deleted'
    ) {}

    public function handle(RedditService $redditService): void
    {
        if (!$this->project->reddit_post_id) {
            Log::warning('Attempted to delete Reddit post but no post ID found', [
                'project_id' => $this->project->id
            ]);
            return;
        }

        try {
            $success = $redditService->deletePost($this->project->reddit_post_id);
            
            if ($success) {
                // Clear Reddit post data
                $this->project->update([
                    'reddit_post_id' => null,
                    'reddit_permalink' => null,
                    'reddit_posted_at' => null,
                    'reddit_last_updated_at' => null,
                    'reddit_upvotes' => null,
                    'reddit_downvotes' => null,
                    'reddit_comments_count' => null,
                ]);
                
                $this->project->addRedditEditHistory('deleted', [
                    'reason' => $this->reason
                ]);
                
                Log::info('Reddit post deleted successfully', [
                    'project_id' => $this->project->id,
                    'reason' => $this->reason
                ]);
            } else {
                throw new \Exception('Reddit API returned false for deletion');
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to delete Reddit post', [
                'project_id' => $this->project->id,
                'reddit_post_id' => $this->project->reddit_post_id,
                'error' => $e->getMessage(),
                'reason' => $this->reason
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }
}
```

### 3.2 Update Reddit Post Job
```php
<?php
// app/Jobs/UpdateRedditPost.php

namespace App\Jobs;

use App\Models\Project;
use App\Services\RedditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateRedditPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 300;

    public function __construct(
        public Project $project,
        public string $updateType = 'manual',
        public array $changes = []
    ) {}

    public function handle(RedditService $redditService): void
    {
        if (!$this->project->reddit_post_id) {
            Log::warning('Attempted to update Reddit post but no post ID found', [
                'project_id' => $this->project->id
            ]);
            return;
        }

        try {
            $success = $redditService->editPost($this->project->reddit_post_id, $this->project);
            
            if ($success) {
                $this->project->update([
                    'reddit_last_updated_at' => now()
                ]);
                
                $this->project->addRedditEditHistory('updated', [
                    'type' => $this->updateType,
                    'changes' => $this->changes
                ]);
                
                Log::info('Reddit post updated successfully', [
                    'project_id' => $this->project->id,
                    'update_type' => $this->updateType,
                    'changes' => $this->changes
                ]);
            } else {
                throw new \Exception('Reddit API returned false for update');
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update Reddit post', [
                'project_id' => $this->project->id,
                'reddit_post_id' => $this->project->reddit_post_id,
                'error' => $e->getMessage(),
                'update_type' => $this->updateType
            ]);
            
            throw $e;
        }
    }
}
```

### 3.3 Fetch Reddit Analytics Job
```php
<?php
// app/Jobs/FetchRedditAnalytics.php

namespace App\Jobs;

use App\Models\Project;
use App\Services\RedditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchRedditAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 600; // 10 minutes

    public function __construct(public Project $project) {}

    public function handle(RedditService $redditService): void
    {
        if (!$this->project->reddit_post_id) {
            return;
        }

        try {
            $analytics = $redditService->getPostAnalytics($this->project->reddit_post_id);
            
            if ($analytics) {
                $this->project->updateRedditAnalytics($analytics);
                
                Log::info('Reddit analytics updated', [
                    'project_id' => $this->project->id,
                    'upvotes' => $analytics['ups'] ?? 0,
                    'comments' => $analytics['num_comments'] ?? 0
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch Reddit analytics', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

## Phase 4: Event Listeners for Automation

### 4.1 Project Observer Enhancement
Add to `app/Observers/ProjectObserver.php`:
```php
/**
 * Handle the Project "updated" event.
 */
public function updated(Project $project): void
{
    // Check if Reddit auto-updates are enabled
    if (!$project->shouldAutoUpdateReddit()) {
        return;
    }

    $relevantFields = [
        'title', 'description', 'budget', 'genre', 
        'artist_name', 'deadline', 'status'
    ];

    $changes = [];
    foreach ($relevantFields as $field) {
        if ($project->isDirty($field)) {
            $changes[$field] = [
                'old' => $project->getOriginal($field),
                'new' => $project->getAttribute($field)
            ];
        }
    }

    if (!empty($changes)) {
        // Dispatch update job
        \App\Jobs\UpdateRedditPost::dispatch(
            $project,
            'auto_update',
            $changes
        )->delay(now()->addMinutes(2)); // Small delay to ensure transaction commits

        Log::info('Scheduled Reddit post update due to project changes', [
            'project_id' => $project->id,
            'changes' => array_keys($changes)
        ]);
    }
}
```

## Phase 5: Enhanced UI Components

### 5.1 Reddit Management Section
Add to `resources/views/livewire/project/page/manage-project.blade.php`:
```blade
{{-- Reddit Management Section --}}
@if ($project->hasBeenPostedToReddit())
    <div class="bg-gradient-to-br from-orange-50 to-red-50 rounded-2xl p-6 border border-orange-200/50">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fab fa-reddit text-orange-500 mr-2"></i>Reddit Management
            </h3>
            <div class="flex items-center space-x-2">
                @if ($project->reddit_upvotes !== null)
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-arrow-up text-green-500"></i> {{ $project->reddit_upvotes }}
                        <i class="fas fa-comment text-blue-500 ml-2"></i> {{ $project->reddit_comments_count ?? 0 }}
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <a href="{{ $project->getRedditUrl() }}" target="_blank" rel="noopener"
                class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-orange-600 hover:to-red-600 hover:shadow-lg">
                <i class="fab fa-reddit mr-2"></i>View on Reddit
            </a>
            
            <button wire:click="updateRedditPost"
                class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-blue-500 to-indigo-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-blue-600 hover:to-indigo-600 hover:shadow-lg">
                <i class="fas fa-sync mr-2"></i>Update Post
            </button>
            
            <button wire:click="confirmDeleteRedditPost"
                class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-red-500 to-pink-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-red-600 hover:to-pink-600 hover:shadow-lg">
                <i class="fas fa-trash mr-2"></i>Delete Post
            </button>
        </div>

        {{-- Auto-update Settings --}}
        <div class="bg-white/70 rounded-xl p-4 border border-orange-200/30">
            <h4 class="font-medium text-gray-900 mb-3">Automation Settings</h4>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" wire:model="project.reddit_auto_update" class="rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                    <span class="ml-2 text-sm text-gray-700">Auto-update Reddit post when project changes</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model="project.reddit_auto_status_update" class="rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                    <span class="ml-2 text-sm text-gray-700">Auto-update when project status changes</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model="project.reddit_auto_deadline_update" class="rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                    <span class="ml-2 text-sm text-gray-700">Auto-update when deadlines change</span>
                </label>
            </div>
        </div>

        {{-- Last Updated Info --}}
        @if ($project->reddit_last_updated_at)
            <div class="mt-3 text-xs text-gray-500">
                Last updated: {{ $project->reddit_last_updated_at->diffForHumans() }}
            </div>
        @endif
    </div>
@endif
```

### 5.2 Enhanced Livewire Methods
Add to `app/Livewire/ManageProject.php`:
```php
/**
 * Update Reddit post manually
 */
public function updateRedditPost()
{
    try {
        $this->authorize('update', $this->project);

        if (!$this->project->hasBeenPostedToReddit()) {
            Toaster::error('This project has not been posted to Reddit.');
            return;
        }

        // Dispatch update job
        \App\Jobs\UpdateRedditPost::dispatch($this->project, 'manual');
        
        Toaster::success('Reddit post update has been queued. Changes will appear shortly.');
        
        Log::info('Manual Reddit post update requested', [
            'project_id' => $this->project->id,
            'user_id' => auth()->id()
        ]);

    } catch (AuthorizationException $e) {
        Toaster::error('You are not authorized to update this project.');
    } catch (\Exception $e) {
        Log::error('Error updating Reddit post', [
            'project_id' => $this->project->id,
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);
        Toaster::error('An error occurred while updating the Reddit post.');
    }
}

/**
 * Confirm Reddit post deletion
 */
public function confirmDeleteRedditPost()
{
    $this->showDeleteRedditConfirmation = true;
}

/**
 * Cancel Reddit post deletion
 */
public function cancelDeleteRedditPost()
{
    $this->showDeleteRedditConfirmation = false;
}

/**
 * Delete Reddit post
 */
public function deleteRedditPost()
{
    try {
        $this->authorize('update', $this->project);

        if (!$this->project->hasBeenPostedToReddit()) {
            Toaster::error('This project has not been posted to Reddit.');
            return;
        }

        // Dispatch deletion job
        \App\Jobs\DeleteRedditPost::dispatch($this->project, 'User deleted');
        
        $this->showDeleteRedditConfirmation = false;
        Toaster::success('Reddit post deletion has been queued.');
        
        Log::info('Reddit post deletion requested', [
            'project_id' => $this->project->id,
            'user_id' => auth()->id()
        ]);

    } catch (AuthorizationException $e) {
        Toaster::error('You are not authorized to delete this post.');
    } catch (\Exception $e) {
        Log::error('Error deleting Reddit post', [
            'project_id' => $this->project->id,
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);
        Toaster::error('An error occurred while deleting the Reddit post.');
    }
}

/**
 * Refresh Reddit analytics
 */
public function refreshRedditAnalytics()
{
    if ($this->project->hasBeenPostedToReddit()) {
        \App\Jobs\FetchRedditAnalytics::dispatch($this->project);
        Toaster::info('Reddit analytics refresh queued.');
    }
}
```

## Phase 6: Console Commands

### 6.1 Analytics Refresh Command
```php
<?php
// app/Console/Commands/RefreshRedditAnalytics.php

namespace App\Console\Commands;

use App\Jobs\FetchRedditAnalytics;
use App\Models\Project;
use Illuminate\Console\Command;

class RefreshRedditAnalytics extends Command
{
    protected $signature = 'reddit:refresh-analytics {--project=}';
    protected $description = 'Refresh Reddit analytics for posted projects';

    public function handle()
    {
        $query = Project::whereNotNull('reddit_post_id');
        
        if ($this->option('project')) {
            $query->where('id', $this->option('project'));
        }
        
        $projects = $query->get();
        
        $this->info("Refreshing Reddit analytics for {$projects->count()} projects...");
        
        foreach ($projects as $project) {
            FetchRedditAnalytics::dispatch($project);
            $this->line("Queued analytics refresh for project {$project->id}");
        }
        
        $this->info('All analytics refresh jobs queued successfully.');
    }
}
```

## Phase 7: Testing Strategy

### 7.1 Feature Tests
```php
<?php
// tests/Feature/RedditAdvancedFeaturesTest.php

namespace Tests\Feature;

use App\Jobs\DeleteRedditPost;
use App\Jobs\UpdateRedditPost;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RedditAdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_reddit_post()
    {
        Queue::fake();
        
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'reddit_post_id' => 'abc123',
            'reddit_permalink' => 'https://reddit.com/r/MixPitch/comments/abc123/test/'
        ]);

        $this->actingAs($user)
            ->livewire(\App\Livewire\ManageProject::class, ['project' => $project])
            ->call('deleteRedditPost');

        Queue::assertPushed(DeleteRedditPost::class);
    }

    public function test_user_can_update_reddit_post()
    {
        Queue::fake();
        
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'reddit_post_id' => 'abc123'
        ]);

        $this->actingAs($user)
            ->livewire(\App\Livewire\ManageProject::class, ['project' => $project])
            ->call('updateRedditPost');

        Queue::assertPushed(UpdateRedditPost::class);
    }

    public function test_project_status_change_triggers_reddit_update()
    {
        Queue::fake();
        
        $project = Project::factory()->create([
            'reddit_post_id' => 'abc123',
            'reddit_auto_status_update' => true
        ]);

        $project->update(['status' => 'completed']);

        Queue::assertPushed(UpdateRedditPost::class);
    }
}
```

## Implementation Timeline

### Week 1: Database & Core Services
- [ ] Create migration for advanced Reddit fields
- [ ] Update Project model with new methods
- [ ] Extend RedditService with delete/edit/analytics methods

### Week 2: Background Jobs & Automation
- [ ] Create DeleteRedditPost job
- [ ] Create UpdateRedditPost job  
- [ ] Create FetchRedditAnalytics job
- [ ] Update ProjectObserver for automation

### Week 3: UI & User Controls
- [ ] Add Reddit management section to project management page
- [ ] Implement Livewire methods for user actions
- [ ] Add confirmation dialogs and loading states

### Week 4: Testing & Polish
- [ ] Write comprehensive tests
- [ ] Add console commands
- [ ] Performance optimization
- [ ] Documentation updates

## Benefits Summary

1. **Professional Project Management**: Complete lifecycle management on Reddit
2. **Enhanced User Control**: Full CRUD operations for Reddit posts
3. **Automation**: Reduces manual work while keeping posts current
4. **Analytics**: Track engagement and performance
5. **Error Handling**: Robust error handling and retry mechanisms
6. **User Experience**: Intuitive controls with proper feedback

This implementation transforms the basic Reddit posting feature into a comprehensive social media management tool that enhances user engagement and maintains professional project representation across platforms. 