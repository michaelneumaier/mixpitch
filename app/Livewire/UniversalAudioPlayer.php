<?php

namespace App\Livewire;

use App\Models\PitchFileComment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UniversalAudioPlayer extends Component
{
    // File properties
    public $file;

    public $fileType; // 'pitch_file' or 'project_file'

    public $breadcrumbs = [];

    // Audio player state (inherited from GlobalAudioPlayer)
    public bool $isPlaying = false;

    public float $currentPosition = 0;

    public float $duration = 0;

    public float $volume = 1.0;

    public bool $isMuted = false;

    // Queue and playback control
    public array $queue = [];

    public int $queuePosition = 0;

    public string $repeatMode = 'off'; // 'off', 'one', 'all'

    public bool $shuffleMode = false;

    // A-B Loop
    public bool $loopEnabled = false;

    public ?float $loopStart = null;

    public ?float $loopEnd = null;

    public ?string $settingLoopPoint = null; // null, 'start', 'end'

    // Comments system (from PitchFilePlayer)
    public $comments = [];

    public $newComment = '';

    public $commentTimestamp = 0;

    public $showAddCommentForm = false;

    public $commentMarkers = [];

    public $replyToCommentId = null;

    public $showReplyForm = false;

    public $replyText = '';

    public $commentToDelete = null;

    public $showDeleteConfirmation = false;

    // Client mode (for pitch files)
    public $clientMode = false;

    public $clientEmail = '';

    public $showResolved = false;

    public $resolvedCount = 0;

    // UI state
    public bool $showComments = true;

    public bool $showQueue = true;

    protected $listeners = [
        'waveformReady' => 'onWaveformReady',
        'playbackStarted' => 'onPlaybackStarted',
        'playbackPaused' => 'onPlaybackPaused',
        'trackEnded' => 'onTrackEnded',
        'updatePosition' => 'updatePosition',
        'updateDuration' => 'updateDuration',
    ];

    public function mount($file, $fileType, $breadcrumbs = [], $clientMode = false, $clientEmail = '')
    {
        $this->file = $file;
        $this->fileType = $fileType;
        $this->breadcrumbs = $breadcrumbs;
        $this->clientMode = $clientMode;
        $this->clientEmail = $clientEmail;

        // Load comments if this is a pitch file
        if ($fileType === 'pitch_file') {
            $this->loadComments();
            $this->showComments = true;
        } else {
            $this->showComments = false;
        }

        // Initialize duration from stored file data
        if ($file->duration > 0) {
            $this->duration = $file->duration;
        }

        // Build contextual queue
        $this->buildContextualQueue();

        // Try to restore audio state from PWA if this file is currently playing
        $this->restoreAudioStateIfCurrent();
    }

    public function render()
    {
        return view('livewire.universal-audio-player');
    }

    // Audio Player Methods (from GlobalAudioPlayer)
    public function togglePlayback()
    {
        $this->isPlaying = ! $this->isPlaying;

        if ($this->isPlaying) {
            $this->dispatch('startPlayback');
            $this->dispatch('playbackStarted');
        } else {
            $this->dispatch('pausePlayback');
            $this->dispatch('playbackPaused');
        }
    }

    public function seekTo($position)
    {
        if (! is_numeric($position)) {
            return;
        }

        $this->currentPosition = (float) $position;
        $this->dispatch('seekToPosition', timestamp: $this->currentPosition);
    }

    public function setVolume($volume)
    {
        if (! is_numeric($volume)) {
            return;
        }

        $this->volume = max(0, min(1, (float) $volume));
        $this->dispatch('volumeChanged', volume: $this->volume);
    }

    public function toggleMute()
    {
        $this->isMuted = ! $this->isMuted;
        $this->dispatch('muteToggled', muted: $this->isMuted);
    }

    public function previousTrack()
    {
        if ($this->queuePosition > 0) {
            $this->queuePosition--;
            $this->loadQueueTrack($this->queuePosition);
        }
    }

    public function nextTrack()
    {
        if ($this->queuePosition < count($this->queue) - 1) {
            $this->queuePosition++;
            $this->loadQueueTrack($this->queuePosition);
        } elseif ($this->repeatMode === 'all') {
            $this->queuePosition = 0;
            $this->loadQueueTrack($this->queuePosition);
        }
    }

    // Queue Management
    protected function buildContextualQueue()
    {
        $this->queue = [];

        if ($this->fileType === 'pitch_file') {
            // Build queue from all audio files in the same pitch
            $pitchFiles = $this->file->pitch->pitchFiles()
                ->where('audio_processed', true)
                ->orderBy('created_at')
                ->get();

            foreach ($pitchFiles as $file) {
                $this->queue[] = [
                    'type' => 'pitch_file',
                    'id' => $file->uuid,
                    'title' => $file->file_name,
                    'artist' => $file->pitch->user->name ?? 'Unknown',
                    'url' => $file->full_file_path,
                    'duration' => $file->duration ?? 0,
                ];

                if ($file->id === $this->file->id) {
                    $this->queuePosition = count($this->queue) - 1;
                }
            }
        } else {
            // Build queue from all audio files in the same project
            $projectFiles = $this->file->project->projectFiles()
                ->where(function ($query) {
                    $query->where('mime_type', 'like', 'audio%')
                        ->orWhere('file_name', 'like', '%.mp3')
                        ->orWhere('file_name', 'like', '%.wav')
                        ->orWhere('file_name', 'like', '%.m4a')
                        ->orWhere('file_name', 'like', '%.ogg');
                })
                ->orderBy('created_at')
                ->get();

            foreach ($projectFiles as $file) {
                $this->queue[] = [
                    'type' => 'project_file',
                    'id' => $file->id,
                    'title' => $file->file_name,
                    'artist' => $file->project->user->name ?? 'Unknown',
                    'url' => route('download.project-file', $file->id),
                    'duration' => $file->duration ?? 0,
                ];

                if ($file->id === $this->file->id) {
                    $this->queuePosition = count($this->queue) - 1;
                }
            }
        }
    }

    protected function loadQueueTrack($index)
    {
        if (! isset($this->queue[$index])) {
            return;
        }

        $track = $this->queue[$index];

        // Navigate to the new track
        if ($track['type'] === 'pitch_file') {
            return redirect()->route('audio.pitch-file.show', $track['id']);
        } else {
            return redirect()->route('audio.project-file.show', $track['id']);
        }
    }

    // Comments System (from PitchFilePlayer)
    public function loadComments()
    {
        if ($this->fileType !== 'pitch_file') {
            return;
        }

        $baseQuery = $this->file->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user', 'replies.replies.user']);

        if ($this->clientMode) {
            $this->resolvedCount = $baseQuery->clone()->where('resolved', true)->count();
        }

        if ($this->clientMode && ! $this->showResolved) {
            $baseQuery->where('resolved', false);
        }

        $this->comments = $baseQuery->orderBy('timestamp')->get();
        $this->calculateCommentMarkers();
    }

    public function calculateCommentMarkers()
    {
        $this->commentMarkers = [];

        if ($this->duration > 0) {
            foreach ($this->comments as $comment) {
                $this->commentMarkers[] = [
                    'id' => $comment->id,
                    'timestamp' => $comment->timestamp,
                    'position' => ($comment->timestamp / $this->duration) * 100,
                    'comment' => $comment->comment,
                    'user' => $comment->user->name ?? 'Client',
                ];
            }
        }
    }

    public function toggleCommentForm()
    {
        $this->showAddCommentForm = ! $this->showAddCommentForm;
        if ($this->showAddCommentForm) {
            $this->commentTimestamp = $this->currentPosition;
        } else {
            $this->newComment = '';
        }
    }

    public function addComment()
    {
        if ($this->fileType !== 'pitch_file') {
            return;
        }

        $this->validate([
            'newComment' => 'required|string|max:1000',
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->file->id,
            'user_id' => Auth::id(),
            'client_email' => $this->clientMode ? $this->clientEmail : null,
            'comment' => $this->newComment,
            'timestamp' => $this->commentTimestamp,
            'is_client_comment' => $this->clientMode,
        ]);

        $this->newComment = '';
        $this->showAddCommentForm = false;
        $this->loadComments();
    }

    // A-B Loop Methods
    public function setLoopStart($position = null)
    {
        $this->loopStart = $position ?? $this->currentPosition;
        if ($this->loopEnd !== null && $this->loopStart >= $this->loopEnd) {
            $this->loopEnd = null;
            $this->loopEnabled = false;
        }
    }

    public function setLoopEnd($position = null)
    {
        $this->loopEnd = $position ?? $this->currentPosition;
        if ($this->loopStart !== null && $this->loopEnd <= $this->loopStart) {
            $this->loopStart = null;
            $this->loopEnabled = false;
        }
    }

    public function toggleLoop()
    {
        if ($this->loopStart !== null && $this->loopEnd !== null) {
            $this->loopEnabled = ! $this->loopEnabled;
        }
    }

    public function clearLoop()
    {
        $this->loopStart = null;
        $this->loopEnd = null;
        $this->loopEnabled = false;
        $this->settingLoopPoint = null;
    }

    // PWA State Management
    protected function restoreAudioStateIfCurrent()
    {
        // Dispatch event to check for saved audio state in service worker
        // The JavaScript will handle the actual restoration
        $this->dispatch('checkForSavedAudioState', [
            'fileType' => $this->fileType,
            'fileId' => $this->fileType === 'pitch_file' ? $this->file->uuid : $this->file->id,
        ]);
    }

    public function restoreSavedAudioState($savedState)
    {
        if (! is_array($savedState) || ! isset($savedState['track'])) {
            return;
        }

        $savedTrack = $savedState['track'];

        // Verify this is the same file
        $currentFileId = $this->fileType === 'pitch_file' ? $this->file->uuid : $this->file->id;
        $savedFileId = $savedTrack['type'] === 'pitch_file' ?
            (\App\Models\PitchFile::find($savedTrack['id'])?->uuid ?? $savedTrack['id']) :
            $savedTrack['id'];

        if ($savedTrack['type'] !== $this->fileType || $savedFileId !== $currentFileId) {
            return; // Not the same file
        }

        // Restore the saved state
        $this->isPlaying = $savedState['isPlaying'] ?? false;
        $this->currentPosition = $savedState['currentPosition'] ?? 0;
        $this->volume = $savedState['volume'] ?? 1.0;
        $this->isMuted = $savedState['isMuted'] ?? false;
        $this->repeatMode = $savedState['repeatMode'] ?? 'off';
        $this->shuffleMode = $savedState['shuffleMode'] ?? false;

        // Restore client mode if applicable
        if ($this->fileType === 'pitch_file') {
            $this->clientMode = $savedState['clientMode'] ?? false;
            $this->clientEmail = $savedState['clientEmail'] ?? '';
        }

        // Dispatch events to sync with JavaScript
        $this->dispatch('audioStateRestored', [
            'position' => $this->currentPosition,
            'isPlaying' => $this->isPlaying,
            'volume' => $this->volume,
            'isMuted' => $this->isMuted,
        ]);

        logger('UniversalAudioPlayer: Restored saved audio state', [
            'file_type' => $this->fileType,
            'file_id' => $currentFileId,
            'position' => $this->currentPosition,
            'isPlaying' => $this->isPlaying,
        ]);
    }

    // Event Handlers
    public function onWaveformReady()
    {
        // Handle waveform ready event
        $this->calculateCommentMarkers();
    }

    public function onPlaybackStarted()
    {
        $this->isPlaying = true;
    }

    public function onPlaybackPaused()
    {
        $this->isPlaying = false;
    }

    public function onTrackEnded()
    {
        $this->isPlaying = false;

        if ($this->repeatMode === 'one') {
            $this->currentPosition = 0;
            $this->dispatch('startPlayback');
        } else {
            $this->nextTrack();
        }
    }

    public function updatePosition($position)
    {
        if (is_numeric($position)) {
            $this->currentPosition = (float) $position;
        }
    }

    public function updateDuration($duration)
    {
        if (is_numeric($duration)) {
            $this->duration = (float) $duration;
            $this->calculateCommentMarkers();
        }
    }

    // Utility Methods
    public function formatTime($seconds)
    {
        if (! $seconds || ! is_numeric($seconds)) {
            return '00:00';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = floor($seconds % 60);

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    public function getFileUrl()
    {
        if ($this->fileType === 'pitch_file') {
            return $this->file->getFileUrl();
        } else {
            // Use the full_file_path attribute which generates signed S3 URLs
            return $this->file->full_file_path;
        }
    }

    public function canShowComments()
    {
        return $this->fileType === 'pitch_file' && ! empty($this->comments);
    }

    public function canAddComments()
    {
        return $this->fileType === 'pitch_file' && Auth::check();
    }

    public function getDownloadUrl()
    {
        if ($this->fileType === 'pitch_file') {
            // For pitch files, use the model's permission-based download URL
            return $this->file->getDownloadUrl(Auth::user(), 60);
        } else {
            // For project files, use the download route
            return route('download.project-file', $this->file->id);
        }
    }
}
