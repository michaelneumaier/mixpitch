<?php

namespace Tests\Feature\Livewire\Pitch\Component;

use App\Livewire\Pitch\Component\ConfirmStatusChange;
use App\Models\User;
use App\Models\Pitch; // Assuming a Pitch model exists
use Livewire\Livewire;
use Tests\TestCase;

class ConfirmStatusChangeTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();
        $pitch = Pitch::factory()->create(); // Adjust factory state if needed

        // This component might be intended to be rendered conditionally/via dispatch.
        // A simple render test might not cover its full usage.
        Livewire::actingAs($user)
            ->test(ConfirmStatusChange::class, ['pitch' => $pitch]) // Provide necessary props
            ->assertOk();
    }
} 