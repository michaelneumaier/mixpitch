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

    // Client mode properties
    public $clientMode = false;
    public $clientEmail = '';
    public $showResolved = false;
    public $resolvedCount = 0;

    protected $listeners = [
        'waveformReady' => 'onWaveformReady',
        'playbackStarted' => 'onPlaybackStarted',
        'playbackPaused' => 'onPlaybackPaused',
        'refresh' => '$refresh',
        'pitch-files-updated' => 'reinitializePlayer',
    ];

    public function mount(PitchFile $file, $isInCard = false, $clientMode = false, $clientEmail = '')
    {
        $this->file = $file;
        $this->isInCard = $isInCard;
        $this->clientMode = $clientMode;
        $this->clientEmail = $clientEmail;
        
        $this->loadComments();

        // Initialize duration from the file's stored duration if available
        if ($file->duration > 0) {
            $this->duration = $file->duration;
        }
    }

    public function loadComments()
    {
        // Load only top-level comments (no parent) with their replies
        $baseQuery = $this->file->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user', 'replies.replies.user']);

        // Calculate resolved count for client mode toggle button
        if ($this->clientMode) {
            $this->resolvedCount = $baseQuery->clone()->where('resolved', true)->count();
        }

        // Filter resolved comments if not showing them (client mode)
        if ($this->clientMode && !$this->showResolved) {
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
                ];
            }

            // Comment markers calculated successfully
        } else {
            // Duration is zero or negative, cannot calculate markers
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

    public function reinitializePlayer()
    {
        // Dispatch a browser event to reinitialize the player JavaScript
        $this->dispatch('reinitialize-player-' . $this->file->id);
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
        
        if ($this->clientMode) {
            // Client comment
            $comment->user_id = null;
            $comment->client_email = $this->clientEmail;
            $comment->is_client_comment = true;
        } else {
            // Regular user comment
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
                // Notify producer of client comment
                app(\App\Services\NotificationService::class)->notifyProducerClientCommented(
                    $this->file->pitch,
                    $this->newComment
                );
            } else {
                // Regular comment notification
                app(\App\Services\NotificationService::class)->notifyPitchFileComment(
                    $this->file,
                    $comment,
                    Auth::id()
                );
            }
        } catch (\Exception $e) {
            // Log error but don't prevent the comment from being added
            \Illuminate\Support\Facades\Log::error('Failed to send pitch file comment notification', [
                'error' => $e->getMessage(),
                'file_id' => $this->file->id,
                'client_mode' => $this->clientMode,
                'user_id' => Auth::id(),
                'client_email' => $this->clientEmail,
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

        // Check permissions: logged-in user owns comment/pitch OR client mode with matching email
        $canResolve = false;
        
        if ($this->clientMode) {
            // In client mode, allow if it's a client comment with matching email OR producer comment
            $canResolve = ($comment->is_client_comment && $comment->client_email === $this->clientEmail)
                       || ($comment->user_id === $this->file->pitch->user_id);
        } else {
            // Regular mode: user owns comment or pitch
            $canResolve = Auth::id() === $comment->user_id || Auth::id() === $this->file->pitch->user_id;
        }

        if ($canResolve) {
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
        // Clients cannot delete comments
        if ($this->clientMode) {
            return;
        }

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
        $reply->parent_id = $this->replyToCommentId;
        $reply->comment = $this->replyText;
        
        if ($this->clientMode) {
            // Client reply
            $reply->user_id = null;
            $reply->client_email = $this->clientEmail;
            $reply->is_client_comment = true;
        } else {
            // Regular user reply
            $reply->user_id = Auth::id();
            $reply->is_client_comment = false;
        }

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
            if ($this->clientMode) {
                // Notify producer of client reply
                app(\App\Services\NotificationService::class)->notifyProducerClientCommented(
                    $this->file->pitch,
                    $this->replyText
                );
            } else {
                // Regular reply notification
                app(\App\Services\NotificationService::class)->notifyPitchFileComment(
                    $this->file,
                    $reply,
                    Auth::id()
                );
            }
        } catch (\Exception $e) {
            // Log error but don't prevent the reply from being added
            \Illuminate\Support\Facades\Log::error('Failed to send pitch file comment reply notification', [
                'error' => $e->getMessage(),
                'file_id' => $this->file->id,
                'parent_comment_id' => $this->replyToCommentId,
                'client_mode' => $this->clientMode,
                'user_id' => $this->clientMode ? null : Auth::id(),
                'client_email' => $this->clientMode ? $this->clientEmail : null,
            ]);
        }

        $this->replyText = '';
        $this->showReplyForm = false;
        $this->replyToCommentId = null;
        $this->loadComments();

        $this->dispatch('commentAdded');
    }

    // Client-specific methods
    public function toggleShowResolved()
    {
        $this->showResolved = !$this->showResolved;
        $this->loadComments();
    }

    public function getCommentPermissions(): array
    {
        if ($this->clientMode) {
            return [
                'can_add' => true,
                'can_edit' => false,
                'can_delete' => false,
                'can_resolve' => true, // Clients can resolve comments
                'can_reply' => true, // Clients can reply to comments
            ];
        }

        return [
            'can_add' => true,
            'can_edit' => false, // Not implemented yet
            'can_delete' => true,
            'can_resolve' => true,
            'can_reply' => true,
        ];
    }

    public function render()
    {
        return view('livewire.pitch-file-player');
    }
}
