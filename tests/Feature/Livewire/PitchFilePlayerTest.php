<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PitchFilePlayer;
use App\Models\User;
use App\Models\PitchFile;
use Livewire\Livewire;
use Tests\TestCase;

class PitchFilePlayerTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $pitchFile = PitchFile::factory()->create([
            'created_at' => now(),
            // Ensure required fields for PitchFile are set by factory or here
            // e.g., 'file_name' => 'test.mp3', 'file_path' => 'dummy/test.mp3'
        ]);

        // Minimal test without actingAs for now
        Livewire::test(PitchFilePlayer::class, ['file' => $pitchFile])
            ->assertOk();
    }
} 