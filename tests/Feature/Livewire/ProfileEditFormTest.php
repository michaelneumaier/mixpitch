<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ProfileEditForm;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileEditFormTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileEditForm::class)
            ->assertOk();
    }
} 