<?php

namespace Tests\Unit;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTimezoneTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_have_timezone_preference()
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        
        $this->assertEquals('America/Los_Angeles', $user->timezone);
        $this->assertEquals('America/Los_Angeles', $user->getTimezone());
    }

    /** @test */
    public function user_defaults_to_est_timezone()
    {
        // Create a user and manually set timezone to null after creation
        $user = User::factory()->create();
        $user->update(['timezone' => null]);
        $user->refresh();
        
        $this->assertEquals('America/New_York', $user->getTimezone());
    }

    /** @test */
    public function user_can_format_dates_in_their_timezone()
    {
        $user = User::factory()->create(['timezone' => 'Europe/London']);
        $utcDate = Carbon::parse('2024-01-01 12:00:00', 'UTC');
        
        $formatted = $user->formatDate($utcDate, 'M j, Y g:i A');
        
        $this->assertEquals('Jan 1, 2024 12:00 PM', $formatted);
    }

    /** @test */
    public function user_timezone_is_saved_properly()
    {
        $user = User::factory()->create();
        $user->timezone = 'Asia/Tokyo';
        $user->save();
        
        $user->refresh();
        
        $this->assertEquals('Asia/Tokyo', $user->timezone);
    }

    /** @test */
    public function user_now_method_returns_current_time_in_user_timezone()
    {
        $user = User::factory()->create(['timezone' => 'America/Chicago']);
        Carbon::setTestNow(Carbon::parse('2024-01-01 18:00:00', 'UTC'));
        
        $userNow = $user->now();
        
        $this->assertEquals('America/Chicago', $userNow->timezone->getName());
        $this->assertEquals('2024-01-01 12:00:00', $userNow->format('Y-m-d H:i:s'));
    }
} 