<?php

namespace Tests\Feature;

use App\Models\PayoutHoldSetting;
use App\Models\PayoutSchedule;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\User;
use App\Services\PayoutHoldService;
use App\Services\PayoutProcessingService;
use App\Services\PitchWorkflowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PayoutHoldPeriodIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected PayoutHoldService $holdService;
    protected PayoutProcessingService $payoutService;
    protected PitchWorkflowService $pitchService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->holdService = app(PayoutHoldService::class);
        $this->payoutService = app(PayoutProcessingService::class);
        $this->pitchService = app(PitchWorkflowService::class);
    }

    /** @test */
    public function it_creates_payouts_with_correct_workflow_specific_hold_periods()
    {
        // Arrange: Configure different hold periods for each workflow
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'default_days' => 3,
            'workflow_days' => [
                'standard' => 1,
                'contest' => 0,  // Immediate
                'client_management' => 2,
            ],
            'business_days_only' => false, // Use calendar days for easier testing
        ]);

        $producer = User::factory()->create([
            'stripe_account_id' => 'acct_test123',
        ]);

        // Test Standard Project
        $standardProject = Project::factory()->configureWorkflow('standard')->create([
            'budget' => 1000
        ]);
        $standardPitch = Pitch::factory()->create([
            'project_id' => $standardProject->id,
            'user_id' => $producer->id,
            'payment_amount' => 1000
        ]);

        // Test Contest Project  
        $contestProject = Project::factory()->configureWorkflow('contest')->create([
            'budget' => 5000
        ]);
        $contestPitch = Pitch::factory()->create([
            'project_id' => $contestProject->id,
            'user_id' => $producer->id,
            'payment_amount' => 5000
        ]);

        // Test Client Management Project
        $clientProject = Project::factory()->configureWorkflow('client_management')->create([
            'budget' => 2000
        ]);
        $clientPitch = Pitch::factory()->create([
            'project_id' => $clientProject->id,
            'user_id' => $producer->id,
            'payment_amount' => 2000
        ]);

        // Act: Schedule payouts
        $standardPayout = $this->payoutService->schedulePayoutForPitch($standardPitch, 'stripe_invoice_123');
        $contestPayout = $this->payoutService->schedulePayoutForPitch($contestPitch, 'stripe_invoice_456');
        $clientPayout = $this->payoutService->schedulePayoutForPitch($clientPitch, 'stripe_invoice_789');

        // Assert: Verify hold periods are correct
        $now = now();
        
        // Standard: 1 day
        $this->assertEquals(
            $now->copy()->addDays(1)->format('Y-m-d'),
            $standardPayout->hold_release_date->format('Y-m-d')
        );
        
        // Contest: 0 days (immediate, but with minimum hold)
        $this->assertEquals(
            $now->format('Y-m-d'),
            $contestPayout->hold_release_date->format('Y-m-d')
        );
        
        // Client Management: 2 days
        $this->assertEquals(
            $now->copy()->addDays(2)->format('Y-m-d'),
            $clientPayout->hold_release_date->format('Y-m-d')
        );
    }

    /** @test */
    public function it_respects_business_days_only_setting()
    {
        // Arrange: Configure business days only
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'default_days' => 3,
            'workflow_days' => ['standard' => 3],
            'business_days_only' => true,
            'processing_time' => '09:00:00',
        ]);

        $producer = User::factory()->create(['stripe_account_id' => 'acct_test123']);
        $project = Project::factory()->configureWorkflow('standard')->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'payment_amount' => 1000
        ]);

        // Set current time to a Friday
        Carbon::setTestNow(Carbon::parse('2024-01-05 14:00:00')); // Friday

        // Act
        $payout = $this->payoutService->schedulePayoutForPitch($pitch, 'stripe_invoice_123');

        // Assert: Should skip weekend and land on Wednesday (3 business days)
        $expectedDate = Carbon::parse('2024-01-10 09:00:00'); // Following Wednesday at 9 AM
        $this->assertEquals(
            $expectedDate->format('Y-m-d H:i'),
            $payout->hold_release_date->format('Y-m-d H:i')
        );
    }

    /** @test */
    public function it_handles_disabled_hold_periods_correctly()
    {
        // Arrange: Disable hold periods but set minimum hold hours
        PayoutHoldSetting::factory()->create([
            'enabled' => false,
            'minimum_hold_hours' => 2,
        ]);

        $producer = User::factory()->create(['stripe_account_id' => 'acct_test123']);
        $project = Project::factory()->configureWorkflow('standard')->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'payment_amount' => 1000
        ]);

        Carbon::setTestNow(Carbon::parse('2024-01-05 14:00:00'));

        // Act
        $payout = $this->payoutService->schedulePayoutForPitch($pitch, 'stripe_invoice_123');

        // Assert: Should only have 2-hour minimum hold
        $expectedDate = Carbon::parse('2024-01-05 16:00:00'); // 2 hours later
        $this->assertEquals(
            $expectedDate->format('Y-m-d H:i'),
            $payout->hold_release_date->format('Y-m-d H:i')
        );
    }

    /** @test */
    public function it_updates_settings_and_applies_to_new_payouts()
    {
        // Arrange: Freeze time for consistent testing
        Carbon::setTestNow(Carbon::parse('2024-01-05 10:00:00'));
        
        // Initial settings
        $settings = PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => ['standard' => 1],
            'business_days_only' => false, // Use calendar days
            'processing_time' => '10:00:00',
        ]);

        $producer = User::factory()->create(['stripe_account_id' => 'acct_test123']);
        $project = Project::factory()->configureWorkflow('standard')->create();
        $pitch1 = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'payment_amount' => 1000
        ]);

        // Act: Create first payout with 1-day hold
        $payout1 = $this->payoutService->schedulePayoutForPitch($pitch1, 'invoice_1');
        
        // Update settings to 3 days
        $this->holdService->updateSettings([
            'enabled' => true,
            'default_days' => 3,
            'workflow_days' => ['standard' => 3],
            'business_days_only' => false,
            'processing_time' => '10:00:00',
            'minimum_hold_hours' => 0,
            'allow_admin_bypass' => true,
            'require_bypass_reason' => false,
            'log_bypasses' => true,
        ], User::factory()->create(['is_admin' => true]));

        // Create second payout with new settings
        $pitch2 = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'payment_amount' => 1000
        ]);
        $payout2 = $this->payoutService->schedulePayoutForPitch($pitch2, 'invoice_2');

        // Assert: Old payout has 1 day, new payout has 3 days
        $baseTime = Carbon::parse('2024-01-05 10:00:00');
        $this->assertEquals(
            $baseTime->copy()->addDays(1)->format('Y-m-d H:i'),
            $payout1->hold_release_date->format('Y-m-d H:i')
        );
        $this->assertEquals(
            $baseTime->copy()->addDays(3)->format('Y-m-d H:i'),
            $payout2->hold_release_date->format('Y-m-d H:i')
        );
    }

    /** @test */
    public function it_handles_admin_bypass_functionality()
    {
        // Arrange
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => ['standard' => 3],
            'allow_admin_bypass' => true,
            'require_bypass_reason' => true,
            'log_bypasses' => true,
            'minimum_hold_hours' => 1,
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $producer = User::factory()->create(['stripe_account_id' => 'acct_test123']);
        $project = Project::factory()->configureWorkflow('standard')->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'payment_amount' => 1000
        ]);

        $payout = $this->payoutService->schedulePayoutForPitch($pitch, 'invoice_123');
        $originalReleaseDate = $payout->hold_release_date;

        // Act: Bypass hold period
        $this->holdService->bypassHoldPeriod($payout, 'Emergency payout request', $admin);

        // Assert: Payout is marked as bypassed and has new release date
        $payout->refresh();
        $this->assertTrue($payout->hold_bypassed);
        $this->assertEquals('Emergency payout request', $payout->bypass_reason);
        $this->assertEquals($admin->id, $payout->bypass_admin_id);
        $this->assertNotNull($payout->bypassed_at);
        
        // Should be set to minimum hold (1 hour)
        $expectedDate = now()->addHours(1);
        $this->assertEquals(
            $expectedDate->format('Y-m-d H:i'),
            $payout->hold_release_date->format('Y-m-d H:i')
        );
    }

    /** @test */
    public function it_provides_correct_hold_period_information()
    {
        // Arrange: Different workflow configurations
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

        // Act & Assert: Test each workflow type
        $standardInfo = $this->holdService->getHoldPeriodInfo('standard');
        $this->assertEquals(3, $standardInfo['hold_days']);
        $this->assertTrue($standardInfo['business_days_only']);
        $this->assertStringContainsString('3 business days', $standardInfo['description']);

        $contestInfo = $this->holdService->getHoldPeriodInfo('contest');
        $this->assertEquals(0, $contestInfo['hold_days']);
        $this->assertStringContainsString('Immediate', $contestInfo['description']);

        $clientInfo = $this->holdService->getHoldPeriodInfo('client_management');
        $this->assertEquals(7, $clientInfo['hold_days']);
        $this->assertStringContainsString('7 business days', $clientInfo['description']);
    }

    /** @test */
    public function it_caches_settings_for_performance()
    {
        // Arrange
        Cache::flush();
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => ['standard' => 1],
        ]);

        // Act: Access settings multiple times
        $settings1 = PayoutHoldSetting::current();
        $settings2 = PayoutHoldSetting::current();
        $settings3 = PayoutHoldSetting::current();

        // Assert: Cache is working (should hit cache for subsequent calls)
        $this->assertTrue(Cache::has('payout_hold_settings'));
        $this->assertEquals($settings1->id, $settings2->id);
        $this->assertEquals($settings1->id, $settings3->id);
    }

    /** @test */
    public function it_integrates_with_client_management_workflow()
    {
        // Arrange
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => ['client_management' => 2],
            'business_days_only' => false,
        ]);

        $producer = User::factory()->create(['stripe_account_id' => 'acct_test123']);
        $project = Project::factory()->configureWorkflow('client_management', [
            'client_email' => 'client@example.com'
        ])->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'payment_amount' => 2000
        ]);

        // Act: Directly test payout scheduling with client management workflow
        $payout = $this->payoutService->schedulePayoutForPitch($pitch, 'invoice_789');

        // Assert: Payout was scheduled with client management hold period (2 days)
        $this->assertEquals('client_management', $payout->workflow_type);
        $expectedDate = now()->addDays(2);
        $this->assertEquals(
            $expectedDate->format('Y-m-d'),
            $payout->hold_release_date->format('Y-m-d')
        );
    }

    /** @test */
    public function it_handles_migration_command_correctly()
    {
        // Arrange: Freeze time for consistent testing
        Carbon::setTestNow(Carbon::parse('2024-01-05 10:00:00'));
        
        // Create old payout with hardcoded hold period
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => ['standard' => 2], // New setting: 2 days
            'business_days_only' => false, // Use calendar days
            'processing_time' => '10:00:00', // Same time as test
        ]);

        $payout = PayoutSchedule::factory()->create([
            'status' => PayoutSchedule::STATUS_SCHEDULED,
            'hold_release_date' => Carbon::parse('2024-01-08 10:00:00'), // Old: 3 days from now
            'workflow_type' => 'standard',
        ]);

        // Act: Run migration command
        $this->artisan('payouts:update-hold-periods')
            ->expectsQuestion('Do you want to proceed with updating these payouts?', 'yes')
            ->assertExitCode(0);

        // Assert: Payout was updated to use new hold period
        $payout->refresh();
        $expectedDate = Carbon::parse('2024-01-07 10:00:00'); // Should now be 2 days from frozen time
        $this->assertEquals(
            $expectedDate->format('Y-m-d H:i'),
            $payout->hold_release_date->format('Y-m-d H:i')
        );
        
        // Should have migration metadata
        $this->assertTrue($payout->metadata['hold_period_migrated'] ?? false);
        $this->assertNotNull($payout->metadata['migration_date'] ?? null);
    }

    /** @test */
    public function it_maintains_backward_compatibility()
    {
        // Arrange: Freeze time and don't create any PayoutHoldSetting (fallback behavior)
        Carbon::setTestNow(Carbon::parse('2024-01-05 10:00:00'));

        $producer = User::factory()->create(['stripe_account_id' => 'acct_test123']);
        $project = Project::factory()->configureWorkflow('standard')->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'payment_amount' => 1000
        ]);

        // Act: Should fall back to config defaults
        $payout = $this->payoutService->schedulePayoutForPitch($pitch, 'invoice_123');

        // Assert: Uses config default (3 days from business.php) with business days
        $expectedDays = config('business.payout_hold_settings.default_days', 3);
        $processingTime = config('business.payout_hold_settings.processing_time', '09:00:00');
        $useBusinessDays = config('business.payout_hold_settings.business_days_only', true);
        
        if ($useBusinessDays) {
            // Business days calculation from Friday 10:00 + 3 business days = Monday 09:00
            $expectedDate = Carbon::parse('2024-01-08 09:00:00');
        } else {
            $expectedDate = Carbon::parse('2024-01-05 10:00:00')->addDays($expectedDays);
        }
        
        $this->assertEquals(
            $expectedDate->format('Y-m-d H:i'),
            $payout->hold_release_date->format('Y-m-d H:i')
        );
    }
}
