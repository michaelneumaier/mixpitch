<?php

use App\Events\ProjectCompleted;
use App\Events\ProjectPitchAccepted;
use App\Jobs\UpdateRedditPostForPitchAccepted;
use App\Jobs\UpdateRedditPostForProjectCompleted;
use App\Listeners\SyncRedditPostOnPitchAccepted;
use App\Listeners\SyncRedditPostOnProjectCompleted;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    $this->owner = User::factory()->create();
    $this->producer = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create([
        'reddit_post_id' => 'abc123',
    ]);
    $this->pitch = Pitch::factory()->for($this->producer)->for($this->project)->create();
});

it('SyncRedditPostOnPitchAccepted dispatches the update job when the project has a reddit post', function () {
    $listener = new SyncRedditPostOnPitchAccepted;
    $listener->handle(new ProjectPitchAccepted($this->project, $this->pitch, $this->owner));

    Queue::assertPushed(UpdateRedditPostForPitchAccepted::class, function ($job) {
        return $job->project->id === $this->project->id
            && $job->pitch->id === $this->pitch->id;
    });
});

it('SyncRedditPostOnPitchAccepted skips the job when there is no reddit post', function () {
    $this->project->update(['reddit_post_id' => null]);

    $listener = new SyncRedditPostOnPitchAccepted;
    $listener->handle(new ProjectPitchAccepted($this->project->fresh(), $this->pitch, $this->owner));

    Queue::assertNotPushed(UpdateRedditPostForPitchAccepted::class);
});

it('SyncRedditPostOnProjectCompleted dispatches the update job when the project has a reddit post', function () {
    $listener = new SyncRedditPostOnProjectCompleted;
    $listener->handle(new ProjectCompleted($this->project));

    Queue::assertPushed(UpdateRedditPostForProjectCompleted::class,
        fn ($job) => $job->project->id === $this->project->id);
});

it('SyncRedditPostOnProjectCompleted skips the job when there is no reddit post', function () {
    $this->project->update(['reddit_post_id' => null]);

    $listener = new SyncRedditPostOnProjectCompleted;
    $listener->handle(new ProjectCompleted($this->project->fresh()));

    Queue::assertNotPushed(UpdateRedditPostForProjectCompleted::class);
});
