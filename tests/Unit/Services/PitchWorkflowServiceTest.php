<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PitchWorkflowService;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\PitchEvent;
use Illuminate\Foundation\Testing\RefreshDatabase; // Use database for model factories
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\Pitch\PitchCreationException;
use Mockery;
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Exceptions\Pitch\SnapshotException;
use App\Models\PitchFile;
use App\Exceptions\Pitch\SubmissionValidationException;

class PitchWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $notificationServiceMock;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock the NotificationService
        $this->notificationServiceMock = Mockery::mock(NotificationService::class);
        $this->app->instance(NotificationService::class, $this->notificationServiceMock);

        // Instantiate the service with the mock
        $this->service = new PitchWorkflowService($this->notificationServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    //-----------------------------------------
    // approveInitialPitch Tests
    //-----------------------------------------

    /** @test */
    public function it_can_approve_an_initial_pitch_successfully()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user') // Associate project with owner
                           ->create();
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user') // Associate pitch with creator
                           ->create(['status' => Pitch::STATUS_PENDING]);

        $this->notificationServiceMock->shouldReceive('notifyPitchApproved')->once()->with(Mockery::on(function ($arg) use ($pitch) {
            return $arg instanceof Pitch && $arg->id === $pitch->id;
        }));

        // Mock DB transaction to just execute the callback
        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $updatedPitch = $this->service->approveInitialPitch($pitch, $projectOwner);

        $this->assertInstanceOf(Pitch::class, $updatedPitch);
        $this->assertEquals(Pitch::STATUS_IN_PROGRESS, $updatedPitch->status);
    }

    /** @test */
    public function approve_initial_pitch_fails_if_user_is_not_project_owner()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $otherUser = User::factory()->create(); // Not the project owner
        $project = Project::factory()->for($projectOwner, 'user')
                           ->create();
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create(['status' => Pitch::STATUS_PENDING]);

        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('approve initial pitch');

        $this->service->approveInitialPitch($pitch, $otherUser);
    }

    /** @test */
    public function approve_initial_pitch_fails_if_pitch_is_not_pending()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')
                           ->create();
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create(['status' => Pitch::STATUS_IN_PROGRESS]); // Not pending

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Pitch must be pending for initial approval.');

        $this->service->approveInitialPitch($pitch, $projectOwner);
    }

    //-----------------------------------------
    // approveSubmittedPitch Tests
    //-----------------------------------------

    /** @test */
    public function it_can_approve_a_submitted_pitch_successfully()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        // Manually associate snapshot with pitch if factory doesn't
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->notificationServiceMock->shouldReceive('notifyPitchSubmissionApproved')
            ->once()
            ->with(
                Mockery::on(fn($p) => $p instanceof Pitch && $p->id === $pitch->id),
                Mockery::on(fn($s) => $s instanceof PitchSnapshot && $s->id === $snapshot->id)
            );

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $updatedPitch = $this->service->approveSubmittedPitch($pitch, $snapshot->id, $projectOwner);

        $this->assertEquals(Pitch::STATUS_APPROVED, $updatedPitch->status);
    }

    /** @test */
    public function approve_submitted_pitch_fails_if_user_is_not_project_owner()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('approve submitted pitch');

        $this->service->approveSubmittedPitch($pitch, $snapshot->id, $otherUser);
    }

    /** @test */
    public function approve_submitted_pitch_fails_if_pitch_not_ready_for_review()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_IN_PROGRESS, // Wrong status
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Pitch must be ready for review with the specified snapshot.');

        $this->service->approveSubmittedPitch($pitch, $snapshot->id, $projectOwner);
    }

    /** @test */
    public function approve_submitted_pitch_fails_if_current_snapshot_id_mismatch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $otherSnapshot = PitchSnapshot::factory()->create(); // Different snapshot
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $otherSnapshot->id, // Mismatch
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();
        $otherSnapshot->pitch_id = $pitch->id;
        $otherSnapshot->save();

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Pitch must be ready for review with the specified snapshot.');

        $this->service->approveSubmittedPitch($pitch, $snapshot->id, $projectOwner);
    }

    /** @test */
    public function approve_submitted_pitch_fails_if_snapshot_not_found()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => 999, // Non-existent snapshot ID
                           ]);

        $nonExistentSnapshotId = 999;
        $this->expectException(SnapshotException::class);
        $this->expectExceptionMessage('Snapshot not found or not pending review');
        
        $this->service->approveSubmittedPitch($pitch, $nonExistentSnapshotId, $projectOwner);
    }

    /** @test */
    public function approve_submitted_pitch_fails_if_snapshot_not_pending()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_ACCEPTED]); // Already accepted
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(SnapshotException::class);
        $this->expectExceptionMessage('Snapshot not found or not pending review');
        
        $this->service->approveSubmittedPitch($pitch, $snapshot->id, $projectOwner);
    }

    /** @test */
    public function approve_submitted_pitch_fails_if_pitch_is_paid_and_completed()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_COMPLETED, // Completed
                               'payment_status' => Pitch::PAYMENT_STATUS_PAID, // Paid
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Paid & completed pitch cannot be modified.');

        $this->service->approveSubmittedPitch($pitch, $snapshot->id, $projectOwner);
    }

    //-----------------------------------------
    // denySubmittedPitch Tests
    //-----------------------------------------

    /** @test */
    public function it_can_deny_a_submitted_pitch_successfully()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();
        $reason = 'Needs more work.';

        $this->notificationServiceMock->shouldReceive('notifyPitchSubmissionDenied')
            ->once()
            ->with(
                Mockery::on(fn($p) => $p instanceof Pitch && $p->id === $pitch->id),
                Mockery::on(fn($s) => $s instanceof PitchSnapshot && $s->id === $snapshot->id),
                $reason
            );

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $updatedPitch = $this->service->denySubmittedPitch($pitch, $snapshot->id, $projectOwner, $reason);

        $this->assertEquals(Pitch::STATUS_DENIED, $updatedPitch->status);
    }

     /** @test */
    public function deny_submitted_pitch_fails_if_user_is_not_project_owner()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('deny submitted pitch');

        $this->service->denySubmittedPitch($pitch, $snapshot->id, $otherUser, 'test reason');
    }

    /** @test */
    public function deny_submitted_pitch_fails_if_pitch_not_ready_for_review()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_IN_PROGRESS, // Wrong status
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Pitch must be ready for review with the specified snapshot to deny.');

        $this->service->denySubmittedPitch($pitch, $snapshot->id, $projectOwner, 'test reason');
    }

    /** @test */
    public function deny_submitted_pitch_fails_if_snapshot_not_pending()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_ACCEPTED]); // Not pending
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(SnapshotException::class);
        $this->expectExceptionMessage('Snapshot not found or not pending review');
        
        $this->service->denySubmittedPitch($pitch, $snapshot->id, $projectOwner, 'Reason');
    }

    /** @test */
    public function deny_submitted_pitch_fails_if_pitch_is_paid_and_completed()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_COMPLETED,
                               'payment_status' => Pitch::PAYMENT_STATUS_PAID,
                           ]);

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Paid & completed pitch cannot be modified');
        
        $this->service->denySubmittedPitch($pitch, 1, $projectOwner, 'Reason');
    }

    //-----------------------------------------
    // requestPitchRevisions Tests
    //-----------------------------------------

    /** @test */
    public function it_can_request_pitch_revisions_successfully()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();
        $feedback = 'Adjust the timing.';

        $this->notificationServiceMock->shouldReceive('notifyPitchRevisionsRequested')
            ->once()
            ->with(
                Mockery::on(fn($p) => $p instanceof Pitch && $p->id === $pitch->id),
                Mockery::on(fn($s) => $s instanceof PitchSnapshot && $s->id === $snapshot->id),
                $feedback
            );

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $updatedPitch = $this->service->requestPitchRevisions($pitch, $snapshot->id, $projectOwner, $feedback);

        $this->assertEquals(Pitch::STATUS_REVISIONS_REQUESTED, $updatedPitch->status);
    }

    /** @test */
    public function request_pitch_revisions_fails_if_user_is_not_project_owner()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('request revisions for this pitch');

        $this->service->requestPitchRevisions($pitch, $snapshot->id, $otherUser, 'test feedback');
    }

    /** @test */
    public function request_pitch_revisions_fails_if_pitch_not_ready_for_review()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_APPROVED, // Wrong status
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Pitch must be ready for review with the specified snapshot to request revisions.');

        $this->service->requestPitchRevisions($pitch, $snapshot->id, $projectOwner, 'test feedback');
    }

    /** @test */
    public function request_pitch_revisions_fails_if_snapshot_not_pending()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_ACCEPTED]); // Not pending
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(SnapshotException::class);
        $this->expectExceptionMessage('Snapshot not found or not pending review');
        
        $this->service->requestPitchRevisions($pitch, $snapshot->id, $projectOwner, 'Feedback');
    }

    /** @test */
    public function request_pitch_revisions_fails_if_feedback_is_empty()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();
        $emptyFeedback = '';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Revision feedback cannot be empty.');

        $this->service->requestPitchRevisions($pitch, $snapshot->id, $projectOwner, $emptyFeedback);
    }

    /** @test */
    public function request_pitch_revisions_fails_if_pitch_is_paid_and_completed()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_COMPLETED,
                               'payment_status' => Pitch::PAYMENT_STATUS_PAID,
                           ]);

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Paid & completed pitch cannot be modified');
        
        $this->service->requestPitchRevisions($pitch, 1, $projectOwner, 'Feedback');
    }

    //-----------------------------------------
    // cancelPitchSubmission Tests
    //-----------------------------------------

    /** @test */
    public function it_can_cancel_a_pitch_submission_successfully()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user') // Pitch creator is cancelling
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        // No notification expected by default
        $this->notificationServiceMock->shouldNotReceive('notify'); // General check

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $updatedPitch = $this->service->cancelPitchSubmission($pitch, $pitchCreator);

        $this->assertEquals(Pitch::STATUS_IN_PROGRESS, $updatedPitch->status);
        $this->assertNull($updatedPitch->current_snapshot_id);
    }

    /** @test */
    public function cancel_pitch_submission_fails_if_user_is_not_pitch_creator()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $otherUser = User::factory()->create(); // Not the creator
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('cancel pitch submission');

        $this->service->cancelPitchSubmission($pitch, $otherUser);
    }

    /** @test */
    public function cancel_pitch_submission_fails_if_pitch_not_ready_for_review()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_IN_PROGRESS, // Wrong status
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Pitch must be ready for review to cancel submission.');

        $this->service->cancelPitchSubmission($pitch, $pitchCreator);
    }

    /** @test */
    public function cancel_pitch_submission_fails_if_snapshot_not_pending()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_ACCEPTED]); // Not pending
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                               'current_snapshot_id' => $snapshot->id,
                           ]);
        $snapshot->pitch_id = $pitch->id;
        $snapshot->save();

        $this->expectException(SnapshotException::class);
        $this->expectExceptionMessage('Cannot cancel submission; the current snapshot is not pending review');
        
        $this->service->cancelPitchSubmission($pitch, $pitchCreator);
    }

    //-----------------------------------------
    // submitPitchForReview Tests
    //-----------------------------------------

    /** @test */
    public function it_can_submit_a_pitch_for_review_successfully_first_time()
    {
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->create(); // Project owner doesn't matter for this action
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        // Create associated files
        PitchFile::factory()->count(2)->for($pitch)->create();

        $this->notificationServiceMock->shouldReceive('notifyPitchReadyForReview')
            ->once()
            ->with(
                Mockery::on(fn($p) => $p instanceof Pitch && $p->id === $pitch->id),
                Mockery::type(PitchSnapshot::class)
            );

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $updatedPitch = $this->service->submitPitchForReview($pitch, $pitchCreator);

        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $updatedPitch->status);
        $this->assertNotNull($updatedPitch->current_snapshot_id);
        
        // Instead of assertDatabaseHas, verify the snapshot was created correctly
        $snapshot = PitchSnapshot::find($updatedPitch->current_snapshot_id);
        $this->assertNotNull($snapshot);
        $this->assertEquals($pitch->id, $snapshot->pitch_id);
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $snapshot->status);
        $this->assertEquals(1, $snapshot->snapshot_data['version'] ?? null);
        
        // Check for event creation
        $this->assertCount(1, $pitch->events()->where('event_type', 'status_change')
            ->where('status', Pitch::STATUS_READY_FOR_REVIEW)
            ->where('snapshot_id', $updatedPitch->current_snapshot_id)
            ->get());
    }

    /** @test */
    public function it_can_resubmit_a_pitch_after_revisions_successfully()
    {
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->create();
        $previousSnapshot = PitchSnapshot::factory()->create([
            'status' => PitchSnapshot::STATUS_REVISIONS_REQUESTED, 
            'snapshot_data' => ['version' => 1]
        ]);
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchCreator, 'user')
            ->create([
                'status' => Pitch::STATUS_REVISIONS_REQUESTED,
                'current_snapshot_id' => $previousSnapshot->id
            ]);
        $previousSnapshot->pitch_id = $pitch->id;
        $previousSnapshot->save();
        PitchFile::factory()->count(1)->for($pitch)->create(); // Ensure files exist

        $this->notificationServiceMock->shouldReceive('notifyPitchReadyForReview')->once();
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $feedbackResponse = 'Addressed the feedback points.';
        $updatedPitch = $this->service->submitPitchForReview($pitch, $pitchCreator, $feedbackResponse);

        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $updatedPitch->status);
        $this->assertNotEquals($previousSnapshot->id, $updatedPitch->current_snapshot_id); // Ensure new snapshot ID
        
        // Verify the new snapshot
        $newSnapshot = PitchSnapshot::find($updatedPitch->current_snapshot_id);
        $this->assertNotNull($newSnapshot);
        $this->assertEquals($pitch->id, $newSnapshot->pitch_id);
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $newSnapshot->status);
        $this->assertEquals(2, $newSnapshot->snapshot_data['version'] ?? null);
        
        // Verify previous snapshot was updated
        $previousSnapshot->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_REVISION_ADDRESSED, $previousSnapshot->status);
        
        // Check for event creation with proper comment
        $event = $pitch->events()
            ->where('event_type', 'status_change')
            ->where('status', Pitch::STATUS_READY_FOR_REVIEW)
            ->where('comment', 'Pitch submitted for review (Version 2). Response: Addressed the feedback points.')
            ->first();
        $this->assertNotNull($event);
    }

    /** @test */
    public function submit_pitch_for_review_fails_if_user_is_not_pitch_creator()
    {
        $pitchCreator = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        PitchFile::factory()->count(1)->for($pitch)->create();

        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('submit this pitch for review');

        $this->service->submitPitchForReview($pitch, $otherUser);
    }

    /** @test */
    public function submit_pitch_for_review_fails_if_pitch_status_is_invalid()
    {
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_APPROVED]); // Invalid status
        PitchFile::factory()->count(1)->for($pitch)->create();

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Pitch cannot be submitted from its current status.');

        $this->service->submitPitchForReview($pitch, $pitchCreator);
    }

    /** @test */
    public function submit_pitch_for_review_fails_if_no_files_are_attached()
    {
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        // No PitchFiles created

        $this->expectException(SubmissionValidationException::class); // Ensure this exception is imported
        $this->expectExceptionMessage('Cannot submit pitch for review with no files attached.');

        $this->service->submitPitchForReview($pitch, $pitchCreator);
    }

    /** @test */
    public function submit_pitch_for_review_fails_on_db_error()
    {
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        PitchFile::factory()->count(1)->for($pitch)->create();

        $this->notificationServiceMock->shouldNotReceive('notifyPitchReadyForReview');
        DB::shouldReceive('transaction')->once()->andThrow(new \Exception('DB Error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to submit pitch for review.');

        $this->service->submitPitchForReview($pitch, $pitchCreator);
    }

    /** @test */
    public function it_can_mark_pitch_as_paid_successfully()
    {
        // Arrange
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $pitch = Pitch::factory()->for($project)->for($user, 'user')->create([
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING
        ]);

        // Mock the events relationship
        $eventsRelation = Mockery::mock('events');
        $eventsRelation->shouldReceive('create')->once()->andReturn(true);
        
        // Setup the pitch mock with the events relation
        $pitchMock = Mockery::mock($pitch)->makePartial();
        $pitchMock->shouldReceive('events')->andReturn($eventsRelation);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())
            ->method('notifyPaymentProcessed')
            ->with(
                $this->equalTo($pitchMock),
                $this->anything(),
                $this->anything()
            );

        $service = new PitchWorkflowService($notificationService);
        $stripeInvoiceId = 'inv_test123456';
        $stripeChargeId = 'ch_test123456';

        // Act
        $result = $service->markPitchAsPaid($pitchMock, $stripeInvoiceId, $stripeChargeId);

        // Assert
        $this->assertEquals(Pitch::PAYMENT_STATUS_PAID, $result->payment_status);
        $this->assertEquals($stripeInvoiceId, $result->final_invoice_id);
        $this->assertNotNull($result->payment_completed_at);
    }

    /** @test */
    public function it_handles_idempotent_paid_status_updates()
    {
        // Arrange
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $pitch = Pitch::factory()->for($project)->for($user, 'user')->create([
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID, // Already paid
            'final_invoice_id' => 'inv_existing123',
            'payment_completed_at' => now()->subDay()
        ]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->never())
            ->method('notifyPaymentProcessed');

        $service = new PitchWorkflowService($notificationService);
        $stripeInvoiceId = 'inv_test123456';

        // Act
        $result = $service->markPitchAsPaid($pitch, $stripeInvoiceId);

        // Assert - should remain unchanged
        $this->assertEquals(Pitch::PAYMENT_STATUS_PAID, $result->payment_status);
        $this->assertEquals('inv_existing123', $result->final_invoice_id); // Should keep original ID
    }

    /** @test */
    public function it_throws_exception_when_marking_non_completed_pitch_as_paid()
    {
        // Arrange
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $pitch = Pitch::factory()->for($project)->for($user, 'user')->create([
            'status' => Pitch::STATUS_APPROVED, // Not COMPLETED
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING
        ]);

        $notificationService = $this->createMock(NotificationService::class);
        $service = new PitchWorkflowService($notificationService);
        $stripeInvoiceId = 'inv_test123456';

        // Assert & Act
        $this->expectException(InvalidStatusTransitionException::class);
        $service->markPitchAsPaid($pitch, $stripeInvoiceId);
    }

    /** @test */
    public function it_can_mark_pitch_payment_as_failed()
    {
        // Arrange
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $pitch = Pitch::factory()->for($project)->for($user, 'user')->create([
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING
        ]);

        // Mock the events relationship
        $eventsRelation = Mockery::mock('events');
        $eventsRelation->shouldReceive('create')->once()->andReturn(true);
        
        // Setup the pitch mock with the events relation
        $pitchMock = Mockery::mock($pitch)->makePartial();
        $pitchMock->shouldReceive('events')->andReturn($eventsRelation);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())
            ->method('notifyPaymentFailed')
            ->with(
                $this->equalTo($pitchMock),
                $this->anything()
            );

        $service = new PitchWorkflowService($notificationService);
        $stripeInvoiceId = 'inv_test123456';
        $failureReason = 'Card declined';

        // Act
        $result = $service->markPitchPaymentFailed($pitchMock, $stripeInvoiceId, $failureReason);

        // Assert
        $this->assertEquals(Pitch::PAYMENT_STATUS_FAILED, $result->payment_status);
        $this->assertEquals($stripeInvoiceId, $result->final_invoice_id);
    }

    /** @test */
    public function it_handles_idempotent_payment_failed_status_updates()
    {
        // Arrange
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $pitch = Pitch::factory()->for($project)->for($user, 'user')->create([
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_FAILED,
            'final_invoice_id' => 'inv_existing123'
        ]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->never())
            ->method('notifyPaymentFailed');

        $service = new PitchWorkflowService($notificationService);
        $stripeInvoiceId = 'inv_test123456';

        // Act
        $result = $service->markPitchPaymentFailed($pitch, $stripeInvoiceId, 'New failure reason');

        // Assert - should remain unchanged with original invoice ID
        $this->assertEquals(Pitch::PAYMENT_STATUS_FAILED, $result->payment_status);
        $this->assertEquals('inv_existing123', $result->final_invoice_id);
    }

    //-----------------------------------------
    // Helper Methods
    //-----------------------------------------

} 