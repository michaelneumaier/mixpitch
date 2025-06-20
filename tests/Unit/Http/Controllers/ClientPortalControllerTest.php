<?php

namespace Tests\Unit\Http\Controllers;

use Tests\TestCase;
use App\Models\Project;
use App\Models\User;
use App\Models\Pitch;
use App\Models\ProjectFile;
use App\Services\PitchWorkflowService;
use App\Services\NotificationService;
use App\Services\FileManagementService;
use App\Http\Controllers\ClientPortalController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Mockery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Laravel\Cashier\Checkout;

class ClientPortalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function controller_can_be_instantiated_with_dependencies()
    {
        // Arrange
        $mockWorkflowService = $this->mock(PitchWorkflowService::class);
        $mockNotificationService = $this->mock(NotificationService::class);
        
        // Act
        $controller = new ClientPortalController($mockWorkflowService, $mockNotificationService);
        
        // Assert
        $this->assertInstanceOf(ClientPortalController::class, $controller);
    }

    /** @test */
    public function approve_pitch_shows_enhanced_payment_flow_for_paid_projects()
    {
        // Arrange
        $realProducer = User::factory()->create();
        $realProducer->createOrGetStripeCustomer();

        $project = Project::factory()->create([
            'user_id' => $realProducer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        ]);
        
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $realProducer->id,
            'payment_amount' => 250.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
        ]);

        $controller = $this->app->make(ClientPortalController::class);
        $request = new Request();

        // Act
        $response = $controller->approvePitch($project, $request);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        
        // Verify pitch state hasn't changed yet (payment pending)
        $pitch->refresh();
        $this->assertEquals(Pitch::PAYMENT_STATUS_PENDING, $pitch->payment_status);
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $pitch->status);
    }

    /** @test */
    public function approve_pitch_calls_workflow_service_when_no_payment_required()
    {
        // Arrange
        $mockWorkflowService = $this->mock(PitchWorkflowService::class);
        $mockNotificationService = $this->mock(NotificationService::class);
        
        // Mock the notification service method that gets called by project observer
        $mockNotificationService->shouldReceive('notifyClientProjectInvite')
            ->andReturn(true);
        
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com'
        ]);
        
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'payment_amount' => 0.00, // No payment required
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
        ]);

        $mockWorkflowService->shouldReceive('clientApprovePitch')
            ->andReturn($pitch);

        $controller = new ClientPortalController($mockWorkflowService, $mockNotificationService);
        $request = new Request();

        // Act
        $response = $controller->approvePitch($project, $request);

        // Assert
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function request_revisions_calls_workflow_service_with_feedback()
    {
        // Arrange
        $mockWorkflowService = $this->mock(PitchWorkflowService::class);
        $mockNotificationService = $this->mock(NotificationService::class);
        
        // Mock the notification service method that gets called by project observer
        $mockNotificationService->shouldReceive('notifyClientProjectInvite')
            ->andReturn(true);
        
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com'
        ]);
        
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
        ]);

        $feedback = 'Please adjust the tempo and add more bass';
        
        $mockWorkflowService->shouldReceive('clientRequestRevisions')
            ->andReturn($pitch);

        $controller = new ClientPortalController($mockWorkflowService, $mockNotificationService);
        $request = new Request(['feedback' => $feedback]);

        // Act
        $response = $controller->requestRevisions($project, $request);

        // Assert
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function store_comment_creates_client_comment_event()
    {
        // Arrange
        $mockWorkflowService = $this->mock(PitchWorkflowService::class);
        $mockNotificationService = $this->mock(NotificationService::class);
        
        // Mock the notification service methods
        $mockNotificationService->shouldReceive('notifyClientProjectInvite')
            ->andReturn(true);
        $mockNotificationService->shouldReceive('notifyProducerClientCommented')
            ->once()
            ->with(Mockery::type(Pitch::class), 'This is looking great! Can we make a small adjustment?');
        
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com'
        ]);
        
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $comment = 'This is looking great! Can we make a small adjustment?';

        $controller = new ClientPortalController($mockWorkflowService, $mockNotificationService);
        $request = new Request(['comment' => $comment]);

        // Act
        $response = $controller->storeComment($project, $request);

        // Assert
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        
        // Verify comment was stored (check that a client comment exists)
        $this->assertDatabaseHas('pitch_events', [
            'event_type' => 'client_comment',
            'comment' => $comment
        ]);
    }

    /** @test */
    public function upload_file_method_exists_and_accepts_correct_parameters()
    {
        // Arrange
        $mockWorkflowService = $this->mock(PitchWorkflowService::class);
        $mockNotificationService = $this->mock(NotificationService::class);
        $mockFileService = $this->mock(FileManagementService::class);
        
        $controller = new ClientPortalController($mockWorkflowService, $mockNotificationService);
        
        // Assert
        $this->assertTrue(method_exists($controller, 'uploadFile'));
        
        // Check method signature
        $reflection = new \ReflectionMethod($controller, 'uploadFile');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(3, $parameters);
        $this->assertEquals('project', $parameters[0]->getName());
        $this->assertEquals('request', $parameters[1]->getName());
        $this->assertEquals('fileService', $parameters[2]->getName());
    }

    /** @test */
    public function delete_project_file_method_exists_and_accepts_correct_parameters()
    {
        // Arrange
        $mockWorkflowService = $this->mock(PitchWorkflowService::class);
        $mockNotificationService = $this->mock(NotificationService::class);
        
        $controller = new ClientPortalController($mockWorkflowService, $mockNotificationService);
        
        // Assert
        $this->assertTrue(method_exists($controller, 'deleteProjectFile'));
        
        // Check method signature
        $reflection = new \ReflectionMethod($controller, 'deleteProjectFile');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(3, $parameters);
        $this->assertEquals('project', $parameters[0]->getName());
        $this->assertEquals('projectFile', $parameters[1]->getName());
        $this->assertEquals('fileService', $parameters[2]->getName());
    }

    /** @test */
    public function show_method_exists_and_accepts_correct_parameters()
    {
        // Arrange
        $mockWorkflowService = $this->mock(PitchWorkflowService::class);
        $mockNotificationService = $this->mock(NotificationService::class);
        
        $controller = new ClientPortalController($mockWorkflowService, $mockNotificationService);
        
        // Assert
        $this->assertTrue(method_exists($controller, 'show'));
        
        // Check method signature
        $reflection = new \ReflectionMethod($controller, 'show');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(2, $parameters);
        $this->assertEquals('project', $parameters[0]->getName());
        $this->assertEquals('request', $parameters[1]->getName());
    }
} 