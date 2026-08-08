<?php

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

function fakeRedditSocialiteUser(string $id, string $nickname, ?string $name = null): SocialiteUser
{
    return tap(new \Laravel\Socialite\Two\User, function ($user) use ($id, $nickname, $name) {
        $user->id = $id;
        $user->nickname = $nickname;
        $user->name = $name;
        $user->email = null; // Reddit's identity scope returns no email
        $user->avatar = null;
        $user->token = 'test-access-token';
        $user->refreshToken = 'test-refresh-token';
    });
}

function mockRedditSocialiteDriver(SocialiteUser $providerUser): void
{
    $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
    $provider->shouldReceive('user')->andReturn($providerUser);

    Socialite::shouldReceive('driver')->with('reddit')->andReturn($provider);
}

it('redirects to login with a friendly error when Reddit sign-in is not configured', function () {
    config(['services.reddit.client_id' => null]);

    $this->get('/auth/reddit/redirect')
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Reddit sign-in is not configured yet.');
});

it('redirects to login instead of erroring for an unsupported provider', function () {
    $response = $this->get('/auth/foobar/redirect');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'That sign-in method is not supported.');
});

it('shows a cancellation message when the user denies Reddit sign-in', function () {
    config(['services.reddit.client_id' => 'test-client-id']);

    $response = $this->get('/auth/reddit/callback?error=access_denied');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'You cancelled the sign-in.');
});

it('shows a generic error without leaking exception details when the callback throws', function () {
    Socialite::shouldReceive('driver')->with('reddit')->andThrow(new \Exception('SQLSTATE[23000]: duplicate key reddit_user_id'));

    $response = $this->get('/auth/reddit/callback?code=fake');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Sign-in failed, please try again.');
});

it('creates a new user from a Reddit OAuth callback with a synthesized email', function () {
    mockRedditSocialiteDriver(fakeRedditSocialiteUser('reddit_abc123', 'coolproducer', 'Cool Producer'));

    $response = $this->get('/auth/reddit/callback?code=fake');

    $response->assertRedirect('/dashboard');

    $user = User::where('provider', 'reddit')->where('provider_id', 'reddit_abc123')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Cool Producer');
    expect($user->email)->toBe('reddit_reddit_abc123@no-email.mixpitch.local');
    expect($user->username)->toBe('coolproducer');
    expect($user->hasVerifiedEmail())->toBeTrue();
    expect($user->provider_token)->toBe('test-access-token');
});

it('falls back to the Reddit nickname when the provider returns no display name', function () {
    mockRedditSocialiteDriver(fakeRedditSocialiteUser('reddit_xyz789', 'lurker99', null));

    $this->get('/auth/reddit/callback?code=fake')->assertRedirect('/dashboard');

    $user = User::where('provider_id', 'reddit_xyz789')->first();
    expect($user->name)->toBe('lurker99');
});

it('logs in an existing Reddit user without creating a duplicate', function () {
    $existing = User::factory()->create([
        'provider' => 'reddit',
        'provider_id' => 'reddit_existing',
        'email' => 'someone@example.com',
    ]);

    mockRedditSocialiteDriver(fakeRedditSocialiteUser('reddit_existing', 'someuser'));

    $response = $this->get('/auth/reddit/callback?code=fake');

    $response->assertRedirect('/dashboard');
    expect(auth()->id())->toBe($existing->id);
    expect(User::where('provider_id', 'reddit_existing')->count())->toBe(1);
});

it('does not accidentally link to an unrelated user when Reddit returns no email', function () {
    // Two other users exist with real emails; the null email from Reddit must not link to either.
    User::factory()->create(['email' => 'a@example.com']);
    User::factory()->create(['email' => 'b@example.com']);

    mockRedditSocialiteDriver(fakeRedditSocialiteUser('reddit_fresh', 'freshuser'));

    $this->get('/auth/reddit/callback?code=fake')->assertRedirect('/dashboard');

    $created = User::where('provider_id', 'reddit_fresh')->first();
    expect($created)->not->toBeNull();
    expect($created->email)->toBe('reddit_reddit_fresh@no-email.mixpitch.local');
    // The other two users must remain unmodified.
    expect(User::where('email', 'a@example.com')->first()->provider)->toBeNull();
    expect(User::where('email', 'b@example.com')->first()->provider)->toBeNull();
});
