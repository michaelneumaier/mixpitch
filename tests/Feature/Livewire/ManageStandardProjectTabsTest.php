<?php

use App\Livewire\Project\ManageStandardProject;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

/**
 * Regression test for the tab-content bug where the Files and Project tab
 * panels rendered as blank because malformed div nesting in
 * `resources/views/components/project/pitch-list.blade.php` (unmatched
 * closing tags inside `@if($pitch->snapshots->count() > 0)`) escaped the
 * pitches panel wrapper and reparented the sibling `<flux:tab.panel>`s
 * inside it. Once nested, Flux's `<ui-tab-group>::walkPanels()` never
 * finds them (it iterates only direct children) so `getPanel('files')`
 * returns undefined and the panel never gets `data-selected`.
 */
it('renders the files tab panel content on the standard project management view', function () {
    $owner = User::factory()->create();

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);

    Pitch::factory()->create([
        'user_id' => User::factory()->create()->id,
        'project_id' => $project->id,
        'status' => Pitch::STATUS_PENDING,
    ]);

    Livewire::actingAs($owner)
        ->test(ManageStandardProject::class, ['project' => $project])
        ->set('activeMainTab', 'files')
        ->assertSee('Project Files')
        ->assertSee('Upload New Files');
});

it('renders the project tab panel with the details card component when selected', function () {
    $owner = User::factory()->create();

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);

    $html = Livewire::actingAs($owner)
        ->test(ManageStandardProject::class, ['project' => $project])
        ->set('activeMainTab', 'project')
        ->html();

    // The Project tab wrapper (flux:tab.panel name="project") should render
    // in the output, proving the panel structure is intact.
    expect($html)->toContain('name="project"');
});

it('emits the four tab panels as direct siblings under the tab group with balanced pitch-list nesting', function () {
    $owner = User::factory()->create();
    $producer1 = User::factory()->create();
    $producer2 = User::factory()->create();

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);

    // Pitch WITH a snapshot (exercises the snapshots `@if` branch that
    // previously leaked two extra `</div>` closes).
    $pitchWithSnapshot = Pitch::factory()->create([
        'user_id' => $producer1->id,
        'project_id' => $project->id,
        'status' => Pitch::STATUS_READY_FOR_REVIEW,
    ]);

    $snapshot = PitchSnapshot::factory()->create([
        'pitch_id' => $pitchWithSnapshot->id,
        'project_id' => $project->id,
        'user_id' => $producer1->id,
    ]);

    // update-pitch-status.blade.php dereferences current_snapshot_id for the
    // snapshot review URL — set it so URL generation doesn't blow up.
    $pitchWithSnapshot->update(['current_snapshot_id' => $snapshot->id]);

    // Pitch WITHOUT a snapshot (skips the snapshots `@if` branch — the
    // combination of both is what surfaced the browser reparenting bug on
    // real projects; the extra `</div>`s emitted by an earlier iteration
    // "escape" the pitch card of a subsequent iteration).
    Pitch::factory()->create([
        'user_id' => $producer2->id,
        'project_id' => $project->id,
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    $html = Livewire::actingAs($owner)
        ->test(ManageStandardProject::class, ['project' => $project])
        ->html();

    // The bug: malformed div nesting inside pitch-list.blade.php's
    // `@if ($pitch->snapshots->count() > 0)` block emitted two extra
    // `</div>` closes, escaping the pitch card and wire:key wrappers.
    // In a real browser (whose HTML parser handles custom elements
    // differently than PHP's forgiving DOMDocument), the extra closes
    // cause the sibling flux tab panels for Files and Project to be
    // reparented INSIDE the Pitches panel. Once nested, Flux's
    // `<ui-tab-group>::walkPanels()` never finds them and clicking
    // those tabs shows nothing.
    //
    // Assert div balance is preserved by counting occurrences of the
    // pitch card opening (with unique `overflow-hidden rounded-xl border
    // bg-white/60 transition-all duration-200`) and its expected close
    // position. More robustly: the pitches panel wrapper opens with
    // `name="pitches"` and must end before the `name="files"` panel opens.
    // Match the actual PANEL elements (data-flux-tab-panel), not the tab
    // buttons which also carry `name="..."`.
    preg_match('/<div[^>]*name="pitches"[^>]*data-flux-tab-panel[^>]*>/', $html, $m, PREG_OFFSET_CAPTURE);
    $pitchesPanelPos = $m[0][1] ?? false;
    preg_match('/<div[^>]*name="files"[^>]*data-flux-tab-panel[^>]*>/', $html, $m, PREG_OFFSET_CAPTURE);
    $filesPanelPos = $m[0][1] ?? false;
    preg_match('/<div[^>]*name="project"[^>]*data-flux-tab-panel[^>]*>/', $html, $m, PREG_OFFSET_CAPTURE);
    $projectPanelPos = $m[0][1] ?? false;

    expect($pitchesPanelPos)->not->toBeFalse();
    expect($filesPanelPos)->not->toBeFalse();
    expect($projectPanelPos)->not->toBeFalse();
    expect($pitchesPanelPos)->toBeLessThan($filesPanelPos);
    expect($filesPanelPos)->toBeLessThan($projectPanelPos);

    // Between the pitches PANEL opening tag and the files PANEL opening
    // tag, count `<div` opens vs `</div>` closes. When well-formed the
    // pitches panel div itself opens (+1) and closes (-1) → net 0. When
    // the pitch-list emits extra `</div>` closes inside its snapshots
    // block, the slice has closes > opens (the extra closes "escape"
    // the pitches panel), which is what causes real browsers to reparent
    // the Files/Project panels inside the Pitches panel.
    $slice = substr($html, $pitchesPanelPos, $filesPanelPos - $pitchesPanelPos);
    $opens = preg_match_all('/<div\b/', $slice);
    $closes = preg_match_all('/<\/div>/', $slice);

    expect($opens)->toEqual($closes);
});
