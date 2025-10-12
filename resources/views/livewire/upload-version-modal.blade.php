{{-- Upload Version Modal --}}
<flux:modal :name="'upload-version'" wire:model="isOpen" class="max-w-lg">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                <flux:icon.arrow-up-tray class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div class="flex-1">
                <flux:heading size="lg" class="!mb-0">Upload New Version</flux:heading>
                @if($file)
                    <flux:subheading class="text-gray-600 dark:text-gray-400 !mb-0">
                        {{ $file->file_name }}
                    </flux:subheading>
                @endif
            </div>
        </div>

        {{-- Info Callout --}}
        @if($file && $file->hasMultipleVersions())
            <flux:callout variant="info">
                <div class="flex items-start gap-2">
                    <flux:icon.information-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <div>
                        <div class="font-medium">Current Version: {{ $file->getVersionLabel() }}</div>
                        <div class="text-sm">This file has {{ $file->getAllVersionsWithSelf()->count() }} version(s)</div>
                    </div>
                </div>
            </flux:callout>
        @endif

        {{-- File Upload Section --}}
        @if(count($uploadedFilesData) === 0)
            <div>
                <flux:field>
                    <flux:label>Select File</flux:label>
                    <flux:description>
                        Choose a file to upload as the next version
                        @if($file)
                            (will become {{ $file->getVersionLabel() ? 'V' . ($file->file_version_number + 1) : 'V2' }})
                        @endif
                    </flux:description>

                    <div class="mt-2">
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8">
                            <div class="text-center">
                                <flux:icon.arrow-up-tray class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-3" />
                                <flux:button
                                    type="button"
                                    variant="primary"
                                    size="sm"
                                    icon="arrow-up-tray"
                                    x-data
                                    x-on:click="
                                        if (window.GlobalUploader) {
                                            window.GlobalUploader.openFileDialog({
                                                modelId: {{ $file?->pitch_id ?? 'null' }},
                                                modelType: 'App\\\\Models\\\\Pitch',
                                                context: 'pitches',
                                                maxFiles: 1,
                                                isVersionUpload: true,
                                                parentFileId: {{ $file?->id ?? 'null' }}
                                            });
                                        } else {
                                            alert('Upload system not ready. Please refresh the page.');
                                        }
                                    ">
                                    Select File
                                </flux:button>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    or drag and drop anywhere on the page
                                </p>
                            </div>
                        </div>
                    </div>
                </flux:field>
            </div>
        @else
            {{-- File Selected State --}}
            <flux:callout variant="success">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <flux:icon.check-circle class="w-5 h-5" />
                        <div>
                            <div class="font-medium">File Selected</div>
                            <div class="text-sm">{{ $uploadedFilesData[0]['name'] ?? 'Unknown' }}</div>
                            <div class="text-xs">{{ number_format(($uploadedFilesData[0]['size'] ?? 0) / 1024 / 1024, 2) }} MB</div>
                        </div>
                    </div>
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="xs"
                        icon="x-mark"
                        wire:click="$set('uploadedFilesData', [])"
                        :disabled="$uploading">
                        Remove
                    </flux:button>
                </div>
            </flux:callout>
        @endif

        {{-- Upload Progress --}}
        @if($uploading && $uploadProgress > 0)
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Creating version...</span>
                    <span class="text-indigo-600 dark:text-indigo-400">{{ $uploadProgress }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div
                        class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-300"
                        style="width: {{ $uploadProgress }}%"></div>
                </div>
            </div>
        @endif

        {{-- Error Message --}}
        @if($errorMessage)
            <flux:callout variant="danger">
                <div class="flex items-start gap-2">
                    <flux:icon.exclamation-triangle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <div>
                        <div class="font-medium">Upload Failed</div>
                        <div class="text-sm">{{ $errorMessage }}</div>
                    </div>
                </div>
            </flux:callout>
        @endif

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3 pt-4">
            <flux:button
                type="button"
                wire:click="close"
                variant="ghost"
                :disabled="$uploading">
                Cancel
            </flux:button>
            <flux:button
                type="button"
                x-data
                x-on:click="$wire.$call('createFileVersion', window.versionFileData)"
                variant="primary"
                icon="arrow-up-tray"
                :disabled="$isUploadDisabled || $uploading || count($uploadedFilesData) === 0">
                <span wire:loading.remove wire:target="createFileVersion">
                    Upload Version
                </span>
                <span wire:loading wire:target="createFileVersion">
                    Uploading...
                </span>
            </flux:button>
        </div>
    </div>
</flux:modal>

{{-- Setup GlobalUploader integration --}}
@script
<script>
    // Global variable to store file data (bypasses Livewire state sync issues)
    window.versionFileData = null;

    // Listen for browser CustomEvent from GlobalFileUploader
    window.addEventListener('version-file-uploaded', (event) => {
        if (event.detail) {
            window.versionFileData = event.detail;

            // Update Livewire component state for UI display
            $wire.set('uploadedFilesData', [event.detail]);
            $wire.set('isUploadDisabled', false);
        }
    });
</script>
@endscript
