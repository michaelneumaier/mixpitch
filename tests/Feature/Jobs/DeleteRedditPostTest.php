<?php

use App\Jobs\DeleteRedditPost;
use App\Models\Project;
use App\Models\User;
use App\Services\RedditService;

beforeEach(function () {
    $this->project = Project::factory()->for(User::factory())->create([
        'reddit_post_id' => 'to_delete',
        'reddit_permalink' => 'https://reddit.com/r/MixPitch/comments/to_delete/x/',
        'reddit_posted_at' => now()->subDay(),
        'reddit_original_body' => 'to be cleared',
    ]);
});

it('deletes the reddit post and clears the ids but preserves reddit_posted_at as audit', function () {
    $service = Mockery::mock(RedditService::class);
    $service->shouldReceive('deletePost')
        ->once()
        ->with('t3_to_delete');

    $postedAt = $this->project->reddit_posted_at;

    (new DeleteRedditPost($this->project))->handle($service);

    $this->project->refresh();
    expect($this->project->reddit_post_id)->toBeNull();
    expect($this->project->reddit_permalink)->toBeNull();
    expect($this->project->reddit_original_body)->toBeNull();
    expect($this->project->reddit_posted_at->format('Y-m-d H:i:s'))
        ->toBe($postedAt->format('Y-m-d H:i:s'));
});

it('bails silently when the project already has no reddit_post_id', function () {
    $this->project->update(['reddit_post_id' => null]);

    $service = Mockery::mock(RedditService::class);
    $service->shouldNotReceive('deletePost');

    (new DeleteRedditPost($this->project))->handle($service);
});

it('rethrows and does not clear ids when Reddit deletion fails', function () {
    $service = Mockery::mock(RedditService::class);
    $service->shouldReceive('deletePost')->once()->andThrow(new \Exception('403 forbidden'));

    expect(fn () => (new DeleteRedditPost($this->project))->handle($service))
        ->toThrow(\Exception::class, '403 forbidden');

    $this->project->refresh();
    expect($this->project->reddit_post_id)->toBe('to_delete');
});
