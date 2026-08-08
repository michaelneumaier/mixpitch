<?php

use App\Livewire\Pitch\Component\ManagePitch;
use App\Livewire\Project\ManageStandardProject;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('shows the cover letter on the owner pitch card and hides the section when absent', function () {
    $owner = User::factory()->create();
    $withLetter = User::factory()->create();
    $withoutLetter = User::factory()->create();

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);

    Pitch::factory()->for($project)->for($withLetter)->withCoverLetter('Unique cover text ZZZ123')->create([
        'status' => Pitch::STATUS_PENDING,
    ]);
    Pitch::factory()->for($project)->for($withoutLetter)->create([
        'status' => Pitch::STATUS_PENDING,
    ]);

    $html = Livewire::actingAs($owner)
        ->test(ManageStandardProject::class, ['project' => $project])
        ->assertSee('Unique cover text ZZZ123')
        ->html();

    // Exactly one pitch card should render a cover letter section — the
    // HTML comment marker survives Blade compilation, one per section.
    expect(substr_count($html, '<!-- Cover Letter Section -->'))->toBe(1);
});

it('keeps pitch-list div nesting balanced when a cover letter renders', function () {
    $owner = User::factory()->create();

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);

    Pitch::factory()->for($project)->for(User::factory()->create())->withCoverLetter()->create([
        'status' => Pitch::STATUS_PENDING,
    ]);

    $html = Livewire::actingAs($owner)
        ->test(ManageStandardProject::class, ['project' => $project])
        ->html();

    preg_match('/<div[^>]*name="pitches"[^>]*data-flux-tab-panel[^>]*>/', $html, $m, PREG_OFFSET_CAPTURE);
    $pitchesPanelPos = $m[0][1] ?? false;
    preg_match('/<div[^>]*name="files"[^>]*data-flux-tab-panel[^>]*>/', $html, $m, PREG_OFFSET_CAPTURE);
    $filesPanelPos = $m[0][1] ?? false;

    expect($pitchesPanelPos)->not->toBeFalse();
    expect($filesPanelPos)->not->toBeFalse();

    $slice = substr($html, $pitchesPanelPos, $filesPanelPos - $pitchesPanelPos);
    expect(preg_match_all('/<div\b/', $slice))->toEqual(preg_match_all('/<\/div>/', $slice));
});

it('renders cover letter content escaped so script tags cannot execute', function () {
    $owner = User::factory()->create();

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);

    Pitch::factory()->for($project)->for(User::factory()->create())
        ->withCoverLetter('<script>alert("xss")</script>')
        ->create(['status' => Pitch::STATUS_PENDING]);

    $html = Livewire::actingAs($owner)
        ->test(ManageStandardProject::class, ['project' => $project])
        ->html();

    expect($html)->not->toContain('<script>alert("xss")</script>');
    expect($html)->toContain('&lt;script&gt;');
});

it('lets the producer update the cover letter while the pitch is pending', function () {
    $producer = User::factory()->create();
    $pitch = Pitch::factory()->for($producer)->create(['status' => Pitch::STATUS_PENDING]);

    Livewire::actingAs($producer)
        ->test(ManagePitch::class, ['pitch' => $pitch])
        ->set('coverLetter', 'Updated proposal text')
        ->call('saveCoverLetter');

    expect($pitch->fresh()->cover_letter)->toBe('Updated proposal text');
});

it('rejects cover letter edits once the pitch has entered review', function () {
    $producer = User::factory()->create();
    $pitch = Pitch::factory()->for($producer)->withCoverLetter('Original')->create([
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    Livewire::actingAs($producer)
        ->test(ManagePitch::class, ['pitch' => $pitch])
        ->set('coverLetter', 'Sneaky rewrite')
        ->call('saveCoverLetter')
        ->assertForbidden();

    expect($pitch->fresh()->cover_letter)->toBe('Original');
});

it('allows contest entry edits before the deadline and rejects them after', function () {
    $producer = User::factory()->create();

    $openContest = Project::factory()->create([
        'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
        'submission_deadline' => now()->addDays(3),
        'status' => Project::STATUS_OPEN,
    ]);
    $openEntry = Pitch::factory()->for($openContest)->for($producer)->create([
        'status' => Pitch::STATUS_CONTEST_ENTRY,
    ]);

    Livewire::actingAs($producer)
        ->test(ManagePitch::class, ['pitch' => $openEntry])
        ->set('coverLetter', 'Note before deadline')
        ->call('saveCoverLetter');

    expect($openEntry->fresh()->cover_letter)->toBe('Note before deadline');

    $closedContest = Project::factory()->create([
        'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
        'submission_deadline' => now()->subDay(),
        'status' => Project::STATUS_OPEN,
    ]);
    $closedEntry = Pitch::factory()->for($closedContest)->for($producer)->withCoverLetter('Frozen')->create([
        'status' => Pitch::STATUS_CONTEST_ENTRY,
    ]);

    Livewire::actingAs($producer)
        ->test(ManagePitch::class, ['pitch' => $closedEntry])
        ->set('coverLetter', 'Too late')
        ->call('saveCoverLetter')
        ->assertForbidden();

    expect($closedEntry->fresh()->cover_letter)->toBe('Frozen');
});

it('forbids anyone other than the pitch owner from saving the cover letter', function () {
    $producer = User::factory()->create();
    $intruder = User::factory()->create();
    $pitch = Pitch::factory()->for($producer)->create(['status' => Pitch::STATUS_PENDING]);

    Livewire::actingAs($intruder)
        ->test(ManagePitch::class, ['pitch' => $pitch])
        ->set('coverLetter', 'Hijacked')
        ->call('saveCoverLetter')
        ->assertForbidden();

    expect($pitch->fresh()->cover_letter)->toBeNull();
});
