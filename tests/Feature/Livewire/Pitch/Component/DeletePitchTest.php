<?php

namespace Tests\Feature\Livewire\Pitch\Component;

use App\Livewire\Pitch\Component\DeletePitch;
use App\Models\Pitch;
use App\Models\User; // Assuming a Pitch model exists
use Livewire\Livewire;
use Tests\TestCase;

class DeletePitchTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();
        $pitch = Pitch::factory()->create(['user_id' => $user->id]); // Create pitch owned by user

        Livewire::actingAs($user)
            ->test(DeletePitch::class, ['pitch' => $pitch])
            ->assertOk();
    }
}
