<?php

use App\Livewire\Pitch\Component\ManagePitch;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

/**
 * Security coverage for ManagePitch::saveNote (bug H2 - IDOR + missing
 * authorization). Also verifies the note save no longer calls a
 * non-existent Pitch::addComment() method (bug L2).
 */
it('forbids editing a note on a file belonging to another pitch (IDOR)', function () {
    $attacker = User::factory()->create();

    // Attacker's own pitch (what they legitimately mount).
    $attackerProject = Project::factory()->create([
        'user_id' => $attacker->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
    ]);
    $attackerPitch = Pitch::factory()->for($attackerProject)->for($attacker)->create([
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    // A victim's pitch + file the attacker should never touch.
    $victim = User::factory()->create();
    $victimPitch = Pitch::factory()->for($victim)->create([
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);
    $victimFile = PitchFile::factory()->create([
        'pitch_id' => $victimPitch->id,
        'user_id' => $victim->id,
        'note' => 'original note',
    ]);

    Livewire::actingAs($attacker)
        ->test(ManagePitch::class, ['pitch' => $attackerPitch])
        ->call('saveNote', $victimFile->id, 'hacked note')
        ->assertForbidden();

    expect($victimFile->fresh()->note)->toBe('original note');
});

it('forbids a non-owner from editing a note on a file of the mounted pitch', function () {
    $owner = User::factory()->create();
    $pitch = Pitch::factory()->for($owner)->create([
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);
    $file = PitchFile::factory()->create([
        'pitch_id' => $pitch->id,
        'user_id' => $owner->id,
        'note' => 'original note',
    ]);

    $stranger = User::factory()->create();

    Livewire::actingAs($stranger)
        ->test(ManagePitch::class, ['pitch' => $pitch])
        ->call('saveNote', $file->id, 'hacked note')
        ->assertForbidden();

    expect($file->fresh()->note)->toBe('original note');
});

it('lets the pitch owner save a note on their own file', function () {
    $owner = User::factory()->create();
    $pitch = Pitch::factory()->for($owner)->create([
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);
    $file = PitchFile::factory()->create([
        'pitch_id' => $pitch->id,
        'user_id' => $owner->id,
        'note' => null,
    ]);

    Livewire::actingAs($owner)
        ->test(ManagePitch::class, ['pitch' => $pitch])
        ->call('saveNote', $file->id, 'my helpful note')
        ->assertOk()
        ->assertHasNoErrors();

    expect($file->fresh()->note)->toBe('my helpful note');
});
