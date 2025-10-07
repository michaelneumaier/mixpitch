<div>
    {{-- File List Component with Comments Enabled --}}
    @livewire('components.file-list', [
        'files' => $files,
        'modelType' => 'pitch',
        'canPlay' => $canPlay,
        'canDownload' => $canDownload,
        'canDelete' => $canDelete,
        'enableBulkActions' => $enableBulkActions,
        'showComments' => $showComments,
        'enableCommentCreation' => $enableCommentCreation,
        'commentsData' => $this->fileCommentsData,
        'headerIcon' => $headerIcon,
        'emptyStateMessage' => $emptyStateMessage,
        'emptyStateSubMessage' => $emptyStateSubMessage,
        'isClientPortal' => $isClientPortal,
        'clientPortalProjectId' => $project->id,
        'currentSnapshot' => $currentSnapshot,
        'colorScheme' => $colorScheme,
    ], key('client-portal-file-list-' . $project->id . '-' . $refreshKey))

    {{-- JavaScript to handle file downloads --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('download-file', (event) => {
                window.location.href = event.url;
            });
        });
    </script>
</div>