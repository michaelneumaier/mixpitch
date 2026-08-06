<?php

use App\Livewire\Project\Component\OverviewCard;
use App\Livewire\Project\Component\WorkSessionControl;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->project = Project::factory()->create([
        'user_id' => $this->user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
    ]);

    $this->pitch = Pitch::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    $this->workflowColors = [
        'bg' => 'bg-blue-50 dark:bg-blue-950',
        'border' => 'border-blue-200 dark:border-blue-800',
        'text_primary' => 'text-blue-900 dark:text-blue-100',
        'text_secondary' => 'text-blue-700 dark:text-blue-300',
        'text_muted' => 'text-blue-600 dark:text-blue-400',
        'accent' => 'bg-blue-600 dark:bg-blue-500',
        'accent_bg' => 'bg-blue-100 dark:bg-blue-900',
        'accent_border' => 'border-blue-600 dark:border-blue-500',
        'icon' => 'text-blue-600 dark:text-blue-400',
    ];
});

it('does not break when overview card renders', function () {
    // Render OverviewCard and verify it renders without errors
    $component = Livewire::test(OverviewCard::class, [
        'pitch' => $this->pitch,
        'project' => $this->project,
        'workflowColors' => $this->workflowColors,
    ]);

    $component->assertOk();
});

it('renders overview card without errors', function () {
    // OverviewCard does not listen for session-started/paused/resumed/ended events.
    // These events are handled by the WorkSessionControl component, not OverviewCard.
    // Verify the component renders and provides expected data.
    $component = Livewire::test(OverviewCard::class, [
        'pitch' => $this->pitch,
        'project' => $this->project,
        'workflowColors' => $this->workflowColors,
    ]);

    $component->assertOk()
        ->assertDontSee('Unable to resolve dependency');
});

it('can switch to files tab via action', function () {
    $component = Livewire::test(OverviewCard::class, [
        'pitch' => $this->pitch,
        'project' => $this->project,
        'workflowColors' => $this->workflowColors,
    ]);

    // OverviewCard has a switchToFilesTab action that dispatches 'switchToTab'
    $component->call('switchToFilesTab');

    $component->assertOk()
        ->assertDispatched('switchToTab');
});

it('can toggle session history', function () {
    $component = Livewire::test(OverviewCard::class, [
        'pitch' => $this->pitch,
        'project' => $this->project,
        'workflowColors' => $this->workflowColors,
    ]);

    // OverviewCard has a toggleSessionHistory action
    $component->call('toggleSessionHistory');

    $component->assertOk()
        ->assertSet('showAllSessions', true);

    $component->call('toggleSessionHistory')
        ->assertSet('showAllSessions', false);
});

it('embedded work session control does not poll', function () {
    $component = Livewire::test(WorkSessionControl::class, [
        'project' => $this->project,
        'pitch' => $this->pitch,
        'variant' => 'embedded',
    ]);

    // Check that the embedded variant doesn't have polling directive
    $html = $component->html();

    // The embedded variant should not have wire:poll on the root div
    expect($html)->not->toContain('<div wire:poll.60s="loadActiveSession">');
});

it('header work session control does poll', function () {
    $component = Livewire::test(WorkSessionControl::class, [
        'project' => $this->project,
        'pitch' => $this->pitch,
        'variant' => 'header',
    ]);

    // Check that the header variant has polling directive
    $html = $component->html();

    // The header variant should have wire:poll
    expect($html)->toContain('wire:poll.60s="loadActiveSession"');
});
