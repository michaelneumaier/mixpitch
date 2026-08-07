<?php

use App\Jobs\UpdateRedditPostForProjectCompleted;
use App\Models\Project;
use App\Models\User;
use App\Services\RedditService;

beforeEach(function () {
    $this->project = Project::factory()->for(User::factory())->create([
        'title' => 'Test',
        'reddit_post_id' => 'xyz789',
        'reddit_permalink' => 'https://reddit.com/r/MixPitch/comments/xyz789/test/',
        'reddit_posted_at' => now()->subDay(),
        'reddit_original_body' => 'Original body of the completed project',
        'completed_at' => now(),
    ]);
});

it('edits the post to prepend a COMPLETED header and posts a completion comment', function () {
    $service = Mockery::mock(RedditService::class);

    $service->shouldReceive('editPost')
        ->once()
        ->with('t3_xyz789', Mockery::on(fn ($body) => str_contains($body, 'COMPLETED')
            && str_ends_with($body, 'Original body of the completed project')))
        ->andReturn(['json' => ['errors' => []]]);

    $service->shouldReceive('postComment')
        ->once()
        ->with('t3_xyz789', Mockery::on(fn ($body) => str_contains($body, 'now complete')
            && str_contains($body, '/projects/')))
        ->andReturn(['json' => ['errors' => []]]);

    (new UpdateRedditPostForProjectCompleted($this->project))->handle($service);
});

it('bails silently when the project has no reddit_post_id', function () {
    $this->project->update(['reddit_post_id' => null]);

    $service = Mockery::mock(RedditService::class);
    $service->shouldNotReceive('editPost');
    $service->shouldNotReceive('postComment');

    (new UpdateRedditPostForProjectCompleted($this->project))->handle($service);
});

it('rethrows when the comment fails so the queue can retry', function () {
    $service = Mockery::mock(RedditService::class);
    $service->shouldReceive('editPost')->once()->andReturn(['json' => ['errors' => []]]);
    $service->shouldReceive('postComment')->once()->andThrow(new \Exception('rate limited'));

    expect(fn () => (new UpdateRedditPostForProjectCompleted($this->project))->handle($service))
        ->toThrow(\Exception::class, 'rate limited');
});
