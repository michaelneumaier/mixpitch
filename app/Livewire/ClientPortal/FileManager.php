<?php

namespace App\Livewire\ClientPortal;

use App\Models\FileComment;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class FileManager extends Component
{
    public Project $project;

    public Pitch $pitch;

    public Collection $files;

    // Comment management
    public array $newFileComment = [];

    public ?int $commentToDelete = null;

    // Component configuration
    public bool $canPlay = true;

    public bool $canDownload = false;

    public bool $canDelete = false;

    public bool $enableBulkActions = false;

    public bool $showComments = true;

    public bool $enableCommentCreation = true;

    public bool $isClientPortal = true;

    public array $colorScheme = [];

    public string $headerIcon = 'document-duplicate';

    public string $emptyStateMessage = 'No files in this version';

    public string $emptyStateSubMessage = 'Files will appear here when uploaded';

    // Component refresh control
    public int $refreshKey = 0;

    protected $rules = [
        'newFileComment.*' => 'required|string|max:2000',
    ];

    public function mount(
        Project $project,
        Pitch $pitch,
        ?Collection $files = null,
        bool $canPlay = true,
        bool $canDownload = false,
        bool $canDelete = false,
        bool $enableBulkActions = false,
        bool $showComments = true,
        bool $enableCommentCreation = true,
        array $colorScheme = [],
        string $headerIcon = 'document-duplicate',
        string $emptyStateMessage = 'No files in this version',
        string $emptyStateSubMessage = 'Files will appear here when uploaded'
    ) {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->files = $files ?? collect();
        $this->canPlay = $canPlay;
        $this->canDownload = $canDownload;
        $this->canDelete = $canDelete;
        $this->enableBulkActions = $enableBulkActions;
        $this->showComments = $showComments;
        $this->enableCommentCreation = $enableCommentCreation;
        $this->colorScheme = $colorScheme;
        $this->headerIcon = $headerIcon;
        $this->emptyStateMessage = $emptyStateMessage;
        $this->emptyStateSubMessage = $emptyStateSubMessage;
    }

    /**
     * Get file comments data for the file-list component
     * Similar to ManageClientProject but for client portal context
     */
    #[Computed]
    public function fileCommentsData(): Collection
    {
        // Get all pitch files for this pitch
        $pitchFileIds = $this->files->pluck('id')->toArray();

        if (empty($pitchFileIds)) {
            return collect();
        }

        // Get comments for all pitch files using the new file_comments system
        // Only fetch parent comments (not replies) - replies are loaded via the 'replies.user' relationship
        return FileComment::where('commentable_type', PitchFile::class)
            ->whereIn('commentable_id', $pitchFileIds)
            ->whereNull('parent_id') // Only parent comments, not replies
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($comment) {
                // Map to match expected structure for backward compatibility
                $comment->client_name = $this->project->client_name ?: 'Client';
                $comment->producer_name = $comment->user->name ?? 'Producer';
                $comment->metadata = [
                    'file_id' => $comment->commentable_id,
                    'client_name' => $comment->client_name,
                    'producer_name' => $comment->producer_name,
                    'type' => $comment->is_client_comment ? 'client_comment' : 'producer_comment',
                ];
                $comment->event_type = $comment->is_client_comment ? 'client_file_comment' : 'producer_comment';

                return $comment;
            });
    }

    /**
     * Handle comment actions from the file-list component
     */
    #[On('commentAction')]
    public function handleCommentAction($data)
    {
        $action = $data['action'] ?? null;
        $commentId = $data['commentId'] ?? null;
        $fileId = $data['fileId'] ?? null;
        $comment = $data['comment'] ?? null;
        $response = $data['response'] ?? null;

        switch ($action) {
            case 'markFileCommentResolved':
                $this->markFileCommentResolved($commentId);
                break;
            case 'respondToFileComment':
                $this->respondToFileComment($commentId, $response);
                break;
            case 'createFileComment':
                $this->createFileComment($fileId, $comment);
                break;
            case 'deleteFileComment':
                $this->deleteFileComment($commentId);
                break;
            case 'unresolveFileComment':
                $this->unresolveFileComment($commentId);
                break;
        }
    }

    /**
     * Mark a file comment as resolved
     */
    public function markFileCommentResolved($commentId)
    {
        try {
            $comment = FileComment::findOrFail($commentId);

            // Verify the comment belongs to a file in this pitch
            $pitchFileIds = $this->files->pluck('id')->toArray();
            if ($comment->commentable_type !== PitchFile::class ||
                ! in_array($comment->commentable_id, $pitchFileIds)) {
                throw new \Exception('Comment does not belong to this pitch');
            }

            $comment->update(['resolved' => true]);

            Log::info('Client portal: File comment marked as resolved', [
                'comment_id' => $commentId,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_email' => $this->project->client_email,
            ]);

            $this->dispatch('commentsUpdated');

        } catch (\Exception $e) {
            Log::error('Client portal: Failed to mark file comment as resolved', [
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Respond to a file comment
     */
    public function respondToFileComment($commentId, $response)
    {
        if (empty(trim($response))) {
            return;
        }

        try {
            $parentComment = FileComment::findOrFail($commentId);

            // Verify the comment belongs to a file in this pitch
            $pitchFileIds = $this->files->pluck('id')->toArray();
            if ($parentComment->commentable_type !== PitchFile::class ||
                ! in_array($parentComment->commentable_id, $pitchFileIds)) {
                throw new \Exception('Comment does not belong to this pitch');
            }

            // Create reply comment
            FileComment::create([
                'commentable_type' => PitchFile::class,
                'commentable_id' => $parentComment->commentable_id,
                'comment' => $response,
                'is_client_comment' => true, // This is a client response
                'client_email' => $this->project->client_email,
                'parent_id' => $parentComment->id,
                'timestamp' => 0.0, // Default timestamp for non-audio-specific replies
            ]);

            Log::info('Client portal: File comment reply created', [
                'parent_comment_id' => $commentId,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_email' => $this->project->client_email,
            ]);

            $this->dispatch('commentsUpdated');

        } catch (\Exception $e) {
            Log::error('Client portal: Failed to respond to file comment', [
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a new comment on a file
     */
    public function createFileComment($fileId, $comment)
    {
        if (empty(trim($comment))) {
            return;
        }

        try {
            // Verify the file belongs to this pitch
            $file = $this->files->where('id', $fileId)->first();
            if (! $file) {
                throw new \Exception('File not found in current files');
            }

            // Create the comment
            FileComment::create([
                'commentable_type' => PitchFile::class,
                'commentable_id' => $fileId,
                'comment' => trim($comment),
                'is_client_comment' => true, // This is a client comment
                'client_email' => $this->project->client_email,
                'timestamp' => 0.0, // Default timestamp for general comments
            ]);

            Log::info('Client portal: File comment created', [
                'file_id' => $fileId,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_email' => $this->project->client_email,
            ]);

            // Clear the comment input
            $this->newFileComment[$fileId] = '';

            $this->dispatch('commentsUpdated');

        } catch (\Exception $e) {
            Log::error('Client portal: Failed to create file comment', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete a file comment (clients can only delete their own comments)
     */
    public function deleteFileComment($commentId)
    {
        try {
            $comment = FileComment::findOrFail($commentId);

            // Verify the comment belongs to a file in this pitch
            $pitchFileIds = $this->files->pluck('id')->toArray();
            if ($comment->commentable_type !== PitchFile::class ||
                ! in_array($comment->commentable_id, $pitchFileIds)) {
                throw new \Exception('Comment does not belong to this pitch');
            }

            // Verify this is a client comment and matches the current client
            if (! $comment->is_client_comment || $comment->client_email !== $this->project->client_email) {
                throw new \Exception('Unauthorized to delete this comment');
            }

            $comment->delete();

            Log::info('Client portal: File comment deleted', [
                'comment_id' => $commentId,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_email' => $this->project->client_email,
            ]);

            $this->dispatch('commentsUpdated');

        } catch (\Exception $e) {
            Log::error('Client portal: Failed to delete file comment', [
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Unresolve a file comment (clients can unresolve their own comments)
     */
    public function unresolveFileComment($commentId)
    {
        try {
            $comment = FileComment::findOrFail($commentId);

            // Verify the comment belongs to a file in this pitch
            $pitchFileIds = $this->files->pluck('id')->toArray();
            if ($comment->commentable_type !== PitchFile::class ||
                ! in_array($comment->commentable_id, $pitchFileIds)) {
                throw new \Exception('Comment does not belong to this pitch');
            }

            // Mark as unresolved
            $comment->update(['resolved' => false]);

            Log::info('Client portal: File comment marked as unresolved', [
                'comment_id' => $commentId,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_email' => $this->project->client_email,
            ]);

            $this->dispatch('commentsUpdated');

        } catch (\Exception $e) {
            Log::error('Client portal: Failed to unresolve file comment', [
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Confirm deletion of a comment
     */
    public function confirmDeleteComment(int $commentId): void
    {
        $this->commentToDelete = $commentId;
        $this->dispatch('modal-show', name: 'delete-comment');
    }

    /**
     * Cancel comment deletion
     */
    public function cancelDeleteComment(): void
    {
        $this->commentToDelete = null;
        $this->dispatch('modal-close', name: 'delete-comment');
    }

    /**
     * Delete the selected comment
     */
    public function deleteComment(): void
    {
        if (! $this->commentToDelete) {
            return;
        }

        $this->deleteFileComment($this->commentToDelete);
        $this->commentToDelete = null;
        $this->dispatch('modal-close', name: 'delete-comment');
    }

    /**
     * Handle file actions (play/download/delete) - delegate to parent or handle here
     */
    #[On('fileAction')]
    public function handleFileAction($data)
    {
        $action = $data['action'] ?? null;
        $fileId = $data['fileId'] ?? null;

        // For client portal, we mainly need to handle play actions
        // Download and delete are typically handled by the controller routes
        switch ($action) {
            case 'playFile':
                // Could dispatch to global audio player or handle play action
                $this->dispatch('playClientPortalFile', fileId: $fileId);
                break;
            default:
                // Other actions might be handled by parent component or routes
                break;
        }
    }

    /**
     * Handle comment updates by refreshing the component
     */
    #[On('commentsUpdated')]
    public function handleCommentsUpdated(): void
    {
        Log::info('🔄 Client portal refreshing comments', [
            'project_id' => $this->project->id,
            'refresh_key_before' => $this->refreshKey,
        ]);

        // Clear cached comment data to force refresh
        unset($this->fileCommentsData);

        // Refresh models to get latest data
        $this->project->refresh();
        $this->pitch->refresh();

        // Increment refresh key to force component re-render
        $this->refreshKey++;

        Log::info('🔄 Client portal comment refresh completed', [
            'project_id' => $this->project->id,
            'refresh_key_after' => $this->refreshKey,
        ]);

        // Force component re-render
        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.client-portal.file-manager');
    }
}
