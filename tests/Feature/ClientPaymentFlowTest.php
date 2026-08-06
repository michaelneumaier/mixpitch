<?php

namespace Tests\Feature;

use App\Http\Controllers\Billing\WebhookController;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use App\Services\PitchWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Cashier\Checkout;
use Mockery;
use Tests\TestCase;

class ClientPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function client_approval_initiates_checkout_when_payment_required()
    {
        // Arrange: Mock NotificationService
        $notificationMock = $this->mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();

        // Create producer with Stripe customer
        $producer = User::factory()->create([
            'stripe_id' => 'cus_test_'.uniqid(), // Fake Stripe customer ID to avoid API calls
        ]);

        // Create project with client management workflow
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
        ]);

        // Get automatically created pitch
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch, 'Pitch was not automatically created for client management project.');

        // Update pitch with required payment
        $pitch->update([
            'payment_amount' => 100.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
        ]);

        // Mock the Checkout class to avoid actual API calls
        $checkoutMock = Mockery::mock('overload:'.Checkout::class);
        $checkoutMock->shouldReceive('create')
            ->once()
            ->andReturn((object) [
                'url' => 'https://checkout.stripe.com/test-session',
            ]);

        // Generate the signed URL for approval
        $signedViewUrl = URL::temporarySignedRoute('client.portal.view', now()->addMinutes(15), ['project' => $project->id]);
        $approveUrl = URL::temporarySignedRoute(
            'client.portal.approve',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Act: Client attempts to approve pitch
        $response = $this->from($signedViewUrl)->post($approveUrl);

        // Assert: We're redirected to checkout
        $response->assertStatus(302);
        // Can't easily test the exact URL in a feature test since we mocked the checkout creation
    }

    /** @test */
    public function client_approval_completes_immediately_when_no_payment_required()
    {
        // Arrange: Mock NotificationService
        $notificationMock = $this->mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();
        $notificationMock->shouldReceive('notifyProducerClientApprovedAndCompleted')->once();

        // Create producer
        $producer = User::factory()->create();

        // Create project with client management workflow
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
        ]);

        // Get automatically created pitch
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch, 'Pitch was not automatically created for client management project.');

        // Update pitch with no required payment
        $pitch->update([
            'payment_amount' => 0.00,
            'payment_status' => Pitch::PAYMENT_STATUS_NOT_REQUIRED,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
        ]);

        // Generate the signed URL for approval
        $signedViewUrl = URL::temporarySignedRoute('client.portal.view', now()->addMinutes(15), ['project' => $project->id]);
        $approveUrl = URL::temporarySignedRoute(
            'client.portal.approve',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Act: Client approves pitch
        $response = $this->from($signedViewUrl)->post($approveUrl);

        // Assert: Success and redirected back to portal
        $response->assertStatus(302); // Redirected back
        $response->assertSessionHas('success', 'Pitch approved successfully.');

        // Check pitch was completed (client management completes immediately when no payment)
        $pitch->refresh();
        $this->assertEquals(Pitch::STATUS_COMPLETED, $pitch->status);
    }

    /** @test */
    public function stripe_webhook_processes_checkout_completion_for_client_payment()
    {
        // Arrange: Setup Dependencies
        $notificationMock = $this->mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();
        $notificationMock->shouldReceive('notifyProducerClientApprovedAndCompleted')->zeroOrMoreTimes()->andReturnNull();
        $notificationMock->shouldReceive('notify')->zeroOrMoreTimes()->andReturnNull();

        // Create producer (no real Stripe customer needed for webhook test)
        $producer = User::factory()->create([
            'stripe_id' => 'cus_test_'.uniqid(),
        ]);

        // Create project with client management workflow
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
        ]);

        // Get automatically created pitch
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch, 'Pitch was not automatically created for client management project.');

        // Update pitch with payment pending and ready for review
        $pitch->update([
            'payment_amount' => 100.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
        ]);

        // Create test Stripe payload
        $sessionId = 'cs_test_'.uniqid();
        $payload = [
            'id' => 'evt_test_'.uniqid(),
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'payment_status' => 'paid',
                    'amount_total' => 10000, // $100.00 in cents
                    'currency' => 'usd',
                    'payment_intent' => 'pi_test_'.uniqid(),
                    'metadata' => [
                        'pitch_id' => (string) $pitch->id,
                        'type' => 'client_pitch_payment',
                    ],
                ],
            ],
        ];

        // Mock InvoiceService in the container
        $invoiceServiceMock = $this->mock(InvoiceService::class, function ($mock) {
            $mock->shouldReceive('createOrUpdateInvoiceForPaidPitch')->zeroOrMoreTimes();
        });

        // Mock PitchWorkflowService - the controller resolves this from the container
        // and calls markPitchAsPaid then clientApprovePitch inside a DB transaction
        $this->mock(PitchWorkflowService::class, function ($mock) {
            $mock->shouldReceive('markPitchAsPaid')
                ->once()
                ->andReturnUsing(function ($argPitch) {
                    $argPitch->payment_status = Pitch::PAYMENT_STATUS_PAID;
                    $argPitch->payment_completed_at = now();
                    $argPitch->save();

                    return $argPitch;
                });
            $mock->shouldReceive('clientApprovePitch')
                ->once()
                ->andReturnUsing(function ($argPitch) {
                    $argPitch->status = Pitch::STATUS_COMPLETED;
                    $argPitch->approved_at = now();
                    $argPitch->completed_at = now();
                    $argPitch->save();

                    return $argPitch;
                });
        });

        // Instantiate the webhook controller
        $controller = $this->app->make(WebhookController::class);

        // Act: Simulate webhook event with correct signature (InvoiceService, NotificationService)
        $response = $controller->handleCheckoutSessionCompleted($payload, $invoiceServiceMock, $notificationMock);

        // Assert: Webhook responds with 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Check pitch was updated
        $pitch->refresh();
        // Client management pitches go to COMPLETED after payment + approval
        $this->assertEquals(Pitch::STATUS_COMPLETED, $pitch->status);
        $this->assertEquals(Pitch::PAYMENT_STATUS_PAID, $pitch->payment_status);
        $this->assertNotNull($pitch->payment_completed_at);
    }

    /** @test */
    public function webhook_controller_ignores_non_client_pitch_checkouts()
    {
        // Arrange: Create test payload with different metadata type
        $payload = [
            'id' => 'evt_test_'.uniqid(),
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_'.uniqid(),
                    'payment_status' => 'paid',
                    'amount_total' => 10000,
                    'currency' => 'usd',
                    'metadata' => [
                        'pitch_id' => '999', // Non-existent pitch
                        'type' => 'other_payment_type', // Different type
                    ],
                ],
            ],
        ];

        // Mock InvoiceService - should not be called
        $invoiceServiceMock = $this->mock(InvoiceService::class, function ($mock) {
            $mock->shouldNotReceive('createOrUpdateInvoiceForPaidPitch');
        });

        // Mock NotificationService
        $notificationMock = $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldNotReceive('notify');
        });

        // Mock PitchWorkflowService - should not be called
        $this->mock(PitchWorkflowService::class, function ($mock) {
            $mock->shouldNotReceive('clientApprovePitch');
        });

        // Instantiate the webhook controller
        $controller = $this->app->make(WebhookController::class);

        // Act: Simulate webhook event with correct signature (InvoiceService, NotificationService)
        $response = $controller->handleCheckoutSessionCompleted($payload, $invoiceServiceMock, $notificationMock);

        // Assert: Webhook still responds with 200 OK (idempotent)
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function webhook_is_idempotent_for_already_paid_pitches()
    {
        // Arrange: Setup Dependencies
        $notificationMock = $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();
        });

        // Create producer (no real Stripe customer needed)
        $producer = User::factory()->create([
            'stripe_id' => 'cus_test_'.uniqid(),
        ]);

        // Create project with client management workflow
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
        ]);

        // Get automatically created pitch and mark it as already paid
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);

        // Set the timestamps properly for our test
        $pitch->update([
            'payment_amount' => 100.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID, // Already paid
            'status' => Pitch::STATUS_APPROVED, // Already approved
            'approved_at' => now()->subMinutes(30), // Explicitly set
            'payment_completed_at' => now()->subMinutes(30), // Paid 30 minutes ago
        ]);

        // Record status values to verify they don't change
        $pitch->refresh(); // Ensure all fields are loaded
        $originalStatus = $pitch->status;
        $originalPaymentStatus = $pitch->payment_status;

        // Create test Stripe payload with same pitch ID
        $sessionId = 'cs_test_'.uniqid();
        $payload = [
            'id' => 'evt_test_'.uniqid(),
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'payment_status' => 'paid',
                    'amount_total' => 10000,
                    'currency' => 'usd',
                    'metadata' => [
                        'pitch_id' => (string) $pitch->id,
                        'type' => 'client_pitch_payment',
                    ],
                ],
            ],
        ];

        // Mock services - they should NOT be called for idempotent operation
        $invoiceServiceMock = $this->mock(InvoiceService::class, function ($mock) {
            $mock->shouldNotReceive('createOrUpdateInvoiceForPaidPitch');
        });

        $this->mock(PitchWorkflowService::class, function ($mock) {
            $mock->shouldNotReceive('clientApprovePitch');
        });

        // Instantiate the webhook controller
        $controller = $this->app->make(WebhookController::class);

        // Act: Simulate webhook event with correct signature (InvoiceService, NotificationService)
        $response = $controller->handleCheckoutSessionCompleted($payload, $invoiceServiceMock, $notificationMock);

        // Assert: Webhook responds with 200 OK
        $this->assertEquals(200, $response->getStatusCode());

        // Pitch should remain unchanged
        $pitch->refresh();
        $this->assertEquals($originalStatus, $pitch->status);
        $this->assertEquals($originalPaymentStatus, $pitch->payment_status);
    }
}
