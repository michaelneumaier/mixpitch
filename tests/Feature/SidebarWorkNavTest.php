<?php

use App\Livewire\SidebarWorkNav;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    $component = new SidebarWorkNav;
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

    $component = new SidebarWorkNav;
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

    $component = new SidebarWorkNav;

    // Client management pitches should not appear in regular pitches
    $recentPitches = $component->getRecentPitches();
    expect($recentPitches)->toHaveCount(0);

    // But client management projects should appear in client projects
    $clientProjects = $component->getRecentClientProjects();
    expect($clientProjects)->toHaveCount(1);
    expect($clientProjects->first()->id)->toBe($clientProject->id);
});

it('shows completed projects in sidebar', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create both an active and a completed project
    $activeProject = Project::factory()->create([
        'user_id' => $user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);

    $completedProject = Project::factory()->create([
        'user_id' => $user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_COMPLETED,
    ]);

    $component = new SidebarWorkNav;
    $counts = $component->getWorkItemCounts();
    $recentProjects = $component->getRecentProjects();

    // Both projects should be counted and shown
    expect($counts['projects'])->toBe(2);
    expect($recentProjects)->toHaveCount(2);
    expect($recentProjects->pluck('id')->contains($completedProject->id))->toBeTrue();
    expect($recentProjects->pluck('id')->contains($activeProject->id))->toBeTrue();
});

it('shows completed pitches in sidebar', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create([
        'user_id' => $user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'status' => Project::STATUS_OPEN,
    ]);

    // Create both an active and a completed pitch
    $activePitch = Pitch::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    $completedPitch = Pitch::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'status' => Pitch::STATUS_COMPLETED,
    ]);

    $component = new SidebarWorkNav;
    $counts = $component->getWorkItemCounts();
    $recentPitches = $component->getRecentPitches();

    // Both pitches should be counted and shown
    expect($counts['pitches'])->toBe(2);
    expect($recentPitches)->toHaveCount(2);
    expect($recentPitches->pluck('id')->contains($completedPitch->id))->toBeTrue();
    expect($recentPitches->pluck('id')->contains($activePitch->id))->toBeTrue();
});

it('shows completed contests in sidebar', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $contestProject = Project::factory()->create([
        'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
        'status' => Project::STATUS_OPEN,
    ]);

    // Create contest pitches with different statuses
    $entryPitch = Pitch::factory()->create([
        'user_id' => $user->id,
        'project_id' => $contestProject->id,
        'status' => Pitch::STATUS_CONTEST_ENTRY,
    ]);

    $notSelectedPitch = Pitch::factory()->create([
        'user_id' => $user->id,
        'project_id' => $contestProject->id,
        'status' => Pitch::STATUS_CONTEST_NOT_SELECTED,
    ]);

    $component = new SidebarWorkNav;
    $counts = $component->getWorkItemCounts();
    $recentContests = $component->getRecentContests();

    // All contest pitches should be counted and shown
    expect($counts['contests'])->toBe(2);
    expect($recentContests)->toHaveCount(2);
    expect($recentContests->pluck('id')->contains($entryPitch->id))->toBeTrue();
    expect($recentContests->pluck('id')->contains($notSelectedPitch->id))->toBeTrue();
});

it('shows completed client projects in sidebar', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create both an active and a completed client project
    $activeClientProject = Project::factory()->create([
        'user_id' => $user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        'status' => Project::STATUS_OPEN,
    ]);

    $completedClientProject = Project::factory()->create([
        'user_id' => $user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        'status' => Project::STATUS_COMPLETED,
    ]);

    $component = new SidebarWorkNav;
    $counts = $component->getWorkItemCounts();
    $recentClientProjects = $component->getRecentClientProjects();

    // Both client projects should be counted and shown
    expect($counts['client_projects'])->toBe(2);
    expect($recentClientProjects)->toHaveCount(2);
    expect($recentClientProjects->pluck('id')->contains($completedClientProject->id))->toBeTrue();
    expect($recentClientProjects->pluck('id')->contains($activeClientProject->id))->toBeTrue();
});
