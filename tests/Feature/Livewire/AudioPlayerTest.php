<?php

namespace Tests\Feature\Livewire;

use App\Livewire\AudioPlayer;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AudioPlayerTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();

        // Note: AudioPlayer likely requires a source URL or model instance.
        // Adjust with required parameters if needed.
        Livewire::actingAs($user)
            ->test(AudioPlayer::class, ['audioUrl' => 'dummy.mp3']) // Use audioUrl parameter
            ->assertOk();
    }
}
