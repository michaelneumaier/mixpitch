<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SnapshotFilePlayer;
use App\Models\PitchFile;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class SnapshotFilePlayerTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();
        $pitchFile = PitchFile::factory()->create([
            'file_name' => 'test_audio.mp3',
        ]);

        // Note: SnapshotFilePlayer likely requires a PitchFile or Snapshot model instance.
        Livewire::actingAs($user)
            ->test(SnapshotFilePlayer::class, ['file' => $pitchFile])
            ->assertOk();
    }
}
