<?php

namespace Tests\Feature\Livewire\Pitch\Component;

use App\Livewire\Pitch\Component\PitchHistory;
use App\Models\Pitch;
use App\Models\User; // Assuming a Pitch model exists
use Livewire\Livewire;
use Tests\TestCase;

class PitchHistoryTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();
        $pitch = Pitch::factory()->create(); // Adjust factory state if needed

        Livewire::actingAs($user)
            ->test(PitchHistory::class, ['pitch' => $pitch])
            ->assertOk();
    }
}
