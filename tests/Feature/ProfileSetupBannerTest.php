<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileSetupBannerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function profile_setup_banner_shows_for_users_with_incomplete_profiles()
    {
        // Create user with incomplete profile (missing username and bio)
        $user = User::factory()->create([
            'username' => null,
            'bio' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Complete Your Profile');
        $response->assertSee('Set Up Profile');
        $response->assertSee('0%'); // Should show 0% completion
    }

    /** @test */
    public function profile_setup_banner_shows_for_users_with_partial_profiles()
    {
        // Create user with partial profile (has username but no bio)
        $user = User::factory()->create([
            'username' => 'testuser',
            'bio' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Complete Your Profile');
        $response->assertSee('20%'); // Should show 20% completion (1 out of 5 fields)
    }

    /** @test */
    public function profile_setup_banner_does_not_show_for_users_with_complete_profiles()
    {
        // Create user with complete profile (has username and bio)
        $user = User::factory()->create([
            'username' => 'testuser',
            'bio' => 'This is my bio',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('Complete Your Profile');
        $response->assertDontSee('Set Up Profile');
    }

    /** @test */
    public function profile_completion_status_calculates_correctly()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'bio' => 'This is my bio',
            'location' => 'New York',
            'website' => null,
            'social_links' => null,
        ]);

        $status = $user->getProfileCompletionStatus();

        $this->assertTrue($status['is_complete']); // Has username and bio
        $this->assertEquals(60, $status['percentage']); // 3 out of 5 fields = 60%
        $this->assertEquals(3, $status['completed_count']);
        $this->assertEquals(5, $status['total_count']);
        
        // Check specific fields
        $this->assertTrue($status['fields']['username']);
        $this->assertTrue($status['fields']['bio']);
        $this->assertTrue($status['fields']['location']);
        $this->assertFalse($status['fields']['website']);
        $this->assertFalse($status['fields']['social_links']);
    }

    /** @test */
    public function missing_profile_fields_returns_correct_fields()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'bio' => null,
            'location' => null,
            'website' => 'https://example.com',
            'social_links' => null,
        ]);

        $missingFields = $user->getMissingProfileFields();

        $this->assertContains('bio', $missingFields);
        $this->assertContains('location', $missingFields);
        $this->assertContains('social_links', $missingFields);
        $this->assertNotContains('username', $missingFields);
        $this->assertNotContains('website', $missingFields);
    }
} 