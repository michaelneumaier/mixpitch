<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PitchOwnerRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected $projectOwner;
    protected $producer;
    protected $otherUser;
    protected $standardProject;
    protected $clientManagementProject;
    protected $directHireProject;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->projectOwner = User::factory()->create();
        $this->producer = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Create different types of projects
        $this->standardProject = Project::factory()
            ->for($this->projectOwner, 'user')
            ->create([
                'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
                'status' => Project::STATUS_OPEN,
                'is_published' => true
            ]);

        $this->clientManagementProject = Project::factory()
            ->for($this->projectOwner, 'user')
            ->create([
                'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
                'status' => Project::STATUS_OPEN,
                'is_published' => true
            ]);

        $this->directHireProject = Project::factory()
            ->for($this->projectOwner, 'user')
            ->create([
                'workflow_type' => Project::WORKFLOW_TYPE_DIRECT_HIRE,
                'status' => Project::STATUS_OPEN,
                'is_published' => true
            ]);
    }

    /** @test */
    public function project_owner_redirected_to_manage_page_when_pitch_has_no_snapshots()
    {
        // Create a pitch without snapshots
        $pitch = Pitch::factory()
            ->for($this->standardProject)
            ->for($this->producer)
            ->create();

        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.show', [
                'project' => $this->standardProject,
                'pitch' => $pitch
            ]));

        $response->assertRedirect(route('projects.manage', $this->standardProject));
        $response->assertSessionHas('info', 'Project owners should manage pitches from the project management page.');
    }

    /** @test */
    public function project_owner_redirected_to_latest_snapshot_when_pitch_has_one_snapshot()
    {
        // Create a pitch with one snapshot
        $pitch = Pitch::factory()
            ->for($this->standardProject)
            ->for($this->producer)
            ->create();

        $snapshot = PitchSnapshot::factory()
            ->for($pitch)
            ->create();

        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.show', [
                'project' => $this->standardProject,
                'pitch' => $pitch
            ]));

        $response->assertRedirect(route('projects.pitches.snapshots.show', [
            'project' => $this->standardProject->slug,
            'pitch' => $pitch->slug,
            'snapshot' => $snapshot->id
        ]));
        $response->assertSessionHas('info', 'Redirected to the latest snapshot for review.');
    }

    /** @test */
    public function project_owner_redirected_to_latest_snapshot_when_pitch_has_multiple_snapshots()
    {
        // Create a pitch with multiple snapshots
        $pitch = Pitch::factory()
            ->for($this->standardProject)
            ->for($this->producer)
            ->create();

        $oldSnapshot = PitchSnapshot::factory()
            ->for($pitch)
            ->create(['created_at' => now()->subHours(2)]);

        $latestSnapshot = PitchSnapshot::factory()
            ->for($pitch)
            ->create(['created_at' => now()->subHour()]);

        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.show', [
                'project' => $this->standardProject,
                'pitch' => $pitch
            ]));

        // Should redirect to the latest snapshot, not the old one
        $response->assertRedirect(route('projects.pitches.snapshots.show', [
            'project' => $this->standardProject->slug,
            'pitch' => $pitch->slug,
            'snapshot' => $latestSnapshot->id
        ]));
        $response->assertSessionHas('info', 'Redirected to the latest snapshot for review.');
    }

    /** @test */
    public function project_owner_can_view_latest_snapshot_directly_without_redirect()
    {
        // Create a pitch with snapshots
        $pitch = Pitch::factory()
            ->for($this->standardProject)
            ->for($this->producer)
            ->create();

        $oldSnapshot = PitchSnapshot::factory()
            ->for($pitch)
            ->create(['created_at' => now()->subHours(2)]);

        $latestSnapshot = PitchSnapshot::factory()
            ->for($pitch)
            ->create(['created_at' => now()->subHour()]);

        // Access the latest snapshot directly - should NOT redirect
        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.snapshots.show', [
                'project' => $this->standardProject,
                'pitch' => $pitch,
                'snapshot' => $latestSnapshot->id
            ]));

        $response->assertOk();
        $response->assertSeeLivewire('pitch.snapshot.show-snapshot');
    }

    /** @test */
    public function project_owner_redirected_from_old_snapshot_to_latest_snapshot()
    {
        // Create a pitch with multiple snapshots
        $pitch = Pitch::factory()
            ->for($this->standardProject)
            ->for($this->producer)
            ->create();

        $oldSnapshot = PitchSnapshot::factory()
            ->for($pitch)
            ->create(['created_at' => now()->subHours(2)]);

        $latestSnapshot = PitchSnapshot::factory()
            ->for($pitch)
            ->create(['created_at' => now()->subHour()]);

        // Try to access old snapshot - should redirect to latest
        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.snapshots.show', [
                'project' => $this->standardProject,
                'pitch' => $pitch,
                'snapshot' => $oldSnapshot->id
            ]));

        $response->assertRedirect(route('projects.pitches.snapshots.show', [
            'project' => $this->standardProject->slug,
            'pitch' => $pitch->slug,
            'snapshot' => $latestSnapshot->id
        ]));
        $response->assertSessionHas('info', 'Redirected to the latest snapshot for review.');
    }

    /** @test */
    public function client_management_project_owner_not_redirected()
    {
        // Create a pitch for client management project
        $pitch = Pitch::factory()
            ->for($this->clientManagementProject)
            ->for($this->producer)
            ->create();

        $snapshot = PitchSnapshot::factory()
            ->for($pitch)
            ->create();

        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.show', [
                'project' => $this->clientManagementProject,
                'pitch' => $pitch
            ]));

        // Should NOT redirect for client management projects
        $response->assertOk();
        $response->assertViewIs('pitches.show');
    }

    /** @test */
    public function direct_hire_project_owner_not_redirected()
    {
        // Create a pitch for direct hire project
        $pitch = Pitch::factory()
            ->for($this->directHireProject)
            ->for($this->producer)
            ->create();

        $snapshot = PitchSnapshot::factory()
            ->for($pitch)
            ->create();

        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.show', [
                'project' => $this->directHireProject,
                'pitch' => $pitch
            ]));

        // Should NOT redirect for direct hire projects
        $response->assertOk();
        $response->assertViewIs('pitches.show');
    }

    /** @test */
    public function pitch_owner_not_redirected_from_their_own_pitch()
    {
        // Create a pitch with snapshots
        $pitch = Pitch::factory()
            ->for($this->standardProject)
            ->for($this->producer)
            ->create();

        $snapshot = PitchSnapshot::factory()
            ->for($pitch)
            ->create();

        // Producer viewing their own pitch - should NOT redirect
        $response = $this->actingAs($this->producer)
            ->get(route('projects.pitches.show', [
                'project' => $this->standardProject,
                'pitch' => $pitch
            ]));

        $response->assertOk();
        $response->assertViewIs('pitches.show');
    }

    /** @test */
    public function other_users_not_affected_by_redirect_logic()
    {
        // Create a pitch with snapshots
        $pitch = Pitch::factory()
            ->for($this->standardProject)
            ->for($this->producer)
            ->create();

        $snapshot = PitchSnapshot::factory()
            ->for($pitch)
            ->create();

        // Other user trying to access (should get 403, but not redirect)
        $response = $this->actingAs($this->otherUser)
            ->get(route('projects.pitches.show', [
                'project' => $this->standardProject,
                'pitch' => $pitch
            ]));

        // Should get 403 (based on policy), not redirect
        $response->assertStatus(403);
    }
} 