<?php

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

function fakeRedditLinkUser(string $id, string $nickname, ?int $createdUtc = null): SocialiteUser
{
    return tap(new \Laravel\Socialite\Two\User, function ($user) use ($id, $nickname, $createdUtc) {
        $user->id = $id;
        $user->nickname = $nickname;
        $user->name = null;
        $user->email = null;
        $user->avatar = null;
        $user->token = 'link-access-token';
        $user->refreshToken = 'link-refresh-token';
        $user->user = ['created_utc' => $createdUtc]; // Socialite base sets raw via user attribute
    });
}

function mockRedditLinkDriver(SocialiteUser $providerUser): void
{
    $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
    $provider->shouldReceive('redirectUrl')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($providerUser);

    Socialite::shouldReceive('driver')->with('reddit')->andReturn($provider);
}

it('requires authentication to access the connect flow', function () {
    $this->get('/account/reddit/connect')->assertRedirect(route('login'));
    $this->get('/account/reddit/callback')->assertRedirect(route('login'));
    $this->post('/account/reddit/disconnect')->assertRedirect(route('login'));
});

it('populates reddit_* columns when a logged-in user completes the link flow', function () {
    $user = User::factory()->create([
        'provider' => 'google',
        'provider_id' => 'google-123',
        'reddit_username' => null,
        'reddit_user_id' => null,
    ]);

    // 2015-01-01 as unix timestamp
    mockRedditLinkDriver(fakeRedditLinkUser('reddit_id_abc', 'coolredditor', 1420070400));

    $response = $this->actingAs($user)->get('/account/reddit/callback?code=fake');

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('success');

    $user->refresh();
    expect($user->reddit_user_id)->toBe('reddit_id_abc');
    expect($user->reddit_username)->toBe('coolredditor');
    expect($user->reddit_account_created_at?->year)->toBe(2015);
    expect($user->reddit_linked_at)->not->toBeNull();
    // Primary auth (Google) is unchanged
    expect($user->provider)->toBe('google');
});

it('refuses to link a Reddit account already owned by another user', function () {
    $owner = User::factory()->create(['reddit_user_id' => 'reddit_id_taken', 'reddit_username' => 'someone_else']);
    $current = User::factory()->create();

    mockRedditLinkDriver(fakeRedditLinkUser('reddit_id_taken', 'someone_else'));

    $response = $this->actingAs($current)->get('/account/reddit/callback?code=fake');

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('error');

    expect($current->fresh()->reddit_user_id)->toBeNull();
    expect($owner->fresh()->reddit_user_id)->toBe('reddit_id_taken');
});

it('nullifies reddit_* columns on disconnect', function () {
    $user = User::factory()->create([
        'reddit_username' => 'oldusername',
        'reddit_user_id' => 'oldid',
        'reddit_account_created_at' => now()->subYears(3),
        'reddit_linked_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->post('/account/reddit/disconnect');

    $response->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->reddit_username)->toBeNull();
    expect($user->reddit_user_id)->toBeNull();
    expect($user->reddit_account_created_at)->toBeNull();
    expect($user->reddit_linked_at)->toBeNull();
});

it('populates reddit_* on primary Reddit signup so the badge works uniformly', function () {
    // Reuses the primary auth callback via the existing SocialiteController
    $providerUser = tap(new \Laravel\Socialite\Two\User, function ($u) {
        $u->id = 'primary_reddit_user';
        $u->nickname = 'primaryredditor';
        $u->name = null;
        $u->email = null;
        $u->token = 'tok';
        $u->refreshToken = 'rtok';
        $u->user = ['created_utc' => 1420070400]; // 2015-01-01
    });

    $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
    $provider->shouldReceive('user')->andReturn($providerUser);
    Socialite::shouldReceive('driver')->with('reddit')->andReturn($provider);

    $this->get('/auth/reddit/callback?code=fake')->assertRedirect('/dashboard');

    $user = User::where('provider_id', 'primary_reddit_user')->first();
    expect($user)->not->toBeNull();
    expect($user->reddit_username)->toBe('primaryredditor');
    expect($user->reddit_user_id)->toBe('primary_reddit_user');
    expect($user->reddit_account_created_at?->year)->toBe(2015);
    expect($user->hasLinkedReddit())->toBeTrue();
});
