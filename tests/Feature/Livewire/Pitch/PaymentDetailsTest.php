<?php

namespace Tests\Feature\Livewire\Pitch;

use App\Livewire\Pitch\PaymentDetails;
use App\Models\User;
use App\Models\Pitch; // Assuming a Pitch model exists
use Livewire\Livewire;
use Tests\TestCase;

class PaymentDetailsTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();
        $pitch = Pitch::factory()->create(); // Adjust factory state if needed

        Livewire::actingAs($user)
            ->test(PaymentDetails::class, ['pitch' => $pitch])
            ->assertOk();
    }
} 