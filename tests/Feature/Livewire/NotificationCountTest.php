<?php

namespace Tests\Feature\Livewire;

use App\Livewire\NotificationCount;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationCountTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCount::class)
            ->assertOk();
    }
} 