<?php

use App\Livewire\SidebarWorkNav;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('excludes client management pitches from regular pitches count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a regular project with a pitch
    $regularProject = Project::factory()->create([
        'user_id' => $user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);
    
    $regularPitch = Pitch::factory()->create([
        'user_id' => $user->id,
        'project_id' => $regularProject->id,
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    // Create a client management project with a pitch
    $clientProject = Project::factory()->create([
        'user_id' => $user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        'status' => Project::STATUS_OPEN,
    ]);
    
    $clientPitch = Pitch::factory()->create([
        'user_id' => $user->id,
        'project_id' => $clientProject->id,
        'status' => Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
    ]);

    $component = new SidebarWorkNav();
    $counts = $component->getWorkItemCounts();

    // Regular pitches should only count the regular pitch, not the client management pitch
    expect($counts['pitches'])->toBe(1);
    expect($counts['client_projects'])->toBe(1);
    expect($counts['projects'])->toBe(1); // Regular project
});

it('excludes client management pitches from recent pitches list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a regular project with a pitch
    $regularProject = Project::factory()->create([
        'user_id' => $user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);
    
    $regularPitch = Pitch::factory()->create([
        'user_id' => $user->id,
        'project_id' => $regularProject->id,
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    // Create a client management project with a pitch
    $clientProject = Project::factory()->create([
        'user_id' => $user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        'status' => Project::STATUS_OPEN,
    ]);
    
    $clientPitch = Pitch::factory()->create([
        'user_id' => $user->id,
        'project_id' => $clientProject->id,
        'status' => Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
    ]);

    $component = new SidebarWorkNav();
    $recentPitches = $component->getRecentPitches();

    // Recent pitches should only include the regular pitch, not the client management pitch
    expect($recentPitches)->toHaveCount(1);
    expect($recentPitches->first()->id)->toBe($regularPitch->id);
    expect($recentPitches->pluck('id')->contains($clientPitch->id))->toBeFalse();
});

it('shows client management pitches only in client projects section', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a client management project with a pitch
    $clientProject = Project::factory()->create([
        'user_id' => $user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        'status' => Project::STATUS_OPEN,
    ]);
    
    $clientPitch = Pitch::factory()->create([
        'user_id' => $user->id,
        'project_id' => $clientProject->id,
        'status' => Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
    ]);

    $component = new SidebarWorkNav();
    
    // Client management pitches should not appear in regular pitches
    $recentPitches = $component->getRecentPitches();
    expect($recentPitches)->toHaveCount(0);
    
    // But client management projects should appear in client projects
    $clientProjects = $component->getRecentClientProjects();
    expect($clientProjects)->toHaveCount(1);
    expect($clientProjects->first()->id)->toBe($clientProject->id);
});
