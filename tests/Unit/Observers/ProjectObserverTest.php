<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Services\NotificationService;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function observer_does_not_create_pitch_if_not_client_management_or_direct_hire()
    {
        // Arrange
        $owner = User::factory()->create();
        $projectData = Project::factory()->make([
            'user_id' => $owner->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD, // Not client mgmt or direct hire
        ])->toArray();

        $mockNotificationService = $this->mock(NotificationService::class);
        // Ensure no notifications are sent
        $mockNotificationService->shouldNotReceive('notifyClientProjectInvite');
        $mockNotificationService->shouldNotReceive('notifyDirectHireAssignment');

        // Act: Create the project
        $project = Project::create($projectData);

        // Assert: No pitch should be created
        $this->assertDatabaseMissing('pitches', [
            'project_id' => $project->id,
        ]);
    }

    /** @test */
    public function it_creates_pitch_with_payment_details_for_client_management_project()
    {
        // Arrange
        $producer = User::factory()->create();
        $paymentAmount = 150.50; // Example payment amount

        $projectData = Project::factory()->make([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'newclient@example.com',
            'client_name' => 'New Client',
            'title' => 'Client Project Title',
            'description' => 'Client project description.',
            'payment_amount' => $paymentAmount, 
        ])->toArray();

        // Mock NotificationService
        $mockNotificationService = $this->mock(NotificationService::class);
        $mockNotificationService->shouldReceive('notifyClientProjectInvite')->once();

        // Act: Create the project (Observer runs here)
        $project = Project::create($projectData);
        $createdPitch = $project->pitches->first();

        // Assert: Check Pitch creation
        $this->assertNotNull($createdPitch, "Pitch was not created.");
        $this->assertDatabaseHas('pitches', [
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'title' => $projectData['title'],
            'description' => $projectData['description'],
            'status' => Pitch::STATUS_IN_PROGRESS,
            'terms_agreed' => 1,
            'payment_amount' => $paymentAmount,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
        ]);

        // Assert: Check PitchEvent creation
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $createdPitch->id,
            'event_type' => 'client_project_created',
        ]);
    }

    /** @test */
    public function it_creates_client_management_pitch_with_payment_not_required_if_amount_is_zero()
    {
        // Arrange
        $producer = User::factory()->create();
        $projectData = Project::factory()->make([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'clientzero@example.com',
            'payment_amount' => 0, // Payment amount is zero
        ])->toArray();

        $mockNotificationService = $this->mock(NotificationService::class);
        $mockNotificationService->shouldReceive('notifyClientProjectInvite')->once();

        // Act
        $project = Project::create($projectData);
        $createdPitch = $project->pitches->first();

        // Assert
        $this->assertNotNull($createdPitch);
        $this->assertDatabaseHas('pitches', [
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'payment_amount' => 0,
            'payment_status' => Pitch::PAYMENT_STATUS_NOT_REQUIRED,
        ]);
    }
} 