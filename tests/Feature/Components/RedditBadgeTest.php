<?php

use App\Models\User;
use Illuminate\Support\Facades\Blade;

it('renders the badge with username and account year when user has linked Reddit', function () {
    $user = User::factory()->create([
        'reddit_username' => 'testuser',
        'reddit_user_id' => 'reddit_id_1',
        'reddit_account_created_at' => \Carbon\Carbon::create(2018, 6, 15),
    ]);

    $html = Blade::render('<x-reddit-badge :user="$user" />', ['user' => $user]);

    expect($html)->toContain('u/testuser');
    expect($html)->toContain('since 2018');
    expect($html)->toContain('https://www.reddit.com/user/testuser');
    expect($html)->toContain('target="_blank"');
    expect($html)->toContain('rel="noopener noreferrer"');
});

it('renders the badge without the "since YYYY" fragment when account_created_at is null', function () {
    $user = User::factory()->create([
        'reddit_username' => 'quietuser',
        'reddit_user_id' => 'reddit_id_2',
        'reddit_account_created_at' => null,
    ]);

    $html = Blade::render('<x-reddit-badge :user="$user" />', ['user' => $user]);

    expect($html)->toContain('u/quietuser');
    expect($html)->not->toContain('since');
});

/**
 * "Renders nothing" means no visible badge markup. Livewire v3's Blade
 * precompiler wraps every @if in <!--[if BLOCK]--> comment markers, so
 * the compiled output of a false condition is never a byte-empty string —
 * assert on the absence of badge content instead.
 */
it('renders nothing when the user has no linked Reddit account', function () {
    $user = User::factory()->create([
        'reddit_username' => null,
        'reddit_user_id' => null,
    ]);

    $html = Blade::render('<x-reddit-badge :user="$user" />', ['user' => $user]);

    expect($html)->not->toContain('reddit.com');
    expect($html)->not->toContain('<a ');
});

it('renders nothing when the user prop is null', function () {
    $html = Blade::render('<x-reddit-badge :user="$user" />', ['user' => null]);

    expect($html)->not->toContain('reddit.com');
    expect($html)->not->toContain('<a ');
});
