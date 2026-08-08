<?php

use App\Livewire\Pitch\Component\ManagePitch;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

function paidStandardProject(): Project
{
    return Project::factory()->create([
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
        'budget' => 500,
    ]);
}

it('prompts an accepted producer without a stripe account to set up payouts', function () {
    $producer = User::factory()->create(['stripe_account_id' => null]);
    $pitch = Pitch::factory()->for(paidStandardProject())->for($producer)->create([
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    Livewire::actingAs($producer)
        ->test(ManagePitch::class, ['pitch' => $pitch])
        ->assertSee('Set Up Payouts')
        ->assertSee('$500 budget')
        ->assertSee(route('payouts.setup.index'));
});

it('does not prompt when the producer already has a stripe account', function () {
    $producer = User::factory()->create(['stripe_account_id' => 'acct_test_123']);
    $pitch = Pitch::factory()->for(paidStandardProject())->for($producer)->create([
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    Livewire::actingAs($producer)
        ->test(ManagePitch::class, ['pitch' => $pitch])
        ->assertDontSee('Set Up Payouts');
});

it('does not prompt on free projects', function () {
    $producer = User::factory()->create(['stripe_account_id' => null]);
    $project = Project::factory()->create([
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
        'budget' => 0,
    ]);
    $pitch = Pitch::factory()->for($project)->for($producer)->create([
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    Livewire::actingAs($producer)
        ->test(ManagePitch::class, ['pitch' => $pitch])
        ->assertDontSee('Set Up Payouts');
});

it('does not prompt before the pitch has been accepted', function () {
    $producer = User::factory()->create(['stripe_account_id' => null]);
    $pitch = Pitch::factory()->for(paidStandardProject())->for($producer)->create([
        'status' => Pitch::STATUS_PENDING,
    ]);

    Livewire::actingAs($producer)
        ->test(ManagePitch::class, ['pitch' => $pitch])
        ->assertDontSee('Set Up Payouts');
});
