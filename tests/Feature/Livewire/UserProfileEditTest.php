<?php

namespace Tests\Feature\Livewire;

use App\Livewire\UserProfileEdit;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class UserProfileEditTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UserProfileEdit::class)
            ->assertOk();
    }
} 