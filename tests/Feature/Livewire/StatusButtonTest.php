<?php

namespace Tests\Feature\Livewire;

use App\Livewire\StatusButton;
use App\Models\User;
use App\Models\Pitch; // Assuming a Pitch model exists
use Livewire\Livewire;
use Tests\TestCase;

class StatusButtonTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();
        // StatusButton likely depends on a model like Pitch or Project
        $pitch = Pitch::factory()->create(); // Adjust based on actual usage

        Livewire::actingAs($user)
            ->test(StatusButton::class, ['model' => $pitch]) // Pass the required model
            ->assertOk();
    }
} 