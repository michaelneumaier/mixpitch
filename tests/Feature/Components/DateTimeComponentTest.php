<?php

namespace Tests\Feature\Components;

use App\Models\User;
use App\View\Components\DateTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateTimeComponentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_date_in_user_timezone()
    {
        // Create a user with Los Angeles timezone
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $this->actingAs($user);

        // Create a UTC date
        $utcDate = Carbon::parse('2024-01-01 20:00:00', 'UTC');

        // Create the component
        $component = new DateTime($utcDate, 'M j, Y g:i A');

        // The formatted date should be in user's timezone (PST)
        $formatted = $component->getFormattedDate();
        $this->assertEquals('Jan 1, 2024 12:00 PM', $formatted);
    }

    /** @test */
    public function it_renders_relative_dates_in_user_timezone()
    {
        $user = User::factory()->create(['timezone' => 'America/New_York']);
        $this->actingAs($user);

        // Mock current time to make relative dates predictable
        Carbon::setTestNow(Carbon::parse('2024-01-01 15:00:00', 'UTC'));
        
        // Create a date 2 hours ago in UTC
        $pastDate = Carbon::parse('2024-01-01 13:00:00', 'UTC');

        $component = new DateTime($pastDate, null, '', true);
        $relative = $component->getRelativeDate();
        
        $this->assertEquals('2 hours ago', $relative);
    }

    /** @test */
    public function it_generates_iso_date_in_user_timezone()
    {
        $user = User::factory()->create(['timezone' => 'Europe/London']);
        $this->actingAs($user);

        $utcDate = Carbon::parse('2024-01-01 12:00:00', 'UTC');
        $component = new DateTime($utcDate);

        $isoDate = $component->getIsoDate();
        
        // Should be converted to London time and formatted as ISO
        $this->assertStringContainsString('2024-01-01T12:00:00', $isoDate);
    }

    /** @test */
    public function it_handles_guest_users_gracefully()
    {
        // No authenticated user (guest)
        $utcDate = Carbon::parse('2024-01-01 20:00:00', 'UTC');
        $component = new DateTime($utcDate, 'M j, Y g:i A');

        // Should use default timezone (EST)
        $formatted = $component->getFormattedDate();
        $this->assertEquals('Jan 1, 2024 3:00 PM', $formatted);
    }

    /** @test */
    public function it_renders_view_with_proper_attributes()
    {
        $user = User::factory()->create(['timezone' => 'America/Chicago']);
        $this->actingAs($user);

        $date = Carbon::parse('2024-01-01 18:00:00', 'UTC');
        
        $view = $this->blade(
            '<x-datetime :date="$date" format="M j, Y" class="test-class" />',
            ['date' => $date]
        );

        $view->assertSee('Jan 1, 2024'); // Should show in user's timezone
        $view->assertSee('test-class', false); // Check raw HTML for class
        $view->assertSee('datetime=', false); // Should have datetime attribute
    }

    /** @test */
    public function it_displays_date_in_specific_user_timezone()
    {
        // Create two users with different timezones
        $estUser = User::factory()->create(['timezone' => 'America/New_York']);
        $pstUser = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        
        // Create a test date in UTC: Jan 1, 2024 at 8:00 PM UTC
        $utcDate = Carbon::createFromFormat('Y-m-d H:i:s', '2024-01-01 20:00:00', 'UTC');
        
        // Test with EST user's timezone (should be 3:00 PM EST)
        $component = new DateTime($utcDate, 'g:i A T', '', false, true, $estUser);
        $formatted = $component->getFormattedDate();
        $this->assertStringContainsString('3:00 PM EST', $formatted);
        
        // Test with PST user's timezone (should be 12:00 PM PST)
        $component = new DateTime($utcDate, 'g:i A T', '', false, true, $pstUser);
        $formatted = $component->getFormattedDate();
        $this->assertStringContainsString('12:00 PM PST', $formatted);
    }

    /** @test */
    public function it_converts_to_viewer_timezone_when_convert_to_viewer_is_true()
    {
        // Create contest owner (EST) and viewer (MDT)
        $contestOwner = User::factory()->create(['timezone' => 'America/New_York']);
        $viewer = User::factory()->create(['timezone' => 'America/Denver']);
        
        // Authenticate as viewer
        auth()->login($viewer);
        
        // Create a test date in summer when DST is active: 9:00 PM EDT = 1:00 AM UTC next day
        $deadline = Carbon::createFromFormat('Y-m-d H:i:s', '2024-07-15 21:00:00', 'America/New_York')->utc();
        
        // Test convertToViewer=true: should show in viewer's timezone (MDT) = 7:00 PM MDT
        $component = new DateTime($deadline, 'g:i A T', '', false, true, $contestOwner, true);
        $formatted = $component->getFormattedDate();
        $this->assertStringContainsString('7:00 PM', $formatted);
        $this->assertStringContainsString('MDT', $formatted);
        
        // Test convertToViewer=false: should show in contest owner's timezone (EDT) = 9:00 PM EDT
        $component = new DateTime($deadline, 'g:i A T', '', false, true, $contestOwner, false);
        $formatted = $component->getFormattedDate();
        $this->assertStringContainsString('9:00 PM', $formatted);
        $this->assertStringContainsString('EDT', $formatted);
    }
} 