<?php

namespace App\Livewire;

use App\Models\PitchFile;
use App\Models\PitchFileComment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PitchFilePlayer extends Component
{
    public PitchFile $file;

    public $comments = [];

    public $newComment = '';

    public $commentTimestamp = 0;

    public $isPlaying = false;

    public $showAddCommentForm = false;

    public $duration = 0;

    public $commentMarkers = [];

    public $replyToCommentId = null;

    public $showReplyForm = false;

    public $replyText = '';

    public $commentToDelete = null;

    public $showDeleteConfirmation = false;

    public $isInCard = false;

    protected $listeners = [
        'waveformReady' => 'onWaveformReady',
        'playbackStarted' => 'onPlaybackStarted',
        'playbackPaused' => 'onPlaybackPaused',
        'refresh' => '$refresh',
    ];

    public function mount(PitchFile $file, $isInCard = false)
    {
        $this->file = $file;
        $this->isInCard = $isInCard;
        $this->loadComments();

        // Initialize duration from the file's stored duration if available
        if ($file->duration > 0) {
            $this->duration = $file->duration;
        }
    }

    public function loadComments()
    {
        // Load only top-level comments (no parent) with their replies
        $this->comments = $this->file->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user', 'replies.replies.user'])
            ->orderBy('timestamp')
            ->get();

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
                ];
            }

            // Log the calculated positions for debugging
            \Illuminate\Support\Facades\Log::info('Calculated comment markers', [
                'duration' => $this->duration,
                'count' => count($this->comments),
                'markers' => $this->commentMarkers,
            ]);
        } else {
            \Illuminate\Support\Facades\Log::warning('Cannot calculate comment markers: duration is zero or negative', [
                'duration' => $this->duration,
            ]);
        }
    }

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

    public function seekTo($timestamp)
    {
        $this->dispatch('seekToPosition', timestamp: $timestamp);
    }

    public function toggleCommentForm($timestamp = null)
    {
        $this->showAddCommentForm = ! $this->showAddCommentForm;

        if ($this->showAddCommentForm && $timestamp !== null) {
            $this->commentTimestamp = $timestamp;
            $this->dispatch('pausePlayback');
        }
    }

    public function addComment()
    {
        $this->validate([
            'newComment' => 'required|min:3',
            'commentTimestamp' => 'required|numeric|min:0',
        ]);

        $comment = new PitchFileComment;
        $comment->pitch_file_id = $this->file->id;
        $comment->user_id = Auth::id();
        $comment->comment = $this->newComment;
        $comment->timestamp = $this->commentTimestamp;
        $comment->resolved = false;
        $comment->save();

        // Send notification to the pitch file owner (if not the commenter)
        try {
            app(\App\Services\NotificationService::class)->notifyPitchFileComment(
                $this->file,
                $comment,
                Auth::id()
            );
        } catch (\Exception $e) {
            // Log error but don't prevent the comment from being added
            \Illuminate\Support\Facades\Log::error('Failed to send pitch file comment notification', [
                'error' => $e->getMessage(),
                'file_id' => $this->file->id,
                'user_id' => Auth::id(),
            ]);
        }

        $this->newComment = '';
        $this->showAddCommentForm = false;
        $this->loadComments();

        $this->dispatch('commentAdded');
    }

    public function toggleResolveComment($commentId)
    {
        $comment = PitchFileComment::find($commentId);

        if (Auth::id() === $comment->user_id || Auth::id() === $this->file->pitch->user_id) {
            $comment->resolved = ! $comment->resolved;
            $comment->save();
            $this->loadComments();
        }
    }

    public function confirmDelete($commentId)
    {
        $this->commentToDelete = $commentId;
        $this->showDeleteConfirmation = true;
    }

    public function cancelDelete()
    {
        $this->commentToDelete = null;
        $this->showDeleteConfirmation = false;
    }

    public function deleteComment($commentId = null)
    {
        // If commentId is not provided, use the one stored in commentToDelete property
        if ($commentId === null) {
            $commentId = $this->commentToDelete;
            $this->showDeleteConfirmation = false;
        }

        $comment = PitchFileComment::find($commentId);

        if (! $comment) {
            return;
        }

        if (Auth::id() === $comment->user_id || Auth::id() === $this->file->pitch->user_id) {
            // Get all nested replies recursively to ensure they're all deleted
            $replies = $comment->getAllReplies();

            // If this is a comment with replies, explicitly delete each reply
            if ($replies->count() > 0) {
                \Illuminate\Support\Facades\Log::info('Preparing to delete nested replies', [
                    'comment_id' => $commentId,
                    'reply_count' => $replies->count(),
                    'reply_ids' => $replies->pluck('id')->toArray(),
                ]);

                // Delete replies from deepest level first to avoid foreign key constraints issues
                foreach ($replies->sortByDesc(function ($reply) {
                    // Count how many ancestors this reply has to determine its depth
                    $depth = 0;
                    $current = $reply;
                    while ($current->parent_id) {
                        $depth++;
                        $current = $current->parent;
                    }

                    return $depth;
                }) as $reply) {
                    try {
                        \Illuminate\Support\Facades\Log::info('Deleting nested reply', [
                            'reply_id' => $reply->id,
                            'parent_id' => $reply->parent_id,
                        ]);
                        $reply->delete();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to delete reply', [
                            'reply_id' => $reply->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Now delete the main comment
            try {
                $comment->delete();
                \Illuminate\Support\Facades\Log::info('Deleted main comment', [
                    'comment_id' => $commentId,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to delete main comment', [
                    'comment_id' => $commentId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Reset reply form if we were replying to this comment or any of its replies
            if ($this->replyToCommentId === $commentId ||
                $replies->pluck('id')->contains($this->replyToCommentId)) {
                $this->showReplyForm = false;
                $this->replyToCommentId = null;
                $this->replyText = '';
            }

            $this->loadComments();
        }

        $this->commentToDelete = null;
    }

    public function toggleReplyForm($commentId = null)
    {
        if ($this->replyToCommentId === $commentId && $this->showReplyForm) {
            // If clicking the same comment's reply button and form is already shown, close it
            $this->showReplyForm = false;
            $this->replyToCommentId = null;
            $this->replyText = '';
        } else {
            // Open reply form for this comment
            $this->showReplyForm = true;
            $this->replyToCommentId = $commentId;
            $this->replyText = '';
        }
    }

    public function submitReply()
    {
        $this->validate([
            'replyText' => 'required|min:3',
            'replyToCommentId' => 'required|exists:pitch_file_comments,id',
        ]);

        $parentComment = PitchFileComment::find($this->replyToCommentId);

        $reply = new PitchFileComment;
        $reply->pitch_file_id = $this->file->id;
        $reply->user_id = Auth::id();
        $reply->parent_id = $this->replyToCommentId;
        $reply->comment = $this->replyText;

        // Use the timestamp from the top-level parent comment
        // If we're replying to a reply, find the original comment's timestamp
        if ($parentComment->parent_id) {
            // This is a reply to a reply, so get the top-level comment's timestamp
            $topLevelComment = PitchFileComment::find($parentComment->parent_id);
            $reply->timestamp = $topLevelComment->timestamp;
        } else {
            // This is a reply to a top-level comment, use its timestamp
            $reply->timestamp = $parentComment->timestamp;
        }

        $reply->resolved = false;
        $reply->save();

        // Send notification to the parent comment author (if not the replier)
        try {
            app(\App\Services\NotificationService::class)->notifyPitchFileComment(
                $this->file,
                $reply,
                Auth::id()
            );
        } catch (\Exception $e) {
            // Log error but don't prevent the reply from being added
            \Illuminate\Support\Facades\Log::error('Failed to send pitch file comment reply notification', [
                'error' => $e->getMessage(),
                'file_id' => $this->file->id,
                'parent_comment_id' => $this->replyToCommentId,
                'user_id' => Auth::id(),
            ]);
        }

        $this->replyText = '';
        $this->showReplyForm = false;
        $this->replyToCommentId = null;
        $this->loadComments();

        $this->dispatch('commentAdded');
    }

    public function render()
    {
        return view('livewire.pitch-file-player');
    }
}
