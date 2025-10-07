<?php

namespace Tests\Feature;

use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Models\User;
use App\Services\FileManagementService;
use App\Services\PitchWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileRevisionTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $producer;

    protected User $projectOwner;

    protected Project $clientProject;

    protected Pitch $pitch;

    protected FileManagementService $fileManagementService;

    protected PitchWorkflowService $pitchWorkflowService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');

        $this->producer = User::factory()->create();
        $this->projectOwner = User::factory()->create();

        // Create client management project
        $this->clientProject = Project::factory()
            ->recycle($this->projectOwner)
            ->create([
                'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
                'client_email' => 'client@example.com',
                'client_name' => 'Test Client',
                'status' => Project::STATUS_OPEN,
            ]);

        // Create pitch for the client project
        $this->pitch = Pitch::factory()
            ->recycle($this->producer)
            ->recycle($this->clientProject)
            ->create([
                'status' => Pitch::STATUS_IN_PROGRESS,
                'included_revisions' => 2,
                'additional_revision_price' => 50.00,
                'revisions_used' => 0,
            ]);

        $this->fileManagementService = app(FileManagementService::class);
        $this->pitchWorkflowService = app(PitchWorkflowService::class);
    }

    /** @test */
    public function new_files_are_tagged_with_revision_round_1_on_initial_upload()
    {
        // Create a fake file in S3
        $s3Key = 'pitches/'.$this->pitch->id.'/test-file.mp3';
        Storage::disk('s3')->put($s3Key, 'fake audio content');

        // Upload file to pitch (initial submission, revisions_used = 0)
        $pitchFile = $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $s3Key,
            'test-file.mp3',
            1024,
            'audio/mpeg',
            $this->producer
        );

        // Assert file was created with revision_round = 1 (initial submission)
        $this->assertEquals(1, $pitchFile->revision_round);
        $this->assertFalse($pitchFile->superseded_by_revision);
    }

    /** @test */
    public function new_files_are_tagged_with_current_revision_round_after_revisions_requested()
    {
        // Initial file upload (revision round 1)
        $initialS3Key = 'pitches/'.$this->pitch->id.'/initial-file.mp3';
        Storage::disk('s3')->put($initialS3Key, 'initial audio');

        $initialFile = $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $initialS3Key,
            'initial-file.mp3',
            1024,
            'audio/mpeg',
            $this->producer
        );

        $this->assertEquals(1, $initialFile->revision_round);

        // Simulate revision requested - increment revisions_used
        $this->pitch->revisions_used = 1;
        $this->pitch->save();

        // Upload new file during revision
        $revisionS3Key = 'pitches/'.$this->pitch->id.'/revision-file.mp3';
        Storage::disk('s3')->put($revisionS3Key, 'revised audio');

        $revisionFile = $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $revisionS3Key,
            'revision-file.mp3',
            2048,
            'audio/mpeg',
            $this->producer
        );

        // Assert new file is tagged with revision_round = 1 (current revision)
        $this->assertEquals(1, $revisionFile->revision_round);

        // Assert initial file still has revision_round = 1
        $this->assertEquals(1, $initialFile->fresh()->revision_round);
    }

    /** @test */
    public function files_track_multiple_revision_rounds()
    {
        // Initial submission (revision 0, round 1)
        $s3Key1 = 'pitches/'.$this->pitch->id.'/file-v1.mp3';
        Storage::disk('s3')->put($s3Key1, 'version 1');

        $file1 = $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $s3Key1,
            'file-v1.mp3',
            1024,
            'audio/mpeg',
            $this->producer
        );

        $this->assertEquals(1, $file1->revision_round);

        // First revision requested (revision 1, round 1)
        $this->pitch->update(['revisions_used' => 1]);

        $s3Key2 = 'pitches/'.$this->pitch->id.'/file-v2.mp3';
        Storage::disk('s3')->put($s3Key2, 'version 2');

        $file2 = $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $s3Key2,
            'file-v2.mp3',
            2048,
            'audio/mpeg',
            $this->producer
        );

        $this->assertEquals(1, $file2->revision_round);

        // Second revision requested (revision 2, round 2 - paid revision)
        $this->pitch->update(['revisions_used' => 2]);

        $s3Key3 = 'pitches/'.$this->pitch->id.'/file-v3.mp3';
        Storage::disk('s3')->put($s3Key3, 'version 3');

        $file3 = $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $s3Key3,
            'file-v3.mp3',
            3072,
            'audio/mpeg',
            $this->producer
        );

        $this->assertEquals(2, $file3->revision_round);

        // Verify all files retained their revision rounds
        $this->assertEquals(1, $file1->fresh()->revision_round);
        $this->assertEquals(1, $file2->fresh()->revision_round);
        $this->assertEquals(2, $file3->fresh()->revision_round);
    }

    /** @test */
    public function revision_tracking_only_applies_to_client_management_projects()
    {
        // Create standard workflow project
        $standardProject = Project::factory()
            ->recycle($this->projectOwner)
            ->create([
                'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
                'status' => Project::STATUS_OPEN,
            ]);

        $standardPitch = Pitch::factory()
            ->recycle($this->producer)
            ->recycle($standardProject)
            ->create([
                'status' => Pitch::STATUS_IN_PROGRESS,
                'revisions_used' => 3, // Even with revisions used
            ]);

        $s3Key = 'pitches/'.$standardPitch->id.'/standard-file.mp3';
        Storage::disk('s3')->put($s3Key, 'standard audio');

        $file = $this->fileManagementService->createPitchFileFromS3(
            $standardPitch,
            $s3Key,
            'standard-file.mp3',
            1024,
            'audio/mpeg',
            $this->producer
        );

        // Standard workflow should always use revision_round = 1
        $this->assertEquals(1, $file->revision_round);
    }

    // ===========================
    // SNAPSHOT STATUS TESTS
    // ===========================

    /** @test */
    public function snapshot_status_is_pending_when_producer_submits_for_review()
    {
        // Upload a file first
        $s3Key = 'pitches/'.$this->pitch->id.'/test.mp3';
        Storage::disk('s3')->put($s3Key, 'audio content');

        $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $s3Key,
            'test.mp3',
            1024,
            'audio/mpeg',
            $this->producer
        );

        // Submit for review
        $this->pitchWorkflowService->submitPitchForReview($this->pitch, $this->producer);

        // Verify snapshot was created with PENDING status
        $this->pitch->refresh();
        $snapshot = $this->pitch->currentSnapshot;

        $this->assertNotNull($snapshot);
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $snapshot->status);
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $this->pitch->status);
    }

    /** @test */
    public function snapshot_status_becomes_accepted_when_client_approves()
    {
        // Setup: Submit for review first
        $s3Key = 'pitches/'.$this->pitch->id.'/test.mp3';
        Storage::disk('s3')->put($s3Key, 'audio content');

        $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $s3Key,
            'test.mp3',
            1024,
            'audio/mpeg',
            $this->producer
        );

        $this->pitchWorkflowService->submitPitchForReview($this->pitch, $this->producer);
        $this->pitch->refresh();

        $snapshot = $this->pitch->currentSnapshot;
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $snapshot->status);

        // Client approves
        $this->pitchWorkflowService->clientApprovePitch($this->pitch, 'client@example.com');

        // Verify snapshot status became ACCEPTED
        $snapshot->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_ACCEPTED, $snapshot->status);
        $this->assertEquals(Pitch::STATUS_COMPLETED, $this->pitch->fresh()->status);
    }

    /** @test */
    public function snapshot_status_becomes_revisions_requested_when_client_requests_revisions()
    {
        // Setup: Submit for review first
        $s3Key = 'pitches/'.$this->pitch->id.'/test.mp3';
        Storage::disk('s3')->put($s3Key, 'audio content');

        $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $s3Key,
            'test.mp3',
            1024,
            'audio/mpeg',
            $this->producer
        );

        $this->pitchWorkflowService->submitPitchForReview($this->pitch, $this->producer);
        $this->pitch->refresh();

        $snapshot = $this->pitch->currentSnapshot;
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $snapshot->status);

        // Client requests revisions
        $this->pitchWorkflowService->clientRequestRevisions(
            $this->pitch,
            'Please adjust the bass levels',
            'client@example.com'
        );

        // Verify snapshot status became REVISIONS_REQUESTED
        $snapshot->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_REVISIONS_REQUESTED, $snapshot->status);
        $this->assertEquals(Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, $this->pitch->fresh()->status);
    }

    /** @test */
    public function snapshot_status_becomes_cancelled_when_producer_recalls_submission()
    {
        // Setup: Submit for review first
        $s3Key = 'pitches/'.$this->pitch->id.'/test.mp3';
        Storage::disk('s3')->put($s3Key, 'audio content');

        $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $s3Key,
            'test.mp3',
            1024,
            'audio/mpeg',
            $this->producer
        );

        $this->pitchWorkflowService->submitPitchForReview($this->pitch, $this->producer);
        $this->pitch->refresh();

        $snapshot = $this->pitch->currentSnapshot;
        $snapshotId = $snapshot->id;
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $snapshot->status);

        // Producer recalls (via cancelPitchSubmission method from standard workflow)
        // Note: Standard workflow MARKS as CANCELLED (keeps for audit trail)
        // Client management workflow DELETES snapshots (tested in ClientSubmitSectionTest)
        $this->pitchWorkflowService->cancelPitchSubmission($this->pitch, $this->producer);

        // Verify snapshot status became CANCELLED (not deleted in standard workflow)
        $cancelledSnapshot = PitchSnapshot::find($snapshotId);
        $this->assertEquals(PitchSnapshot::STATUS_CANCELLED, $cancelledSnapshot->status);
        $this->assertEquals(Pitch::STATUS_IN_PROGRESS, $this->pitch->fresh()->status);
        $this->assertNull($this->pitch->fresh()->current_snapshot_id);
    }

    /** @test */
    public function resubmit_after_revisions_marks_old_snapshot_as_revision_addressed()
    {
        // Setup: Submit for review first
        $s3Key1 = 'pitches/'.$this->pitch->id.'/test-v1.mp3';
        Storage::disk('s3')->put($s3Key1, 'audio content v1');

        $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $s3Key1,
            'test-v1.mp3',
            1024,
            'audio/mpeg',
            $this->producer
        );

        $this->pitchWorkflowService->submitPitchForReview($this->pitch, $this->producer);
        $this->pitch->refresh();

        $firstSnapshot = $this->pitch->currentSnapshot;
        $firstSnapshotId = $firstSnapshot->id;

        // Client requests revisions
        $this->pitchWorkflowService->clientRequestRevisions(
            $this->pitch,
            'Please adjust the mix',
            'client@example.com'
        );

        $firstSnapshot->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_REVISIONS_REQUESTED, $firstSnapshot->status);

        // Producer uploads new file and resubmits
        $s3Key2 = 'pitches/'.$this->pitch->id.'/test-v2.mp3';
        Storage::disk('s3')->put($s3Key2, 'audio content v2');

        $this->fileManagementService->createPitchFileFromS3(
            $this->pitch,
            $s3Key2,
            'test-v2.mp3',
            1024,
            'audio/mpeg',
            $this->producer
        );

        $this->pitchWorkflowService->submitPitchForReview($this->pitch, $this->producer, 'Fixed the mix as requested');

        // Verify old snapshot status became REVISION_ADDRESSED
        $firstSnapshot->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_REVISION_ADDRESSED, $firstSnapshot->status);

        // Verify new snapshot is PENDING
        $this->pitch->refresh();
        $newSnapshot = $this->pitch->currentSnapshot;
        $this->assertNotEquals($firstSnapshotId, $newSnapshot->id);
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $newSnapshot->status);
        $this->assertEquals(2, $newSnapshot->version);
    }

    /** @test */
    public function multiple_revision_cycles_maintain_proper_snapshot_history()
    {
        // Initial submission
        $s3Key1 = 'pitches/'.$this->pitch->id.'/v1.mp3';
        Storage::disk('s3')->put($s3Key1, 'v1');
        $this->fileManagementService->createPitchFileFromS3($this->pitch, $s3Key1, 'v1.mp3', 1024, 'audio/mpeg', $this->producer);
        $this->pitchWorkflowService->submitPitchForReview($this->pitch, $this->producer);
        $this->pitch->refresh();
        $snapshot1 = $this->pitch->currentSnapshot;

        // First revision request
        $this->pitchWorkflowService->clientRequestRevisions($this->pitch, 'Revision 1', 'client@example.com');
        $snapshot1->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_REVISIONS_REQUESTED, $snapshot1->status);

        // Second submission
        $s3Key2 = 'pitches/'.$this->pitch->id.'/v2.mp3';
        Storage::disk('s3')->put($s3Key2, 'v2');
        $this->fileManagementService->createPitchFileFromS3($this->pitch, $s3Key2, 'v2.mp3', 1024, 'audio/mpeg', $this->producer);
        $this->pitchWorkflowService->submitPitchForReview($this->pitch, $this->producer, 'Addressed revision 1');
        $this->pitch->refresh();
        $snapshot2 = $this->pitch->currentSnapshot;

        // Verify first snapshot is REVISION_ADDRESSED
        $snapshot1->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_REVISION_ADDRESSED, $snapshot1->status);
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $snapshot2->status);

        // Second revision request
        $this->pitchWorkflowService->clientRequestRevisions($this->pitch, 'Revision 2', 'client@example.com');
        $snapshot2->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_REVISIONS_REQUESTED, $snapshot2->status);

        // Third submission
        $s3Key3 = 'pitches/'.$this->pitch->id.'/v3.mp3';
        Storage::disk('s3')->put($s3Key3, 'v3');
        $this->fileManagementService->createPitchFileFromS3($this->pitch, $s3Key3, 'v3.mp3', 1024, 'audio/mpeg', $this->producer);
        $this->pitchWorkflowService->submitPitchForReview($this->pitch, $this->producer, 'Addressed revision 2');
        $this->pitch->refresh();
        $snapshot3 = $this->pitch->currentSnapshot;

        // Final verification
        $snapshot2->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_REVISION_ADDRESSED, $snapshot2->status);
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $snapshot3->status);
        $this->assertEquals(3, $snapshot3->version);

        // Client approves final version
        $this->pitchWorkflowService->clientApprovePitch($this->pitch, 'client@example.com');
        $snapshot3->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_ACCEPTED, $snapshot3->status);

        // Verify complete history
        $allSnapshots = $this->pitch->snapshots()->orderBy('created_at')->get();
        $this->assertCount(3, $allSnapshots);
        $this->assertEquals(PitchSnapshot::STATUS_REVISION_ADDRESSED, $allSnapshots[0]->status);
        $this->assertEquals(PitchSnapshot::STATUS_REVISION_ADDRESSED, $allSnapshots[1]->status);
        $this->assertEquals(PitchSnapshot::STATUS_ACCEPTED, $allSnapshots[2]->status);
    }
}
