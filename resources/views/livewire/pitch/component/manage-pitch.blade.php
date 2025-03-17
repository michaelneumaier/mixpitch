<div class="bg-base-100 rounded-lg shadow-sm p-3 sm:p-6 mb-4 sm:mb-8 border border-base-300">
    <h3 class="text-xl sm:text-2xl font-bold mb-3 sm:mb-6 flex items-center">
        <i class="fas fa-tasks mr-2 sm:mr-3 text-blue-500"></i>Pitch Management
    </h3>

    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)">
        @if ($message = session('message'))
        <div x-show="show" class="alert alert-success text-sm sm:text-base p-2 sm:p-3" x-transition>
            {{ $message }}
        </div>
        @endif
    </div>

    <!-- Status Messages -->
    <div class="mb-4 sm:mb-8">
        <div class="rounded-lg overflow-hidden shadow-sm">
            @if($pitch->is_inactive || $pitch->status == 'closed')
            <div class="p-2 sm:p-4 bg-gray-100 border-l-4 border-gray-500">
                <i class="fas fa-lock mr-2"></i>
                {{ $pitch->is_inactive ? 'This pitch is now inactive' : 'This pitch has been closed' }}
            </div>
            @else
            <div class="p-2.5 sm:p-4 border-l-4 {{
                $pitch->status == 'pending' ? 'bg-yellow-50 border-yellow-500' :
                ($pitch->status == 'ready_for_review' ? 'bg-blue-50 border-blue-500' :
                ($pitch->status == 'pending_review' ? 'bg-purple-50 border-purple-500' :
                ($pitch->status == 'denied' ? 'bg-red-50 border-red-500' :
                ($pitch->status == 'approved' ? 'bg-green-50 border-green-500' :
                ($pitch->status == 'revisions_requested' ? 'bg-amber-50 border-amber-500' :
                ($pitch->status == 'completed' ? 'bg-success/20 border-success' : 'bg-base-200'))))))
            }}">
                <div class="flex items-start sm:items-center">
                    <i class="fas {{
                        $pitch->status == 'pending' ? 'fa-clock' :
                        ($pitch->status == 'ready_for_review' ? 'fa-hourglass-half' :
                        ($pitch->status == 'pending_review' ? 'fa-search' :
                        ($pitch->status == 'denied' ? 'fa-times-circle' :
                        ($pitch->status == 'approved' ? 'fa-check-circle' :
                        ($pitch->status == 'revisions_requested' ? 'fa-exclamation-circle' :
                        ($pitch->status == 'completed' ? 'fa-trophy' : 'fa-info-circle'))))))
                    }} mr-2 sm:mr-3 text-base sm:text-lg"></i>
                    <div>
                        <p class="font-semibold text-sm sm:text-base leading-tight sm:leading-normal">
                            {{ match($pitch->status) {
                            'pending' => 'Awaiting Project Owner Access',
                            'ready_for_review' => 'Pitch Under Review',
                            'pending_review' => 'Response Requires Review',
                            'denied' => 'Pitch Not Accepted',
                            'approved' => 'Pitch Approved!',
                            'revisions_requested' => 'Revisions Requested',
                            'completed' => 'Pitch Successfully Completed',
                            default => 'Pitch Status'
                            } }}
                        </p>
                        <p class="text-xs sm:text-sm text-gray-600 mt-1">
                            {{ $pitch->status_description }}
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Denied Pitch Alert Section -->
    @if($pitch->status == 'denied')
    <div class="mb-4 sm:mb-8 bg-red-50 border border-red-200 rounded-lg p-3 sm:p-6">
        <div class="flex items-start space-x-3 sm:space-x-4">
            <div class="flex-shrink-0 bg-red-100 rounded-full p-1.5 sm:p-2">
                <i class="fas fa-times-circle text-red-600 text-lg sm:text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="text-base sm:text-lg font-semibold text-red-800 mb-1.5 sm:mb-2">Your Pitch Has Been Denied</h4>
                <p class="text-sm text-red-700 mb-3 sm:mb-4">
                    The project owner has reviewed your pitch and has decided not to proceed with it at this time. You
                    can view their feedback below, make changes to your files, and resubmit if appropriate.
                </p>

                @if($snapshots->isNotEmpty())
                <div class="bg-white border border-red-200 rounded-lg p-3 sm:p-4 mb-3 sm:mb-4">
                    <h5 class="font-medium text-sm sm:text-base text-red-800 mb-1.5 sm:mb-2">Feedback from Project Owner</h5>
                    <div class="text-xs sm:text-sm text-gray-700">
                        @php
                        // Try to get feedback from different potential sources
                        $feedback = null;

                        // First check if the current snapshot has feedback
                        if ($pitch->currentSnapshot && isset($pitch->currentSnapshot->snapshot_data['feedback'])) {
                        $feedback = $pitch->currentSnapshot->snapshot_data['feedback'];
                        }
                        // Then check events for the latest snapshot_denied event
                        else if ($pitch->events()->where('event_type',
                        'snapshot_denied')->latest()->first()) {
                        $event = $pitch->events()->where('event_type',
                        'snapshot_denied')->latest()->first();
                        $feedback = preg_replace('/^Pitch denied\. Reason: /i', '', $event->comment);
                        // If feedback is empty after stripping, set to null to use default message
                        if (empty(trim($feedback))) {
                        $feedback = null;
                        }
                        }
                        @endphp

                        {{ $feedback ?? 'No specific feedback was provided. Please review your pitch and consider making
                        improvements before resubmitting.' }}
                    </div>
                    <div class="mt-3">
                        @if($pitch->currentSnapshot)
                        <a href="{{ route('pitches.showSnapshot', [$pitch->id, $pitch->currentSnapshot->id]) }}"
                            class="btn btn-sm btn-red hover:bg-red-700 text-xs sm:text-sm py-1.5">
                            <i class="fas fa-eye mr-1"></i>View Denied Snapshot
                        </a>
                        @else
                        <span class="text-gray-500 text-xs sm:text-sm italic">No snapshot available</span>
                        @endif
                    </div>
                </div>
                <div class="mt-3 sm:mt-4">
                    <p class="text-xs sm:text-sm text-gray-700 mb-1.5 sm:mb-2">
                        <i class="fas fa-info-circle mr-1"></i> To resubmit your pitch, make any necessary changes to
                        your files above, then click the "Resubmit Pitch" button at the bottom of the page.
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Revisions Requested Alert Section -->
    @if($pitch->status == 'revisions_requested')
    <div class="mb-4 sm:mb-8 bg-amber-50 border border-amber-200 rounded-lg p-3 sm:p-6">
        <div class="flex items-start space-x-3 sm:space-x-4">
            <div class="flex-shrink-0 bg-amber-100 rounded-full p-1.5 sm:p-2">
                <i class="fas fa-pencil-alt text-amber-600 text-lg sm:text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="text-base sm:text-lg font-semibold text-amber-800 mb-1.5 sm:mb-2">Revisions Have Been Requested</h4>
                <p class="text-sm text-amber-700 mb-3 sm:mb-4">
                    The project owner has reviewed your pitch and requested some changes. Please review the latest
                    snapshot and their feedback,
                    then make the necessary revisions and submit your updated pitch for review.
                </p>

                @if($snapshots->isNotEmpty())
                <div class="bg-white border border-amber-200 rounded-lg p-3 sm:p-4 mb-3 sm:mb-4">
                    <h5 class="font-medium text-sm sm:text-base text-amber-800 mb-1.5 sm:mb-2">Feedback from Project Owner</h5>
                    <div class="text-xs sm:text-sm text-gray-700">
                        @php
                        // Try to get feedback from different potential sources
                        $feedback = null;

                        // First check if the current snapshot has feedback
                        if ($pitch->currentSnapshot && isset($pitch->currentSnapshot->snapshot_data['feedback'])) {
                        $feedback = $pitch->currentSnapshot->snapshot_data['feedback'];
                        }
                        // Then check events for the latest snapshot_revisions_requested event
                        else if ($pitch->events()->where('event_type',
                        'snapshot_revisions_requested')->latest()->first()) {
                        $event = $pitch->events()->where('event_type',
                        'snapshot_revisions_requested')->latest()->first();
                        $feedback = preg_replace('/^Revisions requested\. Reason: /i', '', $event->comment);
                        // If feedback is empty after stripping, set to null to use default message
                        if (empty(trim($feedback))) {
                        $feedback = null;
                        }
                        }
                        @endphp

                        {{ $feedback ?? 'No specific feedback was provided. Please review the latest snapshot for
                        details.' }}
                    </div>
                    <div class="mt-3">
                        @if($pitch->currentSnapshot)
                        <a href="{{ route('pitches.showSnapshot', [$pitch->id, $pitch->currentSnapshot->id]) }}"
                            class="btn btn-sm btn-amber hover:bg-amber-600 text-xs sm:text-sm py-1.5">
                            <i class="fas fa-eye mr-1"></i>View Snapshot Details
                        </a>
                        @else
                        <span class="text-gray-500 text-xs sm:text-sm italic">No snapshot available</span>
                        @endif
                    </div>
                </div>
                @endif
                @if($pitch->status === 'revisions_requested')
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 mt-2">
                    <a href="{{ route('pitches.edit', $pitch->id) }}"
                        class="btn bg-amber-500 hover:bg-amber-600 text-white text-sm py-2.5 sm:py-2">
                        <i class="fas fa-edit mr-1"></i>Make Revisions & Resubmit
                    </a>
                    <button
                        onclick="window.scrollTo({top: document.querySelector('.tracks-container').offsetTop - 100, behavior: 'smooth'})"
                        class="btn btn-outline-amber text-sm py-2.5 sm:py-2">
                        <i class="fas fa-upload mr-1"></i>Upload New Files
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Submitted Pitches -->
    @if($snapshots->isNotEmpty())
    <div class="mb-4 sm:mb-8">
        <h4 class="text-lg sm:text-xl font-semibold mb-2.5 sm:mb-4 flex items-center">
            <i class="fas fa-history mr-2 text-purple-500"></i>Submission History
        </h4>
        <div class="space-y-2.5 sm:space-y-3">
            @foreach($snapshots as $snapshot)
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 sm:p-4 bg-base-100 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <div class="flex-1 min-w-0 mb-2 sm:mb-0">
                    <div class="flex flex-wrap items-center">
                        <i class="fas fa-version mr-2 text-gray-400"></i>
                        <a href="{{ route('pitches.showSnapshot', [$pitch->id, $snapshot->id]) }}"
                            class="font-medium hover:text-blue-600 transition-colors text-sm">
                            Version {{ $snapshot->snapshot_data['version'] }}
                        </a>
                        <span class="text-xs text-gray-500 ml-2 sm:ml-3">
                            {{ $snapshot->created_at->format('M d, Y H:i') }}
                        </span>
                    </div>
                    <div class="mt-1.5">
                        <span class="px-2 py-0.5 sm:py-1 rounded-full text-xs sm:text-sm {{
                            $snapshot->status === 'accepted' ? 'bg-green-100 text-green-800' :
                            ($snapshot->status === 'denied' ? 'bg-red-100 text-red-800' :
                            ($snapshot->status === 'revisions_requested' ? 'bg-amber-100 text-amber-800' :
                            ($snapshot->status === 'revision_addressed' ? 'bg-blue-100 text-blue-800' :
                            'bg-blue-100 text-blue-800')))
                        }}">
                            {{ match($snapshot->status) {
                            'accepted' => 'Accepted',
                            'denied' => 'Denied',
                            'revisions_requested' => 'Revisions Requested',
                            'revision_addressed' => 'Revision Addressed',
                            default => ucfirst($snapshot->status)
                            } }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('pitches.showSnapshot', [$pitch->id, $snapshot->id]) }}"
                        class="btn btn-sm btn-outline-primary py-1.5 text-xs sm:text-sm">
                        <i class="fas fa-eye mr-1"></i>View
                    </a>
                    <button wire:click="deleteSnapshot({{ $snapshot->id }})"
                        wire:confirm="Are you sure you want to delete this version?"
                        class="btn btn-sm btn-outline-danger py-1.5 text-xs sm:text-sm">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- File Management Section -->
    <div class="mb-4 sm:mb-8 tracks-container" x-data="{ 
            isUploading: false, 
            progress: 0,
            deleteModal: {
                isOpen: false,
                fileId: null,
                fileName: ''
            },
            uploadQueue: [],
            currentUploadIndex: 0,

            // Initialize listeners for file upload process
            initFileUpload() {
                const component = this;
                
                // Listen for the signal to upload the next file
                window.addEventListener('uploadNextFile', function(event) {
                    console.log('uploadNextFile event received:', event.detail);
                    
                    // Fix the check for event data
                    if (!event.detail) {
                        console.error('No event detail received');
                        return;
                    }
                    
                    let index, total;
                    
                    // Handle array format from Livewire 3
                    if (Array.isArray(event.detail)) {
                        index = event.detail[0]?.index;
                        total = event.detail[0]?.total;
                    } else {
                        // Handle object format
                        index = event.detail.index;
                        total = event.detail.total;
                    }
                    
                    if (typeof index === 'undefined') {
                        console.error('Invalid event data, no index found:', event.detail);
                        return;
                    }
                    
                    console.log(`Preparing to upload file ${index + 1} of ${total}. Queue length: ${component.uploadQueue.length}`);
                    
                    if (index >= component.uploadQueue.length) {
                        console.error(`Invalid index: ${index}. Queue only has ${component.uploadQueue.length} files.`);
                        // Tell Livewire to move to the next file
                        @this.uploadFailed(index, `Invalid index: ${index}`);
                        return;
                    }
                    
                    setTimeout(() => {
                        component.uploadFileByIndex(index);
                    }, 300);
                });
                
                // Handle file selection
                document.getElementById('newUploadedFiles').addEventListener('change', function(e) {
                    console.log('Files selected:', e.target.files);
                    
                    if (e.target.files.length) {
                        // Store the files in our local queue
                        component.uploadQueue = Array.from(e.target.files);
                        console.log('Upload queue updated:', component.uploadQueue);
                        
                        // Send file metadata to Livewire
                        const fileMetadata = Array.from(e.target.files).map(file => {
                            return {
                                name: file.name,
                                size: file.size,
                                type: file.type,
                                lastModified: file.lastModified
                            };
                        });
                        console.log('Setting file metadata:', fileMetadata);
                        
                        @this.set('tempUploadedFiles', fileMetadata);
                        @this.set('fileSizes', fileMetadata.map(file => component.formatFileSize(file.size)));
                    }
                });
            },
            
            // Format file size for display (called from JS)
            formatFileSize(bytes) {
                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                let i = 0;
                while (bytes > 1024 && i < units.length - 1) {
                    bytes /= 1024;
                    i++;
                }
                return Math.round(bytes * 100) / 100 + ' ' + units[i];
            },
            
            // Upload a single file by index using FormData and fetch
            uploadFileByIndex(index) {
                if (!this.uploadQueue[index]) {
                    console.error(`File at index ${index} not found in queue`);
                    return;
                }
                
                console.log(`Uploading file ${index + 1} of ${this.uploadQueue.length}: ${this.uploadQueue[index].name}`);
                
                const file = this.uploadQueue[index];
                const formData = new FormData();
                formData.append('file', file);
                formData.append('pitch_id', '{{ $pitch->id }}');
                formData.append('_token', '{{ csrf_token() }}');
                
                fetch('/pitch/upload-file', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log(`File ${index + 1} uploaded successfully:`, data);
                    // Tell Livewire the file was uploaded successfully
                    @this.uploadSuccess(index, data.file_path, data.file_id);
                })
                .catch(error => {
                    console.error(`Error uploading file ${index + 1}:`, error);
                    // Tell Livewire the file upload failed
                    @this.uploadFailed(index, error.message);
                });
            }
        }" 
        x-init="initFileUpload(); 
            Livewire.on('upload:start', () => { isUploading = true; })
            Livewire.on('upload:finish', () => { isUploading = false; })
            Livewire.on('upload:error', () => { isUploading = false; })
            Livewire.on('upload:progress', (progress) => { progress = progress; })"
        x-on:new-files-added.window="setTimeout(() => { $wire.clearHighlights() }, 2000)"
        x-on:new-uploads-completed.window="setTimeout(() => { $wire.clearUploadHighlights(); uploadQueue = []; }, 2000)">
        <h4 class="text-lg sm:text-xl font-semibold mb-2.5 sm:mb-4 flex items-center">
            <i class="fas fa-folder-open mr-2 text-green-500"></i>File Management
        </h4>

        <div class="mb-4">
            <div class="flex flex-col">
                <label class="mb-1.5 sm:mb-2 text-sm sm:text-base text-gray-700">Upload new files</label>
                <div class="flex flex-col">
                    <div class="flex flex-col sm:flex-row gap-2 mb-2">
                        <div class="flex-grow min-w-0 overflow-hidden">
                            <label for="newUploadedFiles"
                                class="flex flex-col items-center justify-center w-full h-14 sm:h-16 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 mr-2"></i>
                                    <span class="text-xs sm:text-sm text-gray-500">
                                        Click to add files
                                    </span>
                                </div>
                                <input type="file" id="newUploadedFiles" class="hidden"
                                    accept="audio/mpeg,audio/wav,audio/mp3,audio/aac,audio/ogg,application/pdf,image/jpeg,image/png,image/gif,application/zip"
                                    multiple />
                            </label>
                            @error('singleFileUpload') <span class="text-red-500 text-xs sm:text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="flex-shrink-0">
                            <button wire:click="processQueuedFiles" wire:loading.attr="disabled"
                                class="btn btn-primary hover:bg-primary-focus text-white w-full h-14 sm:h-16 text-sm sm:text-base"
                                @if(empty($tempUploadedFiles)) disabled @endif>
                                <i class="fas fa-upload mr-2"></i> Upload
                            </button>
                        </div>
                    </div>

                    @if(count($tempUploadedFiles) > 0)
                    <div class="bg-base-200/50 p-2 sm:p-3 rounded-lg mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium text-sm sm:text-base">Files to upload ({{ count($tempUploadedFiles) }})</h4>
                            <button wire:click="$set('tempUploadedFiles', []); $set('fileSizes', []);"
                                class="text-red-500 hover:text-red-700 transition-colors text-xs sm:text-sm">
                                Clear All
                            </button>
                        </div>
                        <div class="space-y-1.5 sm:space-y-2 max-h-36 sm:max-h-48 overflow-y-auto">
                            @foreach($tempUploadedFiles as $key => $file)
                            <div class="flex items-center justify-between bg-white p-1.5 sm:p-2 rounded-md transition-all duration-500
                                @if(in_array($key, $newlyAddedFileKeys)) animate-fade-in @endif
                                @if(isset($uploadingFileKey) && $uploadingFileKey === $key) bg-blue-50 @endif">
                                <div class="flex items-center flex-1 min-w-0">
                                    <i class="fas @if(isset($uploadingFileKey) && $uploadingFileKey === $key) fa-spinner fa-spin text-blue-500 @else fa-file text-blue-500 @endif mr-1.5 sm:mr-2 text-sm sm:text-base"></i>
                                    <div class="truncate flex-1 text-xs sm:text-sm">
                                        {{ $file['name'] }}
                                        <span class="text-xs text-gray-500 ml-1">{{ $fileSizes[$key] ?? '' }}</span>
                                        @if(isset($uploadingFileKey) && $uploadingFileKey === $key)
                                        <span class="ml-1 text-xs text-blue-600">Uploading...</span>
                                        @endif
                                    </div>
                                </div>
                                @if(!(isset($uploadingFileKey) && $uploadingFileKey === $key))
                                <button wire:click="removeUploadedFile({{ $key }})"
                                    class="text-red-500 hover:text-red-700 transition-colors ml-1.5 sm:ml-2 p-1.5">
                                    <i class="fas fa-times text-sm sm:text-base"></i>
                                </button>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($isUploading || $isProcessingQueue)
                    <div class="w-full bg-gray-200 rounded-full h-1.5 sm:h-2.5 mb-4">
                        <div class="bg-primary h-1.5 sm:h-2.5 rounded-full" style="width: {{ $uploadProgress }}%">
                        </div>
                    </div>
                    <div class="text-xs sm:text-sm text-gray-600 mb-3 flex justify-between">
                        <span>{{ $uploadProgressMessage }}</span>
                        <span>{{ $uploadProgress }}%</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Uploaded Files List -->
        @if($existingFiles->isEmpty())
        <div class="bg-base-200/50 p-2 sm:p-4 rounded-lg text-center text-gray-500">
            <i class="fas fa-inbox text-2xl sm:text-3xl mb-2"></i>
            <p class="text-sm sm:text-base">No files uploaded yet</p>
        </div>
        @else
        <div class="border border-base-300 rounded-lg overflow-hidden">
            <div class="bg-base-300/50 p-1.5 sm:p-2 flex items-center text-xs sm:text-sm font-medium">
                <div class="flex-1 px-1.5 sm:px-2">Filename</div>
                <div class="w-16 sm:w-24 text-right">Size</div>
                <div class="w-24 sm:w-32 text-center">Actions</div>
            </div>
            @foreach ($existingFiles as $file)
            <div class="flex items-center justify-between p-1.5 sm:p-3 even:bg-base-200/30 hover:bg-base-100 transition-colors
                @if(in_array($file->id, $newlyUploadedFileIds)) bg-green-100 @endif">
                <div class="flex-1 truncate px-1.5 sm:px-2 text-xs sm:text-sm">
                    <i class="fas fa-file-alt text-gray-400 mr-1.5 sm:mr-2"></i>
                    {{ $file->file_name ?? basename($file->file_path) }}
                </div>
                <div class="w-16 sm:w-24 text-right text-xs sm:text-sm text-gray-500">
                    {{ $this->formatFileSize($file->size ?? 0) }}
                </div>
                <div class="w-24 sm:w-32 text-center space-x-1 sm:space-x-2">
                    <button wire:click="downloadFile('{{ $file->id }}')" class="btn btn-xs btn-outline-primary p-1.5 sm:p-1">
                        <i class="fas fa-download"></i>
                    </button>
                    <button
                        @click="deleteModal.isOpen = true; deleteModal.fileId = '{{ $file->id }}'; deleteModal.fileName = '{{ $file->file_name ?? basename($file->file_path) }}'"
                        class="btn btn-xs btn-outline-danger p-1.5 sm:p-1">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Delete Confirmation Modal -->
        <div x-show="deleteModal.isOpen" class="fixed inset-0 z-50 overflow-y-auto"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen p-2 sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <!-- Modal Panel -->
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full max-w-sm sm:w-full">
                    <div class="bg-white px-3 sm:px-4 pt-3 sm:pt-5 pb-2 sm:pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-10 w-10 sm:h-12 sm:w-12 rounded-full bg-red-100 sm:mx-0">
                                <i class="fas fa-exclamation-triangle text-red-600 text-lg sm:text-xl"></i>
                            </div>
                            <div class="mt-2 sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Delete File
                                </h3>
                                <div class="mt-1 sm:mt-2">
                                    <p class="text-xs sm:text-sm text-gray-500">
                                        Are you sure you want to delete <span class="font-semibold break-all"
                                            x-text="deleteModal.fileName"></span>? This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-3 sm:px-4 py-2 sm:py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="deleteFile(deleteModal.fileId)"
                            @click="deleteModal.isOpen = false"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-3 sm:px-4 py-2 bg-red-600 text-xs sm:text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto">
                            Delete
                        </button>
                        <button type="button" @click="deleteModal.isOpen = false"
                            class="mt-2 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-3 sm:px-4 py-2 bg-white text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit for review button section -->
    @if($pitch->status == 'in_progress' || $pitch->status == 'pending_review' || $pitch->status == 'denied' ||
    $pitch->status == 'revisions_requested')
    <div class="mt-4 sm:mt-6 flex flex-col sm:flex-row justify-end items-start sm:items-center space-y-2 sm:space-y-0">
        @error('acceptedTerms')
        <span class="text-red-500 text-xs sm:text-sm">{{ $message }}</span>
        @enderror
        <div class="flex items-center w-full sm:w-auto mb-2 sm:mb-0 sm:mr-4">
            <input type="checkbox" id="terms" class="form-checkbox h-4 w-4 sm:h-5 sm:w-5 text-green-600"
                wire:model.defer="acceptedTerms">
            <label for="terms" class="px-2 text-xs sm:text-sm text-gray-700">I accept the <a href="/terms" target="_blank"
                    class="text-blue-500 hover:underline">terms and conditions</a></label>
        </div>

        <button wire:click="submitForReview" wire:confirm="Are you sure you want to Submit your Pitch?"
            class="w-full sm:w-auto bg-green-500 hover:bg-green-700 text-white text-sm font-semibold py-2.5 sm:py-2 px-4 rounded"
            :disabled="!acceptedTerms">
            <i class="fas fa-check pr-1.5 sm:pr-2"></i>
            {{ $pitch->status == 'denied' || $pitch->status == 'revisions_requested' ? 'Resubmit Pitch' : 'Ready To Submit' }}
        </button>
    </div>
    @endif

    <!-- Cancel submission button section -->
    @if($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW && auth()->id() === $pitch->user_id)
    <div class="mt-4 sm:mt-6 flex justify-end">
        <button wire:click="cancelPitchSubmission"
            wire:confirm="Are you sure you want to cancel your submission? This will return your pitch to 'In Progress' status and delete the current pending snapshot."
            class="w-full sm:w-auto bg-red-500 hover:bg-red-700 text-white text-sm font-semibold py-2.5 sm:py-2 px-4 rounded">
            <i class="fas fa-xmark pr-1.5 sm:pr-2"></i>
            Cancel Submission
        </button>
    </div>
    @endif
</div>