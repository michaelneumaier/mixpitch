<?php

namespace Tests\Feature\Livewire;

use App\Livewire\NotificationList;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationListTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertOk();
    }
} 