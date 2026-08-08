<?php

use App\Events\ContestWinnerSelected;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\PitchWorkflowService;
use Illuminate\Support\Facades\Event;

it('dispatches ContestWinnerSelected when a contest winner is picked', function () {
    Event::fake([ContestWinnerSelected::class]);

    $owner = User::factory()->create();
    $producer = User::factory()->create();

    $project = Project::factory()->for($owner)->create([
        'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
        'prize_amount' => 0,
    ]);

    $entry = Pitch::factory()->for($producer)->for($project)->create([
        'status' => Pitch::STATUS_CONTEST_ENTRY,
    ]);

    app(PitchWorkflowService::class)->selectContestWinner($entry, $owner);

    Event::assertDispatched(ContestWinnerSelected::class, function ($event) use ($project, $entry) {
        return $event->project->id === $project->id
            && $event->winningPitch->id === $entry->id;
    });
});
