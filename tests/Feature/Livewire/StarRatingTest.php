<?php

namespace Tests\Feature\Livewire;

use App\Livewire\StarRating;
use App\Models\User;
use App\Models\Mix;
use Livewire\Livewire;
use Tests\TestCase;

class StarRatingTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();
        $mix = Mix::factory()->create();

        // StarRating might require an initial rating or related model.
        // Assuming it can render without parameters for now.
        Livewire::actingAs($user)
            ->test(StarRating::class, [
                'rating' => 0,
                'mix' => $mix
            ])
            ->assertOk();
    }
} 