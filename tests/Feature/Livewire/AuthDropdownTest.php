<?php

namespace Tests\Feature\Livewire;

use App\Livewire\AuthDropdown;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AuthDropdownTest extends TestCase
{
    /** @test */
    public function renders_successfully_for_authenticated_user()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AuthDropdown::class)
            ->assertOk();
    }

    /** @test */
    public function renders_successfully_for_guest()
    {
        Livewire::test(AuthDropdown::class)
            ->assertOk();
    }
}
