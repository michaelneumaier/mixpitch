<?php

namespace Tests\Feature;

use App\Models\PayoutSchedule;
use App\Models\Pitch;
use App\Models\PitchMilestone;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PayoutProcessingService;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MilestonePayoutIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $producer;

    protected User $client;

    protected Project $project;

    protected Pitch $pitch;

    protected PayoutProcessingService $payoutService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock NotificationService to avoid notification sending
        $notificationMock = Mockery::mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyPayoutScheduled')->andReturn(null);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->andReturn(null);
        $notificationMock->shouldIgnoreMissing(); // Ignore any other notification methods
        $this->app->instance(NotificationService::class, $notificationMock);

        // Mock StripeConnectService to avoid Stripe API calls
        $stripeMock = Mockery::mock(StripeConnectService::class);
        $this->app->instance(StripeConnectService::class, $stripeMock);

        // Create producer with Stripe account
        $this->producer = User::factory()->create([
            'stripe_account_id' => 'acct_test_'.uniqid(),
            'subscription_plan' => 'pro',
            'subscription_tier' => 'engineer',
        ]);

        // Create client
        $this->client = User::factory()->create();

        // Create client management project
        $this->project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'budget' => 5000,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
        ]);

        // Create pitch
        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_IN_PROGRESS,
            'payment_amount' => 5000,
        ]);

        // Resolve service
        $this->payoutService = app(PayoutProcessingService::class);
    }

    public function test_milestone_payment_creates_payout_schedule(): void
    {
        // Create a milestone
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Initial Deposit',
            'description' => '50% upfront payment',
            'amount' => 2500,
            'sort_order' => 1,
            'status' => 'approved',
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'stripe_invoice_id' => 'in_test_'.uniqid(),
        ]);

        // Schedule payout for milestone
        $payoutSchedule = $this->payoutService->schedulePayoutForMilestone(
            $milestone,
            $milestone->stripe_invoice_id,
            false
        );

        // Assert payout schedule was created
        $this->assertInstanceOf(PayoutSchedule::class, $payoutSchedule);
        $this->assertEquals($milestone->id, $payoutSchedule->pitch_milestone_id);
        $this->assertEquals($this->producer->id, $payoutSchedule->producer_user_id);
        $this->assertEquals(2500, $payoutSchedule->gross_amount);
        $this->assertEquals(PayoutSchedule::STATUS_SCHEDULED, $payoutSchedule->status);

        // Assert commission was calculated correctly (default rate = 10%)
        $actualCommissionRate = $this->producer->getPlatformCommissionRate();
        $expectedCommission = 2500 * ($actualCommissionRate / 100);
        $expectedNet = 2500 - $expectedCommission;
        $this->assertEquals($expectedCommission, (float) $payoutSchedule->commission_amount);
        $this->assertEquals($expectedNet, (float) $payoutSchedule->net_amount);

        // Load and assert transaction was created
        $payoutSchedule->load('transaction');
        $this->assertNotNull($payoutSchedule->transaction);
        $this->assertEquals(Transaction::TYPE_PAYMENT, $payoutSchedule->transaction->type);
        $this->assertEquals(Transaction::STATUS_PENDING, $payoutSchedule->transaction->status);

        // Assert metadata contains milestone info
        $this->assertEquals($milestone->id, $payoutSchedule->metadata['milestone_id']);
        $this->assertEquals($milestone->name, $payoutSchedule->metadata['milestone_name']);
        $this->assertFalse($payoutSchedule->metadata['is_manual_payment']);
    }

    public function test_manual_milestone_payment_creates_payout_schedule(): void
    {
        // Create a milestone
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Progress Payment',
            'amount' => 1500,
            'sort_order' => 2,
            'status' => 'approved',
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'stripe_invoice_id' => 'MANUAL_'.time().'_'.uniqid(),
        ]);

        // Schedule payout for manual payment
        $payoutSchedule = $this->payoutService->schedulePayoutForMilestone(
            $milestone,
            $milestone->stripe_invoice_id,
            true // isManual = true
        );

        // Assert payout schedule was created with manual flag
        $this->assertTrue($payoutSchedule->metadata['is_manual_payment']);

        // Assert zero commission for manual payments (paid outside MixPitch)
        $this->assertEquals(0, $payoutSchedule->commission_rate);
        $this->assertEquals(0, $payoutSchedule->commission_amount);
        $this->assertEquals(1500, $payoutSchedule->gross_amount);
        $this->assertEquals(1500, $payoutSchedule->net_amount); // No commission deducted

        // Assert status is completed (not scheduled)
        $this->assertEquals(PayoutSchedule::STATUS_COMPLETED, $payoutSchedule->status);
        $this->assertNotNull($payoutSchedule->completed_at);

        // Assert no hold period
        $this->assertTrue(
            $payoutSchedule->hold_release_date->lte(now()->addMinutes(1)),
            'Manual payments should have no hold period'
        );

        // Load transaction and check description
        $payoutSchedule->load('transaction');
        $this->assertStringContainsString('(Manual)', $payoutSchedule->transaction->description);
    }

    public function test_revision_milestone_includes_revision_metadata(): void
    {
        // Create a revision milestone
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Revision Round 1',
            'amount' => 500,
            'sort_order' => 3,
            'status' => 'approved',
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'stripe_invoice_id' => 'in_test_revision_'.uniqid(),
            'is_revision_milestone' => true,
            'revision_round_number' => 1,
            'revision_request_details' => 'Client requested changes to intro section',
        ]);

        // Schedule payout
        $payoutSchedule = $this->payoutService->schedulePayoutForMilestone(
            $milestone,
            $milestone->stripe_invoice_id,
            false
        );

        // Assert revision metadata is included
        $this->assertTrue($payoutSchedule->metadata['is_revision_milestone']);
        $this->assertEquals(1, $payoutSchedule->metadata['revision_round_number']);
        $this->assertEquals(
            'Client requested changes to intro section',
            $payoutSchedule->metadata['revision_request_details']
        );
    }

    public function test_multiple_milestone_payments_create_separate_payout_schedules(): void
    {
        // Create three milestones
        $milestones = [];
        for ($i = 1; $i <= 3; $i++) {
            $milestones[] = PitchMilestone::create([
                'pitch_id' => $this->pitch->id,
                'name' => "Milestone $i",
                'amount' => 1000,
                'sort_order' => $i,
                'status' => 'approved',
                'payment_status' => Pitch::PAYMENT_STATUS_PAID,
                'payment_completed_at' => now(),
                'stripe_invoice_id' => "in_test_$i".uniqid(),
            ]);
        }

        // Schedule payouts for all milestones
        $payoutSchedules = [];
        foreach ($milestones as $milestone) {
            $payoutSchedules[] = $this->payoutService->schedulePayoutForMilestone(
                $milestone,
                $milestone->stripe_invoice_id,
                false
            );
        }

        // Assert all payouts were created
        $this->assertCount(3, $payoutSchedules);

        // Assert each payout is linked to correct milestone
        foreach ($payoutSchedules as $index => $payout) {
            $this->assertEquals($milestones[$index]->id, $payout->pitch_milestone_id);
            $this->assertEquals(1000, $payout->gross_amount);
        }

        // Assert payouts are independent
        $this->assertCount(3, PayoutSchedule::where('pitch_id', $this->pitch->id)->get());
    }

    public function test_payout_dashboard_displays_milestone_payouts(): void
    {
        // Create milestone with payout
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Final Payment',
            'amount' => 3000,
            'sort_order' => 1,
            'status' => 'approved',
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'stripe_invoice_id' => 'in_test_final_'.uniqid(),
        ]);

        $payoutSchedule = $this->payoutService->schedulePayoutForMilestone(
            $milestone,
            $milestone->stripe_invoice_id,
            false
        );

        // Act as producer and visit payout dashboard
        $this->actingAs($this->producer);
        $response = $this->get(route('payouts.index'));

        // Assert response is successful
        $response->assertSuccessful();

        // Assert milestone payout is visible
        $response->assertSee($milestone->name);
        $response->assertSee('$3,000.00'); // Gross amount
        $response->assertSee($this->project->name);
    }

    public function test_hold_period_is_zero_for_client_management_milestones(): void
    {
        // Create milestone
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 1000,
            'sort_order' => 1,
            'status' => 'approved',
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'stripe_invoice_id' => 'in_test_'.uniqid(),
        ]);

        // Schedule payout
        $payoutSchedule = $this->payoutService->schedulePayoutForMilestone(
            $milestone,
            $milestone->stripe_invoice_id,
            false
        );

        // Assert hold release date is now or in the past (0 day hold for client management)
        $this->assertTrue(
            $payoutSchedule->hold_release_date->lte(now()->addMinutes(5)),
            'Hold release date should be immediate for client management workflow'
        );
    }

    public function test_transaction_includes_milestone_metadata(): void
    {
        // Create milestone
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Metadata Test Milestone',
            'amount' => 750,
            'sort_order' => 1,
            'status' => 'approved',
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'stripe_invoice_id' => 'in_test_metadata_'.uniqid(),
        ]);

        // Schedule payout
        $payoutSchedule = $this->payoutService->schedulePayoutForMilestone(
            $milestone,
            $milestone->stripe_invoice_id,
            false
        );

        // Load and check transaction
        $payoutSchedule->load('transaction');
        $transaction = $payoutSchedule->transaction;

        // Assert transaction metadata includes milestone details
        $this->assertEquals($milestone->id, $transaction->metadata['milestone_id']);
        $this->assertEquals($milestone->name, $transaction->metadata['milestone_name']);
        $this->assertFalse($transaction->metadata['is_revision_milestone']);
        $this->assertEquals(
            Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            $transaction->metadata['workflow_type']
        );
    }

    public function test_payout_relationship_loads_milestone_correctly(): void
    {
        // Create milestone with payout
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Relationship Test',
            'amount' => 1200,
            'sort_order' => 1,
            'status' => 'approved',
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'stripe_invoice_id' => 'in_test_relationship_'.uniqid(),
        ]);

        $payoutSchedule = $this->payoutService->schedulePayoutForMilestone(
            $milestone,
            $milestone->stripe_invoice_id,
            false
        );

        // Reload from database
        $reloadedPayout = PayoutSchedule::with('milestone')->find($payoutSchedule->id);

        // Assert relationship works correctly
        $this->assertNotNull($reloadedPayout->milestone);
        $this->assertEquals($milestone->id, $reloadedPayout->milestone->id);
        $this->assertEquals($milestone->name, $reloadedPayout->milestone->name);
    }
}
