<?php

use App\Livewire\WorkSection;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('renders work section component', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(WorkSection::class)
        ->assertStatus(200)
        ->assertSee('My Work');
});

it('shows empty state when no work items exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(WorkSection::class)
        ->assertSee('Ready to Start Creating?')
        ->assertSee('Create Project')
        ->assertSee('Browse Projects');
});

it('displays user projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Project',
        'status' => Project::STATUS_OPEN,
    ]);

    $this->actingAs($user);

    Livewire::test(WorkSection::class)
        ->assertSee('Test Project')
        ->assertDontSee('Ready to Start Creating?');
});

it('displays user pitches', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $pitch = Pitch::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    $this->actingAs($user);

    Livewire::test(WorkSection::class)
        ->assertSee($project->name)
        ->assertSee('In Progress');
});

it('can filter work items by type', function () {
    $user = User::factory()->create();

    // Create different types of work items
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Project',
        'status' => Project::STATUS_OPEN,
    ]);

    $pitchProject = Project::factory()->create();
    $pitch = Pitch::factory()->create([
        'user_id' => $user->id,
        'project_id' => $pitchProject->id,
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    $this->actingAs($user);

    // Test default shows all
    Livewire::test(WorkSection::class)
        ->assertSee('My Project')
        ->assertSee($pitchProject->name);

    // Test filtering by project
    Livewire::test(WorkSection::class)
        ->call('setFilter', 'project')
        ->assertSet('filter', 'project');

    // Test filtering by pitch
    Livewire::test(WorkSection::class)
        ->call('setFilter', 'pitch')
        ->assertSet('filter', 'pitch');
});
