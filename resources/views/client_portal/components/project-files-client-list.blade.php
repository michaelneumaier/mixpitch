@php
    $clientFiles = $project->files ?? collect();
@endphp

<div class="mb-6 rounded-xl bg-blue-50 p-6 dark:bg-blue-900/20" x-data="clientFileManager">
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
    @livewire('link-importer', ['project' => $project])

    @if ($clientFiles->count() > 0)
        <div class="space-y-3" id="client-files-list">
            @foreach ($clientFiles as $file)
                <div class="flex items-center justify-between rounded-xl border bg-white p-4 dark:bg-gray-700"
                    data-file-id="{{ $file->id }}"
                    x-show="!deletedFiles.includes('{{ $file->id }}')"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <flux:icon.document class="flex-shrink-0 text-blue-500" />
                        <div class="min-w-0 flex-1">
                            <flux:heading size="sm" class="truncate">
                                {{ $file->file_name }}
                            </flux:heading>
                            <flux:subheading>{{ number_format($file->size / 1024, 1) }} KB</flux:subheading>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:button
                            href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('client.portal.download_project_file', now()->addHours(24), ['project' => $project->id, 'projectFile' => $file->id]) }}"
                            variant="outline" size="sm">
                            <flux:icon.arrow-down-tray class="mr-1" />
                            <span class="hidden sm:inline">Download</span>
                        </flux:button>
                        <flux:button variant="danger" size="sm"
                            @click="deleteFile('{{ $file->id }}', '{{ addslashes($file->file_name) }}')"
                            x-bind:disabled="deleting">
                            <flux:icon.trash class="mr-1" />
                            <span class="hidden sm:inline">Delete</span>
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="py-8 text-center" id="client-files-list">
            <flux:icon.folder-open class="mx-auto mb-3 text-blue-500" size="xl" />
            <flux:heading size="sm" class="mb-2">No reference files uploaded yet</flux:heading>
            <flux:subheading>Upload files above to get started</flux:subheading>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('clientFileManager', () => ({
        deletedFiles: [],
        deleting: false,

        async deleteFile(fileId, fileName) {
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
                    self.deletedFiles.push(fileId);
                    window.ToasterHub?.success('File deleted successfully!');
                    
                    // Check if all files are deleted
                    const totalFiles = {{ $clientFiles->count() }};
                    if (self.deletedFiles.length >= totalFiles) {
                        // Show empty state after animation completes
                        setTimeout(() => {
                            document.getElementById('client-files-list').innerHTML = `
                                <div class="py-8 text-center">
                                    <div class="mx-auto mb-3 w-16 h-16 flex items-center justify-center">
                                        <svg class="w-full h-full text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-medium mb-2">No reference files uploaded yet</h4>
                                    <p class="text-sm text-gray-500">Upload files above to get started</p>
                                </div>
                            `;
                        }, 400);
                    }
                } else {
                    window.ToasterHub?.error(data.message || 'Delete failed');
                }
            } catch (error) {
                console.error('Delete error:', error);
                window.ToasterHub?.error('Delete failed: ' + error.message);
            } finally {
                self.deleting = false;
            }
        }
    }));
});
</script>
@endpush
