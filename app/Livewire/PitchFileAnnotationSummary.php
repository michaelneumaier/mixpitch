<?php

namespace App\Livewire;

use App\Models\PitchFile;
use App\Models\PitchFileComment;
use Livewire\Component;

class PitchFileAnnotationSummary extends Component
{
    public PitchFile $pitchFile;

    public array $commentIds = [];

    public bool $showResolved = false;

    public bool $compact = false;

    public bool $showClientComments = false;

    public function mount(PitchFile $pitchFile, bool $compact = false, bool $showClientComments = false)
    {
        $this->pitchFile = $pitchFile;
        $this->compact = $compact;
        $this->showClientComments = $showClientComments;
        $this->loadComments();
    }

    public function loadComments()
    {
        $comments = $this->pitchFile->comments()
            ->with(['user', 'replies.user'])
            ->whereNull('parent_id') // Only top-level comments
            ->when(! $this->showResolved, fn ($q) => $q->where('resolved', false))
            ->when($this->showClientComments, fn ($q) => $q->where('is_client_comment', true))
            ->orderBy('timestamp')
            ->get();

        // Store just the comment IDs for serialization
        $this->commentIds = $comments->pluck('id')->toArray();
    }

    public function getGroupedComments()
    {
        if (empty($this->commentIds)) {
            return collect();
        }

        $comments = PitchFileComment::with(['user', 'replies.user'])
            ->whereIn('id', $this->commentIds)
            ->orderBy('timestamp')
            ->get();

        // Group comments by 30-second intervals
        return $comments->groupBy(function ($comment) {
            return floor($comment->timestamp / 30); // Group by 30-second intervals
        });
    }

    public function toggleShowResolved()
    {
        $this->showResolved = ! $this->showResolved;
        $this->loadComments();
    }

    public function resolveComment(int $commentId)
    {
        $comment = PitchFileComment::where('commentable_type', 'App\Models\PitchFile')
            ->where('commentable_id', $this->pitchFile->id)
            ->findOrFail($commentId);

        $comment->update(['resolved' => true]);
        $this->loadComments();
    }

    public function jumpToTimestamp(float $timestamp)
    {
        // Dispatch globally so any pitch-file-player on the page can respond
        $this->dispatch('seekToPosition', ['timestamp' => $timestamp])->to('pitch-file-player');
    }

    public function getTotalComments(): int
    {
        return $this->pitchFile->comments()
            ->whereNull('parent_id')
            ->count();
    }

    public function getUnresolvedCount(): int
    {
        return $this->pitchFile->comments()
            ->whereNull('parent_id')
            ->where('resolved', false)
            ->count();
    }

    public function getResolvedCount(): int
    {
        return $this->pitchFile->comments()
            ->whereNull('parent_id')
            ->where('resolved', true)
            ->count();
    }

    public function getCommentsByInterval(): array
    {
        $intervals = [];
        $groupedComments = $this->getGroupedComments();

        foreach ($groupedComments as $intervalIndex => $comments) {
            $startTime = $intervalIndex * 30;
            $endTime = ($intervalIndex + 1) * 30;

            // Convert comments to array format for the view
            $commentsArray = $comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'timestamp' => $comment->timestamp,
                    'resolved' => $comment->resolved,
                    'is_client_comment' => $comment->isClientComment(),
                    'client_email' => $comment->client_email,
                    'user' => $comment->user ? $comment->user->toArray() : null,
                    'created_at' => $comment->created_at,
                    'replies' => $comment->replies ? $comment->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'comment' => $reply->comment,
                            'timestamp' => $reply->timestamp,
                            'is_client_comment' => $reply->isClientComment(),
                            'client_email' => $reply->client_email,
                            'user' => $reply->user ? $reply->user->toArray() : null,
                            'created_at' => $reply->created_at,
                        ];
                    })->toArray() : [],
                ];
            })->toArray();

            $intervals[] = [
                'interval' => $intervalIndex,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'time_label' => gmdate('i:s', $startTime).' - '.gmdate('i:s', $endTime),
                'comments' => $commentsArray,
                'comment_count' => count($commentsArray),
            ];
        }

        return $intervals;
    }

    public function render()
    {
        return view('livewire.pitch-file-annotation-summary', [
            'intervals' => $this->getCommentsByInterval(),
            'totalComments' => $this->getTotalComments(),
            'unresolvedCount' => $this->getUnresolvedCount(),
            'resolvedCount' => $this->getResolvedCount(),
            'compact' => $this->compact,
        ]);
    }
}
