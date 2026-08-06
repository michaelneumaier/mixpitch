<?php

use App\Livewire\AudioPlayer;
use App\Livewire\FileComparisonPlayer;
use App\Livewire\PitchFilePlayer;
use App\Livewire\SnapshotFilePlayer;
use App\Models\PitchFile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('s3');
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $this->actingAs($this->user);
});

it('can dispatch global player event from audio player', function () {
    Livewire::test(AudioPlayer::class, [
        'audioUrl' => 'https://example.com/audio.mp3',
        'trackTitle' => 'Test Track',
        'trackArtist' => 'Test Artist',
        'projectTitle' => 'Test Project',
    ])
        ->call('playInGlobalPlayer')
        ->assertDispatched('playTrack');
});

it('can dispatch global player event from pitch file player', function () {
    $pitchFile = PitchFile::factory()->create([
        'file_name' => 'test-pitch.mp3',
    ]);

    Livewire::test(PitchFilePlayer::class, [
        'file' => $pitchFile,
    ])
        ->call('playInGlobalPlayer')
        ->assertDispatched('playPitchFile');
});

it('can dispatch global player event from snapshot file player', function () {
    $pitchFile = PitchFile::factory()->create([
        'file_name' => 'test-snapshot.mp3',
    ]);

    Livewire::test(SnapshotFilePlayer::class, [
        'file' => $pitchFile,
    ])
        ->call('playInGlobalPlayer')
        ->assertDispatched('playPitchFile');
});

it('can dispatch global player events from file comparison player', function () {
    $leftFile = PitchFile::factory()->create(['file_name' => 'left.mp3']);
    $rightFile = PitchFile::factory()->create([
        'file_name' => 'right.mp3',
        'pitch_id' => $leftFile->pitch_id, // Same pitch for comparison
    ]);

    $component = Livewire::test(FileComparisonPlayer::class, [
        'leftFile' => $leftFile,
        'rightFile' => $rightFile,
    ]);

    $component->call('playLeftInGlobalPlayer')
        ->assertDispatched('playPitchFile');

    $component->call('playRightInGlobalPlayer')
        ->assertDispatched('playPitchFile');
});

it('audio player handles generic track data correctly', function () {
    $component = Livewire::test(AudioPlayer::class, [
        'audioUrl' => 'https://example.com/test.mp3',
        'trackTitle' => 'My Track',
        'trackArtist' => 'My Artist',
        'projectTitle' => 'My Project',
        'fileId' => 123,
    ]);

    // Call the method and verify dispatched event contains correct data
    $component->call('playInGlobalPlayer')
        ->assertDispatched('playTrack', function ($name, $data) {
            $trackData = $data['track'];

            return $trackData['title'] === 'My Track'
                && $trackData['artist'] === 'My Artist'
                && $trackData['project_title'] === 'My Project'
                && $trackData['type'] === 'audio_file';
        });
});

it('handles empty audio url gracefully', function () {
    Livewire::test(AudioPlayer::class, [
        'audioUrl' => '',
    ])
        ->call('playInGlobalPlayer')
        ->assertNotDispatched('playTrack');
});

it('pitch file player handles client mode correctly', function () {
    $pitchFile = PitchFile::factory()->create();

    Livewire::test(PitchFilePlayer::class, [
        'file' => $pitchFile,
        'clientMode' => true,
        'clientEmail' => 'client@example.com',
    ])
        ->call('playInGlobalPlayer')
        ->assertDispatched('playPitchFile',
            clientMode: true,
            clientEmail: 'client@example.com'
        );
});

it('all audio players have play in global player method', function () {
    expect(method_exists(AudioPlayer::class, 'playInGlobalPlayer'))->toBeTrue();
    expect(method_exists(PitchFilePlayer::class, 'playInGlobalPlayer'))->toBeTrue();
    expect(method_exists(SnapshotFilePlayer::class, 'playInGlobalPlayer'))->toBeTrue();
    expect(method_exists(FileComparisonPlayer::class, 'playLeftInGlobalPlayer'))->toBeTrue();
    expect(method_exists(FileComparisonPlayer::class, 'playRightInGlobalPlayer'))->toBeTrue();
});
