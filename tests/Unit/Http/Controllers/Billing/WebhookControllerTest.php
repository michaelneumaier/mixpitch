<?php

namespace Tests\Unit\Http\Controllers\Billing;

use Tests\TestCase;
use App\Models\Project;
use App\Models\User;
use App\Models\Pitch;
use App\Services\PitchWorkflowService;
use App\Services\InvoiceService;
use App\Http\Controllers\Billing\WebhookController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Symfony\Component\HttpFoundation\Response;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function create_checkout_session_payload(int $pitchId, string $sessionId, string $paymentStatus = 'paid', int $amountTotal = 10000, string $currency = 'usd'): array
    {
        return [
            'id' => 'evt_test_' . uniqid(),
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'object' => 'checkout.session',
                    'payment_status' => $paymentStatus,
                    'amount_total' => $amountTotal,
                    'currency' => $currency,
                    'metadata' => [
                        'pitch_id' => (string) $pitchId,
                        'type' => 'client_pitch_payment',
                    ],
                    // Add other necessary fields if your controller uses them
                ]
            ]
        ];
    }

    /** @test */
    public function handle_checkout_session_completed_processes_valid_payment()
    {
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
            'payment_amount' => 100.00,
            'status' => Pitch::STATUS_READY_FOR_REVIEW, // Status before approval
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
        ]);

        $mockWorkflowService = $this->mock(PitchWorkflowService::class);
        $mockInvoiceService = $this->mock(InvoiceService::class);
        $controller = $this->app->make(WebhookController::class);

        $sessionId = 'cs_test_' . uniqid();
        $payload = $this->create_checkout_session_payload($pitch->id, $sessionId, 'paid', 10000);
        $amountDollars = 100.00;
        $currency = 'USD';

        // Expectations
        $mockWorkflowService->shouldReceive('clientApprovePitch')
            ->once()
            ->withArgs(function (Pitch $p, string $email) use ($pitch, $project) {
                return $p->id === $pitch->id && $email === $project->client_email;
            });

        $mockInvoiceService->shouldReceive('createOrUpdateInvoiceForPaidPitch')
            ->once()
            ->withArgs(function (Pitch $p, string $sId, float $amount, string $curr) use ($pitch, $sessionId, $amountDollars, $currency) {
                return $p->id === $pitch->id && $sId === $sessionId && $amount === $amountDollars && $curr === $currency;
            });

        // Act
        $response = $controller->handleCheckoutSessionCompleted($payload, $mockWorkflowService, $mockInvoiceService);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        // Verify pitch status updated in DB (best effort in unit test)
        $pitch->refresh();
        $this->assertEquals(Pitch::PAYMENT_STATUS_PAID, $pitch->payment_status);
        $this->assertNotNull($pitch->payment_completed_at);
        // Note: clientApprovePitch is mocked, so pitch status won't be APPROVED here unless we mock that return
    }

    /** @test */
    public function handle_checkout_session_completed_ignores_already_processed_pitch()
    {
        // Arrange
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        ]);
        // Create pitch already marked as PAID
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'payment_amount' => 100.00,
            'status' => Pitch::STATUS_APPROVED, 
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
        ]);

        $mockWorkflowService = $this->mock(PitchWorkflowService::class);
        $mockInvoiceService = $this->mock(InvoiceService::class);
        $controller = $this->app->make(WebhookController::class);

        $sessionId = 'cs_test_' . uniqid();
        $payload = $this->create_checkout_session_payload($pitch->id, $sessionId, 'paid');

        // Expectations: Services should NOT be called
        $mockWorkflowService->shouldNotReceive('clientApprovePitch');
        $mockInvoiceService->shouldNotReceive('createOrUpdateInvoiceForPaidPitch');

        // Act
        $response = $controller->handleCheckoutSessionCompleted($payload, $mockWorkflowService, $mockInvoiceService);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function handle_checkout_session_completed_ignores_other_payment_types()
    {
        // Arrange
        $producer = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $producer->id]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
        ]);

        $mockWorkflowService = $this->mock(PitchWorkflowService::class);
        $mockInvoiceService = $this->mock(InvoiceService::class);
        $controller = $this->app->make(WebhookController::class);

        // Create payload with different metadata type
        $sessionId = 'cs_test_' . uniqid();
        $payload = $this->create_checkout_session_payload($pitch->id, $sessionId, 'paid');
        $payload['data']['object']['metadata']['type'] = 'subscription_payment'; // Different type

        // Expectations: Services should NOT be called
        $mockWorkflowService->shouldNotReceive('clientApprovePitch');
        $mockInvoiceService->shouldNotReceive('createOrUpdateInvoiceForPaidPitch');

        // Act
        $response = $controller->handleCheckoutSessionCompleted($payload, $mockWorkflowService, $mockInvoiceService);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function handle_checkout_session_completed_ignores_non_paid_sessions()
    {
        // Arrange
        $pitch = Pitch::factory()->create([
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
        ]);

        $mockWorkflowService = $this->mock(PitchWorkflowService::class);
        $mockInvoiceService = $this->mock(InvoiceService::class);
        $controller = $this->app->make(WebhookController::class);

        // Create payload with 'unpaid' status
        $sessionId = 'cs_test_' . uniqid();
        $payload = $this->create_checkout_session_payload($pitch->id, $sessionId, 'unpaid');

        // Expectations: Services should NOT be called
        $mockWorkflowService->shouldNotReceive('clientApprovePitch');
        $mockInvoiceService->shouldNotReceive('createOrUpdateInvoiceForPaidPitch');

        // Act
        $response = $controller->handleCheckoutSessionCompleted($payload, $mockWorkflowService, $mockInvoiceService);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
} 