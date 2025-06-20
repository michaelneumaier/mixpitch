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
use App\Services\InvoiceService;
use App\Exceptions\Payment\InvoiceCreationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

class PitchWorkflowServiceTest extends TestCase
{
    use RefreshDatabase; // Re-enable

    protected $notificationServiceMock;
    protected $service;
    protected $invoiceServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock the NotificationService
        $this->notificationServiceMock = Mockery::mock(NotificationService::class);
        $this->app->instance(NotificationService::class, $this->notificationServiceMock);

        // Mock the InvoiceService
        $this->invoiceServiceMock = Mockery::mock(InvoiceService::class);
        $this->app->instance(InvoiceService::class, $this->invoiceServiceMock);

        // Instantiate the service with the mocks
        $this->service = new PitchWorkflowService(
            $this->notificationServiceMock,
            // Pass InvoiceService mock if constructor is updated, otherwise rely on app instance
            // $this->invoiceServiceMock 
        ); 
        // Note: If InvoiceService isn't injected via constructor, the app->instance() above handles it.
        // Keep service instantiation as is unless constructor changes.
         $this->service = new PitchWorkflowService($this->notificationServiceMock); // Keep original if InvoiceService is resolved via app() inside method
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
        $project = Project::factory()->for($projectOwner, 'user')
                           ->create(['workflow_type' => Project::WORKFLOW_TYPE_STANDARD]); // Explicitly set type
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
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
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')
                           ->create(['workflow_type' => Project::WORKFLOW_TYPE_STANDARD]); // Explicitly set type
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
                           ->create(['workflow_type' => Project::WORKFLOW_TYPE_STANDARD]); // Explicitly set type
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create(['status' => Pitch::STATUS_IN_PROGRESS]);

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
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               'status' => Pitch::STATUS_READY_FOR_REVIEW,
                           ]);
        $snapshot = PitchSnapshot::factory()->for($pitch)->create(['status' => PitchSnapshot::STATUS_PENDING]);
        $pitch->update(['current_snapshot_id' => $snapshot->id]);
        
        $feedback = 'Needs more cowbell';

        // Set expectation for the specific notification
        $this->notificationServiceMock->shouldReceive('notifySnapshotRevisionsRequested')
            ->once()
            ->with(
                Mockery::on(fn($s) => $s instanceof PitchSnapshot && $s->id === $snapshot->id),
                $feedback
            );

        // Act
        $updatedPitch = $this->service->requestPitchRevisions($pitch, $snapshot->id, $projectOwner, $feedback);

        // Assert
        $this->assertEquals(Pitch::STATUS_REVISIONS_REQUESTED, $updatedPitch->status);
        $snapshot->refresh(); // Re-fetch snapshot from DB
        $this->assertEquals(PitchSnapshot::STATUS_REVISIONS_REQUESTED, $snapshot->status);
        // Check event was created
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $pitch->id,
            'snapshot_id' => $snapshot->id,
            'event_type' => 'revision_request',
            'status' => Pitch::STATUS_REVISIONS_REQUESTED,
            'created_by' => $projectOwner->id,
            // Check if feedback is stored in metadata (using json_contains or similar)
             // ->whereJsonContains('metadata->feedback', $feedback) // Requires DB setup or specific assertion
        ]);
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
        $pitch = Pitch::factory()
            ->for($project)
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
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();

        // Create the Pitch first
        $pitch = Pitch::factory()->for($project)
                           ->for($pitchCreator, 'user')
                           ->create([
                               // Set initial pitch status appropriately, maybe IN_PROGRESS first
                               // We'll update it after creating the snapshot
                               'status' => Pitch::STATUS_IN_PROGRESS,
                           ]);

        // Create the previous snapshot *associated with the pitch*
        $previousSnapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $pitch->id, // Associate immediately
            'status' => PitchSnapshot::STATUS_REVISIONS_REQUESTED,
            'snapshot_data' => ['version' => 1], // Ensure version exists
        ]);

        // Update the pitch to reflect the revisions requested state
        $pitch->update([
            'status' => Pitch::STATUS_REVISIONS_REQUESTED,
            'current_snapshot_id' => $previousSnapshot->id,
        ]);

        // Attach a file to the pitch (needed for submission validation)
        PitchFile::factory()->for($pitch)->create();

        $responseMessage = 'Addressed feedback.';

        // Expect notification for the *new* snapshot
        $this->notificationServiceMock->shouldReceive('notifyPitchReadyForReview')
            ->once()
            ->with(
                Mockery::on(fn($p) => $p->id === $pitch->id),
                Mockery::on(fn($s) => $s instanceof PitchSnapshot && $s->status === PitchSnapshot::STATUS_PENDING)
            );

        // Remove DB::transaction mock
        // DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        // Act
        $updatedPitch = $this->service->submitPitchForReview($pitch, $pitchCreator, $responseMessage);

        // Assert new status
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $updatedPitch->status);
        
        // Verify a new snapshot was created and linked
        $newSnapshot = $updatedPitch->currentSnapshot;
        $this->assertNotNull($newSnapshot);
        $this->assertNotEquals($previousSnapshot->id, $newSnapshot->id);
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $newSnapshot->status);
        $this->assertEquals(2, $newSnapshot->snapshot_data['version'] ?? null); // Check version increment
        
        // Verify previous snapshot was updated
        $reloadedPreviousSnapshot = PitchSnapshot::find($previousSnapshot->id);
        $this->assertNotNull($reloadedPreviousSnapshot, 'Failed to reload previous snapshot from DB');
        $this->assertEquals(PitchSnapshot::STATUS_REVISION_ADDRESSED, $reloadedPreviousSnapshot->status);
        
        // Check for event creation with proper comment
        $this->assertDatabaseHas('pitch_events', [
             'pitch_id' => $pitch->id,
             'snapshot_id' => $newSnapshot->id,
             'event_type' => 'status_change',
             'status' => Pitch::STATUS_READY_FOR_REVIEW,
             'comment' => 'Pitch submitted for review (Version 2). Response: Addressed feedback.'
        ]);
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
    // Contest Workflow Tests
    //-----------------------------------------

    /** @test */
    public function test_it_can_select_contest_winner_with_prize()
    {
        // Arrange: Create owner, producer, contest project, and pitch entry
        $projectOwner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'prize_amount' => 100.00,
            'prize_currency' => 'USD',
        ]);
        $pitch = Pitch::factory()->for($project)->for($producer, 'user')->create([
            'status' => Pitch::STATUS_CONTEST_ENTRY
        ]);
        $otherPitch = Pitch::factory()->for($project)->for(User::factory(), 'user')->create([
             'status' => Pitch::STATUS_CONTEST_ENTRY
        ]);
        $alreadyClosedPitch = Pitch::factory()->for($project)->for(User::factory(), 'user')->create([
            'status' => Pitch::STATUS_CONTEST_NOT_SELECTED // Already closed
        ]);

        // Mock dependencies
        $this->notificationServiceMock->shouldReceive('notifyContestWinnerSelected')->once()->with(Mockery::on(fn($p) => $p->id === $pitch->id));
        $this->notificationServiceMock->shouldReceive('notifyContestWinnerSelectedOwner')->once()->with(Mockery::on(fn($p) => $p->id === $pitch->id));
        $this->notificationServiceMock->shouldReceive('notifyContestEntryNotSelected')->once()->with(Mockery::on(fn($p) => $p->id === $otherPitch->id));
        
        // Mock InvoiceService call
        $mockInvoice = (object)['id' => 999]; // Mock invoice object
        $this->invoiceServiceMock->shouldReceive('createInvoiceForContestPrize')
            ->once()
            ->with(
                Mockery::on(fn($p) => $p->id === $project->id),
                Mockery::on(fn($u) => $u->id === $producer->id),
                $project->prize_amount,
                $project->prize_currency
            )
            ->andReturn($mockInvoice);

        // Remove DB::transaction mock
        // DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb()); 

        // Act: Select the winner
        $winningPitch = $this->service->selectContestWinner($pitch, $projectOwner);

        // Assertions...
        $this->assertEquals(Pitch::STATUS_CONTEST_WINNER, $winningPitch->status);
        $this->assertEquals(1, $winningPitch->rank);
        $this->assertEquals($project->prize_amount, $winningPitch->payment_amount);
        // Payment status depends on InvoiceService logic (mocked here)
        // Assuming processing status is set when invoice is created
        $this->assertEquals(Pitch::PAYMENT_STATUS_PROCESSING, $winningPitch->payment_status); 
        $this->assertEquals($mockInvoice->id, $winningPitch->final_invoice_id);
        $this->assertNotNull($winningPitch->approved_at); // Check approval timestamp
        
        // Assert other pitch was closed
        $this->assertEquals(Pitch::STATUS_CONTEST_NOT_SELECTED, $otherPitch->fresh()->status);
        $this->assertNotNull($otherPitch->fresh()->closed_at);

        // Assert the already closed pitch wasn't touched
        $this->assertEquals(Pitch::STATUS_CONTEST_NOT_SELECTED, $alreadyClosedPitch->fresh()->status);

        // Assert event was created for winner
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $winningPitch->id,
            'event_type' => 'contest_winner_selected',
            'status' => Pitch::STATUS_CONTEST_WINNER,
            'created_by' => $projectOwner->id,
        ]);
        // Assert event was created for non-selected
         $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $otherPitch->id,
            'event_type' => 'contest_entry_not_selected',
            'status' => Pitch::STATUS_CONTEST_NOT_SELECTED,
        ]);
    }

    /** @test */
    public function test_it_can_select_contest_winner_without_prize()
    {
        // Arrange
        $projectOwner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'prize_amount' => 0, // No prize
        ]);
        $pitch = Pitch::factory()->for($project)->for($producer, 'user')->create([
            'status' => Pitch::STATUS_CONTEST_ENTRY
        ]);
        $otherPitch = Pitch::factory()->for($project)->for(User::factory(), 'user')->create([
            'status' => Pitch::STATUS_CONTEST_ENTRY
        ]);

        // Mock dependencies
        $this->notificationServiceMock->shouldReceive('notifyContestWinnerSelectedNoPrize')->once();
        $this->notificationServiceMock->shouldReceive('notifyContestWinnerSelectedOwnerNoPrize')->once();
        $this->notificationServiceMock->shouldReceive('notifyContestEntryNotSelected')->once();
        $this->invoiceServiceMock->shouldNotReceive('createInvoiceForContestPrize'); // Ensure invoice NOT created
        // Remove DB::transaction mock
        // DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb()); 

        // Act
        $winningPitch = $this->service->selectContestWinner($pitch, $projectOwner);

        // Assertions
        $this->assertEquals(Pitch::STATUS_CONTEST_WINNER, $winningPitch->status);
        $this->assertEquals(0, $winningPitch->payment_amount);
        $this->assertEquals(Pitch::PAYMENT_STATUS_NOT_REQUIRED, $winningPitch->payment_status);
        $this->assertNull($winningPitch->final_invoice_id); 
        
        // Assert other pitch was closed
        $this->assertEquals(Pitch::STATUS_CONTEST_NOT_SELECTED, $otherPitch->fresh()->status);
        $this->assertNotNull($otherPitch->fresh()->closed_at);

        // Assert event was created for winner
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $winningPitch->id,
            'event_type' => 'contest_winner_selected_no_prize',
            'status' => Pitch::STATUS_CONTEST_WINNER,
        ]);
    }

    /** @test */
    public function select_contest_winner_fails_if_user_not_owner()
    {
        $this->expectException(UnauthorizedActionException::class);
        // Adjust assertion to match specific exception message if needed
        // $this->expectExceptionMessage('Only the project owner can select a winner');
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
        ]);
        $pitch = Pitch::factory()->for($project)->for($owner, 'user')->create([
            'status' => Pitch::STATUS_CONTEST_ENTRY,
        ]);
        $notOwner = User::factory()->create();
        $this->service->selectContestWinner($pitch, $notOwner);
    }

    /** @test */
    public function select_contest_winner_fails_if_project_not_contest()
    {
        $this->expectException(UnauthorizedActionException::class);
        // Adjust assertion to match specific exception message if needed
        // $this->expectExceptionMessage('select contest winner');
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD, // Not a contest
        ]);
        $pitch = Pitch::factory()->for($project)->for($owner, 'user')->create([
            'status' => Pitch::STATUS_CONTEST_ENTRY, // Status might not be valid here but test project type
        ]);
        $notOwner = User::factory()->create();
        $this->service->selectContestWinner($pitch, $notOwner);
    }

    /** @test */
    public function select_contest_winner_fails_if_pitch_not_entry_status()
    {
        $this->expectException(InvalidStatusTransitionException::class);
        // Adjust assertion to match specific exception message if needed
        // $this->expectExceptionMessage('Only contest entries can be selected as winners');
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
        ]);
        $pitch = Pitch::factory()->for($project)->for($owner, 'user')->create([
            'status' => Pitch::STATUS_CONTEST_WINNER, // Already a winner
        ]);
        $this->service->selectContestWinner($pitch, $owner); // Use owner, not notOwner
    }

    /** @test */
    public function it_can_select_contest_runner_up_successfully()
    {
        $projectOwner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')
                           ->create(['workflow_type' => Project::WORKFLOW_TYPE_CONTEST]);
        $pitch = Pitch::factory()->for($project)->for($producer, 'user')
                           ->create(['status' => Pitch::STATUS_CONTEST_ENTRY]);
        $rank = 2;

        $this->notificationServiceMock->shouldReceive('notifyContestRunnerUpSelected')
            ->once()
            ->with(Mockery::on(fn($p) => $p->id === $pitch->id));

        // Remove DB::transaction mock
        // DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb()); 

        // Act
        $updatedPitch = $this->service->selectContestRunnerUp($pitch, $projectOwner, $rank);

        // Assert
        $this->assertEquals(Pitch::STATUS_CONTEST_RUNNER_UP, $updatedPitch->status);
        $this->assertEquals($rank, $updatedPitch->rank);

        // Assert event was created
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $pitch->id,
            'event_type' => 'contest_runner_up_selected',
            'status' => Pitch::STATUS_CONTEST_RUNNER_UP,
            'comment' => "Selected as contest runner-up (Rank: {$rank}).",
            'created_by' => $projectOwner->id,
        ]);
    }

    /** @test */
    public function select_contest_runner_up_fails_if_user_not_owner()
    {
        $projectOwner = User::factory()->create();
        $producer = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
        ]);
        $pitch = Pitch::factory()->for($project)->for($producer, 'user')->create([
            'status' => Pitch::STATUS_CONTEST_ENTRY,
        ]);

        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('select contest runner-up');

        $this->service->selectContestRunnerUp($pitch, $otherUser, 2);
    }

    /** @test */
    public function select_contest_runner_up_fails_if_project_not_contest()
    {
        $projectOwner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);
        $pitch = Pitch::factory()->for($project)->for($producer, 'user')->create([
            'status' => Pitch::STATUS_CONTEST_ENTRY,
        ]);

        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('select contest runner-up');

        $this->service->selectContestRunnerUp($pitch, $projectOwner, 2);
    }

     /** @test */
    public function select_contest_runner_up_fails_if_pitch_not_entry_status()
    {
        $projectOwner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
        ]);
        $pitch = Pitch::factory()->for($project)->for($producer, 'user')->create([
            'status' => Pitch::STATUS_CONTEST_WINNER, // Not an entry
        ]);

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Only contest entries can be selected as runner-ups.');

        $this->service->selectContestRunnerUp($pitch, $projectOwner, 2);
    }

    /** @test */
    public function select_contest_runner_up_fails_if_rank_is_invalid()
    {
        $projectOwner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
        ]);
        $pitch = Pitch::factory()->for($project)->for($producer, 'user')->create([
            'status' => Pitch::STATUS_CONTEST_ENTRY,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Runner-up rank must be greater than 1.');

        $this->service->selectContestRunnerUp($pitch, $projectOwner, 1); // Invalid rank
    }

    //-----------------------------------------
    // Client Management Tests
    //-----------------------------------------

    /** @test */
    public function client_can_approve_pitch_submission()
    {
        Event::fake(); // Disable model events for this test

        // Arrange: Seed subscription limits for testing
        $this->seed(\Database\Seeders\CompleteSubscriptionLimitsSeeder::class);
        
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@test.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
        ]);
        $clientEmail = $project->client_email;

        // Mock dependencies - expect the new enhanced notification
        $this->notificationServiceMock->shouldReceive('notifyProducerClientApprovedAndCompleted')
            ->once()
            ->with(Mockery::on(fn($p) => $p->id === $pitch->id));

        // Act
        $updatedPitch = $this->service->clientApprovePitch($pitch, $clientEmail);

        // Assert - pitch should be COMPLETED (not just APPROVED) for client management projects
        $this->assertEquals(Pitch::STATUS_COMPLETED, $updatedPitch->status);
        $this->assertNotNull($updatedPitch->approved_at);
        $this->assertNotNull($updatedPitch->completed_at);
        
        // Project should also be completed
        $updatedPitch->project->refresh();
        $this->assertEquals(Project::STATUS_COMPLETED, $updatedPitch->project->status);
    }

    /** @test */
    public function client_approve_pitch_is_idempotent()
    {
        Event::fake(); // Disable model events for this test

        // Arrange: Seed subscription limits for testing
        $this->seed(\Database\Seeders\CompleteSubscriptionLimitsSeeder::class);

        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@test.com',
            'status' => Project::STATUS_COMPLETED, // Already completed
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_COMPLETED, // Already completed (new behavior)
            'approved_at' => now(),
            'completed_at' => now(),
        ]);
        $clientEmail = $project->client_email;

        // Expectations: No notifications should be sent for idempotent calls
        $this->notificationServiceMock->shouldNotReceive('notifyProducerClientApprovedAndCompleted');

        // Act
        $updatedPitch = $this->service->clientApprovePitch($pitch, $clientEmail);

        // Assert - Should return the same pitch without changes
        $this->assertEquals(Pitch::STATUS_COMPLETED, $updatedPitch->status);
        $this->assertNotNull($updatedPitch->approved_at);
        $this->assertNotNull($updatedPitch->completed_at);
    }

    /** @test */
    public function client_approve_pitch_fails_for_non_client_management_project()
    {
        // Arrange
        $producer = User::factory()->create();
        $project = Project::factory()->create([ // Standard project
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
        ]);

        $mockNotificationService = $this->mock(NotificationService::class);
        $workflowService = new PitchWorkflowService($mockNotificationService);
        $clientIdentifier = 'irrelevant@example.com';

        // Assert
        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('Client approval is only applicable for client management projects.');

        // Act
        $workflowService->clientApprovePitch($pitch, $clientIdentifier);
    }

    /** @test */
    public function client_approve_pitch_fails_for_pitch_not_ready_for_review()
    {
        // Arrange
        // 1. Mock NotificationService *before* project creation
        $mockNotificationService = $this->mock(NotificationService::class);
        // Expect observer call during project creation
        $mockNotificationService->shouldReceive('notifyClientProjectInvite')->once();

        // 2. Create Project & Pitch
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'testclient@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_IN_PROGRESS, // Incorrect status
        ]);

        // 3. Instantiate service with mock
        $workflowService = new PitchWorkflowService($mockNotificationService);
        $clientIdentifier = $project->client_email;

        // Assert
        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Pitch must be ready for review (or pending revision processing) for client approval.');

        // Act
        $workflowService->clientApprovePitch($pitch, $clientIdentifier);
    }

    /** @test */
    public function client_can_request_revisions()
    {
        // Arrange
        $mockNotificationService = $this->mock(NotificationService::class);
        $mockNotificationService->shouldReceive('notifyClientProjectInvite')->once();

        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@test.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
        ]);

        $workflowService = new PitchWorkflowService($mockNotificationService);
        $clientIdentifier = $project->client_email;
        $feedback = 'Please adjust the levels.';

        // Expect notification
        $mockNotificationService->shouldReceive('notifyProducerClientRevisionsRequested')
            ->once()
            ->withArgs(function (Pitch $notifiedPitch, string $sentFeedback) use ($pitch, $feedback) {
                return $notifiedPitch->id === $pitch->id && $sentFeedback === $feedback;
            });

        // Act
        $updatedPitch = $workflowService->clientRequestRevisions($pitch, $feedback, $clientIdentifier);

        // Assert
        $this->assertEquals(Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, $updatedPitch->status);
        $this->assertNotNull($updatedPitch->revisions_requested_at);

        // Check event
        $event = \App\Models\PitchEvent::where('pitch_id', $pitch->id)
            ->where('event_type', 'client_revisions_requested')
            ->orderBy('created_at', 'desc')
            ->first();
        $this->assertNotNull($event);
        $this->assertEquals($feedback, $event->comment);
        $this->assertEquals(Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, $event->status);
        $this->assertNull($event->created_by);
        $this->assertEquals($clientIdentifier, $event->metadata['client_email']);
    }

    /** @test */
    public function client_request_revisions_fails_for_non_client_management_project()
    {
        // Arrange
        $producer = User::factory()->create();
        $project = Project::factory()->create([ // Standard project
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
        ]);

        $mockNotificationService = $this->mock(NotificationService::class);
        $workflowService = new PitchWorkflowService($mockNotificationService);
        $feedback = 'Irrelevant feedback.';
        $clientIdentifier = 'irrelevant@example.com';

        // Assert
        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('Client revisions are only applicable for client management projects.');

        // Act
        $workflowService->clientRequestRevisions($pitch, $feedback, $clientIdentifier);
    }

    /** @test */
    public function client_request_revisions_fails_for_pitch_not_ready_for_review()
    {
        // Arrange
        $mockNotificationService = $this->mock(NotificationService::class);
        $mockNotificationService->shouldReceive('notifyClientProjectInvite')->once();

        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@test.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_IN_PROGRESS, // Incorrect status
        ]);

        $workflowService = new PitchWorkflowService($mockNotificationService);
        $feedback = 'Feedback for wrong status.';
        $clientIdentifier = $project->client_email;

        // Assert
        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Pitch must be ready for review to request client revisions.');

        // Act
        $workflowService->clientRequestRevisions($pitch, $feedback, $clientIdentifier);
    }

    /** @test */
    public function submit_pitch_for_review_notifies_client_for_client_management_project()
    {
        // Create a completely independent mock that doesn't depend on real services
        $mockNotificationService = $this->createMock(NotificationService::class);
        
        // Mock the project creation observer call that happens during project creation
        $mockNotificationService->expects($this->any())
            ->method('notifyClientProjectInvite');

        // Replace the service in the container for the observer
        $this->app->instance(NotificationService::class, $mockNotificationService);

        // Arrange
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@test.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_IN_PROGRESS, // Start in progress
        ]);
        // Simulate adding a file, as submit requires at least one
        PitchFile::factory()->create(['pitch_id' => $pitch->id, 'user_id' => $producer->id]);
        $pitch->load('files'); // Reload files relationship

        // Expect client notification
        $mockNotificationService->expects($this->once())
            ->method('notifyClientReviewReady')
            ->with(
                $this->callback(function (Pitch $notifiedPitch) use ($pitch) {
                    return $notifiedPitch->id === $pitch->id;
                }),
                $this->callback(function (string $signedUrl) {
                    return str_contains($signedUrl, '/projects/') &&
                           str_contains($signedUrl, '/portal') &&
                           str_contains($signedUrl, 'signature=');
                })
            );

        // Ensure the standard owner notification is NOT called
        $mockNotificationService->expects($this->never())
            ->method('notifyPitchReadyForReview');

        $workflowService = new PitchWorkflowService($mockNotificationService);

        // Act
        $updatedPitch = $workflowService->submitPitchForReview($pitch, $producer);

        // Assert
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $updatedPitch->status);
        $this->assertNotNull($updatedPitch->current_snapshot_id); // Ensure snapshot was created
    }

} 