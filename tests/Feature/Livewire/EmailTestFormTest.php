<?php

namespace Tests\Feature\Livewire;

use App\Livewire\EmailTestForm;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class EmailTestFormTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        // Assuming this component might need admin privileges or specific roles.
        $user = User::factory()->create(); // Adjust user factory if needed

        Livewire::actingAs($user)
            ->test(EmailTestForm::class)
            ->assertOk();
    }
}
