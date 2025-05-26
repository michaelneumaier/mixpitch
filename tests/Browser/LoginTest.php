<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use RefreshDatabase;

    /**
     * Test if the login page loads correctly.
     */
    public function testLoginPageLoadsCorrectly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->assertPathIs('/login')
                    ->assertVisible('input[name="email"]')
                    ->assertVisible('input[name="password"]')
                    ->assertVisible('button[type="submit"]');
        });
    }

    /**
     * Test successful user login.
     */
    public function testUserCanLoginSuccessfully(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                    ->type('email', $user->email)
                    ->type('password', 'password')
                    ->press('button[type="submit"]')
                    ->assertPathIs('/projects')
                    ->assertAuthenticatedAs($user);
        });
    }

    /**
     * Test failed user login with incorrect credentials.
     */
    public function testUserLoginFailsWithIncorrectCredentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                    ->type('email', $user->email)
                    ->type('password', 'wrong-password')
                    ->press('button[type="submit"]')
                    ->assertPathIs('/login')
                    ->assertSee('These credentials do not match our records.')
                    ->assertGuest();
        });
    }
}
