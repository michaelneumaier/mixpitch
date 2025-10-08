@php
    $clientFiles = $project->files ?? collect();
@endphp

<div class="mb-2 rounded-xl bg-blue-50 p-2 md:p-6 dark:bg-blue-900/20" x-data="clientFileManager">
    <div class="mb-4 flex items-center gap-3">
        <flux:icon.cloud-arrow-up class="text-blue-500" />
        <flux:heading size="lg">Your Reference Files</flux:heading>
    </div>
    <flux:text size="sm" class="mb-6">
        Upload briefs, references, or examples to help the producer understand your requirements perfectly.
    </flux:text>

    <x-file-management.upload-section :model="$project" context="client_portal"
        accept="audio/*,video/*,.pdf,.doc,.docx,.jpg,.jpeg,.png" :max-files="10" class="mb-4" />
    
    {{-- Import from Link functionality --}}
    <div class="m-2">
    @livewire('link-importer', ['project' => $project])
    </div>

    <div id="client-files-list">
        @livewire('components.file-list', [
            'files' => $clientFiles,
            'modelType' => 'project',
            'modelId' => $project->id,
            'canPlay' => true,
            'canDownload' => true,
            'canDelete' => true,
            'enableBulkActions' => false,
            'showComments' => false,
            'enableCommentCreation' => false,
            'headerIcon' => 'cloud-arrow-up',
            'emptyStateMessage' => 'No reference files uploaded yet',
            'emptyStateSubMessage' => 'Upload files above to get started',
            'colorScheme' => [
                'bg' => 'bg-blue-50 dark:bg-blue-950',
                'border' => 'border-blue-200 dark:border-blue-800',
                'text_primary' => 'text-blue-900 dark:text-blue-100',
                'text_secondary' => 'text-blue-700 dark:text-blue-300',
                'text_muted' => 'text-blue-600 dark:text-blue-400',
                'accent_bg' => 'bg-blue-100 dark:bg-blue-900',
                'accent_border' => 'border-blue-200 dark:border-blue-800',
                'icon' => 'text-blue-600 dark:text-blue-400',
            ],
            'deleteMethod' => 'deleteClientFile',
            'downloadMethod' => 'downloadClientFile'
        ])
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('clientFileManager', () => ({
        deletedFiles: [],
        deleting: false,

        async deleteClientFile(fileId, fileName) {
            if (!confirm(`Are you sure you want to delete "${fileName}"? This action cannot be undone.`)) {
                return;
            }

            const self = this;
            self.deleting = true;
            
            const deleteUrl = '{{ URL::signedRoute('client.portal.delete_project_file', ['project' => $project->id, 'projectFile' => 'PROJECT_FILE_ID']) }}'
                .replace('PROJECT_FILE_ID', fileId);

            try {
                const response = await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    window.ToasterHub?.success('File deleted successfully!');
                    // Refresh the page to update the file list
                    location.reload();
                } else {
                    window.ToasterHub?.error(data.message || 'Delete failed');
                }
            } catch (error) {
                console.error('Delete error:', error);
                window.ToasterHub?.error('Delete failed: ' + error.message);
            } finally {
                self.deleting = false;
            }
        },

        downloadClientFile(fileId) {
            const downloadUrl = '{{ URL::temporarySignedRoute('client.portal.download_project_file', now()->addHours(24), ['project' => $project->id, 'projectFile' => 'PROJECT_FILE_ID']) }}'
                .replace('PROJECT_FILE_ID', fileId);
            window.location.href = downloadUrl;
        }
    }));
});

// Listen for file action events from the file-list component
document.addEventListener('livewire:init', () => {
    Livewire.on('fileAction', (data) => {
        const action = data[0];
        console.log('File action received:', action);
        
        if (action.action === 'deleteClientFile') {
            // Get Alpine instance and call delete method
            const manager = Alpine.$data(document.querySelector('[x-data*="clientFileManager"]'));
            if (manager) {
                // Find the file name for confirmation
                const fileName = `File ${action.fileId}`; // Fallback name
                manager.deleteClientFile(action.fileId, fileName);
            }
        } else if (action.action === 'downloadClientFile') {
            const manager = Alpine.$data(document.querySelector('[x-data*="clientFileManager"]'));
            if (manager) {
                manager.downloadClientFile(action.fileId);
            }
        }
    });
});
</script>
@endpush
