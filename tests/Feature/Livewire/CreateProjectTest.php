<?php

namespace Tests\Feature\Livewire;

use App\Livewire\CreateProject;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class CreateProjectTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->assertOk();
    }
}
