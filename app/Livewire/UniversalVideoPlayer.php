<?php

namespace App\Livewire;

use App\Models\FileComment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UniversalVideoPlayer extends Component
{
    // File properties
    public $file;

    public $fileType; // 'pitch_file' or 'project_file'

    public $breadcrumbs = [];

    // Video player state
    public bool $isPlaying = false;

    public float $currentPosition = 0;

    public float $duration = 0;

    public float $volume = 1.0;

    public bool $isMuted = false;

    public bool $isFullscreen = false;

    // Playback control
    public string $playbackRate = '1.0'; // '0.5', '1.0', '1.25', '1.5', '2.0'

    // Comments system
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

    // Error handling
    public $errorMessage = '';

    public $showError = false;

    protected $listeners = [
        'videoReady' => 'onVideoReady',
        'playbackStarted' => 'onPlaybackStarted',
        'playbackPaused' => 'onPlaybackPaused',
        'videoEnded' => 'onVideoEnded',
        'updatePosition' => 'updatePosition',
        'updateDuration' => 'updateDuration',
        'togglePlayback' => 'togglePlayback',
        'skipForward' => 'skipForward',
        'skipBackward' => 'skipBackward',
        'toggleFullscreen' => 'toggleFullscreen',
        'showVideoError' => 'showVideoError',
    ];

    public function mount($file, $fileType, $breadcrumbs = [], $clientMode = false, $clientEmail = '')
    {
        $this->file = $file;
        $this->fileType = $fileType;
        $this->breadcrumbs = $breadcrumbs;
        $this->clientMode = $clientMode;
        $this->clientEmail = $clientEmail;

        // Load comments for both pitch files and project files
        $this->loadComments();
        $this->showComments = true;

        // Initialize duration from stored file data if available
        if (isset($file->duration) && $file->duration > 0) {
            $this->duration = $file->duration;
        }

        // Try to restore video state from PWA if this file is currently playing
        $this->restoreVideoStateIfCurrent();
    }

    public function render()
    {
        return view('livewire.universal-video-player');
    }

    // Video Player Methods
    public function togglePlayback()
    {
        $this->isPlaying = ! $this->isPlaying;

        if ($this->isPlaying) {
            $this->dispatch('startVideoPlayback');
            $this->dispatch('playbackStarted');
        } else {
            $this->dispatch('pauseVideoPlayback');
            $this->dispatch('playbackPaused');
        }
    }

    public function seekTo($position)
    {
        if (! is_numeric($position)) {
            return;
        }

        $this->currentPosition = (float) $position;
        $this->dispatch('seekToVideoPosition', timestamp: $this->currentPosition);
    }

    public function setVolume($volume)
    {
        if (! is_numeric($volume)) {
            return;
        }

        $this->volume = max(0, min(1, (float) $volume));
        $this->dispatch('videoVolumeChanged', volume: $this->volume);
    }

    public function toggleMute()
    {
        $this->isMuted = ! $this->isMuted;
        $this->dispatch('videoMuteToggled', muted: $this->isMuted);
    }

    public function toggleFullscreen()
    {
        $this->isFullscreen = ! $this->isFullscreen;
        $this->dispatch('videoFullscreenToggled', fullscreen: $this->isFullscreen);
    }

    public function setPlaybackRate($rate)
    {
        $allowedRates = ['0.5', '0.75', '1.0', '1.25', '1.5', '2.0'];
        if (in_array($rate, $allowedRates)) {
            $this->playbackRate = $rate;
            $this->dispatch('videoPlaybackRateChanged', rate: (float) $rate);
        }
    }

    public function skipForward($seconds = 10)
    {
        $newPosition = min($this->currentPosition + $seconds, $this->duration);
        $this->seekTo($newPosition);
    }

    public function skipBackward($seconds = 10)
    {
        $newPosition = max($this->currentPosition - $seconds, 0);
        $this->seekTo($newPosition);
    }

    // Comments System
    public function loadComments()
    {
        $baseQuery = $this->file->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user', 'replies.replies.user']);

        if ($this->clientMode && $this->fileType === 'pitch_file') {
            $this->resolvedCount = $baseQuery->clone()->where('resolved', true)->count();
        }

        if ($this->clientMode && ! $this->showResolved && $this->fileType === 'pitch_file') {
            $baseQuery->where('resolved', false);
        }

        $this->comments = $baseQuery->orderBy('timestamp')->get();
        $this->calculateCommentMarkers();
    }

    /**
     * Group comments by rounded timestamp (to nearest second)
     * This prevents stacking of markers when multiple comments exist at the same time
     */
    public function getGroupedComments()
    {
        $grouped = [];

        foreach ($this->comments as $comment) {
            // Round timestamp to nearest second
            $roundedTimestamp = round($comment->timestamp ?? 0);

            if (! isset($grouped[$roundedTimestamp])) {
                $grouped[$roundedTimestamp] = [
                    'timestamp' => $roundedTimestamp,
                    'comments' => [],
                    'count' => 0,
                    'resolved' => true, // Will be set to false if any comment is unresolved
                ];
            }

            $grouped[$roundedTimestamp]['comments'][] = $comment;
            $grouped[$roundedTimestamp]['count']++;

            // If any comment in the group is unresolved, mark the group as unresolved
            if (! ($comment->resolved ?? false)) {
                $grouped[$roundedTimestamp]['resolved'] = false;
            }
        }

        // Sort by timestamp and return as indexed array
        ksort($grouped);

        return array_values($grouped);
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

    public function toggleCommentForm($timestamp = null)
    {
        $this->showAddCommentForm = ! $this->showAddCommentForm;
        if ($this->showAddCommentForm) {
            $this->commentTimestamp = is_numeric($timestamp)
                ? (float) $timestamp
                : $this->currentPosition;
        } else {
            $this->newComment = '';
        }
    }

    public function addComment()
    {
        $this->validate([
            'newComment' => 'required|string|max:1000',
        ]);

        FileComment::create([
            'commentable_type' => get_class($this->file),
            'commentable_id' => $this->file->id,
            'user_id' => $this->clientMode ? null : Auth::id(),
            'comment' => $this->newComment,
            'timestamp' => $this->commentTimestamp,
            'resolved' => false,
            'client_email' => $this->clientMode ? $this->clientEmail : null,
            'is_client_comment' => $this->clientMode,
        ]);

        $this->newComment = '';
        $this->showAddCommentForm = false;
        $this->loadComments();
    }

    public function toggleReplyForm($commentId = null)
    {
        if ($this->replyToCommentId === $commentId) {
            $this->showReplyForm = false;
            $this->replyToCommentId = null;
            $this->replyText = '';
        } else {
            $this->showReplyForm = true;
            $this->replyToCommentId = $commentId;
            $this->replyText = '';
        }
    }

    public function submitReply()
    {
        if ($this->fileType !== 'pitch_file') {
            return;
        }

        $this->validate([
            'replyText' => 'required|string|max:1000',
        ]);

        FileComment::create([
            'commentable_type' => get_class($this->file),
            'commentable_id' => $this->file->id,
            'user_id' => $this->clientMode ? null : Auth::id(),
            'parent_id' => $this->replyToCommentId,
            'comment' => $this->replyText,
            'timestamp' => 0, // Replies don't have timestamps
            'resolved' => false,
            'client_email' => $this->clientMode ? $this->clientEmail : null,
            'is_client_comment' => $this->clientMode,
        ]);

        $this->replyText = '';
        $this->showReplyForm = false;
        $this->replyToCommentId = null;
        $this->loadComments();
    }

    public function toggleResolveComment($commentId)
    {
        if ($this->fileType !== 'pitch_file') {
            return;
        }

        $comment = FileComment::find($commentId);
        if ($comment && $comment->commentable_id === $this->file->id) {
            $comment->update(['resolved' => ! $comment->resolved]);
            $this->loadComments();
        }
    }

    public function confirmDelete($commentId)
    {
        $this->commentToDelete = $commentId;
        $this->showDeleteConfirmation = true;
    }

    public function deleteComment()
    {
        if ($this->commentToDelete) {
            $comment = FileComment::find($this->commentToDelete);
            if ($comment && $comment->commentable_id === $this->file->id) {
                // Delete all replies first
                FileComment::where('parent_id', $comment->id)->delete();
                // Delete the comment
                $comment->delete();
                $this->loadComments();
            }
        }

        $this->commentToDelete = null;
        $this->showDeleteConfirmation = false;
    }

    public function cancelDelete()
    {
        $this->commentToDelete = null;
        $this->showDeleteConfirmation = false;
    }

    // Error Handling
    public function showVideoError($message)
    {
        $this->errorMessage = $message;
        $this->showError = true;
    }

    public function dismissError()
    {
        $this->showError = false;
        $this->errorMessage = '';
    }

    // PWA State Management
    protected function restoreVideoStateIfCurrent()
    {
        // Dispatch event to check for saved video state in service worker
        $this->dispatch('checkForSavedVideoState', [
            'fileType' => $this->fileType,
            'fileId' => $this->fileType === 'pitch_file' ? $this->file->uuid : $this->file->id,
        ]);
    }

    public function restoreSavedVideoState($savedState)
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
        $this->playbackRate = $savedState['playbackRate'] ?? '1.0';

        // Restore client mode if applicable
        if ($this->fileType === 'pitch_file') {
            $this->clientMode = $savedState['clientMode'] ?? false;
            $this->clientEmail = $savedState['clientEmail'] ?? '';
        }

        // Dispatch events to sync with JavaScript
        $this->dispatch('videoStateRestored', [
            'position' => $this->currentPosition,
            'isPlaying' => $this->isPlaying,
            'volume' => $this->volume,
            'isMuted' => $this->isMuted,
            'playbackRate' => (float) $this->playbackRate,
        ]);
    }

    // Event Handlers
    public function onVideoReady()
    {
        // Handle video ready event
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

    public function onVideoEnded()
    {
        $this->isPlaying = false;
        $this->currentPosition = 0;
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

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = floor($seconds % 60);

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    public function getFileUrl()
    {
        if ($this->fileType === 'pitch_file') {
            return $this->file->getStreamingUrl(auth()->user());
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
        if (! Auth::check()) {
            return false;
        }

        if ($this->fileType === 'pitch_file') {
            return true;
        }

        if ($this->fileType === 'project_file') {
            // For project files, only the project owner and collaborators can add comments
            return Auth::id() === $this->file->project->user_id;
        }

        return false;
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

    /**
     * Format file size for display
     */
    public function formatFileSize(int $bytes, int $precision = 2): string
    {
        if ($bytes === null || $bytes <= 0) {
            return '0 bytes';
        }

        $units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
