<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ProjectsComponent;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectsComponentTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProjectsComponent::class)
            ->assertOk();
    }
} 