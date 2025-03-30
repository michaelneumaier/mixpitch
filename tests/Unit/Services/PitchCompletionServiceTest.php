<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PitchCompletionService;
use App\Services\Project\ProjectManagementService;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\Pitch\CompletionValidationException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use Mockery;

class PitchCompletionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $projectManagementServiceMock;
    protected $notificationServiceMock;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectManagementServiceMock = Mockery::mock(\App\Services\Project\ProjectManagementService::class);
        $this->notificationServiceMock = Mockery::mock(NotificationService::class);

        $this->app->instance(\App\Services\Project\ProjectManagementService::class, $this->projectManagementServiceMock);
        $this->app->instance(NotificationService::class, $this->notificationServiceMock);

        $this->service = new PitchCompletionService(
            $this->projectManagementServiceMock,
            $this->notificationServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_complete_a_pitch_successfully_for_free_project()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create(['budget' => 0]);
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_ACCEPTED]);
        $pitchToComplete = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create([
                'status' => Pitch::STATUS_APPROVED,
                'current_snapshot_id' => $snapshot->id
            ]);
        $snapshot->pitch_id = $pitchToComplete->id; // Ensure association
        $snapshot->save();

        // Mock dependencies
        $this->projectManagementServiceMock->shouldReceive('completeProject')
            ->once()->with(Mockery::on(fn($p) => $p instanceof Project && $p->id === $project->id))
            ->andReturn($project); // Return the project
        $this->notificationServiceMock->shouldReceive('notifyPitchCompleted')->once();
        $this->notificationServiceMock->shouldReceive('notifyPitchClosed')->never(); // No other pitches to close

        // Mock DB transaction
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $feedback = 'Great work!';
        $completedPitch = $this->service->completePitch($pitchToComplete, $projectOwner, $feedback);

        $this->assertEquals(Pitch::STATUS_COMPLETED, $completedPitch->status);
        $this->assertEquals(Pitch::PAYMENT_STATUS_NOT_REQUIRED, $completedPitch->payment_status);
        $this->assertEquals($feedback, $completedPitch->completion_feedback);
        $this->assertNotNull($completedPitch->completed_at);
        
        // Verify the snapshot status
        $snapshot->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_COMPLETED, $snapshot->status);
        
        // Verify the event was created
        $event = $completedPitch->events()
            ->where('event_type', 'status_change')
            ->where('comment', 'Pitch marked as completed by project owner. Feedback: Great work!')
            ->first();
        $this->assertNotNull($event);
    }

    /** @test */
    public function it_sets_payment_status_to_pending_for_paid_project()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create(['budget' => 500]); // Paid project
        $snapshot = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_ACCEPTED]);
        $pitchToComplete = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create([
                'status' => Pitch::STATUS_APPROVED,
                'current_snapshot_id' => $snapshot->id
            ]);
         $snapshot->pitch_id = $pitchToComplete->id; $snapshot->save();

        // Mock dependencies
        $this->projectManagementServiceMock->shouldReceive('completeProject')->once()->andReturn($project);
        $this->notificationServiceMock->shouldReceive('notifyPitchCompleted')->once();
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $completedPitch = $this->service->completePitch($pitchToComplete, $projectOwner);

        $this->assertEquals(Pitch::STATUS_COMPLETED, $completedPitch->status);
        $this->assertEquals(Pitch::PAYMENT_STATUS_PENDING, $completedPitch->payment_status);
    }

    /** @test */
    public function it_closes_other_active_pitches_on_completion()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator1 = User::factory()->create();
        $pitchCreator2 = User::factory()->create(); // Second pitcher
        $project = Project::factory()->for($projectOwner, 'user')->create(['budget' => 100]);
        $snapshot1 = PitchSnapshot::factory()->create(['status' => PitchSnapshot::STATUS_ACCEPTED]);
        $pitchToComplete = Pitch::factory()
            ->for($project)->for($pitchCreator1, 'user')
            ->create([
                'status' => Pitch::STATUS_APPROVED,
                'current_snapshot_id' => $snapshot1->id
            ]);
         $snapshot1->pitch_id = $pitchToComplete->id; $snapshot1->save();

        // Create another active pitch
        $otherPitch = Pitch::factory()
            ->for($project)->for($pitchCreator2, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);

        // Mock dependencies
        $this->projectManagementServiceMock->shouldReceive('completeProject')->once()->andReturn($project);
        $this->notificationServiceMock->shouldReceive('notifyPitchCompleted')->once();
        $this->notificationServiceMock->shouldReceive('notifyPitchClosed')
            ->once()
            ->with(Mockery::on(fn($p) => $p instanceof Pitch && $p->id === $otherPitch->id));
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($cb) => $cb());

        $this->service->completePitch($pitchToComplete, $projectOwner);

        // Assert the other pitch is now closed
        $this->assertEquals(Pitch::STATUS_CLOSED, $otherPitch->refresh()->status);
    }

    /** @test */
    public function complete_pitch_fails_if_user_is_not_project_owner()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $wrongUser = User::factory()->create(); // Not the owner
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitchToComplete = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_APPROVED]);

        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('complete this pitch');

        $this->service->completePitch($pitchToComplete, $wrongUser);
    }

    /** @test */
    public function complete_pitch_fails_if_pitch_is_not_approved()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitchToComplete = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]); // Wrong status

        $this->expectException(CompletionValidationException::class);
        $this->expectExceptionMessage('Pitch must be approved before it can be completed.');

        $this->service->completePitch($pitchToComplete, $projectOwner);
    }

    /** @test */
    public function complete_pitch_fails_if_pitch_is_already_paid()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitchToComplete = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create([
                'status' => Pitch::STATUS_APPROVED, // Status is okay
                'payment_status' => Pitch::PAYMENT_STATUS_PAID // But already paid
            ]);

        $this->expectException(CompletionValidationException::class);
        $this->expectExceptionMessage('This pitch has already been completed and paid/is processing payment.');

        $this->service->completePitch($pitchToComplete, $projectOwner);
    }

     /** @test */
    public function complete_pitch_fails_on_db_error()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitchToComplete = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_APPROVED]);

        // Mock DB transaction to throw error
        DB::shouldReceive('transaction')->once()->andThrow(new \Exception('DB Error'));

        // Don't expect notifications or project completion if DB fails
        $this->projectManagementServiceMock->shouldNotReceive('completeProject');
        $this->notificationServiceMock->shouldNotReceive('notifyPitchCompleted');
        $this->notificationServiceMock->shouldNotReceive('notifyPitchClosed');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('An unexpected error occurred while completing the pitch.');

        $this->service->completePitch($pitchToComplete, $projectOwner);
    }
} 