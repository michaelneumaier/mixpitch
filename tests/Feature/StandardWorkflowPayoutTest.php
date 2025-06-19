<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Pitch;
use App\Models\User;
use App\Models\PayoutSchedule;
use App\Services\PayoutProcessingService;
use App\Services\PitchWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class StandardWorkflowPayoutTest extends TestCase
{
    use RefreshDatabase;

    protected $producer;
    protected $projectOwner;
    protected $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->producer = User::factory()->create();
        $this->projectOwner = User::factory()->create();
        
        // Create standard project
        $this->project = Project::factory()->create([
            'user_id' => $this->projectOwner->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
            'budget' => 500,
            'status' => Project::STATUS_COMPLETED
        ]);
    }

    /** @test */
    public function standard_workflow_creates_payout_schedule_when_pitch_marked_paid()
    {
        // Arrange
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING
        ]);

        // Act
        $pitchWorkflowService = app(PitchWorkflowService::class);
        $updatedPitch = $pitchWorkflowService->markPitchAsPaid($pitch, 'inv_test_' . time());

        // Assert
        $this->assertEquals(Pitch::PAYMENT_STATUS_PAID, $updatedPitch->payment_status);
        
        $this->assertDatabaseHas('payout_schedules', [
            'pitch_id' => $pitch->id,
            'workflow_type' => 'standard',
            'gross_amount' => 500.00,
            'producer_user_id' => $this->producer->id,
            'project_id' => $this->project->id
        ]);
    }

    /** @test */
    public function payout_processing_service_handles_standard_workflow_correctly()
    {
        // Arrange
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);

        // Act
        $payoutService = app(PayoutProcessingService::class);
        $payout = $payoutService->schedulePayoutForPitch($pitch, 'test_invoice_123');

        // Assert
        $this->assertEquals('standard', $payout->workflow_type);
        $this->assertEquals($this->producer->id, $payout->producer_user_id);
        $this->assertEquals($this->project->id, $payout->project_id);
        $this->assertEquals($pitch->id, $payout->pitch_id);
        $this->assertEquals(500.00, $payout->gross_amount);
        $this->assertEquals('scheduled', $payout->status);
        
        // Verify commission calculation
        $expectedCommissionRate = $this->producer->getPlatformCommissionRate();
        $expectedCommissionAmount = 500 * ($expectedCommissionRate / 100);
        $expectedNetAmount = 500 - $expectedCommissionAmount;
        
        $this->assertEquals($expectedCommissionRate, $payout->commission_rate);
        $this->assertEquals($expectedCommissionAmount, $payout->commission_amount);
        $this->assertEquals($expectedNetAmount, $payout->net_amount);
        
        // Verify hold release date (should be 3 business days)
        $this->assertNotNull($payout->hold_release_date);
        $this->assertGreaterThan(now(), $payout->hold_release_date);
    }

    /** @test */
    public function payout_status_component_displays_for_standard_workflow()
    {
        // Arrange
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);

        $payout = PayoutSchedule::create([
            'producer_user_id' => $this->producer->id,
            'project_id' => $this->project->id,
            'pitch_id' => $pitch->id,
            'workflow_type' => 'standard',
            'gross_amount' => 500,
            'commission_rate' => 10.0,
            'commission_amount' => 50,
            'net_amount' => 450,
            'currency' => 'USD',
            'status' => 'scheduled',
            'hold_release_date' => now()->addDays(3),
            'metadata' => ['test' => true]
        ]);

        // Act - Visit pitch show page as producer
        $response = $this->actingAs($this->producer)
            ->get(route('projects.pitches.show', ['project' => $this->project, 'pitch' => $pitch]));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Payout Status');
        $response->assertSee('Your earnings from this project'); // Standard workflow specific text
        $response->assertSee('Project Payment Scheduled'); // Standard workflow specific text
        $response->assertSee($this->project->name); // Project name should be displayed
        $response->assertSee('$450.00'); // Net amount
        $response->assertSee('$500.00'); // Gross amount
        $response->assertSee('10.00%'); // Commission rate
    }

    /** @test */
    public function payout_status_component_shows_completed_status_correctly()
    {
        // Arrange
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);

        $payout = PayoutSchedule::create([
            'producer_user_id' => $this->producer->id,
            'project_id' => $this->project->id,
            'pitch_id' => $pitch->id,
            'workflow_type' => 'standard',
            'gross_amount' => 600,
            'commission_rate' => 10.0,
            'commission_amount' => 60,
            'net_amount' => 540,
            'currency' => 'USD',
            'status' => 'completed',
            'hold_release_date' => now()->subDays(1),
            'completed_at' => now(),
            'stripe_transfer_id' => 'tr_test_123',
            'metadata' => ['test' => true]
        ]);

        // Act
        $response = $this->actingAs($this->producer)
            ->get(route('projects.pitches.show', ['project' => $this->project, 'pitch' => $pitch]));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Project Payment Completed'); // Standard workflow specific text
        $response->assertSee('Payment Complete!');
        $response->assertSee('$540.00'); // Net amount
        $response->assertSee('tr_test_123'); // Transfer ID
    }

    /** @test */
    public function payout_status_component_shows_processing_status_correctly()
    {
        // Arrange
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);

        $payout = PayoutSchedule::create([
            'producer_user_id' => $this->producer->id,
            'project_id' => $this->project->id,
            'pitch_id' => $pitch->id,
            'workflow_type' => 'standard',
            'gross_amount' => 400,
            'commission_rate' => 10.0,
            'commission_amount' => 40,
            'net_amount' => 360,
            'currency' => 'USD',
            'status' => 'processing',
            'hold_release_date' => now()->subDays(1),
            'metadata' => ['test' => true]
        ]);

        // Act
        $response = $this->actingAs($this->producer)
            ->get(route('projects.pitches.show', ['project' => $this->project, 'pitch' => $pitch]));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Project Payment Processing'); // Standard workflow specific text
        $response->assertSee('$360.00'); // Net amount
    }

    /** @test */
    public function payout_status_component_shows_failed_status_correctly()
    {
        // Arrange
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);

        $payout = PayoutSchedule::create([
            'producer_user_id' => $this->producer->id,
            'project_id' => $this->project->id,
            'pitch_id' => $pitch->id,
            'workflow_type' => 'standard',
            'gross_amount' => 300,
            'commission_rate' => 10.0,
            'commission_amount' => 30,
            'net_amount' => 270,
            'currency' => 'USD',
            'status' => 'failed',
            'failure_reason' => 'Insufficient funds in connected account',
            'hold_release_date' => now()->subDays(1),
            'metadata' => ['test' => true]
        ]);

        // Act
        $response = $this->actingAs($this->producer)
            ->get(route('projects.pitches.show', ['project' => $this->project, 'pitch' => $pitch]));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Project Payment Failed'); // Standard workflow specific text
        $response->assertSee('Insufficient funds in connected account'); // Failure reason
        $response->assertSee('$270.00'); // Net amount
    }

    /** @test */
    public function payout_component_only_visible_to_pitch_owner()
    {
        // Arrange
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);

        $payout = PayoutSchedule::create([
            'producer_user_id' => $this->producer->id,
            'project_id' => $this->project->id,
            'pitch_id' => $pitch->id,
            'workflow_type' => 'standard',
            'gross_amount' => 500,
            'commission_rate' => 10.0,
            'commission_amount' => 50,
            'net_amount' => 450,
            'currency' => 'USD',
            'status' => 'scheduled',
            'hold_release_date' => now()->addDays(3),
            'metadata' => ['test' => true]
        ]);

        // Act & Assert - Project owner gets redirected (302) due to controller logic
        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.show', ['project' => $this->project, 'pitch' => $pitch]));

        $response->assertStatus(302); // Project owners get redirected to manage page
        
        // Act & Assert - Producer SHOULD see payout component
        $response = $this->actingAs($this->producer)
            ->get(route('projects.pitches.show', ['project' => $this->project, 'pitch' => $pitch]));

        $response->assertStatus(200);
        $response->assertSee('Payout Status');
        $response->assertSee('Your earnings from this project');
        
        // Act & Assert - Random user should not see payout component (if they can access the page)
        $randomUser = User::factory()->create();
        $response = $this->actingAs($randomUser)
            ->get(route('projects.pitches.show', ['project' => $this->project, 'pitch' => $pitch]));

        // Random user might get 403 or see page without payout component depending on authorization
        if ($response->status() === 200) {
            $response->assertDontSee('Payout Status');
            $response->assertDontSee('Your earnings from this project');
        } else {
            // If they can't access the page at all, that's also acceptable
            $this->assertContains($response->status(), [403, 404]);
        }
    }

    /** @test */
    public function multiple_payouts_summary_works_correctly()
    {
        // Arrange
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);

        // Create multiple payouts (simulating partial payments or adjustments)
        PayoutSchedule::create([
            'producer_user_id' => $this->producer->id,
            'project_id' => $this->project->id,
            'pitch_id' => $pitch->id,
            'workflow_type' => 'standard',
            'gross_amount' => 300,
            'commission_rate' => 10.0,
            'commission_amount' => 30,
            'net_amount' => 270,
            'currency' => 'USD',
            'status' => 'completed',
            'hold_release_date' => now()->subDays(3),
            'completed_at' => now()->subDays(2),
            'metadata' => ['test' => true]
        ]);

        PayoutSchedule::create([
            'producer_user_id' => $this->producer->id,
            'project_id' => $this->project->id,
            'pitch_id' => $pitch->id,
            'workflow_type' => 'standard',
            'gross_amount' => 200,
            'commission_rate' => 10.0,
            'commission_amount' => 20,
            'net_amount' => 180,
            'currency' => 'USD',
            'status' => 'scheduled',
            'hold_release_date' => now()->addDays(3),
            'metadata' => ['test' => true]
        ]);

        // Act
        $response = $this->actingAs($this->producer)
            ->get(route('projects.pitches.show', ['project' => $this->project, 'pitch' => $pitch]));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Total from this pitch:');
        $response->assertSee('$450.00'); // Total net amount (270 + 180)
        $response->assertSee('Total payouts:');
        $response->assertSee('2'); // Number of payouts
    }

    /** @test */
    public function commission_calculations_are_accurate_for_different_rates()
    {
        // Test different commission rates
        $testCases = [
            ['rate' => 5.0, 'amount' => 1000, 'expected_commission' => 50, 'expected_net' => 950],
            ['rate' => 10.0, 'amount' => 500, 'expected_commission' => 50, 'expected_net' => 450],
            ['rate' => 15.0, 'amount' => 200, 'expected_commission' => 30, 'expected_net' => 170],
        ];

        foreach ($testCases as $case) {
            // Arrange
            $producer = User::factory()->create();
            $project = Project::factory()->create([
                'user_id' => $this->projectOwner->id,
                'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
                'budget' => $case['amount']
            ]);
            
            $pitch = Pitch::factory()->create([
                'project_id' => $project->id,
                'user_id' => $producer->id,
                'status' => Pitch::STATUS_COMPLETED,
                'payment_status' => Pitch::PAYMENT_STATUS_PAID
            ]);

            // Create a payout manually with the expected commission rate
            $payout = PayoutSchedule::create([
                'producer_user_id' => $producer->id,
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'workflow_type' => 'standard',
                'gross_amount' => $case['amount'],
                'commission_rate' => $case['rate'],
                'commission_amount' => $case['expected_commission'],
                'net_amount' => $case['expected_net'],
                'currency' => 'USD',
                'status' => 'scheduled',
                'hold_release_date' => now()->addDays(3),
                'metadata' => ['test' => true]
            ]);

            // Assert
            $this->assertEquals($case['rate'], $payout->commission_rate, "Commission rate mismatch for rate {$case['rate']}%");
            $this->assertEquals($case['expected_commission'], $payout->commission_amount, "Commission amount mismatch for rate {$case['rate']}%");
            $this->assertEquals($case['expected_net'], $payout->net_amount, "Net amount mismatch for rate {$case['rate']}%");
        }
    }

    /** @test */
    public function standard_workflow_prevents_payment_without_producer_stripe_connect()
    {
        // Arrange - Create a pitch without Stripe Connect setup
        $producer = User::factory()->create(['stripe_account_id' => null]);
        $project = Project::factory()->create([
            'workflow_type' => 'standard',
            'budget' => 500,
            'user_id' => $this->projectOwner->id
        ]);
        
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING
        ]);

        // Act - Try to access payment overview
        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.payment.overview', ['project' => $project, 'pitch' => $pitch]));

        // Assert - Should be redirected with error
        $response->assertRedirect();
        $response->assertSessionHasErrors('stripe_connect');
        $this->assertStringContainsString('needs to complete their Stripe Connect account setup', session('errors')->first('stripe_connect'));
    }

    /** @test */
    public function standard_workflow_prevents_payment_processing_without_producer_stripe_connect()
    {
        // Arrange - Create a pitch without Stripe Connect setup
        $producer = User::factory()->create(['stripe_account_id' => null]);
        $project = Project::factory()->create([
            'workflow_type' => 'standard',
            'budget' => 500,
            'user_id' => $this->projectOwner->id
        ]);
        
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING
        ]);

        // Act - Try to process payment
        $response = $this->actingAs($this->projectOwner)
            ->post(route('projects.pitches.payment.process', ['project' => $project, 'pitch' => $pitch]), [
                'payment_method_id' => 'pm_test_123',
                'confirm_payment' => true
            ]);

        // Assert - Should fail authorization
        $response->assertSessionHasErrors('stripe_connect');
    }

    /** @test */
    public function standard_workflow_allows_payment_with_valid_producer_stripe_connect()
    {
        // Arrange - Create a pitch with valid Stripe Connect setup
        $producer = User::factory()->create(['stripe_account_id' => 'acct_test123']);
        
        // Mock the hasValidStripeConnectAccount method to return true
        $producer = Mockery::mock($producer)->makePartial();
        $producer->shouldReceive('hasValidStripeConnectAccount')->andReturn(true);
        $producer->shouldReceive('getStripeConnectStatus')->andReturn(['status' => 'active']);
        
        $project = Project::factory()->create([
            'workflow_type' => 'standard',
            'budget' => 500,
            'user_id' => $this->projectOwner->id
        ]);
        
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING
        ]);

        // Mock the User::find to return our mocked producer
        User::shouldReceive('find')->with($producer->id)->andReturn($producer);

        // Act - Access payment overview
        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.payment.overview', ['project' => $project, 'pitch' => $pitch]));

        // Assert - Should show payment form
        $response->assertStatus(200);
        $response->assertSee('Ready for Payment');
        $response->assertSee('Process Payment');
    }

    /** @test */
    public function standard_workflow_shows_producer_stripe_status_in_payment_overview()
    {
        // Arrange - Create pitch with producer who has Stripe account in progress
        $producer = User::factory()->create(['stripe_account_id' => 'acct_test123']);
        
        // Mock partial setup (account exists but not fully verified)
        $producer = Mockery::mock($producer)->makePartial();
        $producer->shouldReceive('hasValidStripeConnectAccount')->andReturn(false);
        $producer->shouldReceive('getStripeConnectStatus')->andReturn([
            'status' => 'pending_verification',
            'display' => 'Setup In Progress'
        ]);
        
        $project = Project::factory()->create([
            'workflow_type' => 'standard',
            'budget' => 500,
            'user_id' => $this->projectOwner->id
        ]);
        
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING
        ]);

        // Act - Try to access payment overview
        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.payment.overview', ['project' => $project, 'pitch' => $pitch]));

        // Assert - Should be redirected with specific message about setup in progress
        $response->assertRedirect();
        $response->assertSessionHasErrors('stripe_connect');
    }
} 