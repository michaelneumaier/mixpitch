<?php

namespace App\Livewire;

use App\Models\FileComment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ImagePreview extends Component
{
    // File properties
    public $file;

    public $fileType; // 'pitch_file' or 'project_file'

    public $breadcrumbs = [];

    // Comments system
    public $comments = [];

    public $newComment = '';

    public $showAddCommentForm = false;

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

    public bool $isModalOpen = false;

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
    }

    public function render()
    {
        return view('livewire.image-preview');
    }

    public function toggleModal()
    {
        $this->isModalOpen = ! $this->isModalOpen;
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

        $this->comments = $baseQuery->orderBy('created_at')->get();
    }

    public function toggleCommentForm()
    {
        $this->showAddCommentForm = ! $this->showAddCommentForm;
        if (! $this->showAddCommentForm) {
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
            'timestamp' => 0, // Images don't have timestamps
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

    // Utility Methods
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

    public function isImageFile()
    {
        return str_starts_with($this->file->mime_type, 'image/');
    }
}
