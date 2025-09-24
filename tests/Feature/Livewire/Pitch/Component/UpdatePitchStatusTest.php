<?php

namespace Tests\Feature\Livewire\Pitch\Component;

use App\Livewire\Pitch\Component\UpdatePitchStatus;
use App\Models\Pitch;
use App\Models\User; // Assuming a Pitch model exists
use Livewire\Livewire;
use Tests\TestCase;

class UpdatePitchStatusTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();
        $pitch = Pitch::factory()->create(); // Adjust factory state if needed

        Livewire::actingAs($user)
            ->test(UpdatePitchStatus::class, ['pitch' => $pitch])
            ->assertOk();
    }
}
