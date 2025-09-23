<?php

namespace App\Livewire;

use App\Models\FileComment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VideoPlayer extends Component
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

    public bool $showControls = true;

    protected $listeners = [
        'videoReady' => 'onVideoReady',
        'playbackStarted' => 'onPlaybackStarted',
        'playbackPaused' => 'onPlaybackPaused',
        'videoEnded' => 'onVideoEnded',
        'updatePosition' => 'updatePosition',
        'updateDuration' => 'updateDuration',
        'toggleFullscreen' => 'toggleFullscreen',
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
    }

    public function render()
    {
        return view('livewire.video-player');
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

    public function toggleFullscreen()
    {
        $this->isFullscreen = ! $this->isFullscreen;
        $this->dispatch('fullscreenToggled', fullscreen: $this->isFullscreen);
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

    // Event Handlers
    public function onVideoReady()
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

        $minutes = floor($seconds / 60);
        $remainingSeconds = floor($seconds % 60);

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
        return ! empty($this->comments);
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
}
