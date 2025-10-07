<?php

namespace Tests\Feature;

use App\Livewire\Project\Component\ClientSubmitSection;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientSubmitSectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected Pitch $pitch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create client management project
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'client_management',
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
        ]);

        $this->pitch = Pitch::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => Pitch::STATUS_IN_PROGRESS,
        ]);

        $this->actingAs($this->user);
    }

    public function test_component_renders_submit_section_when_in_progress()
    {
        $workflowColors = [
            'icon' => 'text-purple-600',
            'text_primary' => 'text-purple-900',
            'text_muted' => 'text-purple-600',
        ];

        Livewire::test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ])
            ->assertSee('Ready to Submit')
            ->assertSee('No deliverables uploaded')
            ->assertDontSee('Submission Under Review');
    }

    public function test_component_renders_recall_section_when_ready_for_review()
    {
        $this->pitch->update(['status' => Pitch::STATUS_READY_FOR_REVIEW]);

        $workflowColors = [
            'icon' => 'text-purple-600',
            'text_primary' => 'text-purple-900',
            'text_muted' => 'text-purple-600',
        ];

        Livewire::test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ])
            ->assertSee('Submission Under Review')
            ->assertSee('Awaiting Client Review')
            ->assertSee('Recall Submission')
            ->assertDontSee('Ready to Submit for Review?');
    }

    public function test_watermarking_toggle_works()
    {
        // Add a file to the pitch so watermarking section shows
        PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test.mp3',
        ]);

        $workflowColors = [
            'icon' => 'text-purple-600',
            'text_primary' => 'text-purple-900',
            'text_muted' => 'text-purple-600',
        ];

        Livewire::test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ])
            ->assertSee('Audio Protection')
            ->assertSet('watermarkingEnabled', false)
            ->set('watermarkingEnabled', true)
            ->assertSet('watermarkingEnabled', true);
    }

    public function test_recall_submission_ui_displays_correctly()
    {
        $this->pitch->update(['status' => Pitch::STATUS_READY_FOR_REVIEW]);

        // First verify the policy works correctly
        $this->assertTrue($this->user->can('recallSubmission', $this->pitch));

        $workflowColors = [
            'icon' => 'text-purple-600',
            'text_primary' => 'text-purple-900',
            'text_muted' => 'text-purple-600',
        ];

        // Test that the UI displays correctly
        $component = Livewire::actingAs($this->user)->test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ]);

        $component->assertSee('Recall Submission')
            ->assertSee('Awaiting Client Review')
            ->assertSee('Need to make changes?');
    }

    public function test_recall_submission_deletes_snapshot_and_updates_pitch_status()
    {
        // Setup: Create a file and submit for review to create a snapshot
        $pitchFile = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test.mp3',
        ]);

        $pitchWorkflowService = app(\App\Services\PitchWorkflowService::class);
        $pitchWorkflowService->submitPitchForReview($this->pitch, $this->user);

        $this->pitch->refresh();

        // Verify snapshot was created
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $this->pitch->status);
        $this->assertNotNull($this->pitch->current_snapshot_id);

        $snapshot = $this->pitch->currentSnapshot;
        $this->assertNotNull($snapshot);
        $snapshotId = $snapshot->id;
        $this->assertEquals(\App\Models\PitchSnapshot::STATUS_PENDING, $snapshot->status);

        $workflowColors = [
            'icon' => 'text-purple-600',
            'text_primary' => 'text-purple-900',
            'text_muted' => 'text-purple-600',
        ];

        // Execute: Recall the submission
        Livewire::actingAs($this->user)->test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ])
            ->call('recallSubmission')
            ->assertDispatched('pitchStatusChanged');

        // Verify: Pitch status changed back to IN_PROGRESS
        $this->pitch->refresh();
        $this->assertEquals(Pitch::STATUS_IN_PROGRESS, $this->pitch->status);

        // Verify: current_snapshot_id is cleared
        $this->assertNull($this->pitch->current_snapshot_id);

        // Verify: Snapshot was DELETED (not just cancelled)
        $this->assertDatabaseMissing('pitch_snapshots', [
            'id' => $snapshotId,
        ]);

        // Verify: Event was created with snapshot metadata for audit trail
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $this->pitch->id,
            'event_type' => 'submission_recalled',
            'status' => Pitch::STATUS_IN_PROGRESS,
        ]);

        // Verify: Pitch file still exists (files are not deleted)
        $this->assertDatabaseHas('pitch_files', [
            'id' => $pitchFile->id,
            'pitch_id' => $this->pitch->id,
        ]);
    }

    public function test_recall_submission_preserves_audit_trail_in_event_metadata()
    {
        // Setup: Create a file and submit for review
        $pitchFile = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test.mp3',
        ]);

        $pitchWorkflowService = app(\App\Services\PitchWorkflowService::class);
        $pitchWorkflowService->submitPitchForReview($this->pitch, $this->user);

        $this->pitch->refresh();
        $snapshot = $this->pitch->currentSnapshot;
        $originalSnapshotId = $snapshot->id;
        $originalVersion = $snapshot->version;

        $workflowColors = [
            'icon' => 'text-purple-600',
            'text_primary' => 'text-purple-900',
            'text_muted' => 'text-purple-600',
        ];

        // Execute: Recall the submission
        Livewire::actingAs($this->user)->test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ])->call('recallSubmission');

        // Verify: Event contains snapshot metadata
        $event = \App\Models\PitchEvent::where('pitch_id', $this->pitch->id)
            ->where('event_type', 'submission_recalled')
            ->latest()
            ->first();

        $this->assertNotNull($event);
        $this->assertNotNull($event->metadata);
        $this->assertEquals($originalSnapshotId, $event->metadata['snapshot_id']);
        $this->assertEquals($originalVersion, $event->metadata['version']);
        $this->assertArrayHasKey('file_ids', $event->metadata);
        $this->assertArrayHasKey('file_count', $event->metadata);
        $this->assertArrayHasKey('restored_status', $event->metadata);
        $this->assertArrayHasKey('restored_snapshot_id', $event->metadata);
    }

    public function test_recall_restores_to_client_revisions_requested_when_that_was_previous_state()
    {
        // Setup: Submit V1
        $v1File = PitchFile::factory()->create(['pitch_id' => $this->pitch->id, 'file_name' => 'v1.mp3']);
        $pitchWorkflowService = app(\App\Services\PitchWorkflowService::class);
        $pitchWorkflowService->submitPitchForReview($this->pitch, $this->user);
        $this->pitch->refresh();

        $v1Snapshot = $this->pitch->currentSnapshot;

        // Client requests revisions on V1
        $pitchWorkflowService->clientRequestRevisions($this->pitch, 'Please change X', 'client@example.com');
        $this->pitch->refresh();
        $this->assertEquals(Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, $this->pitch->status);

        // Producer submits V2 in response to revisions
        $v2File = PitchFile::factory()->create(['pitch_id' => $this->pitch->id, 'file_name' => 'v2.mp3']);
        $pitchWorkflowService->submitPitchForReview($this->pitch, $this->user, 'Fixed X as requested');
        $this->pitch->refresh();

        $v2Snapshot = $this->pitch->currentSnapshot;
        $v2SnapshotId = $v2Snapshot->id;
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $this->pitch->status);
        $this->assertEquals($v2Snapshot->id, $this->pitch->current_snapshot_id);

        $workflowColors = ['icon' => 'text-purple-600', 'text_primary' => 'text-purple-900', 'text_muted' => 'text-purple-600'];

        // Producer recalls V2
        Livewire::actingAs($this->user)->test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ])->call('recallSubmission');

        // Verify: Status restored to CLIENT_REVISIONS_REQUESTED
        $this->pitch->refresh();
        $this->assertEquals(Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, $this->pitch->status);

        // Verify: current_snapshot_id restored to V1
        $this->assertEquals($v1Snapshot->id, $this->pitch->current_snapshot_id);

        // Verify: V2 snapshot was deleted
        $this->assertDatabaseMissing('pitch_snapshots', ['id' => $v2SnapshotId]);

        // Verify: V1 snapshot still exists
        $this->assertDatabaseHas('pitch_snapshots', ['id' => $v1Snapshot->id]);
    }

    public function test_recall_preserves_previous_snapshot_when_restoring_state()
    {
        // Setup: Submit V1, get approved
        $v1File = PitchFile::factory()->create(['pitch_id' => $this->pitch->id, 'file_name' => 'v1.mp3']);
        $pitchWorkflowService = app(\App\Services\PitchWorkflowService::class);
        $pitchWorkflowService->submitPitchForReview($this->pitch, $this->user);
        $this->pitch->refresh();

        $v1Snapshot = $this->pitch->currentSnapshot;

        // Client approves V1 (simulate - create proper event)
        $v1Snapshot->update(['status' => \App\Models\PitchSnapshot::STATUS_ACCEPTED]);
        $this->pitch->update(['status' => Pitch::STATUS_COMPLETED]);

        // Create event for the status change to COMPLETED (mimicking what would happen in real workflow)
        $this->pitch->events()->create([
            'event_type' => 'status_change',
            'status' => Pitch::STATUS_COMPLETED,
            'comment' => 'Client approved pitch',
            'snapshot_id' => $v1Snapshot->id,
            'created_by' => null, // Client action
        ]);

        // Producer submits V2 (additional work)
        $v2File = PitchFile::factory()->create(['pitch_id' => $this->pitch->id, 'file_name' => 'v2.mp3']);
        // Manually transition to IN_PROGRESS first (would normally happen through workflow)
        $this->pitch->update(['status' => Pitch::STATUS_IN_PROGRESS]);
        $pitchWorkflowService->submitPitchForReview($this->pitch, $this->user, 'Additional improvements');
        $this->pitch->refresh();

        $v2Snapshot = $this->pitch->currentSnapshot;
        $v2SnapshotId = $v2Snapshot->id;

        $workflowColors = ['icon' => 'text-purple-600', 'text_primary' => 'text-purple-900', 'text_muted' => 'text-purple-600'];

        // Producer recalls V2
        Livewire::actingAs($this->user)->test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ])->call('recallSubmission');

        // Verify: Status restored to COMPLETED (V1's state)
        $this->pitch->refresh();
        $this->assertEquals(Pitch::STATUS_COMPLETED, $this->pitch->status);

        // Verify: current_snapshot_id restored to V1
        $this->assertEquals($v1Snapshot->id, $this->pitch->current_snapshot_id);

        // Verify: V1 snapshot still exists and is still accepted
        $v1Snapshot->refresh();
        $this->assertEquals(\App\Models\PitchSnapshot::STATUS_ACCEPTED, $v1Snapshot->status);

        // Verify: V2 snapshot was deleted
        $this->assertDatabaseMissing('pitch_snapshots', ['id' => $v2SnapshotId]);
    }
}
