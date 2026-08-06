<?php

use App\Livewire\GlobalAudioPlayer;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('s3');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create a project and pitch owned by the test user so PitchFilePolicy allows access
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->pitch = Pitch::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
    ]);
});

it('can render the global audio player component', function () {
    Livewire::test(GlobalAudioPlayer::class)
        ->assertStatus(200)
        ->assertViewIs('livewire.global-audio-player');
});

it('starts hidden by default', function () {
    Livewire::test(GlobalAudioPlayer::class)
        ->assertSet('isVisible', false)
        ->assertSet('showMiniPlayer', false)
        ->assertSet('showFullPlayer', false);
});

it('can play a pitch file', function () {
    $pitchFile = PitchFile::factory()->create([
        'pitch_id' => $this->pitch->id,
        'user_id' => $this->user->id,
        'file_name' => 'test-audio.mp3',
        'duration' => 120,
    ]);

    Livewire::test(GlobalAudioPlayer::class)
        ->call('playPitchFile', $pitchFile->id)
        ->assertSet('isVisible', true)
        ->assertSet('showMiniPlayer', true)
        ->assertSet('duration', 120)
        ->assertDispatched('globalPlayerTrackChanged')
        ->assertDispatched('startPersistentAudio');
});

it('can toggle playback', function () {
    $pitchFile = PitchFile::factory()->create([
        'pitch_id' => $this->pitch->id,
        'user_id' => $this->user->id,
    ]);

    $component = Livewire::test(GlobalAudioPlayer::class);

    // First load a track
    $component->call('playPitchFile', $pitchFile->id)
        ->assertSet('isVisible', true);

    // Test toggle playback
    $component->call('togglePlayback')
        ->assertSet('isPlaying', true)
        ->assertDispatched('resumePlayback');

    $component->call('togglePlayback')
        ->assertSet('isPlaying', false)
        ->assertDispatched('pausePlayback');
});

it('can seek to position', function () {
    $pitchFile = PitchFile::factory()->create([
        'pitch_id' => $this->pitch->id,
        'user_id' => $this->user->id,
        'duration' => 120,
    ]);

    Livewire::test(GlobalAudioPlayer::class)
        ->call('playPitchFile', $pitchFile->id)
        ->call('seekTo', 60)
        ->assertSet('currentPosition', 60)
        ->assertDispatched('seekToPosition', timestamp: 60);
});

it('can adjust volume', function () {
    Livewire::test(GlobalAudioPlayer::class)
        ->call('setVolume', 0.5)
        ->assertSet('volume', 0.5)
        ->assertDispatched('volumeChanged', volume: 0.5);
});

it('can mute and unmute', function () {
    Livewire::test(GlobalAudioPlayer::class)
        ->call('toggleMute')
        ->assertSet('isMuted', true)
        ->assertDispatched('muteToggled', muted: true)
        ->call('toggleMute')
        ->assertSet('isMuted', false)
        ->assertDispatched('muteToggled', muted: false);
});

it('can show and hide full player', function () {
    Livewire::test(GlobalAudioPlayer::class)
        ->set('showFullPlayer', true)
        ->assertSet('showFullPlayer', true)
        ->call('hideFullPlayer')
        ->assertSet('showFullPlayer', false)
        ->assertSet('showComments', false);
});

it('can close the player', function () {
    $pitchFile = PitchFile::factory()->create([
        'pitch_id' => $this->pitch->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test(GlobalAudioPlayer::class)
        ->call('playPitchFile', $pitchFile->id)
        ->assertSet('isVisible', true)
        ->call('closePlayer')
        ->assertSet('isVisible', false)
        ->assertSet('showMiniPlayer', false)
        ->assertSet('showFullPlayer', false)
        ->assertSet('currentTrack', null)
        ->assertSet('isPlaying', false)
        ->assertDispatched('stopPlayback');
});

it('can add comments to pitch files', function () {
    $pitchFile = PitchFile::factory()->create([
        'pitch_id' => $this->pitch->id,
        'user_id' => $this->user->id,
    ]);

    $component = Livewire::test(GlobalAudioPlayer::class)
        ->call('playPitchFile', $pitchFile->id)
        ->assertSet('currentTrack.type', 'pitch_file');

    // Test adding a comment
    $component->set('newComment', 'This sounds great!')
        ->set('commentTimestamp', 30)
        ->call('addComment')
        ->assertSet('newComment', '')
        ->assertSet('showAddCommentForm', false);

    // Verify comment was created
    $pitchFile->refresh();
    expect($pitchFile->comments)->toHaveCount(1);
    expect($pitchFile->comments->first()->comment)->toBe('This sounds great!');
    expect((int) $pitchFile->comments->first()->timestamp)->toBe(30);
});

it('validates comment form', function () {
    $pitchFile = PitchFile::factory()->create([
        'pitch_id' => $this->pitch->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test(GlobalAudioPlayer::class)
        ->call('playPitchFile', $pitchFile->id)
        ->set('newComment', 'ab') // Too short
        ->set('commentTimestamp', 30)
        ->call('addComment')
        ->assertHasErrors(['newComment' => 'min']);
});

it('handles client mode for pitch files', function () {
    $pitchFile = PitchFile::factory()->create([
        'pitch_id' => $this->pitch->id,
        'user_id' => $this->user->id,
    ]);

    $component = Livewire::test(GlobalAudioPlayer::class)
        ->call('playPitchFile', $pitchFile->id, clientMode: true, clientEmail: 'client@example.com')
        ->assertSet('clientMode', true)
        ->assertSet('clientEmail', 'client@example.com');

    // Test client comment
    $component->set('newComment', 'Client feedback here')
        ->set('commentTimestamp', 45)
        ->call('addComment');

    $pitchFile->refresh();
    $comment = $pitchFile->comments->first();
    expect($comment->is_client_comment)->toBeTrue();
    expect($comment->client_email)->toBe('client@example.com');
    expect($comment->user_id)->toBeNull();
});

it('can get current track data', function () {
    $pitchFile = PitchFile::factory()->create([
        'pitch_id' => $this->pitch->id,
        'user_id' => $this->user->id,
        'file_name' => 'test-track.mp3',
        'original_file_name' => 'test-track.mp3',
        'duration' => 180,
    ]);

    $component = Livewire::test(GlobalAudioPlayer::class)
        ->call('playPitchFile', $pitchFile->id)
        ->assertSet('currentTrack.title', 'test-track.mp3')
        ->set('volume', 0.8)
        ->set('isMuted', true);

    $trackData = $component->instance()->getCurrentTrackData();

    expect($trackData)->toHaveKeys(['track', 'isPlaying', 'currentPosition', 'duration', 'volume', 'isMuted']);
    expect($trackData['track']['title'])->toBe('test-track.mp3');
    expect($trackData['volume'])->toBe(0.8);
    expect($trackData['isMuted'])->toBeTrue();
});

it('respects file permissions', function () {
    $anotherUser = User::factory()->create();
    $anotherPitch = Pitch::factory()->create(['user_id' => $anotherUser->id]);
    $pitchFile = PitchFile::factory()->create([
        'pitch_id' => $anotherPitch->id,
        'user_id' => $anotherUser->id,
    ]);

    // The test user should NOT have permission to view another user's pitch file
    $component = Livewire::test(GlobalAudioPlayer::class)
        ->call('playPitchFile', $pitchFile->id);

    // Player should not start playing the file due to permission failure
    expect($component->instance()->currentTrack)->toBeNull();
});

it('can handle generic track playback', function () {
    $track = [
        'type' => 'audio_file',
        'id' => 'test-123',
        'title' => 'Generic Audio',
        'url' => 'https://example.com/audio.mp3',
        'duration' => 240,
        'artist' => 'Test Artist',
        'has_comments' => false,
    ];

    Livewire::test(GlobalAudioPlayer::class)
        ->call('handlePlayTrack', $track)
        ->assertSet('isVisible', true)
        ->assertSet('duration', 240)
        ->assertDispatched('globalPlayerTrackChanged')
        ->assertDispatched('startPlayback');
});
