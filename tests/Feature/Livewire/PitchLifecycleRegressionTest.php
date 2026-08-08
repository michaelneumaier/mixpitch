<?php

use App\Livewire\Pitch\Component\ManagePitch;
use App\Livewire\Project\ManageStandardProject;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

/**
 * Regressions found by the 2026-08-08 end-to-end walkthrough. Both bugs
 * only appear after a pitch completes one revision cycle, a state no
 * earlier test constructed.
 */
it('renders the owner manage page for a pitch with two snapshots', function () {
    // Previously 500'd: the version-comparison modal eagerly mounted
    // FileComparisonPlayer with snapshot params while its mount() expects
    // two PitchFile models, taking down the entire manage page once a
    // pitch had 2+ snapshots.
    $owner = User::factory()->create();
    $producer = User::factory()->create();

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);

    $pitch = Pitch::factory()->for($project)->for($producer)->create([
        'status' => Pitch::STATUS_READY_FOR_REVIEW,
    ]);

    $first = PitchSnapshot::factory()->create([
        'pitch_id' => $pitch->id,
        'project_id' => $project->id,
        'user_id' => $producer->id,
        'status' => PitchSnapshot::STATUS_REVISIONS_REQUESTED,
        'snapshot_data' => ['version' => 1],
    ]);
    $second = PitchSnapshot::factory()->create([
        'pitch_id' => $pitch->id,
        'project_id' => $project->id,
        'user_id' => $producer->id,
        'status' => PitchSnapshot::STATUS_PENDING,
        'snapshot_data' => ['version' => 2],
    ]);
    $pitch->update(['current_snapshot_id' => $second->id]);

    Livewire::actingAs($owner)
        ->test(ManageStandardProject::class, ['project' => $project])
        ->assertOk()
        ->assertSee('Version 1')
        ->assertSee('Version 2');
});

it('wires the resubmit button to a component method that exists', function () {
    // Previously wire:click="resubmitPitch" — a method that does not exist
    // on ManagePitch — so producers could not resubmit after revisions.
    $producer = User::factory()->create();
    $snapshot = null;

    $pitch = Pitch::factory()->for($producer)->create([
        'status' => Pitch::STATUS_REVISIONS_REQUESTED,
    ]);
    $snapshot = PitchSnapshot::factory()->create([
        'pitch_id' => $pitch->id,
        'project_id' => $pitch->project_id,
        'user_id' => $producer->id,
        'status' => PitchSnapshot::STATUS_REVISIONS_REQUESTED,
        'snapshot_data' => ['version' => 1],
    ]);
    $pitch->update(['current_snapshot_id' => $snapshot->id]);

    $html = Livewire::actingAs($producer)
        ->test(ManagePitch::class, ['pitch' => $pitch])
        ->html();

    expect($html)->not->toContain('resubmitPitch');
    expect($html)->toContain('wire:click="submitForReview"');
});
