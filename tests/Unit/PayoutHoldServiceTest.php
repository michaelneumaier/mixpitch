<?php

namespace Tests\Unit;

use App\Models\PayoutHoldSetting;
use App\Models\PayoutSchedule;
use App\Models\User;
use App\Services\PayoutHoldService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PayoutHoldServiceTest extends TestCase
{
    use RefreshDatabase;

    private PayoutHoldService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new PayoutHoldService();
    }

    /** @test */
    public function it_calculates_hold_release_date_for_standard_workflow()
    {
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => [
                'standard' => 1,
                'contest' => 0,
                'client_management' => 0,
            ],
            'business_days_only' => false,
            'processing_time' => '09:00:00',
        ]);

        $releaseDate = $this->service->calculateHoldReleaseDate('standard');

        $this->assertEquals(now()->addDay()->setTime(9, 0, 0), $releaseDate);
    }

    /** @test */
    public function it_calculates_immediate_release_for_contest_workflow()
    {
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => [
                'standard' => 1,
                'contest' => 0,
                'client_management' => 0,
            ],
            'minimum_hold_hours' => 0,
        ]);

        $releaseDate = $this->service->calculateHoldReleaseDate('contest');

        // Should be immediate (now + 0 hours)
        $this->assertEqualsWithDelta(now()->timestamp, $releaseDate->timestamp, 5);
    }

    /** @test */
    public function it_calculates_immediate_release_for_client_management_workflow()
    {
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => [
                'standard' => 1,
                'contest' => 0,
                'client_management' => 0,
            ],
            'minimum_hold_hours' => 0,
        ]);

        $releaseDate = $this->service->calculateHoldReleaseDate('client_management');

        // Should be immediate (now + 0 hours)
        $this->assertEqualsWithDelta(now()->timestamp, $releaseDate->timestamp, 5);
    }

    /** @test */
    public function it_handles_disabled_hold_periods()
    {
        PayoutHoldSetting::factory()->disabled()->create([
            'minimum_hold_hours' => 2,
        ]);

        $releaseDate = $this->service->calculateHoldReleaseDate('standard');

        $this->assertEqualsWithDelta(now()->addHours(2)->timestamp, $releaseDate->timestamp, 5);
    }

    /** @test */
    public function it_calculates_business_days_correctly()
    {
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => ['standard' => 2],
            'business_days_only' => true,
            'processing_time' => '10:00:00',
        ]);

        // Set to Friday
        Carbon::setTestNow(Carbon::create(2024, 1, 5, 14, 0)); // Friday 2pm

        $releaseDate = $this->service->calculateHoldReleaseDate('standard');

        // Should skip weekend and add 2 business days (Mon + Tue = Tuesday 10am)
        $expectedDate = Carbon::create(2024, 1, 9, 10, 0); // Tuesday 10am
        
        $this->assertEquals($expectedDate, $releaseDate);
    }

    /** @test */
    public function it_handles_calendar_days_correctly()
    {
        PayoutHoldSetting::factory()->calendarDays()->create([
            'enabled' => true,
            'workflow_days' => ['standard' => 2],
            'processing_time' => '10:00:00',
        ]);

        // Set to Friday
        Carbon::setTestNow(Carbon::create(2024, 1, 5, 14, 0)); // Friday 2pm

        $releaseDate = $this->service->calculateHoldReleaseDate('standard');

        // Should include weekend (Friday + 2 days = Sunday 10am)
        $expectedDate = Carbon::create(2024, 1, 7, 10, 0); // Sunday 10am
        
        $this->assertEquals($expectedDate, $releaseDate);
    }

    /** @test */
    public function it_checks_admin_bypass_permissions_correctly()
    {
        $adminUser = User::factory()->create(['is_admin' => true]);
        $regularUser = User::factory()->create(['is_admin' => false]);

        PayoutHoldSetting::factory()->create(['allow_admin_bypass' => true]);

        $this->assertTrue($this->service->canBypassHold($adminUser));
        $this->assertFalse($this->service->canBypassHold($regularUser));
    }

    /** @test */
    public function it_prevents_bypass_when_disabled_in_settings()
    {
        $adminUser = User::factory()->create(['is_admin' => true]);

        PayoutHoldSetting::factory()->noAdminBypass()->create();

        $this->assertFalse($this->service->canBypassHold($adminUser));
    }

    /** @test */
    public function it_bypasses_hold_period_successfully()
    {
        $adminUser = User::factory()->create(['name' => 'Admin User', 'is_admin' => true]);

        $payout = PayoutSchedule::factory()->create([
            'hold_release_date' => now()->addDays(3),
            'hold_bypassed' => false,
        ]);

        PayoutHoldSetting::factory()->create([
            'allow_admin_bypass' => true,
            'require_bypass_reason' => true,
            'log_bypasses' => true,
            'minimum_hold_hours' => 1,
        ]);

        Log::shouldReceive('info')->once();

        $this->service->bypassHoldPeriod($payout, 'Urgent client request', $adminUser);

        $payout->refresh();
        
        $this->assertTrue($payout->hold_bypassed);
        $this->assertEquals('Urgent client request', $payout->bypass_reason);
        $this->assertEquals($adminUser->id, $payout->bypass_admin_id);
        $this->assertNotNull($payout->bypassed_at);
        $this->assertEqualsWithDelta(now()->addHour()->timestamp, $payout->hold_release_date->timestamp, 5);
    }

    /** @test */
    public function it_throws_exception_when_unauthorized_user_tries_bypass()
    {
        $regularUser = User::factory()->create(['is_admin' => false]);

        $payout = PayoutSchedule::factory()->create();
        PayoutHoldSetting::factory()->create(['allow_admin_bypass' => true]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthorized: Admin bypass not allowed or insufficient permissions');

        $this->service->bypassHoldPeriod($payout, 'Test reason', $regularUser);
    }

    /** @test */
    public function it_throws_exception_when_bypass_reason_required_but_empty()
    {
        $adminUser = User::factory()->create(['is_admin' => true]);

        $payout = PayoutSchedule::factory()->create();
        PayoutHoldSetting::factory()->create([
            'allow_admin_bypass' => true,
            'require_bypass_reason' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bypass reason is required');

        $this->service->bypassHoldPeriod($payout, '   ', $adminUser);
    }

    /** @test */
    public function it_provides_hold_period_info_correctly()
    {
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => ['standard' => 1],
            'business_days_only' => true,
            'processing_time' => '09:00:00',
        ]);

        $info = $this->service->getHoldPeriodInfo('standard');

        $this->assertTrue($info['enabled']);
        $this->assertEquals('standard', $info['workflow_type']);
        $this->assertEquals(1, $info['hold_days']);
        $this->assertTrue($info['business_days_only']);
        $this->assertEquals('09:00', $info['processing_time']);
        $this->assertFalse($info['is_immediate']);
        $this->assertStringContainsString('1 business day', $info['description']);
    }

    /** @test */
    public function it_provides_immediate_payout_info_for_zero_days()
    {
        PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'workflow_days' => ['contest' => 0],
            'minimum_hold_hours' => 0,
        ]);

        $info = $this->service->getHoldPeriodInfo('contest');

        $this->assertTrue($info['is_immediate']);
        $this->assertEquals('Immediate payout processing', $info['description']);
    }

    /** @test */
    public function it_provides_disabled_hold_period_info()
    {
        PayoutHoldSetting::factory()->disabled()->create();

        $info = $this->service->getHoldPeriodInfo('standard');

        $this->assertFalse($info['enabled']);
        $this->assertEquals('Hold periods are disabled - payouts processed immediately', $info['description']);
    }

    /** @test */
    public function it_updates_settings_with_admin_authorization()
    {
        $adminUser = User::factory()->create(['name' => 'Admin User', 'is_admin' => true]);

        $settings = PayoutHoldSetting::factory()->create();
        
        Log::shouldReceive('info')->once();

        $newData = ['enabled' => false, 'default_days' => 5];
        $updatedSettings = $this->service->updateSettings($newData, $adminUser);

        $this->assertFalse($updatedSettings->enabled);
        $this->assertEquals(5, $updatedSettings->default_days);
    }

    /** @test */
    public function it_throws_exception_when_non_admin_tries_to_update_settings()
    {
        $regularUser = User::factory()->create(['is_admin' => false]);

        PayoutHoldSetting::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthorized: Admin access required');

        $this->service->updateSettings(['enabled' => false], $regularUser);
    }
}
