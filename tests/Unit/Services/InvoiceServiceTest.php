<?php

namespace Tests\Unit\Services;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Stripe\Invoice;
use Stripe\StripeClient;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $invoiceService;

    protected $stripeMock;

    protected $user;

    protected $project;

    protected $pitch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create(['stripe_id' => 'cus_test123']);
        $this->project = Project::factory()->for($this->user)->create([
            'budget' => 500,
            'name' => 'Test Project',
        ]);
        $this->pitch = Pitch::factory()->for($this->project)->create([
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
        ]);

        // Create a partial mock of InvoiceService to mock Stripe client
        $this->invoiceService = Mockery::mock(InvoiceService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods(); // Enable protected method mocking

        // Create a mock for Stripe client
        $this->stripeMock = Mockery::mock(StripeClient::class);

        // Set up the mocked StripeClient with the required method chains
        $this->stripeMock->invoices = Mockery::mock();
        $this->stripeMock->invoiceItems = Mockery::mock();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_pitch_invoice_success()
    {
        // Mock the stripe client creation
        $this->invoiceService->shouldReceive('newStripeClient')
            ->once()
            ->andReturn($this->stripeMock);

        // Set up the stripe mock responses
        $mockInvoice = (object) [
            'id' => 'inv_test123',
            'customer' => 'cus_test123',
            'metadata' => (object) [
                'pitch_id' => $this->pitch->id,
                'project_id' => $this->project->id,
                'invoice_id' => 'INV-ABCDEF1234',
            ],
        ];

        $this->stripeMock->invoices->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($params) {
                return $params['customer'] === 'cus_test123' &&
                       $params['metadata']['pitch_id'] === $this->pitch->id;
            }))
            ->andReturn($mockInvoice);

        $this->stripeMock->invoiceItems->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($params) {
                return $params['customer'] === 'cus_test123' &&
                       $params['amount'] === 50000 && // 500 * 100 cents
                       $params['invoice'] === 'inv_test123';
            }))
            ->andReturn((object) []); // Return a basic object

        // Call the method
        $result = $this->invoiceService->createPitchInvoice($this->pitch, 500, 'pm_test_card');

        // Assert the result
        $this->assertTrue($result['success']);
        $this->assertEquals($mockInvoice, $result['invoice']);
        $this->assertNotNull($result['invoiceId']);
    }

    /** @test */
    public function it_handles_stripe_error_during_invoice_creation()
    {
        // Mock the stripe client creation
        $this->invoiceService->shouldReceive('newStripeClient')
            ->once()
            ->andReturn($this->stripeMock);

        // Set up the stripe mock to throw a custom exception class
        $this->stripeMock->invoices->shouldReceive('create')
            ->once()
            ->andReturnUsing(function () {
                $e = new \Exception('Stripe API error');
                throw $e;
            });

        // Call the method
        $result = $this->invoiceService->createPitchInvoice($this->pitch, 500, 'pm_test_card');

        // Assert the result
        $this->assertFalse($result['success']);
        $this->assertNull($result['invoice']);
        $this->assertNotNull($result['invoiceId']);
        $this->assertEquals('Stripe API error', $result['error']);
    }

    /** @test */
    public function it_can_process_invoice_payment_success()
    {
        // Mock the stripe client creation
        $this->invoiceService->shouldReceive('newStripeClient')
            ->once()
            ->andReturn($this->stripeMock);

        // Mock invoice objects with all required properties
        $mockInvoice = (object) [
            'id' => 'inv_test123',
            'status' => 'draft',
            'total' => 50000,
            'amount_due' => 50000,
        ];

        $finalizedInvoice = (object) [
            'id' => 'inv_test123',
            'status' => 'finalized',
            'total' => 50000,
            'amount_due' => 50000,
        ];

        $paidInvoice = (object) [
            'id' => 'inv_test123',
            'status' => 'paid',
            'total' => 50000,
            'amount_due' => 0,
            'paid' => true,
        ];

        // Set up the stripe mock responses
        $this->stripeMock->invoices->shouldReceive('finalizeInvoice')
            ->once()
            ->with('inv_test123')
            ->andReturn($finalizedInvoice);

        $this->stripeMock->invoices->shouldReceive('pay')
            ->once()
            ->with('inv_test123', [
                'payment_method' => 'pm_test_card',
                'off_session' => true,
            ])
            ->andReturn($paidInvoice);

        // Call the method
        $result = $this->invoiceService->processInvoicePayment($mockInvoice, 'pm_test_card');

        // Assert the result
        $this->assertTrue($result['success']);
        $this->assertEquals($paidInvoice, $result['paymentResult']);
    }

    /** @test */
    public function it_handles_card_error_during_payment_processing()
    {
        // Mock the stripe client creation
        $this->invoiceService->shouldReceive('newStripeClient')
            ->once()
            ->andReturn($this->stripeMock);

        // Mock invoice object with all required properties
        $mockInvoice = (object) [
            'id' => 'inv_test123',
            'status' => 'draft',
            'total' => 50000,
            'amount_due' => 50000,
        ];

        // Set up the stripe mock responses with properly mocked finalized invoice
        $this->stripeMock->invoices->shouldReceive('finalizeInvoice')
            ->once()
            ->with('inv_test123')
            ->andReturn((object) [
                'id' => 'inv_test123',
                'status' => 'finalized',
                'total' => 50000,
                'amount_due' => 50000,
            ]);

        // Simulate card error during payment
        $this->stripeMock->invoices->shouldReceive('pay')
            ->once()
            ->andReturnUsing(function () {
                $e = new \Exception('Your card was declined');
                throw $e;
            });

        // Call the method
        $result = $this->invoiceService->processInvoicePayment($mockInvoice, 'pm_test_card');

        // Assert the result
        $this->assertFalse($result['success']);
        $this->assertEquals('Your card was declined', $result['error']);
    }

    /** @test */
    public function it_handles_general_api_error_during_payment_processing()
    {
        // Mock the stripe client creation
        $this->invoiceService->shouldReceive('newStripeClient')
            ->once()
            ->andReturn($this->stripeMock);

        // Mock invoice object with all required properties
        $mockInvoice = (object) [
            'id' => 'inv_test123',
            'status' => 'draft',
            'total' => 50000,
            'amount_due' => 50000,
        ];

        // Set up the stripe mock to throw a standard exception
        $this->stripeMock->invoices->shouldReceive('finalizeInvoice')
            ->once()
            ->with('inv_test123')
            ->andReturnUsing(function () {
                $e = new \Exception('Stripe API error');
                throw $e;
            });

        // Call the method
        $result = $this->invoiceService->processInvoicePayment($mockInvoice, 'pm_test_card');

        // Assert the result
        $this->assertFalse($result['success']);
        $this->assertEquals('Stripe API error', $result['error']);
    }

    /** @test */
    public function it_handles_already_finalized_invoice_during_payment()
    {
        // Mock the stripe client creation
        $this->invoiceService->shouldReceive('newStripeClient')
            ->once()
            ->andReturn($this->stripeMock);

        // Mock invoice object with all required properties
        $mockInvoice = (object) [
            'id' => 'inv_test123',
            'status' => 'finalized', // Already finalized
            'total' => 50000,
            'amount_due' => 50000,
        ];

        // Mock the finalizeInvoice to throw an "already finalized" error
        $this->stripeMock->invoices->shouldReceive('finalizeInvoice')
            ->once()
            ->with('inv_test123')
            ->andReturnUsing(function () {
                $e = new \Exception('Invoice inv_test123 is already finalized');
                throw $e;
            });

        // Mock the retrieve call that happens when already finalized
        $this->stripeMock->invoices->shouldReceive('retrieve')
            ->once()
            ->with('inv_test123')
            ->andReturn((object) [
                'id' => 'inv_test123',
                'status' => 'finalized',
                'total' => 50000,
                'amount_due' => 50000,
            ]);

        // Mock the successful payment
        $paidInvoice = (object) [
            'id' => 'inv_test123',
            'status' => 'paid',
            'total' => 50000,
            'amount_due' => 0,
            'paid' => true,
        ];

        $this->stripeMock->invoices->shouldReceive('pay')
            ->once()
            ->with('inv_test123', [
                'payment_method' => 'pm_test_card',
                'off_session' => true,
            ])
            ->andReturn($paidInvoice);

        // Call the method
        $result = $this->invoiceService->processInvoicePayment($mockInvoice, 'pm_test_card');

        // Assert the result
        $this->assertTrue($result['success']);
        $this->assertEquals($paidInvoice, $result['paymentResult']);
    }

    /** @test */
    public function it_creates_new_invoice_for_paid_pitch()
    {
        // Arrange
        $user = User::factory()->create(['stripe_id' => 'cus_test123']);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'title' => 'Test Pitch',
            'final_invoice_id' => null, // No existing invoice
        ]);

        // Mock invoice response
        $mockInvoice = (object) [
            'id' => 'inv_test123',
        ];

        // Mock invoice item response
        $mockInvoiceItem = (object) [
            'id' => 'ii_test123',
        ];

        // Create mock Stripe client
        $mockStripe = Mockery::mock(StripeClient::class);
        $mockStripe->invoices = Mockery::mock();
        $mockStripe->invoiceItems = Mockery::mock();

        // Setup expectations
        $mockStripe->invoices->shouldReceive('create')
            ->once()
            ->andReturn($mockInvoice);

        $mockStripe->invoiceItems->shouldReceive('create')
            ->once()
            ->andReturn($mockInvoiceItem);

        $mockStripe->invoices->shouldReceive('markAsPaid')
            ->once()
            ->andReturn($mockInvoice);

        // Create service with mocked Stripe client
        $service = $this->partialMock(InvoiceService::class, function (MockInterface $mock) use ($mockStripe) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('newStripeClient')
                ->andReturn($mockStripe);
        });

        // Act
        $result = $service->createOrUpdateInvoiceForPaidPitch($pitch, 'cs_test123', 100.00, 'USD');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals($mockInvoice, $result['invoice']);
        $this->assertEquals('Created new invoice and marked as paid', $result['message']);

        // Check that pitch was updated with invoice ID
        $pitch->refresh();
        $this->assertEquals('inv_test123', $pitch->final_invoice_id);
    }

    /** @test */
    public function it_retrieves_existing_invoice_for_paid_pitch()
    {
        // Arrange
        $user = User::factory()->create(['stripe_id' => 'cus_test123']);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'final_invoice_id' => 'inv_existing123', // Already has invoice ID
        ]);

        // Mock invoice response
        $mockInvoice = (object) [
            'id' => 'inv_existing123',
        ];

        // Create mock Stripe client
        $mockStripe = Mockery::mock(StripeClient::class);
        $mockStripe->invoices = Mockery::mock();

        // Setup expectations
        $mockStripe->invoices->shouldReceive('retrieve')
            ->once()
            ->with('inv_existing123')
            ->andReturn($mockInvoice);

        // Create service with mocked Stripe client
        $service = $this->partialMock(InvoiceService::class, function (MockInterface $mock) use ($mockStripe) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('newStripeClient')
                ->andReturn($mockStripe);
        });

        // Act
        $result = $service->createOrUpdateInvoiceForPaidPitch($pitch, 'cs_test123', 100.00, 'USD');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals($mockInvoice, $result['invoice']);
        $this->assertEquals('Retrieved existing invoice', $result['message']);
    }

    /** @test */
    public function it_handles_errors_when_creating_invoice_for_paid_pitch()
    {
        // Arrange
        $user = User::factory()->create(['stripe_id' => 'cus_test123']);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        // Create mock Stripe client
        $mockStripe = Mockery::mock(StripeClient::class);
        $mockStripe->invoices = Mockery::mock();

        // Setup expectations to simulate error
        $mockStripe->invoices->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Stripe API error'));

        // Create service with mocked Stripe client
        $service = $this->partialMock(InvoiceService::class, function (MockInterface $mock) use ($mockStripe) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('newStripeClient')
                ->andReturn($mockStripe);
        });

        // Act
        $result = $service->createOrUpdateInvoiceForPaidPitch($pitch, 'cs_test123', 100.00, 'USD');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Stripe API error', $result['error']);
    }
}
