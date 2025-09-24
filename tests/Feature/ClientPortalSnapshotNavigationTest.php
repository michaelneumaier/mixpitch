<?php

namespace Tests\Feature;

use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClientPortalSnapshotNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected $producer;

    protected $project;

    protected $pitch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a producer user
        $this->producer = User::factory()->create();

        // Create a client management project
        $this->project = Project::factory()->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'status' => Project::STATUS_OPEN,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
        ]);

        // Create the pitch
        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
        ]);
    }

    /** @test */
    public function client_can_view_snapshot_history()
    {
        // Create multiple snapshots
        $snapshot1 = PitchSnapshot::factory()->create([
            'pitch_id' => $this->pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'snapshot_data' => ['version' => 1, 'file_ids' => []],
            'status' => 'accepted',
        ]);

        $snapshot2 = PitchSnapshot::factory()->create([
            'pitch_id' => $this->pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'snapshot_data' => ['version' => 2, 'file_ids' => []],
            'status' => 'pending',
        ]);

        // Generate signed URL for client portal
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        // Access client portal
        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertSee('Producer Deliverables');
        $response->assertSee('Version 2 of 2'); // Should show latest by default
        $response->assertSee('2 versions available');
        $response->assertSee('Submission History');
    }

    /** @test */
    public function client_can_navigate_between_snapshots()
    {
        // Create multiple snapshots
        $snapshot1 = PitchSnapshot::factory()->create([
            'pitch_id' => $this->pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'snapshot_data' => ['version' => 1, 'file_ids' => []],
            'status' => 'accepted',
        ]);

        $snapshot2 = PitchSnapshot::factory()->create([
            'pitch_id' => $this->pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'snapshot_data' => ['version' => 2, 'file_ids' => []],
            'status' => 'pending',
        ]);

        // Generate signed URL for specific snapshot
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.snapshot',
            now()->addDays(7),
            ['project' => $this->project->id, 'snapshot' => $snapshot1->id]
        );

        // Access specific snapshot
        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertSee('Version 1 of 2'); // Should show specific version
        $response->assertSee('Files in Version 1');
    }

    /** @test */
    public function single_snapshot_hides_navigation()
    {
        // Create only one snapshot
        $snapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $this->pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'snapshot_data' => ['version' => 1, 'file_ids' => []],
            'status' => 'pending',
        ]);

        // Generate signed URL for client portal
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        // Access client portal
        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertSee('Version 1 of 1');
        $response->assertDontSee('versions available'); // Should not show navigation
        $response->assertDontSee('Submission History');
    }

    /** @test */
    public function client_can_download_files_from_specific_snapshots()
    {
        // Create a file directly on the pitch (this will trigger virtual snapshot logic)
        $file = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test_file.mp3',
        ]);

        // Generate signed URL for client portal
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        // Access client portal
        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertSee('Version 1 of 1'); // Virtual snapshot should show
        $response->assertSee('test_file.mp3');
        $response->assertSee('Download');
    }

    /** @test */
    public function unauthorized_access_to_snapshot_is_blocked()
    {
        // Create snapshot
        $snapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $this->pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
        ]);

        // Try to access without signed URL
        $response = $this->get("/projects/{$this->project->id}/portal/snapshot/{$snapshot->id}");

        $response->assertStatus(403); // Should be blocked
    }

    /** @test */
    public function virtual_snapshot_works_for_backward_compatibility()
    {
        // Create files directly on the pitch (no snapshots)
        $file1 = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'legacy_file.mp3',
        ]);

        $file2 = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'another_file.wav',
        ]);

        // Generate signed URL for client portal
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        // Access client portal
        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertSee('Producer Deliverables');
        $response->assertSee('Version 1 of 1'); // Virtual snapshot
        $response->assertSee('legacy_file.mp3');
        $response->assertSee('another_file.wav');
        $response->assertSee('Download');
        $response->assertDontSee('versions available'); // Single version
        $response->assertDontSee('Submission History'); // No navigation for single version
    }
}
