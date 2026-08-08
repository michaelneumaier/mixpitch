<?php

use App\Jobs\UpdateRedditPostForContestWinner;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\RedditService;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->winner = User::factory()->create([
        'username' => 'winnerguy',
        'reddit_username' => 'winnerguy_reddit',
        'reddit_user_id' => 'r-2',
    ]);
    $this->project = Project::factory()->for($this->owner)->create([
        'title' => 'Contest',
        'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
        'reddit_post_id' => 'abc123',
        'reddit_permalink' => 'https://reddit.com/r/MixPitch/comments/abc123/contest/',
        'reddit_posted_at' => now()->subHour(),
        'reddit_original_body' => 'Original contest body',
    ]);
    $this->pitch = Pitch::factory()->for($this->winner)->for($this->project)->create([
        'status' => Pitch::STATUS_CONTEST_WINNER,
    ]);
});

it('edits the post to prepend a WINNER SELECTED header and posts a comment', function () {
    $service = Mockery::mock(RedditService::class);

    $service->shouldReceive('editPost')
        ->once()
        ->with('t3_abc123', Mockery::on(fn ($body) => str_contains($body, 'WINNER SELECTED')
            && str_contains($body, 'u/winnerguy_reddit')
            && str_ends_with($body, 'Original contest body')))
        ->andReturn(['json' => ['errors' => []]]);

    $service->shouldReceive('postComment')
        ->once()
        ->with('t3_abc123', Mockery::on(fn ($body) => str_contains($body, 'has a winner')
            && str_contains($body, 'u/winnerguy_reddit')
            && str_contains($body, '/projects/')))
        ->andReturn(['json' => ['errors' => []]]);

    (new UpdateRedditPostForContestWinner($this->project, $this->pitch))->handle($service);
});

it('falls back to MixPitch username when the winner has no linked Reddit account', function () {
    $this->winner->update(['reddit_username' => null, 'reddit_user_id' => null]);
    $this->pitch->refresh();

    $service = Mockery::mock(RedditService::class);
    $service->shouldReceive('editPost')
        ->once()
        ->with('t3_abc123', Mockery::on(fn ($body) => str_contains($body, '@winnerguy on MixPitch')))
        ->andReturn(['json' => ['errors' => []]]);
    $service->shouldReceive('postComment')
        ->once()
        ->andReturn(['json' => ['errors' => []]]);

    (new UpdateRedditPostForContestWinner($this->project, $this->pitch))->handle($service);
});

it('bails silently when the project no longer has a reddit_post_id', function () {
    $this->project->update(['reddit_post_id' => null]);

    $service = Mockery::mock(RedditService::class);
    $service->shouldNotReceive('editPost');
    $service->shouldNotReceive('postComment');

    (new UpdateRedditPostForContestWinner($this->project, $this->pitch))->handle($service);
});

it('still posts a comment even if the edit fails', function () {
    $service = Mockery::mock(RedditService::class);
    $service->shouldReceive('editPost')->once()->andThrow(new \Exception('403 not author'));
    $service->shouldReceive('postComment')->once()->andReturn(['json' => ['errors' => []]]);

    (new UpdateRedditPostForContestWinner($this->project, $this->pitch))->handle($service);
});
