<?php

use App\Models\User;

it('shows a connect reddit chip in the profile setup banner when reddit is not linked', function () {
    $user = User::factory()->create([
        'username' => null,
        'bio' => null,
        'reddit_username' => null,
        'reddit_user_id' => null,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Not linked')
        ->assertSee(route('account.reddit.connect'));
});

it('shows the reddit chip as complete when the user has linked reddit', function () {
    $user = User::factory()->create([
        'username' => null,
        'bio' => null,
        'reddit_username' => 'linked_user',
        'reddit_user_id' => 't2_xyz789',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Not linked');
});
