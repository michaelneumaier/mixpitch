<?php

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->owner = User::factory()->create();
    $this->producer = User::factory()->create();

    $this->project = Project::factory()->for($this->owner)->create([
        'status' => Project::STATUS_OPEN,
        'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        'slug' => 'test-project-detail',
    ]);
});

it('loads the project detail page for a logged-out visitor', function () {
    $response = $this->get(route('projects.show', $this->project));

    $response->assertOk();
});

it('loads the project detail page for a logged-in producer with no pitch', function () {
    $response = $this->actingAs($this->producer)
        ->get(route('projects.show', $this->project));

    $response->assertOk();
});

it('loads the project detail page for a logged-in producer with an existing pitch', function () {
    Pitch::factory()
        ->for($this->producer, 'user')
        ->for($this->project)
        ->create([
            'status' => Pitch::STATUS_IN_PROGRESS,
        ]);

    $response = $this->actingAs($this->producer)
        ->get(route('projects.show', $this->project));

    $response->assertOk();
    // Producer already has a pitch, so a "Manage" affordance is shown (not "Start Pitch").
    $response->assertSee('Manage Pitch');
    $response->assertDontSee('Start Pitch');
});

it('loads the project detail page for the project owner', function () {
    $response = $this->actingAs($this->owner)
        ->get(route('projects.show', $this->project));

    $response->assertOk();
});
