<?php

namespace App\Livewire\Components;

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class FileList extends Component
{
    // File data
    public Collection $files;

    public string $modelType = 'project'; // 'project', 'pitch', 'client', etc.

    public ?int $modelId = null;

    // Theming - accepts array of color classes or predefined theme name
    public array|string $colorScheme = [];

    public string $variant = 'standard'; // 'standard', 'compact', 'detailed'

    // Capabilities
    public bool $canPlay = true;

    public bool $canDownload = true;

    public bool $canDelete = true;

    // Action method names - allows parent component to define custom method names
    public string $playMethod = 'playFile';

    public string $downloadMethod = 'downloadFile';

    public string $deleteMethod = 'confirmDeleteFile';

    // Display options
    public bool $showFileCount = true;

    public bool $showTotalSize = true;

    public bool $showAnimations = true;

    public string $emptyStateMessage = 'No files uploaded yet';

    public string $emptyStateSubMessage = 'Upload files to share with collaborators';

    // Animation support for newly uploaded files
    public array $newlyUploadedFileIds = [];

    // Icon configuration
    public string $headerIcon = 'folder';

    public string $emptyIcon = 'folder-open';

    // Bulk actions configuration
    public bool $enableBulkActions = false;

    public array $selectedFileIds = [];

    public bool $isSelectMode = false;

    public array $bulkActions = ['delete', 'download']; // configurable actions

    // Bulk action method names
    public string $bulkDeleteMethod = 'bulkDeleteFiles';

    public string $bulkDownloadMethod = 'bulkDownloadFiles';

    // Comment support
    public bool $showComments = false;

    public ?Collection $commentsData = null;

    public string $commentsMethod = 'handleCommentAction';

    public array $fileCommentResponse = [];

    public array $newFileComment = [];

    public bool $enableCommentCreation = false;

    public ?int $commentToDelete = null;

    public function mount(
        ?Collection $files = null,
        string $modelType = 'project',
        ?int $modelId = null,
        array|string $colorScheme = [],
        string $variant = 'standard',
        bool $canPlay = true,
        bool $canDownload = true,
        bool $canDelete = true,
        string $playMethod = 'playFile',
        string $downloadMethod = 'downloadFile',
        string $deleteMethod = 'confirmDeleteFile',
        bool $showFileCount = true,
        bool $showTotalSize = true,
        bool $showAnimations = true,
        string $emptyStateMessage = 'No files uploaded yet',
        string $emptyStateSubMessage = 'Upload files to share with collaborators',
        array $newlyUploadedFileIds = [],
        string $headerIcon = 'folder',
        string $emptyIcon = 'folder-open',
        bool $enableBulkActions = false,
        array $bulkActions = ['delete', 'download'],
        string $bulkDeleteMethod = 'bulkDeleteFiles',
        string $bulkDownloadMethod = 'bulkDownloadFiles',
        bool $showComments = false,
        ?Collection $commentsData = null,
        string $commentsMethod = 'handleCommentAction',
        bool $enableCommentCreation = false
    ) {
        $this->files = $files ?? collect();
        $this->modelType = $modelType;
        $this->modelId = $modelId;
        $this->colorScheme = $colorScheme;
        $this->variant = $variant;
        $this->canPlay = $canPlay;
        $this->canDownload = $canDownload;
        $this->canDelete = $canDelete;
        $this->playMethod = $playMethod;
        $this->downloadMethod = $downloadMethod;
        $this->deleteMethod = $deleteMethod;
        $this->showFileCount = $showFileCount;
        $this->showTotalSize = $showTotalSize;
        $this->showAnimations = $showAnimations;
        $this->emptyStateMessage = $emptyStateMessage;
        $this->emptyStateSubMessage = $emptyStateSubMessage;
        $this->newlyUploadedFileIds = $newlyUploadedFileIds;
        $this->headerIcon = $headerIcon;
        $this->emptyIcon = $emptyIcon;
        $this->enableBulkActions = $enableBulkActions;
        $this->bulkActions = $bulkActions;
        $this->bulkDeleteMethod = $bulkDeleteMethod;
        $this->bulkDownloadMethod = $bulkDownloadMethod;
        $this->showComments = $showComments;
        $this->commentsData = $commentsData ?? collect();
        $this->commentsMethod = $commentsMethod;
        $this->enableCommentCreation = $enableCommentCreation;
    }

    /**
     * Get the resolved color scheme with default fallbacks
     */
    #[Computed]
    public function resolvedColorScheme(): array
    {
        if (is_array($this->colorScheme) && ! empty($this->colorScheme)) {
            return $this->colorScheme;
        }

        // Predefined theme schemes
        $themes = [
            'project' => [
                'bg' => 'bg-blue-50 dark:bg-blue-950',
                'border' => 'border-blue-200 dark:border-blue-800',
                'text_primary' => 'text-blue-900 dark:text-blue-100',
                'text_secondary' => 'text-blue-700 dark:text-blue-300',
                'text_muted' => 'text-blue-600 dark:text-blue-400',
                'accent_bg' => 'bg-blue-100 dark:bg-blue-900',
                'accent_border' => 'border-blue-200 dark:border-blue-800',
                'icon' => 'text-blue-600 dark:text-blue-400',
            ],
            'client-portal' => [
                'bg' => 'bg-indigo-50 dark:bg-indigo-950',
                'border' => 'border-indigo-200 dark:border-indigo-800',
                'text_primary' => 'text-indigo-900 dark:text-indigo-100',
                'text_secondary' => 'text-indigo-700 dark:text-indigo-300',
                'text_muted' => 'text-indigo-600 dark:text-indigo-400',
                'accent_bg' => 'bg-indigo-100 dark:bg-indigo-900',
                'accent_border' => 'border-indigo-200 dark:border-indigo-800',
                'icon' => 'text-indigo-600 dark:text-indigo-400',
            ],
            'default' => [
                'bg' => 'bg-gray-50 dark:bg-gray-950',
                'border' => 'border-gray-200 dark:border-gray-800',
                'text_primary' => 'text-gray-900 dark:text-gray-100',
                'text_secondary' => 'text-gray-700 dark:text-gray-300',
                'text_muted' => 'text-gray-600 dark:text-gray-400',
                'accent_bg' => 'bg-gray-100 dark:bg-gray-900',
                'accent_border' => 'border-gray-200 dark:border-gray-800',
                'icon' => 'text-gray-600 dark:text-gray-400',
            ],
        ];

        $themeName = is_string($this->colorScheme) ? $this->colorScheme : 'default';

        return $themes[$themeName] ?? $themes['default'];
    }

    /**
     * Get total file size in bytes
     */
    #[Computed]
    public function totalFileSize(): int
    {
        return $this->files->sum('size') ?? 0;
    }

    /**
     * Get total size of selected files
     */
    #[Computed]
    public function selectedFileSize(): int
    {
        return $this->files
            ->whereIn('id', $this->selectedFileIds)
            ->sum('size') ?? 0;
    }

    /**
     * Get count of selected files
     */
    #[Computed]
    public function selectedFileCount(): int
    {
        return count($this->selectedFileIds);
    }

    /**
     * Check if all files are selected
     */
    #[Computed]
    public function allFilesSelected(): bool
    {
        if ($this->files->isEmpty()) {
            return false;
        }

        return $this->files->pluck('id')->diff($this->selectedFileIds)->isEmpty();
    }

    /**
     * Check if any files are selected
     */
    #[Computed]
    public function hasSelectedFiles(): bool
    {
        return ! empty($this->selectedFileIds);
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

    /**
     * Delegate file play action to parent component
     */
    public function playFile(int $fileId): void
    {
        $this->dispatch('fileAction', [
            'action' => $this->playMethod,
            'fileId' => $fileId,
            'modelType' => $this->modelType,
            'modelId' => $this->modelId,
        ]);
    }

    /**
     * Delegate file download action to parent component
     */
    public function downloadFile(int $fileId): void
    {
        $this->dispatch('fileAction', [
            'action' => $this->downloadMethod,
            'fileId' => $fileId,
            'modelType' => $this->modelType,
            'modelId' => $this->modelId,
        ]);
    }

    /**
     * Delegate file delete action to parent component
     */
    public function confirmDeleteFile(int $fileId): void
    {
        $this->dispatch('fileAction', [
            'action' => $this->deleteMethod,
            'fileId' => $fileId,
            'modelType' => $this->modelType,
            'modelId' => $this->modelId,
        ]);
    }

    /**
     * Check if a file is an audio file
     */
    public function isAudioFile($file): bool
    {
        if (method_exists($file, 'isAudioFile')) {
            return $file->isAudioFile();
        }

        // Fallback: check mime type or file extension
        $audioMimeTypes = ['audio/mpeg', 'audio/wav', 'audio/mp3', 'audio/mp4', 'audio/aac', 'audio/flac'];
        $audioExtensions = ['mp3', 'wav', 'm4a', 'aac', 'flac'];

        if (isset($file->mime_type) && in_array($file->mime_type, $audioMimeTypes)) {
            return true;
        }

        if (isset($file->file_name)) {
            $extension = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));

            return in_array($extension, $audioExtensions);
        }

        return false;
    }

    /**
     * Toggle selection for a specific file
     */
    public function toggleFileSelection(int $fileId): void
    {
        if (! $this->enableBulkActions) {
            return;
        }

        $index = array_search($fileId, $this->selectedFileIds);

        if ($index !== false) {
            // File is selected, remove it
            unset($this->selectedFileIds[$index]);
            $this->selectedFileIds = array_values($this->selectedFileIds); // Re-index array
        } else {
            // File is not selected, add it
            $this->selectedFileIds[] = $fileId;
        }

        // Enter selection mode if we have selections
        if (! empty($this->selectedFileIds)) {
            $this->isSelectMode = true;
        } else {
            $this->isSelectMode = false;
        }
    }

    /**
     * Select all files
     */
    public function selectAllFiles(): void
    {
        if (! $this->enableBulkActions) {
            return;
        }

        $this->selectedFileIds = $this->files->pluck('id')->toArray();
        $this->isSelectMode = true;
    }

    /**
     * Clear all selections
     */
    public function clearSelection(): void
    {
        $this->selectedFileIds = [];
        $this->isSelectMode = false;
    }

    /**
     * Toggle select mode
     */
    public function toggleSelectMode(): void
    {
        if (! $this->enableBulkActions) {
            return;
        }

        $this->isSelectMode = ! $this->isSelectMode;

        if (! $this->isSelectMode) {
            $this->clearSelection();
        }
    }

    /**
     * Toggle select all/none based on current state
     */
    public function toggleSelectAll(): void
    {
        if (! $this->enableBulkActions) {
            return;
        }

        // Check if all files are currently selected by comparing counts
        $allSelected = count($this->selectedFileIds) === $this->files->count() && $this->files->count() > 0;

        if ($allSelected) {
            $this->clearSelection();
        } else {
            $this->selectAllFiles();
        }
    }

    /**
     * Check if a file is selected
     */
    public function isFileSelected(int $fileId): bool
    {
        return in_array($fileId, $this->selectedFileIds);
    }

    /**
     * Show bulk delete confirmation modal
     */
    public function bulkDeleteSelected(): void
    {
        if (! $this->enableBulkActions || ! in_array('delete', $this->bulkActions) || ! $this->canDelete) {
            return;
        }

        if (empty($this->selectedFileIds)) {
            return;
        }

        // Dispatch to parent to show bulk delete confirmation modal
        $this->dispatch('bulkFileAction', [
            'action' => 'confirmBulkDeleteFiles',
            'fileIds' => $this->selectedFileIds,
            'modelType' => $this->modelType,
            'modelId' => $this->modelId,
        ]);
    }

    /**
     * Bulk download selected files
     */
    public function bulkDownloadSelected(): void
    {
        if (! $this->enableBulkActions || ! in_array('download', $this->bulkActions) || ! $this->canDownload) {
            return;
        }

        if (empty($this->selectedFileIds)) {
            return;
        }

        $this->dispatch('bulkFileAction', [
            'action' => $this->bulkDownloadMethod,
            'fileIds' => $this->selectedFileIds,
            'modelType' => $this->modelType,
            'modelId' => $this->modelId,
        ]);
    }

    /**
     * Handle file upload events to refresh the file list
     */
    #[On('filesUploaded')]
    public function handleFilesUploaded($eventData): void
    {
        // Check if this event is for our specific model context
        if (isset($eventData['model_type']) && isset($eventData['model_id'])) {
            $modelClass = match ($this->modelType) {
                'project' => \App\Models\Project::class,
                'pitch' => \App\Models\Pitch::class,
                default => null,
            };

            if ($modelClass === $eventData['model_type'] && $this->modelId == $eventData['model_id']) {
                // Clear selection when files are refreshed
                $this->clearSelection();

                // Reload the files directly
                $this->reloadFiles();
            }
        }
    }

    /**
     * Handle generic refresh files events
     */
    #[On('refreshFiles')]
    public function refreshFiles(): void
    {
        // Clear selection when files are refreshed
        $this->clearSelection();

        // Reload the files directly
        $this->reloadFiles();
    }

    /**
     * Reload files from the database
     */
    protected function reloadFiles(): void
    {
        $modelClass = match ($this->modelType) {
            'project' => \App\Models\Project::class,
            'pitch' => \App\Models\Pitch::class,
            default => null,
        };

        if ($modelClass && $this->modelId) {
            $model = $modelClass::find($this->modelId);
            if ($model) {
                $this->files = $model->files()->orderBy('created_at', 'desc')->get();
            }
        }
    }

    /**
     * Handle storage change events
     */
    #[On('storageChanged')]
    public function handleStorageChanged(): void
    {
        // Clear selection when files are refreshed
        $this->clearSelection();

        $this->dispatch('fileListRefreshRequested', [
            'modelType' => $this->modelType,
            'modelId' => $this->modelId,
            'source' => 'storage_change',
        ]);
    }

    /**
     * Handle file deletion events
     */
    #[On('file-deleted')]
    public function handleFileDeleted(): void
    {
        // Clear selection when files are deleted
        $this->clearSelection();

        // Reload the files directly
        $this->reloadFiles();
    }

    /**
     * Handle comment updates
     */
    #[On('commentsUpdated')]
    public function handleCommentsUpdated(): void
    {
        // The parent component will refresh, which will update our commentsData
        // No need to do anything here as the parent will re-render this component
    }

    /**
     * Get comments for a specific file
     */
    public function getFileComments(int $fileId): Collection
    {
        if (! $this->showComments || ! $this->commentsData) {
            return collect();
        }

        return $this->commentsData->filter(function ($comment) use ($fileId) {
            return isset($comment->metadata['file_id']) && (int) $comment->metadata['file_id'] === (int) $fileId;
        })->sortByDesc('created_at')->values(); // Sort newest first, values() resets array keys
    }

    /**
     * Get comment count for a specific file
     */
    public function getFileCommentCount(int $fileId): int
    {
        return $this->getFileComments($fileId)->count();
    }

    /**
     * Mark a file comment as resolved
     */
    public function markFileCommentResolved(int $commentId): void
    {
        $this->dispatch('commentAction', [
            'action' => 'markFileCommentResolved',
            'commentId' => $commentId,
            'modelType' => $this->modelType,
            'modelId' => $this->modelId,
        ]);
    }

    /**
     * Respond to a file comment
     */
    public function respondToFileComment(int $commentId): void
    {
        $response = $this->fileCommentResponse[$commentId] ?? '';

        if (empty(trim($response))) {
            return;
        }

        $this->dispatch('commentAction', [
            'action' => 'respondToFileComment',
            'commentId' => $commentId,
            'response' => $response,
            'modelType' => $this->modelType,
            'modelId' => $this->modelId,
        ]);

        // Clear the response after sending
        $this->fileCommentResponse[$commentId] = '';
    }

    /**
     * Create a new comment on a file
     */
    public function createFileComment(int $fileId): void
    {
        $comment = trim($this->newFileComment[$fileId] ?? '');

        if (empty($comment)) {
            return;
        }

        $this->dispatch('commentAction', [
            'action' => 'createFileComment',
            'fileId' => $fileId,
            'comment' => $comment,
            'modelType' => $this->modelType,
            'modelId' => $this->modelId,
        ]);

        // Clear the comment after sending
        $this->newFileComment[$fileId] = '';
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
     * Delete a comment
     */
    public function deleteComment(): void
    {
        if (!$this->commentToDelete) {
            return;
        }

        $this->dispatch('commentAction', [
            'action' => 'deleteFileComment',
            'commentId' => $this->commentToDelete,
            'modelType' => $this->modelType,
            'modelId' => $this->modelId,
        ]);

        $this->commentToDelete = null;
        
        // Close the modal after successful deletion
        $this->dispatch('modal-close', name: 'delete-comment');
    }

    public function render()
    {
        return view('livewire.components.file-list');
    }
}
