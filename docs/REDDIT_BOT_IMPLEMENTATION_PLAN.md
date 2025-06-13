# Reddit Bot Implementation Plan for MixPitch

## Project Overview
Implement a "Post to r/MixPitch" button in the Quick Actions section of the project management page that allows users to automatically post their projects to the r/MixPitch subreddit using Reddit's API.

## Technical Analysis

### Current Codebase Structure
- **Framework**: Laravel 10 with Livewire 3
- **Project Management**: `app/Livewire/ManageProject.php`
- **Quick Actions UI**: `resources/views/livewire/project/page/manage-project.blade.php` (lines 160-190)
- **Job Queue**: Existing jobs in `app/Jobs/` directory
- **Services**: Service layer architecture in `app/Services/`
- **Configuration**: External APIs configured in `config/services.php`

### Reddit API Requirements (2024)
- **Authentication**: OAuth 2.0 with "script" application type
- **Rate Limits**: 100 requests per minute for authenticated requests
- **Endpoint**: `https://oauth.reddit.com/api/submit`
- **Pricing**: Free for non-commercial use up to 100 QPM
- **User-Agent**: Required for all requests

## Implementation Plan

### Phase 1: Reddit API Setup and Configuration

#### 1.1 Reddit Application Registration
```markdown
Manual Steps:
1. Create Reddit bot account (e.g., u/MixPitchBot)
2. Set up profile with avatar and description
3. Visit Reddit Apps page: https://www.reddit.com/prefs/apps
4. Create "script" type application
5. Set redirect URI: http://localhost (unused for script apps)
6. Note down client_id (14 characters) and client_secret
7. Add bot to r/MixPitch approved submitters list
```

#### 1.2 Environment Configuration
Update `.env` file:
```env
# Reddit API Configuration
REDDIT_CLIENT_ID=your_14_char_client_id
REDDIT_CLIENT_SECRET=your_client_secret
REDDIT_BOT_USERNAME=MixPitchBot
REDDIT_BOT_PASSWORD=your_bot_password
REDDIT_USER_AGENT="MixPitch/1.0 (by u/MixPitchBot)"
```

#### 1.3 Services Configuration
Add to `config/services.php`:
```php
'reddit' => [
    'client_id' => env('REDDIT_CLIENT_ID'),
    'client_secret' => env('REDDIT_CLIENT_SECRET'),
    'username' => env('REDDIT_BOT_USERNAME'),
    'password' => env('REDDIT_BOT_PASSWORD'),
    'user_agent' => env('REDDIT_USER_AGENT', 'MixPitch/1.0'),
],
```

### Phase 2: Reddit Service Implementation

#### 2.1 Reddit Service Class
Create `app/Services/RedditService.php`:
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Project;

class RedditService
{
    private string $baseUrl = 'https://oauth.reddit.com';
    private string $authUrl = 'https://www.reddit.com/api/v1/access_token';
    
    public function getAccessToken(): string
    {
        // Cache token for 50 minutes (expires in 60)
        return Cache::remember('reddit_access_token', 3000, function () {
            $response = Http::withBasicAuth(
                config('services.reddit.client_id'),
                config('services.reddit.client_secret')
            )
            ->withUserAgent(config('services.reddit.user_agent'))
            ->asForm()
            ->post($this->authUrl, [
                'grant_type' => 'password',
                'username' => config('services.reddit.username'),
                'password' => config('services.reddit.password'),
            ]);

            if (!$response->successful()) {
                Log::error('Reddit API authentication failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Reddit authentication failed');
            }

            $data = $response->json();
            return $data['access_token'] ?? throw new \Exception('No access token received');
        });
    }
    
    public function submitProject(Project $project): array
    {
        $token = $this->getAccessToken();
        
        $title = $this->formatTitle($project);
        $url = route('projects.show', $project);
        
        $response = Http::withHeaders([
            'Authorization' => "bearer {$token}",
            'User-Agent' => config('services.reddit.user_agent'),
        ])
        ->asForm()
        ->post("{$this->baseUrl}/api/submit", [
            'sr' => 'MixPitch',
            'kind' => 'link',
            'title' => $title,
            'url' => $url,
            'resubmit' => false, // Prevent duplicate submissions
        ]);

        if (!$response->successful()) {
            Log::error('Reddit submission failed', [
                'project_id' => $project->id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Reddit submission failed: ' . $response->body());
        }

        return $response->json();
    }
    
    private function formatTitle(Project $project): string
    {
        $title = "🎛️ {$project->title}";
        
        // Add genre if available
        if ($project->genre) {
            $title .= " [{$project->genre}]";
        }
        
        // Add budget info if paid
        if ($project->budget > 0) {
            $title .= " [${$project->budget} {$project->currency}]";
        }
        
        // Ensure title is under 300 characters
        if (strlen($title) > 300) {
            $title = substr($title, 0, 297) . '...';
        }
        
        return $title;
    }
}
```

#### 2.2 Job Queue Implementation
Create `app/Jobs/PostProjectToReddit.php`:
```php
<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\RedditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PostProjectToReddit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 900; // 15 minutes

    public function __construct(
        public Project $project
    ) {}

    public function handle(RedditService $redditService): void
    {
        try {
            $response = $redditService->submitProject($this->project);
            
            // Update project with Reddit post information
            $this->project->update([
                'reddit_post_id' => $response['json']['data']['id'] ?? null,
                'reddit_permalink' => $response['json']['data']['url'] ?? null,
                'reddit_posted_at' => now(),
            ]);
            
            Log::info('Project posted to Reddit successfully', [
                'project_id' => $this->project->id,
                'reddit_post_id' => $response['json']['data']['id'] ?? null,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to post project to Reddit', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            
            // Retry with exponential backoff
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff * $this->attempts());
            }
            
            throw $e;
        }
    }
}
```

### Phase 3: Database Migrations

#### 3.1 Add Reddit Tracking Fields
Create migration `database/migrations/add_reddit_fields_to_projects_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('reddit_post_id')->nullable()->after('slug');
            $table->string('reddit_permalink')->nullable()->after('reddit_post_id');
            $table->timestamp('reddit_posted_at')->nullable()->after('reddit_permalink');
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['reddit_post_id', 'reddit_permalink', 'reddit_posted_at']);
        });
    }
};
```

#### 3.2 Update Project Model
Add to `app/Models/Project.php` fillable array:
```php
protected $fillable = [
    // ... existing fields ...
    'reddit_post_id',
    'reddit_permalink', 
    'reddit_posted_at',
];

protected $casts = [
    // ... existing casts ...
    'reddit_posted_at' => 'datetime',
];

// Add helper methods
public function hasBeenPostedToReddit(): bool
{
    return !is_null($this->reddit_post_id);
}

public function getRedditUrl(): ?string
{
    return $this->reddit_permalink;
}
```

### Phase 4: Livewire Component Updates

#### 4.1 Update ManageProject Component
Add to `app/Livewire/ManageProject.php`:
```php
use App\Jobs\PostProjectToReddit;

// Add method
public function postToReddit()
{
    // Validate project is ready for posting
    if (!$this->project->is_published) {
        $this->dispatch('toast', type: 'error', message: 'Project must be published before posting to Reddit.');
        return;
    }
    
    if ($this->project->hasBeenPostedToReddit()) {
        $this->dispatch('toast', type: 'warning', message: 'This project has already been posted to Reddit.');
        return;
    }
    
    // Dispatch job
    PostProjectToReddit::dispatch($this->project);
    
    $this->dispatch('toast', type: 'success', message: 'Your project is being posted to r/MixPitch! You\'ll receive a notification when it\'s complete.');
}
```

#### 4.2 Update Quick Actions UI
Modify `resources/views/livewire/project/page/manage-project.blade.php`:

Add Reddit button to the Quick Actions grid (around line 170):
```blade
<div class="grid grid-cols-2 gap-3">
    <a href="{{ route('projects.show', $project) }}"
        class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-indigo-700 hover:shadow-lg">
        <i class="fas fa-eye mr-2"></i>View Public
    </a>
    <a href="{{ route('projects.edit', $project) }}"
        class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-gray-600 to-gray-700 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-gray-700 hover:to-gray-800 hover:shadow-lg">
        <i class="fas fa-edit mr-2"></i>Edit Project
    </a>
    
    {{-- Reddit Post Button --}}
    @if ($project->is_published)
        @if ($project->hasBeenPostedToReddit())
            <a href="{{ $project->getRedditUrl() }}" target="_blank" rel="noopener"
                class="col-span-2 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-orange-600 hover:to-red-600 hover:shadow-lg">
                <i class="fab fa-reddit mr-2"></i>View on Reddit
            </a>
        @else
            <button wire:click="postToReddit"
                class="col-span-2 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-orange-600 hover:to-red-600 hover:shadow-lg">
                <i class="fab fa-reddit mr-2"></i>Post to r/MixPitch
            </button>
        @endif
    @endif
    
    {{-- Existing publish/unpublish buttons --}}
    @if ($project->is_published)
        <button wire:click="unpublish"
            class="col-span-2 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-amber-600 hover:to-orange-600 hover:shadow-lg">
            <i class="fas fa-eye-slash mr-2"></i>Unpublish Project
        </button>
    @else
        <button wire:click="publish"
            class="col-span-2 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-green-700 hover:to-emerald-700 hover:shadow-lg">
            <i class="fas fa-globe mr-2"></i>Publish Project
        </button>
    @endif
</div>
```

### Phase 5: Error Handling and Validation

#### 5.1 Custom Exception
Create `app/Exceptions/RedditApiException.php`:
```php
<?php

namespace App\Exceptions;

use Exception;

class RedditApiException extends Exception
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Reddit API Error: {$message}", $code, $previous);
    }
}
```

#### 5.2 Validation Rules
Add validation to the `postToReddit()` method:
```php
public function postToReddit()
{
    // Validate project requirements
    if (!$this->project->is_published) {
        $this->dispatch('toast', type: 'error', message: 'Project must be published first.');
        return;
    }
    
    if (empty($this->project->title) || empty($this->project->description)) {
        $this->dispatch('toast', type: 'error', message: 'Project must have a title and description.');
        return;
    }
    
    if ($this->project->hasBeenPostedToReddit()) {
        $this->dispatch('toast', type: 'warning', message: 'Project already posted to Reddit.');
        return;
    }
    
    // Rate limiting check (optional)
    $recentPosts = auth()->user()->projects()
        ->whereNotNull('reddit_posted_at')
        ->where('reddit_posted_at', '>', now()->subHour())
        ->count();
        
    if ($recentPosts >= 3) {
        $this->dispatch('toast', type: 'error', message: 'You can only post 3 projects per hour to Reddit.');
        return;
    }
    
    PostProjectToReddit::dispatch($this->project);
    $this->dispatch('toast', type: 'success', message: 'Posting to r/MixPitch...');
}
```

### Phase 6: Testing Strategy

#### 6.1 Unit Tests
Create `tests/Unit/Services/RedditServiceTest.php`:
```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RedditService;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class RedditServiceTest extends TestCase
{
    public function test_formats_title_correctly()
    {
        $service = new RedditService();
        $project = Project::factory()->make([
            'title' => 'Test Project',
            'genre' => 'Hip Hop',
            'budget' => 500,
            'currency' => 'USD'
        ]);
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('formatTitle');
        $method->setAccessible(true);
        
        $result = $method->invoke($service, $project);
        
        $this->assertEquals('🎛️ Test Project [Hip Hop] [$500 USD]', $result);
    }
    
    public function test_submits_project_successfully()
    {
        Http::fake([
            'https://www.reddit.com/api/v1/access_token' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => 3600
            ]),
            'https://oauth.reddit.com/api/submit' => Http::response([
                'json' => [
                    'data' => [
                        'id' => 'test_post_id',
                        'url' => 'https://reddit.com/r/MixPitch/test_post'
                    ]
                ]
            ])
        ]);
        
        $service = new RedditService();
        $project = Project::factory()->create();
        
        $result = $service->submitProject($project);
        
        $this->assertArrayHasKey('json', $result);
        $this->assertEquals('test_post_id', $result['json']['data']['id']);
    }
}
```

#### 6.2 Feature Tests  
Create `tests/Feature/RedditIntegrationTest.php`:
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Jobs\PostProjectToReddit;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RedditIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_post_published_project_to_reddit()
    {
        Queue::fake();
        
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'is_published' => true
        ]);
        
        $response = $this->actingAs($user)
            ->get(route('projects.manage', $project));
            
        $response->assertStatus(200);
        $response->assertSee('Post to r/MixPitch');
        
        $this->livewire('manage-project', ['project' => $project])
            ->call('postToReddit');
            
        Queue::assertPushed(PostProjectToReddit::class);
    }
}
```

### Phase 7: Monitoring and Analytics

#### 7.1 Reddit Performance Tracking
Add method to Project model:
```php
public function getRedditEngagementStats(): array
{
    // This would require additional Reddit API calls to fetch post stats
    // Implementation depends on requirements
    return [
        'upvotes' => null,
        'comments' => null,
        'posted_at' => $this->reddit_posted_at,
        'url' => $this->reddit_permalink,
    ];
}
```

#### 7.2 Admin Dashboard Integration
Consider adding Reddit post tracking to Filament admin panel:
```php
// In ProjectResource.php
Tables\Columns\TextColumn::make('reddit_posted_at')
    ->label('Reddit Posted')
    ->dateTime()
    ->sortable(),
    
Tables\Columns\TextColumn::make('reddit_permalink')
    ->label('Reddit URL')
    ->url(fn ($record) => $record->reddit_permalink)
    ->openUrlInNewTab(),
```

## Security Considerations

1. **API Credentials**: Store securely in environment variables
2. **Rate Limiting**: Implement user-level rate limiting
3. **Input Validation**: Sanitize all project data before posting
4. **Error Logging**: Log failures without exposing sensitive data
5. **Permission Checks**: Only allow project owners to post

## Deployment Checklist

- [ ] Create Reddit bot account
- [ ] Register Reddit API application
- [ ] Add environment variables
- [ ] Run database migrations
- [ ] Deploy code changes
- [ ] Configure queue workers
- [ ] Test with sample project
- [ ] Add bot to r/MixPitch approved submitters
- [ ] Monitor logs for errors

## Maintenance Notes

- Reddit access tokens expire after 1 hour
- API rate limits: 100 requests per minute
- Monitor for Reddit API changes
- Consider upgrading to paid tier if volume increases
- Regularly check bot account status

## Future Enhancements

1. **Scheduling**: Allow users to schedule posts
2. **Templates**: Customizable post templates
3. **Analytics**: Track Reddit engagement metrics
4. **Multi-Subreddit**: Support posting to multiple subreddits
5. **Auto-Moderation**: Integrate with Reddit's moderation tools