<?php

namespace Tests\Feature\Livewire\Pitch;

use App\Livewire\Pitch\CompletePitch;
use App\Models\Pitch;
use App\Models\User; // Assuming a Pitch model exists
use Livewire\Livewire;
use Tests\TestCase;

class CompletePitchTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();
        $pitch = Pitch::factory()->create(); // Adjust factory state if needed

        Livewire::actingAs($user)
            ->test(CompletePitch::class, ['pitch' => $pitch])
            ->assertOk();
    }
}
