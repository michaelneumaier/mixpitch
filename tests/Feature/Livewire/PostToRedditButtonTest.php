<?php

use App\Jobs\DeleteRedditPost;
use App\Jobs\PostProjectToReddit;
use App\Livewire\Project\ManageContestProject;
use App\Livewire\Project\ManageStandardProject;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Queue::fake();

    $this->owner = User::factory()->create();
    $this->project = Project::factory()
        ->for($this->owner)
        ->published()
        ->create([
            'title' => 'A Testable Project',
            'description' => 'It really is testable',
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);
});

it('dispatches PostProjectToReddit job on the happy path', function () {
    Livewire::actingAs($this->owner)
        ->test(ManageStandardProject::class, ['project' => $this->project])
        ->call('postToReddit')
        ->assertSet('isPostingToReddit', true);

    Queue::assertPushed(PostProjectToReddit::class, fn ($job) => $job->project->id === $this->project->id);
});

it('does not dispatch when the project is unpublished', function () {
    $this->project->update(['is_published' => false, 'status' => Project::STATUS_UNPUBLISHED]);

    Livewire::actingAs($this->owner)
        ->test(ManageStandardProject::class, ['project' => $this->project])
        ->call('postToReddit')
        ->assertSet('isPostingToReddit', false);

    Queue::assertNotPushed(PostProjectToReddit::class);
});

it('does not dispatch when the project has no title or description', function () {
    $this->project->update(['description' => '']);

    Livewire::actingAs($this->owner)
        ->test(ManageStandardProject::class, ['project' => $this->project])
        ->call('postToReddit')
        ->assertSet('isPostingToReddit', false);

    Queue::assertNotPushed(PostProjectToReddit::class);
});

it('does not dispatch when the project has already been posted to Reddit', function () {
    $this->project->update([
        'reddit_post_id' => 'already_posted',
        'reddit_posted_at' => now()->subHours(2),
    ]);

    Livewire::actingAs($this->owner)
        ->test(ManageStandardProject::class, ['project' => $this->project])
        ->call('postToReddit')
        ->assertSet('isPostingToReddit', false);

    Queue::assertNotPushed(PostProjectToReddit::class);
});

it('blocks a fourth post within an hour (rate limit)', function () {
    Project::factory()->count(3)->for($this->owner)->create([
        'reddit_posted_at' => now()->subMinutes(15),
        'reddit_post_id' => 'other',
    ]);

    Livewire::actingAs($this->owner)
        ->test(ManageStandardProject::class, ['project' => $this->project])
        ->call('postToReddit')
        ->assertSet('isPostingToReddit', false);

    Queue::assertNotPushed(PostProjectToReddit::class);
});

it('blocks users who are not the project owner (mount aborts 403)', function () {
    $otherUser = User::factory()->create();

    Livewire::actingAs($otherUser)
        ->test(ManageStandardProject::class, ['project' => $this->project])
        ->assertForbidden();

    Queue::assertNotPushed(PostProjectToReddit::class);
});

it('dispatches DeleteRedditPost when the owner unposts', function () {
    $this->project->update([
        'reddit_post_id' => 'already_posted',
        'reddit_permalink' => 'https://reddit.com/r/MixPitch/comments/already_posted/x/',
        'reddit_posted_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->owner)
        ->test(ManageStandardProject::class, ['project' => $this->project])
        ->call('unpostFromReddit');

    Queue::assertPushed(DeleteRedditPost::class, fn ($job) => $job->project->id === $this->project->id);
});

it('does not dispatch delete when project was never posted', function () {
    Livewire::actingAs($this->owner)
        ->test(ManageStandardProject::class, ['project' => $this->project])
        ->call('unpostFromReddit');

    Queue::assertNotPushed(DeleteRedditPost::class);
});

it('works on ManageContestProject via the shared trait', function () {
    $contest = Project::factory()
        ->for($this->owner)
        ->published()
        ->configureWorkflow(Project::WORKFLOW_TYPE_CONTEST)
        ->create([
            'title' => 'Contest Reddit Test',
            'description' => 'Contest description',
        ]);

    Livewire::actingAs($this->owner)
        ->test(ManageContestProject::class, ['project' => $contest])
        ->call('postToReddit')
        ->assertSet('isPostingToReddit', true);

    Queue::assertPushed(PostProjectToReddit::class, fn ($job) => $job->project->id === $contest->id);
});
