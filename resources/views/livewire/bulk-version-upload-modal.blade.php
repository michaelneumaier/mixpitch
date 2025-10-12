{{-- Bulk Version Upload Modal --}}
<flux:modal :name="'bulk-version-upload'" wire:model="isOpen" class="max-w-4xl" :dismissible="false" :closable="false">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                <flux:icon.arrow-up-tray class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div class="flex-1">
                <flux:heading size="lg" class="!mb-0">Bulk Upload New Versions</flux:heading>
                <flux:subheading class="text-gray-600 dark:text-gray-400 !mb-0">
                    Upload multiple files and match them to existing files
                </flux:subheading>
            </div>
        </div>

        {{-- Step Indicator --}}
        <div class="flex items-center justify-between">
            @foreach([1 => 'Upload Files', 2 => 'Review Matches', 3 => 'Complete'] as $step => $label)
                <div class="flex-1 flex items-center">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full
                            {{ $currentStep > $step ? 'bg-green-500 text-white' : ($currentStep === $step ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500') }}">
                            @if($currentStep > $step)
                                <flux:icon.check class="w-5 h-5" />
                            @else
                                <span class="text-sm font-semibold">{{ $step }}</span>
                            @endif
                        </div>
                        <span class="text-sm font-medium {{ $currentStep === $step ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $label }}
                        </span>
                    </div>
                    @if($step < 3)
                        <div class="flex-1 h-px mx-4 {{ $currentStep > $step ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Step 1: Upload Files --}}
        @if($currentStep === 1)
            <div class="space-y-4">
                <flux:callout variant="info">
                    <div class="flex items-start gap-2">
                        <flux:icon.information-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        <div class="text-sm">
                            <div class="font-medium mb-1">How it works</div>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Upload multiple audio files at once</li>
                                <li>We'll automatically match files to existing ones based on name</li>
                                <li>You can review and adjust matches before finalizing</li>
                                <li>Unmatched files will be added as new files</li>
                            </ul>
                        </div>
                    </div>
                </flux:callout>

                {{-- Upload Trigger Button --}}
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-12">
                    <div class="text-center">
                        <flux:icon.arrow-up-tray class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" />
                        <flux:button
                            type="button"
                            variant="primary"
                            icon="arrow-up-tray"
                            x-data
                            x-on:click="
                                if (window.GlobalUploader) {
                                    window.GlobalUploader.openFileDialog({
                                        modelId: {{ $pitch?->id ?? 'null' }},
                                        modelType: 'App\\\\Models\\\\Pitch',
                                        context: 'pitches',
                                        isBulkVersionUpload: true,
                                        maxFiles: null
                                    });
                                } else {
                                    alert('Upload system not ready. Please refresh the page.');
                                }
                            ">
                            Select Files to Upload
                        </flux:button>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">
                            or drag and drop files anywhere on this page
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                            Audio files only • No size limit
                        </p>
                    </div>
                </div>

                {{-- Show file count when files are being uploaded --}}
                @if(count($uploadedFilesData) > 0)
                    <flux:callout variant="success">
                        <div class="flex items-center gap-2">
                            <flux:icon.check-circle class="w-5 h-5" />
                            <div>
                                <div class="font-medium">{{ count($uploadedFilesData) }} file(s) uploaded successfully</div>
                                <div class="text-sm">Processing matches...</div>
                            </div>
                        </div>
                    </flux:callout>
                @endif
            </div>
        @endif

        {{-- Step 2: Review Matches --}}
        @if($currentStep === 2)
            <div class="space-y-4">
                {{-- Upload Progress Summary --}}
                @if($isUploadingToS3)
                    @php
                        $summary = $this->uploadProgressSummary;
                    @endphp
                    <flux:callout variant="info">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">Uploading files to cloud storage...</span>
                                <span class="text-sm">{{ $summary['completed'] }} / {{ $summary['total'] }} complete</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-300"
                                     style="width: {{ $summary['percentComplete'] }}%"></div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                You can start matching files now. Click "Confirm & Upload" anytime - if uploads are still in progress, they'll continue in the background.
                            </p>
                        </div>
                    </flux:callout>
                @endif

                {{-- Summary Stats --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <div class="flex items-center gap-2">
                            <flux:icon.check-circle class="w-5 h-5 text-green-600 dark:text-green-400" />
                            <div>
                                <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $this->matchedCount }}</div>
                                <div class="text-sm text-green-700 dark:text-green-300">Files matched to versions</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <div class="flex items-center gap-2">
                            <flux:icon.plus-circle class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                            <div>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $this->newFilesCount }}</div>
                                <div class="text-sm text-blue-700 dark:text-blue-300">New files to be added</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Matched Files Table --}}
                @if(count($this->currentlyMatchedFiles) > 0)
                    <div>
                        <flux:heading size="sm" class="!mb-2">Matched Files (Will Create New Versions)</flux:heading>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Uploaded File</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Match To</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($this->currentlyMatchedFiles as $index)
                                        @php
                                            $filesToCheck = !empty($pendingFiles) ? $pendingFiles : $uploadedFilesData;
                                            $fileData = $filesToCheck[$index] ?? null;
                                            $matchedFileId = $manualOverrides[$index] ?? null;
                                            $matchedFile = $matchedFileId ? $this->availableFilesForMatching->firstWhere('id', $matchedFileId) : null;
                                        @endphp
                                        @if($fileData && $matchedFile)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <flux:icon.musical-note class="w-4 h-4 text-gray-400" />
                                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $fileData['name'] }}</span>
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 ml-6">{{ number_format($fileData['size'] / 1024 / 1024, 2) }} MB</div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <flux:select
                                                        wire:model.live="manualOverrides.{{ $index }}"
                                                        wire:change="updateManualMatch('{{ $index }}', $event.target.value)"
                                                        class="w-full">
                                                        <option value="">Create as new file</option>
                                                        @foreach($this->availableFilesForMatching as $file)
                                                            @php
                                                                $isAutoMatched = ($file->id == ($autoMatches[$index] ?? null));
                                                                $isAlreadyUsed = in_array($file->id, $this->alreadyMatchedFileIds) && $file->id != $matchedFileId;
                                                                $versionLabel = $file->getVersionLabel();

                                                                // Build display name
                                                                $displayName = $file->file_name;
                                                                if ($versionLabel) {
                                                                    $displayName .= " ({$versionLabel})";
                                                                }
                                                                if ($isAutoMatched) {
                                                                    $displayName .= " [AUTO]";
                                                                }
                                                            @endphp
                                                            <option
                                                                value="{{ $file->id }}"
                                                                {{ $file->id == $matchedFileId ? 'selected' : '' }}
                                                                {{ $isAlreadyUsed ? 'disabled' : '' }}>
                                                                {{ $displayName }}
                                                            </option>
                                                        @endforeach
                                                    </flux:select>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <flux:badge size="sm" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                                        {{-- Check if the current value differs from auto match to determine if it's manual --}}
                                                        {{ (isset($manualOverrides[$index]) && $manualOverrides[$index] != ($autoMatches[$index] ?? null)) ? 'Manual' : 'Auto' }}
                                                    </flux:badge>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- New Files Table --}}
                @if(count($this->currentlyUnmatchedFiles) > 0)
                    <div>
                        <flux:heading size="sm" class="!mb-2">New Files (Will Be Added)</flux:heading>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">File Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Size</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Match Manually</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($this->currentlyUnmatchedFiles as $index)
                                        @php
                                            $filesToCheck = !empty($pendingFiles) ? $pendingFiles : $uploadedFilesData;
                                            $fileData = $filesToCheck[$index] ?? null;
                                        @endphp
                                        @if($fileData)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <flux:icon.musical-note class="w-4 h-4 text-gray-400" />
                                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $fileData['name'] }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($fileData['size'] / 1024 / 1024, 2) }} MB</span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <flux:select
                                                        wire:model.live="manualOverrides.{{ $index }}"
                                                        wire:change="updateManualMatch('{{ $index }}', $event.target.value)"
                                                        class="w-full">
                                                        <option value="">Keep as new file</option>
                                                        @foreach($this->availableFilesForMatching as $file)
                                                            @php
                                                                $isAlreadyUsed = in_array($file->id, $this->alreadyMatchedFileIds);
                                                                $versionLabel = $file->getVersionLabel();

                                                                $displayName = $file->file_name;
                                                                if ($versionLabel) {
                                                                    $displayName .= " ({$versionLabel})";
                                                                }
                                                            @endphp
                                                            <option
                                                                value="{{ $file->id }}"
                                                                {{ $isAlreadyUsed ? 'disabled' : '' }}>
                                                                {{ $displayName }}
                                                            </option>
                                                        @endforeach
                                                    </flux:select>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- No Files Warning --}}
                @if(count($uploadedFilesData) === 0)
                    <flux:callout variant="warning">
                        <div class="flex items-start gap-2">
                            <flux:icon.exclamation-triangle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                            <div class="text-sm">
                                No files were uploaded. Please go back and upload files.
                            </div>
                        </div>
                    </flux:callout>
                @endif
            </div>
        @endif

        {{-- Step 3: Upload Progress --}}
        @if($currentStep === 3)
            <div class="space-y-6 py-8">
                @if($uploading)
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/30 mb-4">
                            <svg class="animate-spin h-8 w-8 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <flux:heading size="lg" class="!mb-2">Creating Versions...</flux:heading>
                        <flux:subheading class="!mb-6">Please wait while we process your files</flux:subheading>

                        {{-- Progress Bar --}}
                        <div class="max-w-md mx-auto">
                            <div class="flex items-center justify-between text-sm mb-2">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Progress</span>
                                <span class="text-indigo-600 dark:text-indigo-400">{{ $uploadProgress }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="bg-indigo-600 dark:bg-indigo-500 h-3 rounded-full transition-all duration-300"
                                     style="width: {{ $uploadProgress }}%"></div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 mb-4">
                            <flux:icon.check class="w-8 h-8 text-green-600 dark:text-green-400" />
                        </div>
                        <flux:heading size="lg" class="!mb-2">Upload Complete!</flux:heading>
                        <flux:subheading>Your files have been processed successfully</flux:subheading>
                    </div>
                @endif
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
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <div>
                {{-- No back button - users must complete or cancel --}}
            </div>

            <div class="flex items-center gap-3">
                <flux:button
                    type="button"
                    wire:click="close"
                    variant="ghost"
                    :disabled="$uploading">
                    {{ $currentStep === 3 && !$uploading ? 'Done' : 'Cancel' }}
                </flux:button>

                @if($currentStep === 2)
                    <flux:button
                        type="button"
                        wire:click="confirmAndUpload"
                        variant="primary"
                        icon="arrow-up-tray">
                        Confirm & Upload
                    </flux:button>
                @endif
            </div>
        </div>
    </div>
</flux:modal>

{{-- Setup GlobalUploader integration --}}
@script
<script>
    (function() {
        // Track if uploads are active for beforeunload handler
        let hasActiveUploads = false;

        // Create bulk upload store immediately (available before Alpine loads)
        const bulkUploadStore = {
            pitchId: null,
            manualOverrides: [],
            isActive: false,

            start(pitchId, manualOverrides) {
                this.pitchId = pitchId;
                this.manualOverrides = manualOverrides;
                this.isActive = true;
                console.log('[Alpine Store] Background bulk upload started', {
                    pitchId,
                    overridesCount: Object.keys(manualOverrides).length
                });
            },

            clear() {
                this.pitchId = null;
                this.manualOverrides = [];
                this.isActive = false;
                console.log('[Alpine Store] Background state cleared');
            },

            hasActiveUpload() {
                return this.isActive && this.pitchId !== null;
            }
        };

        // Safe accessor for store (always available)
        function getBulkUploadStore() {
            return bulkUploadStore;
        }

        // Prevent navigation while uploads are in progress
        const beforeUnloadHandler = (e) => {
            if (hasActiveUploads) {
                e.preventDefault();
                e.returnValue = 'Files are still uploading. Are you sure you want to leave?';
                return e.returnValue;
            }
        };
        window.addEventListener('beforeunload', beforeUnloadHandler);

        // Listen for Livewire events
        $wire.on('uploadsStarted', () => {
            hasActiveUploads = true;
        });

        $wire.on('uploadsComplete', () => {
            hasActiveUploads = false;
        });

        $wire.on('clearBulkUploads', () => {
            console.log('[BulkUpload] Clearing bulk uploads array');
            hasActiveUploads = false;

            // Clear the GlobalUploader's internal array
            if (window.GlobalUploader && typeof window.GlobalUploader.clearBulkVersionUploads === 'function') {
                window.GlobalUploader.clearBulkVersionUploads();
            }
        });

        // Listen for cancel event from Livewire
        $wire.on('cancel-bulk-version-upload', () => {
            console.log('[BulkUpload] Cancelling all uploads');

            // Cancel all uploads in GlobalUploader
            if (window.GlobalUploader && typeof window.GlobalUploader.cancelAll === 'function') {
                window.GlobalUploader.cancelAll();
            }

            // Clear store if active
            getBulkUploadStore().clear();

            // Allow navigation
            hasActiveUploads = false;

            console.log('[BulkUpload] All uploads cancelled');
        });

        // Listen for early file selection (files selected, not yet uploaded)
        window.addEventListener('bulk-version-file-selected', (event) => {
            console.log('[BulkUpload] File selected', event.detail);
            hasActiveUploads = true;
            $wire.dispatch('uploadsStarted');

            // Call Livewire method to handle file selection
            $wire.call('handleFileSelected', event.detail);
        });

        // Listen for upload progress updates
        window.addEventListener('bulk-version-upload-progress', (event) => {
            console.log('[BulkUpload] Upload progress', event.detail);

            // Update progress in Livewire
            $wire.call('handleUploadProgress', event.detail);
        });

        // Listen for individual file upload completion (with S3 key)
        window.addEventListener('bulk-version-file-uploaded', (event) => {
            console.log('[BulkUpload] File uploaded to S3', event.detail);

            // Update file with S3 key in Livewire
            $wire.call('handleFileUploaded', event.detail);
        });

        // Listen for when ALL bulk version files finish uploading
        window.addEventListener('bulk-version-files-uploaded', (event) => {
            console.log('[BulkUpload] All files uploaded', event.detail);

            // Check store for background mode
            const store = getBulkUploadStore();

            if (store.hasActiveUpload()) {
                console.log('[BulkUpload] Processing in background mode via Livewire');

                // Format files for Livewire
                const formattedFiles = event.detail.map(file => ({
                    name: file.name,
                    s3_key: file.key,
                    size: file.size,
                    type: file.type,
                    id: file.id
                }));

                // Dispatch to GlobalFileUploader component
                Livewire.dispatch('processBulkVersionsBackground', {
                    pitchId: store.pitchId,
                    files: formattedFiles,
                    manualOverrides: store.manualOverrides
                });

                // Clear store
                store.clear();
                return;
            }

            // Normal mode - modal is still open
            if (event.detail && Array.isArray(event.detail)) {
                // Format file data for Livewire (convert 'key' to 's3_key')
                const formattedFiles = event.detail.map(file => ({
                    name: file.name || '',
                    s3_key: file.key || '',
                    size: file.size || 0,
                    type: file.type || ''
                }));

                // Use direct property assignment instead of dispatch to avoid serialization issues
                $wire.set('uploadedFilesData', formattedFiles);

                // Manually trigger preview
                $wire.call('previewMatches');
            }
        });

        // Prevent ESC key from closing modal
        const preventEscapeKey = (e) => {
            // Check if modal is open via Livewire property
            if ($wire.isOpen && e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                console.log('[BulkUpload] ESC key blocked - use Cancel button');
                return false;
            }
        };

        // Add listener with capture phase to intercept before Alpine/Flux
        document.addEventListener('keydown', preventEscapeKey, true);

        // Cleanup on component destroy (optional but good practice)
        window.addEventListener('beforeunload', () => {
            document.removeEventListener('keydown', preventEscapeKey, true);
        });

        // ============================================
        // BACKGROUND UPLOAD PROCESSING (ALPINE STORE)
        // ============================================

        // Register existing store with Alpine when it initializes
        document.addEventListener('alpine:init', () => {
            Alpine.store('bulkVersionUpload', bulkUploadStore);
            console.log('[BulkUpload] Store registered with Alpine');
        });

        // Listen for startBackgroundUpload event from Livewire
        window.addEventListener('startBackgroundUpload', (event) => {
            console.log('[BulkUpload] Starting background upload', event.detail);

            // Livewire wraps dispatch data in an array
            const { pitchId, manualOverrides } = event.detail[0];

            // Save to store (available immediately)
            const store = getBulkUploadStore();
            store.start(pitchId, manualOverrides);
        });
    })();
</script>
@endscript
