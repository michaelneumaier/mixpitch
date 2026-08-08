<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_login_screen_hides_reddit_button_when_not_configured(): void
    {
        config(['services.reddit.client_id' => null]);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('Sign in with Reddit');
    }

    public function test_login_screen_shows_reddit_button_when_configured(): void
    {
        config(['services.reddit.client_id' => 'test-client-id']);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign in with Reddit');
    }
}
