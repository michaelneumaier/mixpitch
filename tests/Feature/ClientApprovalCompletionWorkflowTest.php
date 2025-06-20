<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\PayoutSchedule;
use App\Services\PitchWorkflowService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;

class ClientApprovalCompletionWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $producer;
    protected $project;
    protected $pitch;
    protected $workflowService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create producer
        $this->producer = User::factory()->create([
            'subscription_plan' => 'pro',
            'subscription_tier' => 'artist'
        ]);
        
        // Create client management project
        $this->project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_name' => 'Jane Smith',
            'client_email' => 'jane@example.com',
            'title' => 'Music Production Project'
        ]);
        
        // Create pitch ready for client approval
        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
            'payment_amount' => 500.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
        ]);
        
        $this->workflowService = app(PitchWorkflowService::class);
    }

    /** @test */
    public function client_approval_automatically_completes_free_project()
    {
        // Arrange: Set up free project
        $this->pitch->update([
            'payment_amount' => 0.00,
            'payment_status' => Pitch::PAYMENT_STATUS_NOT_REQUIRED
        ]);

        // Act: Client approves the project
        $completedPitch = $this->workflowService->clientApprovePitch($this->pitch, $this->project->client_email);

        // Assert: Pitch is completed
        $this->assertEquals(Pitch::STATUS_COMPLETED, $completedPitch->status);
        $this->assertNotNull($completedPitch->approved_at);
        $this->assertNotNull($completedPitch->completed_at);

        // Assert: Project is completed
        $this->project->refresh();
        $this->assertEquals(Project::STATUS_COMPLETED, $this->project->status);

        // Assert: Events were created
        $approvalEvent = $this->pitch->events()
            ->where('event_type', 'client_approved')
            ->first();
        $this->assertNotNull($approvalEvent);
        $this->assertEquals('Client approved the submission.', $approvalEvent->comment);

        $completionEvent = $this->pitch->events()
            ->where('event_type', 'client_completed')
            ->first();
        $this->assertNotNull($completionEvent);
        $this->assertEquals('Project automatically completed after client approval.', $completionEvent->comment);
    }

    /** @test */
    public function client_approval_with_payment_completes_project_and_schedules_payout()
    {
        // Arrange: Set up paid project
        $this->pitch->update([
            'payment_amount' => 500.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID, // Simulate payment completed
            'payment_completed_at' => now()
        ]);

        // Act: Client approves the project
        $completedPitch = $this->workflowService->clientApprovePitch($this->pitch, $this->project->client_email);

        // Assert: Pitch is completed
        $this->assertEquals(Pitch::STATUS_COMPLETED, $completedPitch->status);
        $this->assertNotNull($completedPitch->approved_at);
        $this->assertNotNull($completedPitch->completed_at);

        // Assert: Project is completed
        $this->project->refresh();
        $this->assertEquals(Project::STATUS_COMPLETED, $this->project->status);

        // Assert: Payout was scheduled
        $payoutSchedule = PayoutSchedule::where('pitch_id', $this->pitch->id)->first();
        $this->assertNotNull($payoutSchedule);
        $this->assertEquals($this->producer->id, $payoutSchedule->producer_user_id);
        $this->assertEquals(500.00, $payoutSchedule->gross_amount);
        $this->assertEquals(PayoutSchedule::STATUS_SCHEDULED, $payoutSchedule->status);
        
        // Verify commission calculation
        $expectedCommissionRate = $this->producer->getPlatformCommissionRate();
        $expectedCommissionAmount = 500.00 * ($expectedCommissionRate / 100);
        $expectedNetAmount = 500.00 - $expectedCommissionAmount;
        
        $this->assertEquals($expectedCommissionRate, $payoutSchedule->commission_rate);
        $this->assertEquals($expectedCommissionAmount, $payoutSchedule->commission_amount);
        $this->assertEquals($expectedNetAmount, $payoutSchedule->net_amount);
        
        // Assert: Hold release date is set (7 days from now)
        $this->assertNotNull($payoutSchedule->hold_release_date);
        $this->assertEquals(
            now()->addDays(7)->format('Y-m-d'),
            $payoutSchedule->hold_release_date->format('Y-m-d')
        );
    }

    /** @test */
    public function client_approval_is_idempotent()
    {
        // Arrange: Project already completed
        $this->pitch->update([
            'status' => Pitch::STATUS_COMPLETED,
            'approved_at' => now()->subHour(),
            'completed_at' => now()->subHour()
        ]);

        $originalApprovedAt = $this->pitch->approved_at;
        $originalCompletedAt = $this->pitch->completed_at;

        // Act: Try to approve again
        $result = $this->workflowService->clientApprovePitch($this->pitch, $this->project->client_email);

        // Assert: No changes made
        $this->assertEquals(Pitch::STATUS_COMPLETED, $result->status);
        $this->assertEquals($originalApprovedAt->timestamp, $result->approved_at->timestamp);
        $this->assertEquals($originalCompletedAt->timestamp, $result->completed_at->timestamp);
    }

    /** @test */
    public function client_approval_fails_for_wrong_status()
    {
        // Arrange: Set pitch to wrong status
        $this->pitch->update(['status' => Pitch::STATUS_IN_PROGRESS]);

        // Act & Assert: Should throw exception
        $this->expectException(\App\Exceptions\Pitch\InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Pitch must be ready for review (or pending revision processing) for client approval.');

        $this->workflowService->clientApprovePitch($this->pitch, $this->project->client_email);
    }



    /** @test */
    public function payout_scheduling_handles_errors_gracefully()
    {
        // Arrange: Set up paid project but simulate error in payout creation
        $this->pitch->update([
            'payment_amount' => 500.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);

        // Mock PayoutSchedule to throw exception
        $this->mock(PayoutSchedule::class, function ($mock) {
            $mock->shouldReceive('create')->andThrow(new \Exception('Database error'));
        });

        // Act: Client approval should still work even if payout fails
        $completedPitch = $this->workflowService->clientApprovePitch($this->pitch, $this->project->client_email);

        // Assert: Pitch and project are still completed despite payout error
        $this->assertEquals(Pitch::STATUS_COMPLETED, $completedPitch->status);
        $this->project->refresh();
        $this->assertEquals(Project::STATUS_COMPLETED, $this->project->status);
    }

    /** @test */
    public function client_approval_creates_correct_notification()
    {
        // Arrange: Create a new workflow service with mocked notification service
        $mockNotificationService = $this->mock(NotificationService::class);
        $mockNotificationService->shouldReceive('notifyProducerClientApprovedAndCompleted')
            ->once()
            ->with(\Mockery::on(function ($pitch) {
                return $pitch->id === $this->pitch->id && 
                       $pitch->status === Pitch::STATUS_COMPLETED;
            }))
            ->andReturn(null);
            
        $workflowService = new PitchWorkflowService($mockNotificationService);

        // Act: Client approves
        $workflowService->clientApprovePitch($this->pitch, $this->project->client_email);

        // Assertion is in the mock expectation above
    }

    /** @test */
    public function client_approval_from_revisions_requested_status()
    {
        // Arrange: Set pitch to revisions requested status
        $this->pitch->update(['status' => Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]);

        // Act: Client approves
        $completedPitch = $this->workflowService->clientApprovePitch($this->pitch, $this->project->client_email);

        // Assert: Should work and complete the project
        $this->assertEquals(Pitch::STATUS_COMPLETED, $completedPitch->status);
        $this->project->refresh();
        $this->assertEquals(Project::STATUS_COMPLETED, $this->project->status);
    }

    /** @test */
    public function payout_metadata_contains_correct_information()
    {
        // Arrange: Set up paid project
        $this->pitch->update([
            'payment_amount' => 750.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);

        // Act: Client approves
        $this->workflowService->clientApprovePitch($this->pitch, $this->project->client_email);

        // Assert: Payout metadata is correct
        $payoutSchedule = PayoutSchedule::where('pitch_id', $this->pitch->id)->first();
        $this->assertNotNull($payoutSchedule);
        
        $metadata = $payoutSchedule->metadata;
        $this->assertEquals('client_management_completion', $metadata['type']);
        $this->assertEquals($this->project->client_email, $metadata['client_email']);
        $this->assertEquals($this->project->title, $metadata['project_title']);
    }

    /** @test */
    public function no_payout_scheduled_for_unpaid_projects()
    {
        // Arrange: Project with payment but not yet paid
        $this->pitch->update([
            'payment_amount' => 300.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING // Not paid yet
        ]);

        // Act: Client approves
        $this->workflowService->clientApprovePitch($this->pitch, $this->project->client_email);

        // Assert: No payout scheduled since payment not completed
        $payoutSchedule = PayoutSchedule::where('pitch_id', $this->pitch->id)->first();
        $this->assertNull($payoutSchedule);

        // But project should still be completed
        $this->assertEquals(Pitch::STATUS_COMPLETED, $this->pitch->fresh()->status);
        $this->assertEquals(Project::STATUS_COMPLETED, $this->project->fresh()->status);
    }

    /** @test */
    public function client_approval_fails_for_non_client_management_project()
    {
        // Arrange: Change to standard project and ensure it's saved
        $this->project->workflow_type = Project::WORKFLOW_TYPE_STANDARD;
        $this->project->save();
        $this->project->refresh();
        
        // Refresh the pitch to clear any cached project relationship
        $this->pitch->refresh();
        $this->pitch->load('project');
        
        // Verify the change took effect
        $this->assertEquals(Project::WORKFLOW_TYPE_STANDARD, $this->project->workflow_type);
        $this->assertFalse($this->project->isClientManagement());
        $this->assertEquals(Project::WORKFLOW_TYPE_STANDARD, $this->pitch->project->workflow_type);
        $this->assertFalse($this->pitch->project->isClientManagement());

        // Act & Assert: Should throw exception
        $this->expectException(\App\Exceptions\Pitch\UnauthorizedActionException::class);
        $this->expectExceptionMessage('Client approval is only applicable for client management projects.');

        $this->workflowService->clientApprovePitch($this->pitch, $this->project->client_email);
    }
} 