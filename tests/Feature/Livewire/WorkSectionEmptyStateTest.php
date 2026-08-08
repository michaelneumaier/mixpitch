<?php

use App\Livewire\WorkSection;
use App\Models\User;
use Livewire\Livewire;

it('shows browse projects as the primary call to action for producers with no work', function () {
    $producer = User::factory()->create(['role' => User::ROLE_PRODUCER]);

    Livewire::actingAs($producer)
        ->test(WorkSection::class)
        ->assertSee('Find Your Next Project')
        ->assertSee('Browse Projects')
        ->assertDontSee('Ready to Start Creating?');
});

it('shows create project as the primary call to action for musicians with no work', function () {
    $musician = User::factory()->create(['role' => User::ROLE_CLIENT]);

    Livewire::actingAs($musician)
        ->test(WorkSection::class)
        ->assertSee('Ready to Start Creating?')
        ->assertSee('Create Project')
        ->assertDontSee('Find Your Next Project');
});
