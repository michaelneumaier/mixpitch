<?php

namespace App\Livewire;

use App\Models\PitchFile;
use App\Models\PitchFileComment;
use App\Models\ProjectFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class GlobalAudioPlayer extends Component
{
    // Current playback state
    public ?array $currentTrack = null;

    public bool $isPlaying = false;

    public bool $isVisible = false;

    public float $currentPosition = 0;

    public float $duration = 0;

    public bool $isMuted = false;

    public float $volume = 1.0;

    // Track queue management
    public array $queue = [];

    public int $queuePosition = 0;

    public bool $shuffleMode = false;

    public string $repeatMode = 'off'; // 'off', 'one', 'all'

    // UI state
    public bool $showMiniPlayer = false;

    public bool $showFullPlayer = false;

    public bool $showComments = false;

    // Comment system state (inherited from PitchFilePlayer)
    public array $comments = [];

    public string $newComment = '';

    public float $commentTimestamp = 0;

    public bool $showAddCommentForm = false;

    public array $commentMarkers = [];

    public ?int $replyToCommentId = null;

    public bool $showReplyForm = false;

    public string $replyText = '';

    // Client mode properties
    public bool $clientMode = false;

    public string $clientEmail = '';

    public bool $showResolved = false;

    public int $resolvedCount = 0;

    protected $listeners = [
        'playTrack' => 'handlePlayTrack',
        'playPitchFile' => 'playPitchFile',
        'playProjectFile' => 'playProjectFile',
        'togglePlayback' => 'togglePlayback',
        'nextTrack' => 'nextTrack',
        'previousTrack' => 'previousTrack',
        'toggleMute' => 'toggleMute',
        'closePlayer' => 'closePlayer',
        'navigateToFullPlayer' => 'navigateToFullPlayer',
        'hideFullPlayer' => 'hideFullPlayer',
        'waveformReady' => 'onWaveformReady',
        'playbackStarted' => 'onPlaybackStarted',
        'playbackPaused' => 'onPlaybackPaused',
        'trackEnded' => 'onTrackEnded',
        'persistent-audio-update' => 'handlePersistentAudioUpdate',
    ];

    public function mount()
    {
        // Initialize with empty state
        $this->currentTrack = null;
        $this->isVisible = false;
    }

    /**
     * Play a PitchFile in the global player
     */
    public function playPitchFile($pitchFileId, $clientMode = false, $clientEmail = '')
    {
        if (! is_numeric($pitchFileId)) {
            return;
        }

        $pitchFile = PitchFile::find((int) $pitchFileId);

        if (! $pitchFile) {
            return;
        }

        // Check permissions
        if (! Auth::check() || ! Auth::user()->can('view', $pitchFile)) {
            return;
        }

        $this->clientMode = $clientMode;
        $this->clientEmail = $clientEmail;

        $this->currentTrack = [
            'type' => 'pitch_file',
            'id' => $pitchFile->id,
            'title' => $pitchFile->file_name,
            'url' => $pitchFile->getStreamingUrl(Auth::user()),
            'duration' => $pitchFile->duration ?? 0,
            'waveform_data' => $pitchFile->waveform_data,
            'pitch_id' => $pitchFile->pitch_id,
            'project_id' => $pitchFile->pitch->project_id,
            'artist' => $pitchFile->pitch->user->name ?? 'Unknown Artist',
            'project_title' => $pitchFile->pitch->project->project_title ?? '',
            'file_size' => $pitchFile->file_size,
            'created_at' => $pitchFile->created_at->toISOString(),
            'has_comments' => $pitchFile->comments()->exists(),
        ];

        // Build contextual queue - all pitch files from this project
        $this->buildPitchFileQueue($pitchFile);

        $this->duration = $pitchFile->duration ?? 0;
        $this->loadComments($pitchFile);
        $this->showPlayer();

        $this->dispatch('globalPlayerTrackChanged', track: $this->currentTrack, queue: $this->queue, queuePosition: $this->queuePosition);
        $this->dispatch('startPersistentAudio', track: $this->currentTrack);
    }

    /**
     * Play a ProjectFile in the global player with smart queue
     */
    public function playProjectFile($projectFileId)
    {
        \Log::info('GlobalAudioPlayer playProjectFile called', [
            'project_file_id' => $projectFileId,
            'user_id' => Auth::id(),
            'is_authenticated' => Auth::check(),
        ]);

        if (! is_numeric($projectFileId)) {
            return;
        }

        $projectFile = ProjectFile::with('project')->find((int) $projectFileId);

        if (! $projectFile) {
            \Log::warning('ProjectFile not found for playProjectFile', [
                'project_file_id' => $projectFileId,
            ]);

            return;
        }

        // Check permissions
        if (! Auth::check() || ! Auth::user()->can('view', $projectFile)) {
            \Log::warning('Authorization failed for playProjectFile', [
                'user_id' => Auth::id(),
                'project_file_id' => $projectFileId,
                'project_id' => $projectFile->project_id,
                'auth_check' => Auth::check(),
            ]);

            return;
        }

        // Check if we can get the file URL
        $fileUrl = $projectFile->full_file_path;

        if (! $fileUrl) {
            return;
        }

        $this->currentTrack = [
            'type' => 'project_file',
            'id' => $projectFile->id,
            'title' => $projectFile->file_name,
            'url' => $fileUrl,
            'duration' => 0, // ProjectFile doesn't have duration stored
            'project_id' => $projectFile->project_id,
            'artist' => $projectFile->project->user->name ?? 'Project Owner',
            'project_title' => $projectFile->project->title ?? '',
            'file_size' => $projectFile->size,
            'created_at' => $projectFile->created_at->toISOString(),
            'has_comments' => false, // Project files don't have comments like pitch files
        ];

        // Build contextual queue - all audio files from this project
        $this->buildProjectFileQueue($projectFile);

        $this->duration = 0; // Will be set when audio loads
        $this->comments = []; // No comments for project files
        $this->showPlayer();

        $this->dispatch('globalPlayerTrackChanged', track: $this->currentTrack, queue: $this->queue, queuePosition: $this->queuePosition);
        $this->dispatch('startPersistentAudio', track: $this->currentTrack);
    }

    /**
     * Handle the playTrack event from other components
     */
    public function handlePlayTrack($track = null)
    {
        if (! is_array($track)) {
            return;
        }

        $this->playTrack($track);
    }

    /**
     * Generic track playback (for backwards compatibility)
     */
    protected function playTrack($track)
    {
        if (! is_array($track)) {
            return;
        }

        $this->currentTrack = $track;
        $this->duration = $track['duration'] ?? 0;
        $this->showPlayer();

        $this->dispatch('globalPlayerTrackChanged', track: $this->currentTrack);
        $this->dispatch('startPlayback');
    }

    public function togglePlayback()
    {
        if ($this->currentTrack) {
            $this->isPlaying = ! $this->isPlaying;

            if ($this->isPlaying) {
                $this->dispatch('resumePlayback');
            } else {
                $this->dispatch('pausePlayback');
            }
        }
    }

    /**
     * Seek to a specific time position (called directly from JavaScript)
     */
    public function seekTo($time)
    {
        if (! is_numeric($time)) {
            return;
        }

        $time = (float) $time;
        if ($this->currentTrack && $time >= 0 && $time <= $this->duration) {
            $this->currentPosition = $time;
            $this->dispatch('seekToPosition', timestamp: $time);
        }
    }

    public function nextTrack()
    {
        if (count($this->queue) > 0) {
            if ($this->queuePosition < count($this->queue) - 1) {
                $this->queuePosition++;
            } elseif ($this->repeatMode === 'all') {
                $this->queuePosition = 0;
            } else {
                return; // End of queue
            }

            $nextTrack = $this->queue[$this->queuePosition];
            $this->playTrackFromQueue($nextTrack);
        }
    }

    public function previousTrack()
    {
        if (count($this->queue) > 0 && $this->queuePosition > 0) {
            $this->queuePosition--;
            $prevTrack = $this->queue[$this->queuePosition];
            $this->playTrackFromQueue($prevTrack);
        }
    }

    /**
     * Play a track from the queue without rebuilding the queue
     */
    protected function playTrackFromQueue($track)
    {
        $this->currentTrack = $track;
        $this->duration = $track['duration'] ?? 0;

        // Load comments if it's a pitch file
        if ($track['type'] === 'pitch_file') {
            $pitchFile = PitchFile::find($track['id']);
            if ($pitchFile) {
                $this->loadComments($pitchFile);
            }
        } else {
            $this->comments = [];
        }

        $this->showPlayer();
        $this->dispatch('globalPlayerTrackChanged', track: $this->currentTrack, queue: $this->queue, queuePosition: $this->queuePosition);
        $this->dispatch('startPersistentAudio', track: $this->currentTrack);
    }

    /**
     * Build contextual queue for project files
     */
    protected function buildProjectFileQueue(ProjectFile $currentFile)
    {
        // Get all audio files from the same project
        $audioFiles = ProjectFile::where('project_id', $currentFile->project_id)
            ->whereNotNull('mime_type')
            ->where(function ($query) {
                $query->where('mime_type', 'like', 'audio/%')
                    ->orWhere('mime_type', 'like', 'video/%'); // Include video files that might have audio
            })
            ->orderBy('created_at')
            ->get();

        $this->queue = [];
        $currentFilePosition = 0;

        foreach ($audioFiles as $index => $file) {
            if ($file->full_file_path) {
                $track = [
                    'type' => 'project_file',
                    'id' => $file->id,
                    'title' => $file->file_name,
                    'url' => $file->full_file_path,
                    'duration' => 0,
                    'project_id' => $file->project_id,
                    'artist' => $file->project->user->name ?? 'Project Owner',
                    'project_title' => $file->project->title ?? '',
                    'file_size' => $file->size,
                    'created_at' => $file->created_at->toISOString(),
                    'has_comments' => false,
                ];

                $this->queue[] = $track;

                if ($file->id === $currentFile->id) {
                    $currentFilePosition = count($this->queue) - 1;
                }
            }
        }

        $this->queuePosition = $currentFilePosition;
    }

    /**
     * Build contextual queue for pitch files
     */
    protected function buildPitchFileQueue(PitchFile $currentFile)
    {
        // Get all pitch files from the same project
        $pitchFiles = PitchFile::whereHas('pitch', function ($query) use ($currentFile) {
            $query->where('project_id', $currentFile->pitch->project_id);
        })
            ->with(['pitch.user', 'pitch.project'])
            ->orderBy('created_at')
            ->get();

        $this->queue = [];
        $currentFilePosition = 0;

        foreach ($pitchFiles as $index => $file) {
            $track = [
                'type' => 'pitch_file',
                'id' => $file->id,
                'title' => $file->file_name,
                'url' => $file->getStreamingUrl(Auth::user()),
                'duration' => $file->duration ?? 0,
                'waveform_data' => $file->waveform_data,
                'pitch_id' => $file->pitch_id,
                'project_id' => $file->pitch->project_id,
                'artist' => $file->pitch->user->name ?? 'Unknown Artist',
                'project_title' => $file->pitch->project->project_title ?? '',
                'file_size' => $file->file_size,
                'created_at' => $file->created_at->toISOString(),
                'has_comments' => $file->comments()->exists(),
            ];

            $this->queue[] = $track;

            if ($file->id === $currentFile->id) {
                $currentFilePosition = count($this->queue) - 1;
            }
        }

        $this->queuePosition = $currentFilePosition;
    }

    /**
     * Set the volume level (called directly from JavaScript)
     */
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

    /**
     * Update the current playback position (called directly from JavaScript)
     */
    public function updatePosition($position)
    {
        if (! is_numeric($position)) {
            return;
        }

        $newPosition = (float) $position;

        // Only update if the position has changed significantly to prevent unnecessary re-renders
        if (abs($newPosition - $this->currentPosition) < 0.5) {
            return;
        }

        $this->currentPosition = $newPosition;

        // Update PWA media session
        $this->dispatch('updateMediaSession',
            position: $this->currentPosition,
            duration: $this->duration
        );
    }

    /**
     * Update the track duration (called directly from JavaScript)
     */
    public function updateDuration($duration)
    {
        if (! is_numeric($duration)) {
            return;
        }

        $this->duration = (float) $duration;
        $this->calculateCommentMarkers();
    }

    public function showPlayer()
    {
        // Only update if the state is actually changing to prevent flickering
        if (! $this->isVisible || ! $this->showMiniPlayer) {
            $this->isVisible = true;
            $this->showMiniPlayer = true;
        }
    }

    public function closePlayer()
    {
        $this->isVisible = false;
        $this->showMiniPlayer = false;
        $this->showFullPlayer = false;
        $this->currentTrack = null;
        $this->isPlaying = false;
        $this->currentPosition = 0;
        $this->duration = 0;
        $this->comments = [];
        $this->dispatch('stopPlayback');
        $this->dispatch('playerClosed'); // Let frontend know player was closed
    }

    public function navigateToFullPlayer()
    {
        logger('GlobalAudioPlayer: navigateToFullPlayer method called, navigating to universal player');

        if (! $this->currentTrack) {
            logger('GlobalAudioPlayer: No current track, cannot show full player');

            return;
        }

        // Save current audio state to PWA service worker for seamless continuation
        $this->saveAudioStateForNavigation();

        // Navigate to the appropriate universal audio player route
        if ($this->currentTrack['type'] === 'pitch_file') {
            // For pitch files, use UUID if available, otherwise fall back to ID
            $pitchFile = \App\Models\PitchFile::find($this->currentTrack['id']);
            if ($pitchFile) {
                return redirect()->route('audio.pitch-file.show', ['file' => $pitchFile->uuid]);
            }
        } elseif ($this->currentTrack['type'] === 'project_file') {
            // For project files, use the ID directly
            return redirect()->route('audio.project-file.show', ['file' => $this->currentTrack['id']]);
        }

        // Fallback to generic universal audio player route
        return redirect()->route('audio.show', [
            'type' => $this->currentTrack['type'],
            'id' => $this->currentTrack['type'] === 'pitch_file' ?
                (\App\Models\PitchFile::find($this->currentTrack['id'])?->uuid ?? $this->currentTrack['id']) :
                $this->currentTrack['id'],
        ]);
    }

    /**
     * Save current audio state to service worker for seamless navigation
     */
    protected function saveAudioStateForNavigation()
    {
        if (! $this->currentTrack) {
            return;
        }

        $audioState = [
            'track' => $this->currentTrack,
            'isPlaying' => $this->isPlaying,
            'currentPosition' => $this->currentPosition,
            'duration' => $this->duration,
            'volume' => $this->volume,
            'isMuted' => $this->isMuted,
            'queue' => $this->queue,
            'queuePosition' => $this->queuePosition,
            'repeatMode' => $this->repeatMode,
            'shuffleMode' => $this->shuffleMode,
            'clientMode' => $this->clientMode,
            'clientEmail' => $this->clientEmail,
        ];

        // Dispatch event to save state via JavaScript to service worker
        $this->dispatch('saveAudioStateForNavigation', state: $audioState);
    }

    public function hideFullPlayer()
    {
        $this->showFullPlayer = false;
        $this->showComments = false;
        $this->showAddCommentForm = false;
        $this->showReplyForm = false;
    }

    // Comment system methods (adapted from PitchFilePlayer)
    protected function loadComments(PitchFile $file)
    {
        if (! $file) {
            $this->comments = [];

            return;
        }

        $baseQuery = $file->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user', 'replies.replies.user']);

        if ($this->clientMode) {
            $this->resolvedCount = $baseQuery->clone()->where('resolved', true)->count();
        }

        if ($this->clientMode && ! $this->showResolved) {
            $baseQuery->where('resolved', false);
        }

        $this->comments = $baseQuery->orderBy('timestamp')->get()->toArray();
        $this->calculateCommentMarkers();
    }

    public function calculateCommentMarkers()
    {
        $this->commentMarkers = [];

        if ($this->duration > 0 && ! empty($this->comments)) {
            foreach ($this->comments as $comment) {
                $this->commentMarkers[] = [
                    'id' => $comment['id'],
                    'timestamp' => $comment['timestamp'],
                    'position' => ($comment['timestamp'] / $this->duration) * 100,
                    'resolved' => $comment['resolved'] ?? false,
                    'comment' => $comment['comment'] ?? '',
                ];
            }
        }

        // Dispatch event to update JavaScript comment markers
        $this->dispatch('commentMarkersUpdated', comments: $this->commentMarkers);
    }

    public function toggleCommentForm($timestamp = null)
    {
        $this->showAddCommentForm = ! $this->showAddCommentForm;

        if ($this->showAddCommentForm && $timestamp !== null) {
            $this->commentTimestamp = $timestamp;
            $this->dispatch('pausePlayback');
        } elseif ($this->showAddCommentForm) {
            $this->commentTimestamp = $this->currentPosition;
        }
    }

    public function addComment()
    {
        if (! $this->currentTrack || $this->currentTrack['type'] !== 'pitch_file') {
            return;
        }

        $this->validate([
            'newComment' => 'required|min:3',
            'commentTimestamp' => 'required|numeric|min:0',
        ]);

        $pitchFile = PitchFile::find($this->currentTrack['id']);
        if (! $pitchFile) {
            return;
        }

        $comment = new PitchFileComment;
        $comment->pitch_file_id = $pitchFile->id;

        if ($this->clientMode) {
            $comment->user_id = null;
            $comment->client_email = $this->clientEmail;
            $comment->is_client_comment = true;
        } else {
            $comment->user_id = Auth::id();
            $comment->is_client_comment = false;
        }

        $comment->comment = $this->newComment;
        $comment->timestamp = $this->commentTimestamp;
        $comment->resolved = false;
        $comment->save();

        // Send notification
        try {
            if ($this->clientMode) {
                app(\App\Services\NotificationService::class)->notifyProducerClientCommented(
                    $pitchFile->pitch,
                    $this->newComment
                );
            } else {
                app(\App\Services\NotificationService::class)->notifyPitchFileComment(
                    $pitchFile,
                    $comment,
                    Auth::id()
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send global player comment notification', [
                'error' => $e->getMessage(),
                'file_id' => $pitchFile->id,
                'client_mode' => $this->clientMode,
            ]);
        }

        $this->newComment = '';
        $this->showAddCommentForm = false;
        $this->loadComments($pitchFile);

        $this->dispatch('commentAdded');
    }

    // Event handlers
    public function onWaveformReady()
    {
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
            $this->seekTo(0);
            $this->dispatch('startPlayback');
        } elseif (count($this->queue) > 0) {
            $this->nextTrack();
        }

        // Let Alpine store handle auto-advancing through queue
        $this->dispatch('trackEnded');
    }

    public function handlePersistentAudioUpdate($data)
    {
        if (! isset($data['property']) || ! isset($data['value'])) {
            return;
        }

        switch ($data['property']) {
            case 'isPlaying':
                $this->isPlaying = $data['value'];
                break;
            case 'currentPosition':
                $this->currentPosition = $data['value'];
                break;
            case 'duration':
                $this->duration = $data['value'];
                break;
        }

        // Skip updating the component to avoid loops
    }

    /**
     * Update the queue order after drag and drop reordering
     */
    public function updateQueueOrder($newQueue, $newQueuePosition)
    {
        if (! is_array($newQueue) || ! is_numeric($newQueuePosition)) {
            return;
        }

        $this->queue = $newQueue;
        $this->queuePosition = (int) $newQueuePosition;

        // Update the current track if the queue position changed
        if (isset($this->queue[$this->queuePosition])) {
            $this->currentTrack = $this->queue[$this->queuePosition];
        }
    }

    /**
     * Jump to a specific position in the queue and play that track
     */
    public function jumpToQueuePosition($index)
    {
        if (! is_numeric($index) || $index < 0 || $index >= count($this->queue)) {
            return;
        }

        $index = (int) $index;
        $this->queuePosition = $index;
        $track = $this->queue[$index];

        if (! $track) {
            return;
        }

        $this->playTrackFromQueue($track);
    }

    public function getCurrentTrackData(): array
    {
        return [
            'track' => $this->currentTrack,
            'isPlaying' => $this->isPlaying,
            'currentPosition' => $this->currentPosition,
            'duration' => $this->duration,
            'volume' => $this->volume,
            'isMuted' => $this->isMuted,
        ];
    }

    /**
     * Format seconds into MM:SS format
     */
    public function formatTime($seconds): string
    {
        if (! $seconds || ! is_numeric($seconds)) {
            return '00:00';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = floor($seconds % 60);

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    public function render()
    {
        return view('livewire.global-audio-player');
    }
}
