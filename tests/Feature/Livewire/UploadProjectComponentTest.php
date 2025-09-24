<?php

namespace Tests\Feature\Livewire;

use App\Livewire\UploadProjectComponent;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class UploadProjectComponentTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UploadProjectComponent::class)
            ->assertOk();
    }
}
