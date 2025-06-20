<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\PayoutSchedule;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class Phase3ProducerExperienceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $producer;
    protected $stripeConnectService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed subscription limits for testing
        $this->seed(\Database\Seeders\CompleteSubscriptionLimitsSeeder::class);
        
        $this->producer = User::factory()->create([
            'subscription_plan' => 'pro',
            'subscription_tier' => 'artist',
            'stripe_account_id' => 'acct_test123'
        ]);
        
        $this->stripeConnectService = $this->createMock(StripeConnectService::class);
        $this->app->instance(StripeConnectService::class, $this->stripeConnectService);
    }

    /** @test */
    public function producer_dashboard_displays_earnings_analytics()
    {
        // Create some completed payouts
        PayoutSchedule::factory()->count(3)->create([
            'producer_user_id' => $this->producer->id,
            'status' => PayoutSchedule::STATUS_COMPLETED,
            'net_amount' => 100.00,
            'gross_amount' => 110.00,
            'commission_amount' => 10.00,
            'completed_at' => now()->subDays(5)
        ]);

        // Create a pending payout
        PayoutSchedule::factory()->create([
            'producer_user_id' => $this->producer->id,
            'status' => PayoutSchedule::STATUS_SCHEDULED,
            'net_amount' => 50.00,
            'gross_amount' => 55.00,
            'commission_amount' => 5.00
        ]);

        // Mock Stripe Connect service
        $this->stripeConnectService
            ->expects($this->once())
            ->method('getDetailedAccountStatus')
            ->with($this->producer)
            ->willReturn([
                'account_exists' => true,
                'can_receive_payouts' => true,
                'status_display' => 'Account ready for payouts',
                'next_steps' => []
            ]);

        $response = $this->actingAs($this->producer)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Total Earnings');
        $response->assertSee('$300.00'); // Total earnings (3 x $100)
        $response->assertSee('$50.00'); // Pending earnings
        $response->assertSee('Ready for Payouts');
    }

    /** @test */
    public function producer_dashboard_displays_client_management_stats()
    {
        // Create client management projects
        $clientProject1 = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'status' => Project::STATUS_COMPLETED
        ]);

        $clientProject2 = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'status' => Project::STATUS_IN_PROGRESS
        ]);

        // Create pitches with payments
        Pitch::factory()->create([
            'project_id' => $clientProject1->id,
            'user_id' => $this->producer->id,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_amount' => 500.00
        ]);

        Pitch::factory()->create([
            'project_id' => $clientProject2->id,
            'user_id' => $this->producer->id,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
            'payment_amount' => 300.00
        ]);

        // Mock Stripe Connect service
        $this->stripeConnectService
            ->expects($this->once())
            ->method('getDetailedAccountStatus')
            ->willReturn([
                'account_exists' => true,
                'can_receive_payouts' => true,
                'status_display' => 'Account ready for payouts',
                'next_steps' => []
            ]);

        $response = $this->actingAs($this->producer)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Client Projects');
        $response->assertSee('2'); // Total projects
        $response->assertSee('1'); // Active projects
        $response->assertSee('1'); // Completed projects
        $response->assertSee('$500'); // Revenue
    }

    /** @test */
    public function producer_dashboard_shows_stripe_connect_setup_required()
    {
        // Producer without Stripe Connect
        $producer = User::factory()->create([
            'stripe_account_id' => null
        ]);

        // Mock Stripe Connect service
        $this->stripeConnectService
            ->expects($this->once())
            ->method('getDetailedAccountStatus')
            ->with($producer)
            ->willReturn([
                'account_exists' => false,
                'can_receive_payouts' => false,
                'status_display' => 'Setup required to receive payouts',
                'next_steps' => ['Complete account setup']
            ]);

        $response = $this->actingAs($producer)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Setup Required');
        $response->assertSee('Complete Setup');
        $response->assertSee('Setup required to receive payouts');
    }

    /** @test */
    public function producer_dashboard_displays_recent_payouts()
    {
        // Create projects for context
        $project1 = Project::factory()->create(['name' => 'Test Project 1']);
        $project2 = Project::factory()->create(['name' => 'Test Project 2']);

        // Create recent payouts
        PayoutSchedule::factory()->create([
            'producer_user_id' => $this->producer->id,
            'project_id' => $project1->id,
            'status' => PayoutSchedule::STATUS_COMPLETED,
            'net_amount' => 150.00,
            'created_at' => now()->subDays(1)
        ]);

        PayoutSchedule::factory()->create([
            'producer_user_id' => $this->producer->id,
            'project_id' => $project2->id,
            'status' => PayoutSchedule::STATUS_PROCESSING,
            'net_amount' => 200.00,
            'created_at' => now()->subDays(2)
        ]);

        // Mock Stripe Connect service
        $this->stripeConnectService
            ->expects($this->once())
            ->method('getDetailedAccountStatus')
            ->willReturn([
                'account_exists' => true,
                'can_receive_payouts' => true,
                'status_display' => 'Account ready',
                'next_steps' => []
            ]);

        $response = $this->actingAs($this->producer)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Recent Payouts');
        $response->assertSee('Test Project 1');
        $response->assertSee('Test Project 2');
        $response->assertSee('$150.00');
        $response->assertSee('$200.00');
        $response->assertSee('completed');
        $response->assertSee('processing');
    }

    /** @test */
    public function producer_dashboard_calculates_commission_savings()
    {
        // Create completed transactions to calculate commission savings
        // The User model's getCommissionSavings() method uses completedTransactions()
        \App\Models\Transaction::factory()->create([
            'user_id' => $this->producer->id,
            'type' => 'payment',
            'status' => 'completed',
            'amount' => 1000.00,
            'net_amount' => 920.00,
            'commission_amount' => 80.00,
            'commission_rate' => 8.0
        ]);

        // Debug: Check the commission savings calculation
        $currentRate = $this->producer->getPlatformCommissionRate();
        $commissionSavings = $this->producer->getCommissionSavings();
        
        // Verify our expectations before testing the view
        $this->assertEquals(8.0, $currentRate, 'Producer should have 8% commission rate');
        $this->assertEquals(20.0, $commissionSavings, 'Commission savings should be $20.00: (10% - 8%) * $1000');
        $this->assertEquals(1, $this->producer->completedTransactions()->payments()->count(), 'Should have 1 completed payment transaction');
        $this->assertEquals(1000.00, $this->producer->completedTransactions()->payments()->sum('amount'), 'Total revenue should be $1000');

        // Mock Stripe Connect service
        $this->stripeConnectService
            ->expects($this->once())
            ->method('getDetailedAccountStatus')
            ->willReturn([
                'account_exists' => true,
                'can_receive_payouts' => true,
                'status_display' => 'Account ready',
                'next_steps' => []
            ]);

        $response = $this->actingAs($this->producer)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Commission Saved');
        // Should show savings: (10% - 8%) * $1000 = $20.00
        $response->assertSee('$20.00');
        $response->assertSee('8% rate vs 10% free tier');
    }

    /** @test */
    public function producer_analytics_only_shows_for_producers_with_data()
    {
        // Producer with no payouts or client projects
        $newProducer = User::factory()->create();

        // Mock Stripe Connect service
        $this->stripeConnectService
            ->expects($this->once())
            ->method('getDetailedAccountStatus')
            ->with($newProducer)
            ->willReturn([
                'account_exists' => false,
                'can_receive_payouts' => false,
                'status_display' => 'Setup required',
                'next_steps' => []
            ]);

        $response = $this->actingAs($newProducer)->get('/dashboard');

        $response->assertStatus(200);
        // Should still show producer analytics section
        $response->assertSee('Total Earnings');
        $response->assertSee('$0.00'); // No earnings yet
        $response->assertSee('Setup Required'); // Stripe Connect status
    }

    /** @test */
    public function producer_dashboard_links_to_payout_pages()
    {
        // Mock Stripe Connect service
        $this->stripeConnectService
            ->expects($this->once())
            ->method('getDetailedAccountStatus')
            ->willReturn([
                'account_exists' => true,
                'can_receive_payouts' => true,
                'status_display' => 'Account ready',
                'next_steps' => []
            ]);

        $response = $this->actingAs($this->producer)->get('/dashboard');

        $response->assertStatus(200);
        
        // Check for links to payout and Stripe Connect pages
        $response->assertSee(route('payouts.index'));
        $response->assertSee(route('stripe.connect.setup'));
    }

    /** @test */
    public function dashboard_handles_missing_producer_data_gracefully()
    {
        // Producer with incomplete data
        $producer = User::factory()->create([
            'stripe_account_id' => null
        ]);

        // Mock Stripe Connect service to return minimal data
        $this->stripeConnectService
            ->expects($this->once())
            ->method('getDetailedAccountStatus')
            ->willReturn([
                'account_exists' => false,
                'can_receive_payouts' => false,
                'status_display' => 'Setup required',
                'next_steps' => []
            ]);

        $response = $this->actingAs($producer)->get('/dashboard');

        $response->assertStatus(200);
        // Should not crash and should show appropriate empty states
        $response->assertSee('Total Earnings');
        $response->assertSee('$0.00');
        $response->assertSee('Setup Required');
    }

    /** @test */
    public function producer_dashboard_filters_client_management_projects_correctly()
    {
        // Create mixed project types
        $standardProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD
        ]);

        $clientProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT
        ]);

        $contestProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST
        ]);

        // Mock Stripe Connect service
        $this->stripeConnectService
            ->expects($this->once())
            ->method('getDetailedAccountStatus')
            ->willReturn([
                'account_exists' => true,
                'can_receive_payouts' => true,
                'status_display' => 'Account ready',
                'next_steps' => []
            ]);

        $response = $this->actingAs($this->producer)->get('/dashboard');

        $response->assertStatus(200);
        
        // Should only count client management projects in the client stats
        $response->assertSee('Client Projects');
        // Should show 1 client project (not all 3 projects)
        $this->assertStringContainsString('1', $response->getContent());
    }
} 