<?php

use App\Jobs\PostProjectToReddit;
use App\Models\Project;
use App\Models\User;
use App\Services\RedditService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->for($this->user)->create([
        'title' => 'Test Project for Reddit Job',
    ]);
});

it('updates the project with Reddit post metadata and captures the original body on successful submission', function () {
    $service = Mockery::mock(RedditService::class);
    $service->shouldReceive('submitProject')
        ->once()
        ->with(Mockery::on(fn ($p) => $p->id === $this->project->id))
        ->andReturn([
            'json' => [
                'data' => [
                    'id' => 'redditpost1',
                    'url' => 'https://reddit.com/r/MixPitch/comments/redditpost1/test/',
                    'permalink' => 'https://reddit.com/r/MixPitch/comments/redditpost1/test/',
                ],
            ],
        ]);
    $service->shouldReceive('buildPostBody')
        ->once()
        ->andReturn('the-formatted-initial-body');

    (new PostProjectToReddit($this->project))->handle($service);

    $this->project->refresh();
    expect($this->project->reddit_post_id)->toBe('redditpost1');
    expect($this->project->reddit_permalink)->toBe('https://reddit.com/r/MixPitch/comments/redditpost1/test/');
    expect($this->project->reddit_posted_at)->not->toBeNull();
    expect($this->project->reddit_original_body)->toBe('the-formatted-initial-body');
});

it('throws and does not update the project when Reddit returns no post id', function () {
    $service = Mockery::mock(RedditService::class);
    $service->shouldReceive('submitProject')
        ->once()
        ->andReturn([
            'json' => [
                'data' => [
                    'id' => null,
                    'url' => null,
                    'permalink' => null,
                ],
            ],
        ]);

    expect(fn () => (new PostProjectToReddit($this->project))->handle($service))
        ->toThrow(\Exception::class, 'Reddit API did not return a post ID');

    $this->project->refresh();
    expect($this->project->reddit_post_id)->toBeNull();
    expect($this->project->reddit_posted_at)->toBeNull();
});

it('propagates the RedditService exception so the queue can retry', function () {
    $service = Mockery::mock(RedditService::class);
    $service->shouldReceive('submitProject')
        ->once()
        ->andThrow(new \Exception('Reddit submission failed: rate limited'));

    expect(fn () => (new PostProjectToReddit($this->project))->handle($service))
        ->toThrow(\Exception::class, 'Reddit submission failed: rate limited');

    $this->project->refresh();
    expect($this->project->reddit_post_id)->toBeNull();
});

it('is configured with 3 tries and a progressive backoff', function () {
    $job = new PostProjectToReddit($this->project);

    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe([900, 1800, 2700]);
});

it('bails without submitting when the project already has a reddit_post_id', function () {
    $this->project->update(['reddit_post_id' => 'already-posted']);

    $service = Mockery::mock(RedditService::class);
    $service->shouldNotReceive('submitProject');

    (new PostProjectToReddit($this->project))->handle($service);
});
