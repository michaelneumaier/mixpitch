<?php

use App\Jobs\UpdateRedditPostForPitchAccepted;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\RedditService;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->producer = User::factory()->create([
        'username' => 'producerguy',
        'reddit_username' => 'producerguy_reddit',
        'reddit_user_id' => 'r-1',
    ]);
    $this->project = Project::factory()->for($this->owner)->create([
        'title' => 'Test',
        'reddit_post_id' => 'abc123',
        'reddit_permalink' => 'https://reddit.com/r/MixPitch/comments/abc123/test/',
        'reddit_posted_at' => now()->subHour(),
        'reddit_original_body' => 'Original post body content',
    ]);
    $this->pitch = Pitch::factory()->for($this->producer)->for($this->project)->create();
});

it('edits the post to prepend an IN PROGRESS header and posts a comment', function () {
    $service = Mockery::mock(RedditService::class);

    $service->shouldReceive('editPost')
        ->once()
        ->with('t3_abc123', Mockery::on(fn ($body) => str_contains($body, 'IN PROGRESS')
            && str_contains($body, 'u/producerguy_reddit')
            && str_ends_with($body, 'Original post body content')))
        ->andReturn(['json' => ['errors' => []]]);

    $service->shouldReceive('postComment')
        ->once()
        ->with('t3_abc123', Mockery::on(fn ($body) => str_contains($body, 'accepted by')
            && str_contains($body, 'u/producerguy_reddit')
            && str_contains($body, '/projects/')))
        ->andReturn(['json' => ['errors' => []]]);

    (new UpdateRedditPostForPitchAccepted($this->project, $this->pitch))->handle($service);
});

it('falls back to MixPitch username when producer has no linked Reddit account', function () {
    $this->producer->update(['reddit_username' => null, 'reddit_user_id' => null]);
    $this->pitch->refresh();

    $service = Mockery::mock(RedditService::class);
    $service->shouldReceive('editPost')
        ->once()
        ->with('t3_abc123', Mockery::on(fn ($body) => str_contains($body, '@producerguy on MixPitch')))
        ->andReturn(['json' => ['errors' => []]]);
    $service->shouldReceive('postComment')
        ->once()
        ->andReturn(['json' => ['errors' => []]]);

    (new UpdateRedditPostForPitchAccepted($this->project, $this->pitch))->handle($service);
});

it('bails silently when the project no longer has a reddit_post_id', function () {
    $this->project->update(['reddit_post_id' => null]);

    $service = Mockery::mock(RedditService::class);
    $service->shouldNotReceive('editPost');
    $service->shouldNotReceive('postComment');

    (new UpdateRedditPostForPitchAccepted($this->project, $this->pitch))->handle($service);
});

it('still posts a comment even if the edit fails', function () {
    $service = Mockery::mock(RedditService::class);
    $service->shouldReceive('editPost')->once()->andThrow(new \Exception('403 not author'));
    $service->shouldReceive('postComment')->once()->andReturn(['json' => ['errors' => []]]);

    (new UpdateRedditPostForPitchAccepted($this->project, $this->pitch))->handle($service);
});
