<?php

use App\Livewire\Project\ManageStandardProject;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('shows the reddit trust badge on a pitch card when the producer has linked reddit', function () {
    $owner = User::factory()->create();
    $producer = User::factory()->create([
        'reddit_username' => 'bob_the_mixer',
        'reddit_user_id' => 't2_abc123',
        'reddit_account_created_at' => now()->subYears(6),
    ]);

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);

    Pitch::factory()->create([
        'user_id' => $producer->id,
        'project_id' => $project->id,
        'status' => Pitch::STATUS_PENDING,
    ]);

    Livewire::actingAs($owner)
        ->test(ManageStandardProject::class, ['project' => $project])
        ->assertSee('u/bob_the_mixer')
        ->assertSee('https://www.reddit.com/user/bob_the_mixer');
});

it('does not show a reddit badge for producers without a linked reddit account', function () {
    $owner = User::factory()->create();
    $producer = User::factory()->create([
        'reddit_username' => null,
        'reddit_user_id' => null,
    ]);

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);

    Pitch::factory()->create([
        'user_id' => $producer->id,
        'project_id' => $project->id,
        'status' => Pitch::STATUS_PENDING,
    ]);

    Livewire::actingAs($owner)
        ->test(ManageStandardProject::class, ['project' => $project])
        ->assertDontSee('reddit.com/user/');
});
