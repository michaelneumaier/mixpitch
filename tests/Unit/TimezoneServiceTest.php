<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimezoneServiceTest extends TestCase
{
    use RefreshDatabase;

    private TimezoneService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TimezoneService();
    }

    /** @test */
    public function it_converts_utc_to_user_timezone()
    {
        $user = User::factory()->create(['timezone' => 'America/New_York']);
        $utcDate = Carbon::parse('2024-01-01 12:00:00', 'UTC');

        $userDate = $this->service->convertToUserTimezone($utcDate, $user);

        $this->assertEquals('America/New_York', $userDate->timezone->getName());
        $this->assertEquals('2024-01-01 07:00:00', $userDate->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_formats_date_for_user_timezone()
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $utcDate = Carbon::parse('2024-01-01 20:00:00', 'UTC');

        $formatted = $this->service->formatForUser($utcDate, $user, 'M j, Y g:i A');

        $this->assertEquals('Jan 1, 2024 12:00 PM', $formatted);
    }

    /** @test */
    public function it_validates_timezone_strings()
    {
        $this->assertTrue($this->service->validateTimezone('America/New_York'));
        $this->assertTrue($this->service->validateTimezone('Europe/London'));
        $this->assertFalse($this->service->validateTimezone('Invalid/Timezone'));
        $this->assertFalse($this->service->validateTimezone(''));
    }

    /** @test */
    public function it_gets_user_timezone()
    {
        $user = User::factory()->create(['timezone' => 'Europe/London']);
        
        $timezone = $this->service->getUserTimezone($user);
        
        $this->assertEquals('Europe/London', $timezone);
    }

    /** @test */
    public function it_defaults_to_est_for_users_without_timezone()
    {
        // Create a user and manually set timezone to null after creation
        $user = User::factory()->create();
        $user->update(['timezone' => null]);
        $user->refresh();
        
        $timezone = $this->service->getUserTimezone($user);
        
        $this->assertEquals('America/New_York', $timezone);
    }

    /** @test */
    public function it_defaults_to_est_for_no_user()
    {
        $timezone = $this->service->getUserTimezone(null);
        
        $this->assertEquals('America/New_York', $timezone);
    }

    /** @test */
    public function it_gets_available_timezones()
    {
        $timezones = $this->service->getAvailableTimezones();
        
        $this->assertIsArray($timezones);
        $this->assertArrayHasKey('America/New_York', $timezones);
        $this->assertArrayHasKey('America/Los_Angeles', $timezones);
        $this->assertArrayHasKey('Europe/London', $timezones);
    }

    /** @test */
    public function it_converts_user_input_to_utc()
    {
        $user = User::factory()->create(['timezone' => 'America/New_York']);
        
        $utcDate = $this->service->convertToUtc('2024-01-01 12:00:00', $user);
        
        $this->assertEquals('UTC', $utcDate->timezone->getName());
        $this->assertEquals('2024-01-01 17:00:00', $utcDate->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_gets_current_time_in_user_timezone()
    {
        $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00', 'UTC'));
        
        $userNow = $this->service->now($user);
        
        $this->assertEquals('Asia/Tokyo', $userNow->timezone->getName());
        $this->assertEquals('2024-01-01 21:00:00', $userNow->format('Y-m-d H:i:s'));
    }
} 