<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Socialite config
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    public function test_oauth_redirect_works()
    {
        $response = $this->get('/auth/google/redirect');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('accounts.google.com', $response->getTargetUrl());
    }

    public function test_new_oauth_user_is_auto_verified()
    {
        // Mock the Socialite user
        $mockSocialiteUser = Mockery::mock(SocialiteUser::class);
        $mockSocialiteUser->shouldReceive('getId')->andReturn('google-user-123');
        $mockSocialiteUser->shouldReceive('getName')->andReturn('John Doe');
        $mockSocialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $mockSocialiteUser->shouldReceive('getNickname')->andReturn('johndoe');
        $mockSocialiteUser->token = 'mock-token';
        $mockSocialiteUser->refreshToken = 'mock-refresh-token';

        // Mock the Socialite facade
        Socialite::shouldReceive('driver->user')->andReturn($mockSocialiteUser);

        // Ensure no user exists with this email
        $this->assertDatabaseMissing('users', ['email' => 'john@example.com']);

        // Make the callback request
        $response = $this->get('/auth/google/callback');

        // Assert user was created and auto-verified
        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at, 'OAuth user should be auto-verified');
        $this->assertEquals('google', $user->provider);
        $this->assertEquals('google-user-123', $user->provider_id);
        $this->assertEquals('mock-token', $user->provider_token);

        // Assert user is logged in
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());

        // Assert redirected to dashboard (not email verification)
        $response->assertRedirect('/dashboard');
    }

    public function test_existing_oauth_user_login()
    {
        // Create an existing OAuth user
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'provider' => 'google',
            'provider_id' => 'google-user-456',
            'provider_token' => 'old-token',
            'email_verified_at' => now(),
        ]);

        // Mock the Socialite user
        $mockSocialiteUser = Mockery::mock(SocialiteUser::class);
        $mockSocialiteUser->shouldReceive('getId')->andReturn('google-user-456');
        $mockSocialiteUser->shouldReceive('getName')->andReturn('Existing User');
        $mockSocialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $mockSocialiteUser->shouldReceive('getNickname')->andReturn('existinguser');
        $mockSocialiteUser->token = 'new-token';
        $mockSocialiteUser->refreshToken = 'new-refresh-token';

        // Mock the Socialite facade
        Socialite::shouldReceive('driver->user')->andReturn($mockSocialiteUser);

        // Make the callback request
        $response = $this->get('/auth/google/callback');

        // Assert user token was updated
        $existingUser->refresh();
        $this->assertEquals('new-token', $existingUser->provider_token);
        $this->assertEquals('new-refresh-token', $existingUser->provider_refresh_token);

        // Assert user is logged in
        $this->assertTrue(Auth::check());
        $this->assertEquals($existingUser->id, Auth::id());

        // Assert redirected to dashboard
        $response->assertRedirect('/dashboard');
    }

    public function test_linking_oauth_to_existing_email_user()
    {
        // Create an existing user with just email (no OAuth)
        $existingUser = User::factory()->create([
            'email' => 'test@example.com',
            'provider' => null,
            'provider_id' => null,
            'email_verified_at' => null, // Not verified yet
        ]);

        // Mock the Socialite user
        $mockSocialiteUser = Mockery::mock(SocialiteUser::class);
        $mockSocialiteUser->shouldReceive('getId')->andReturn('google-user-789');
        $mockSocialiteUser->shouldReceive('getName')->andReturn('Test User');
        $mockSocialiteUser->shouldReceive('getEmail')->andReturn('test@example.com');
        $mockSocialiteUser->shouldReceive('getNickname')->andReturn('testuser');
        $mockSocialiteUser->token = 'link-token';
        $mockSocialiteUser->refreshToken = 'link-refresh-token';

        // Mock the Socialite facade
        Socialite::shouldReceive('driver->user')->andReturn($mockSocialiteUser);

        // Make the callback request
        $response = $this->get('/auth/google/callback');

        // Assert user was updated with OAuth data and auto-verified
        $existingUser->refresh();
        $this->assertEquals('google', $existingUser->provider);
        $this->assertEquals('google-user-789', $existingUser->provider_id);
        $this->assertEquals('link-token', $existingUser->provider_token);
        $this->assertNotNull($existingUser->email_verified_at, 'Linked OAuth user should be auto-verified');

        // Assert user is logged in
        $this->assertTrue(Auth::check());
        $this->assertEquals($existingUser->id, Auth::id());

        // Assert redirected to dashboard
        $response->assertRedirect('/dashboard');
    }

    public function test_oauth_user_can_access_verified_routes()
    {
        // Create and login an OAuth user
        $user = User::factory()->create([
            'email' => 'oauth@example.com',
            'provider' => 'google',
            'provider_id' => 'google-user-verified',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        // Test that they can access routes that require verification
        $response = $this->get('/profile/edit');
        $response->assertStatus(200);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_failed_oauth_callback_redirects_with_error()
    {
        // Mock Socialite to throw an exception
        Socialite::shouldReceive('driver->user')->andThrow(new \Exception('OAuth failed'));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Something went wrong with social login', session('error'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
