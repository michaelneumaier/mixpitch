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
        'accent_border' => 'border-blue-600 dark:border-blue-500',
    ];
});

it('does not break when work session events are dispatched', function () {
    // Render OverviewCard with embedded WorkSessionControl
    $component = Livewire::test(OverviewCard::class, [
        'pitch' => $this->pitch,
        'project' => $this->project,
        'workflowColors' => $this->workflowColors,
    ]);

    $component->assertOk()
        ->assertDontSee('wire:id');
});

it('handles session-started event without parameter binding error', function () {
    $component = Livewire::test(OverviewCard::class, [
        'pitch' => $this->pitch,
        'project' => $this->project,
        'workflowColors' => $this->workflowColors,
    ]);

    // Dispatch the session-started event
    $component->dispatch('session-started');

    // Should not throw parameter binding error
    $component->assertOk()
        ->assertDontSee('wire:id')
        ->assertDontSee('Unable to resolve dependency');
});

it('handles switchTab event with parameter correctly', function () {
    $component = Livewire::test(OverviewCard::class, [
        'pitch' => $this->pitch,
        'project' => $this->project,
        'workflowColors' => $this->workflowColors,
    ]);

    // This should not cause a parameter binding error
    $component->dispatch('switchTab', tabName: 'your-files');

    $component->assertOk()
        ->assertDispatched('switchToTab');
});

it('refreshes when multiple session events occur', function () {
    $component = Livewire::test(OverviewCard::class, [
        'pitch' => $this->pitch,
        'project' => $this->project,
        'workflowColors' => $this->workflowColors,
    ]);

    // Simulate multiple session lifecycle events
    $component->dispatch('session-started')
        ->assertOk();

    $component->dispatch('session-paused')
        ->assertOk();

    $component->dispatch('session-resumed')
        ->assertOk();

    $component->dispatch('session-ended')
        ->assertOk();

    // Component should still render properly
    $component->assertDontSee('wire:id')
        ->assertDontSee('Unable to resolve dependency');
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
