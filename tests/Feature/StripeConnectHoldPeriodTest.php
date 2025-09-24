<?php

namespace Tests\Feature;

use App\Models\PayoutHoldSetting;
use App\Models\PayoutSchedule;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\PayoutHoldService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeConnectHoldPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected PayoutHoldService $holdService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->holdService = app(PayoutHoldService::class);
    }

    /** @test */
    public function it_schedules_stripe_payouts_with_correct_hold_periods()
    {
        // Arrange: Configure workflow-specific hold periods
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => [
                'standard' => 2,
                'contest' => 0,
                'client_management' => 1,
            ],
            'business_days_only' => false,
            'processing_time' => '09:00:00',
            'minimum_hold_hours' => 0, // No minimum hold for immediate payouts
        ]);

        Carbon::setTestNow(Carbon::parse('2024-01-05 14:00:00'));

        $producer = User::factory()->create([
            'stripe_account_id' => 'acct_test_producer123',
        ]);

        // Test different project types
        $standardProject = Project::factory()->configureWorkflow('standard')->create();
        $contestProject = Project::factory()->configureWorkflow('contest')->create();
        $clientProject = Project::factory()->configureWorkflow('client_management')->create();

        $standardPitch = Pitch::factory()->create([
            'project_id' => $standardProject->id,
            'user_id' => $producer->id,
            'payment_amount' => 1500,
        ]);

        $contestPitch = Pitch::factory()->create([
            'project_id' => $contestProject->id,
            'user_id' => $producer->id,
            'payment_amount' => 2500,
        ]);

        $clientPitch = Pitch::factory()->create([
            'project_id' => $clientProject->id,
            'user_id' => $producer->id,
            'payment_amount' => 3000,
        ]);

        // Act: Test that hold service calculates correctly
        $standardHoldDate = $this->holdService->calculateHoldReleaseDate('standard');
        $contestHoldDate = $this->holdService->calculateHoldReleaseDate('contest');
        $clientHoldDate = $this->holdService->calculateHoldReleaseDate('client_management');

        // Assert: Verify hold periods are correctly calculated
        // Current time: 2024-01-05 14:00:00 (Friday afternoon)
        // With calendar days: Next day + hold days at 09:00:00

        // Standard: 2 calendar days (Friday + 2 days = Sunday)
        $this->assertEquals(
            Carbon::parse('2024-01-07 09:00:00')->format('Y-m-d H:i'),
            $standardHoldDate->format('Y-m-d H:i')
        );

        // Contest: 0 days (immediate processing - same time as now with no minimum hold)
        $this->assertEquals(
            Carbon::parse('2024-01-05 14:00:00')->format('Y-m-d H:i'),
            $contestHoldDate->format('Y-m-d H:i')
        );

        // Client Management: 1 calendar day (Friday + 1 day = Saturday)
        $this->assertEquals(
            Carbon::parse('2024-01-06 09:00:00')->format('Y-m-d H:i'),
            $clientHoldDate->format('Y-m-d H:i')
        );

        // Also test creating actual payouts with these dates
        $standardPayout = PayoutSchedule::create([
            'pitch_id' => $standardPitch->id,
            'producer_user_id' => $producer->id,
            'producer_stripe_account_id' => $producer->stripe_account_id,
            'gross_amount' => 1500,
            'commission_rate' => 8.0,
            'commission_amount' => 120,
            'net_amount' => 1380,
            'currency' => 'USD',
            'status' => PayoutSchedule::STATUS_SCHEDULED,
            'workflow_type' => 'standard',
            'hold_release_date' => $standardHoldDate,
            'metadata' => [
                'stripe_invoice_id' => 'in_test_standard_123',
                'stripe_account_id' => $producer->stripe_account_id,
                'commission_percentage' => 8,
                'gross_amount' => 1500,
            ],
        ]);

        // Verify Stripe metadata is preserved
        $this->assertEquals('acct_test_producer123', $standardPayout->metadata['stripe_account_id']);
        $this->assertEquals('in_test_standard_123', $standardPayout->metadata['stripe_invoice_id']);
        $this->assertEquals(8, $standardPayout->metadata['commission_percentage']);
    }

    /** @test */
    public function it_handles_ready_for_stripe_payout_status()
    {
        // Arrange: Create payout that's ready for Stripe processing
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => ['standard' => 1],
            'business_days_only' => false,
        ]);

        Carbon::setTestNow(Carbon::parse('2024-01-05 14:00:00'));

        $producer = User::factory()->create([
            'stripe_account_id' => 'acct_test_ready123',
        ]);

        $project = Project::factory()->configureWorkflow('standard')->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'payment_amount' => 1000,
        ]);

        // Create payout with past hold release date
        $payout = PayoutSchedule::create([
            'pitch_id' => $pitch->id,
            'producer_user_id' => $producer->id,
            'producer_stripe_account_id' => $producer->stripe_account_id,
            'gross_amount' => 1000,
            'commission_rate' => 8.0,
            'commission_amount' => 80,
            'net_amount' => 920,
            'currency' => 'USD',
            'status' => PayoutSchedule::STATUS_SCHEDULED,
            'workflow_type' => 'standard',
            'hold_release_date' => Carbon::parse('2024-01-04 09:00:00'), // Yesterday
            'metadata' => [
                'stripe_invoice_id' => 'in_test_ready_123',
                'stripe_account_id' => $producer->stripe_account_id,
            ],
        ]);

        // Act: Check if payout is ready for processing
        $isReadyForStripe = $payout->hold_release_date <= now() &&
                           $payout->status === PayoutSchedule::STATUS_SCHEDULED;

        // Assert: Payout should be ready for Stripe processing
        $this->assertTrue($isReadyForStripe);
        $this->assertEquals(PayoutSchedule::STATUS_SCHEDULED, $payout->status);
        $this->assertNotNull($payout->metadata['stripe_invoice_id']);
        $this->assertNotNull($payout->metadata['stripe_account_id']);
    }

    /** @test */
    public function it_handles_admin_bypass_for_stripe_payouts()
    {
        // Arrange
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => ['standard' => 5],
            'allow_admin_bypass' => true,
            'require_bypass_reason' => true,
            'minimum_hold_hours' => 1,
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $producer = User::factory()->create([
            'stripe_account_id' => 'acct_test_bypass123',
        ]);

        $project = Project::factory()->configureWorkflow('standard')->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'payment_amount' => 2000,
        ]);

        $payout = PayoutSchedule::create([
            'pitch_id' => $pitch->id,
            'producer_user_id' => $producer->id,
            'producer_stripe_account_id' => $producer->stripe_account_id,
            'gross_amount' => 2000,
            'commission_rate' => 8.0,
            'commission_amount' => 160,
            'net_amount' => 1840,
            'currency' => 'USD',
            'status' => PayoutSchedule::STATUS_SCHEDULED,
            'workflow_type' => 'standard',
            'hold_release_date' => now()->addDays(5),
            'metadata' => [
                'stripe_invoice_id' => 'in_test_bypass_456',
                'stripe_account_id' => $producer->stripe_account_id,
            ],
        ]);

        // Act: Admin bypasses hold period
        $this->holdService->bypassHoldPeriod($payout, 'Emergency payout for producer', $admin);

        // Assert: Payout is bypassed and ready for Stripe processing
        $payout->refresh();
        $this->assertTrue($payout->hold_bypassed);
        $this->assertEquals('Emergency payout for producer', $payout->bypass_reason);
        $this->assertEquals($admin->id, $payout->bypass_admin_id);
        $this->assertLessThanOrEqual(now()->addHours(1), $payout->hold_release_date);

        // Should still have Stripe metadata intact
        $this->assertEquals('in_test_bypass_456', $payout->metadata['stripe_invoice_id']);
        $this->assertEquals('acct_test_bypass123', $payout->metadata['stripe_account_id']);
    }

    /** @test */
    public function it_provides_stripe_compatible_payout_information()
    {
        // Arrange
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => [
                'standard' => 3,
                'contest' => 0,
                'client_management' => 7,
            ],
            'business_days_only' => true,
            'processing_time' => '09:00:00',
        ]);

        // Act & Assert: Test hold period info for Stripe integration
        $standardInfo = $this->holdService->getHoldPeriodInfo('standard');
        $this->assertEquals(3, $standardInfo['hold_days']);
        $this->assertTrue($standardInfo['business_days_only']);
        $this->assertStringContainsString('3 business days', $standardInfo['description']);
        $this->assertEquals('09:00', $standardInfo['processing_time']);

        $contestInfo = $this->holdService->getHoldPeriodInfo('contest');
        $this->assertEquals(0, $contestInfo['hold_days']);
        $this->assertStringContainsString('Immediate', $contestInfo['description']);

        $clientInfo = $this->holdService->getHoldPeriodInfo('client_management');
        $this->assertEquals(7, $clientInfo['hold_days']);
        $this->assertStringContainsString('7 business days', $clientInfo['description']);
    }
}
