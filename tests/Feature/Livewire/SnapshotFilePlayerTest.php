<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SnapshotFilePlayer;
use App\Models\PitchFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SnapshotFilePlayerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function renders_successfully()
    {
        // Fake S3 storage to prevent null bucket errors
        Storage::fake('s3');

        $user = User::factory()->create();
        $pitchFile = PitchFile::factory()->create([
            'file_name' => 'test_audio.mp3',
            'size' => 1024000,
        ]);

        Livewire::actingAs($user)
            ->test(SnapshotFilePlayer::class, ['file' => $pitchFile])
            ->assertOk();
    }
}
