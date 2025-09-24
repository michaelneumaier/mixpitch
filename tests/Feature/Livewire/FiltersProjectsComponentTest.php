<?php

namespace Tests\Feature\Livewire;

use App\Livewire\FiltersProjectsComponent;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class FiltersProjectsComponentTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FiltersProjectsComponent::class)
            ->assertOk();
    }
}
