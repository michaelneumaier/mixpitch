<div class="bg-base-100 rounded-lg shadow-sm p-6 mb-8 border border-base-300">
    <h3 class="text-2xl font-bold mb-6 flex items-center">
        <i class="fas fa-tasks mr-3 text-blue-500"></i>Pitch Management
    </h3>

    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)">
        @if ($message = session('message'))
        <div x-show="show" class="alert alert-success" x-transition>
            {{ $message }}
        </div>
        @endif
    </div>

    <!-- Status Messages -->
    <div class="mb-8">
        <div class="rounded-lg overflow-hidden shadow-sm">
            @if($pitch->is_inactive || $pitch->status == 'closed')
            <div class="p-4 bg-gray-100 border-l-4 border-gray-500">
                <i class="fas fa-lock mr-2"></i>
                {{ $pitch->is_inactive ? 'This pitch is now inactive' : 'This pitch has been closed' }}
            </div>
            @else
            <div class="p-4 border-l-4 {{
                $pitch->status == 'pending' ? 'bg-yellow-50 border-yellow-500' :
                ($pitch->status == 'ready_for_review' ? 'bg-blue-50 border-blue-500' :
                ($pitch->status == 'pending_review' ? 'bg-purple-50 border-purple-500' :
                ($pitch->status == 'denied' ? 'bg-red-50 border-red-500' :
                ($pitch->status == 'approved' ? 'bg-green-50 border-green-500' :
                ($pitch->status == 'revisions_requested' ? 'bg-amber-50 border-amber-500' :
                ($pitch->status == 'completed' ? 'bg-success/20 border-success' : 'bg-base-200'))))))
            }}">
                <div class="flex items-center">
                    <i class="fas {{
                        $pitch->status == 'pending' ? 'fa-clock' :
                        ($pitch->status == 'ready_for_review' ? 'fa-hourglass-half' :
                        ($pitch->status == 'pending_review' ? 'fa-search' :
                        ($pitch->status == 'denied' ? 'fa-times-circle' :
                        ($pitch->status == 'approved' ? 'fa-check-circle' :
                        ($pitch->status == 'revisions_requested' ? 'fa-exclamation-circle' :
                        ($pitch->status == 'completed' ? 'fa-trophy' : 'fa-info-circle'))))))
                    }} mr-3 text-lg"></i>
                    <div>
                        <p class="font-semibold">
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
                        <p class="text-sm text-gray-600 mt-1">
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
    <div class="mb-8 bg-red-50 border border-red-200 rounded-lg p-6">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 bg-red-100 rounded-full p-2">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-lg font-semibold text-red-800 mb-2">Your Pitch Has Been Denied</h4>
                <p class="text-red-700 mb-4">
                    The project owner has reviewed your pitch and has decided not to proceed with it at this time. You
                    can view their feedback below, make changes to your files, and resubmit if appropriate.
                </p>

                @if($snapshots->isNotEmpty())
                <div class="bg-white border border-red-200 rounded-lg p-4 mb-4">
                    <h5 class="font-medium text-red-800 mb-2">Feedback from Project Owner</h5>
                    <div class="text-gray-700">
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
                            class="btn btn-sm btn-red hover:bg-red-700">
                            <i class="fas fa-eye mr-1"></i>View Denied Snapshot
                        </a>
                        @else
                        <span class="text-gray-500 text-sm italic">No snapshot available</span>
                        @endif
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-700 mb-2">
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
    <div class="mb-8 bg-amber-50 border border-amber-200 rounded-lg p-6">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 bg-amber-100 rounded-full p-2">
                <i class="fas fa-pencil-alt text-amber-600 text-xl"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-lg font-semibold text-amber-800 mb-2">Revisions Have Been Requested</h4>
                <p class="text-amber-700 mb-4">
                    The project owner has reviewed your pitch and requested some changes. Please review the latest
                    snapshot and their feedback,
                    then make the necessary revisions and submit your updated pitch for review.
                </p>

                @if($snapshots->isNotEmpty())
                <div class="bg-white border border-amber-200 rounded-lg p-4 mb-4">
                    <h5 class="font-medium text-amber-800 mb-2">Feedback from Project Owner</h5>
                    <div class="text-gray-700">
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
                            class="btn btn-sm btn-amber hover:bg-amber-600">
                            <i class="fas fa-eye mr-1"></i>View Snapshot Details
                        </a>
                        @else
                        <span class="text-gray-500 text-sm italic">No snapshot available</span>
                        @endif
                    </div>
                </div>
                @endif
                @if($pitch->status === 'revisions_requested')
                <div class="flex flex-col sm:flex-row gap-3 mt-2">
                    <a href="{{ route('pitches.edit', $pitch->id) }}"
                        class="btn bg-amber-500 hover:bg-amber-600 text-white">
                        <i class="fas fa-edit mr-1"></i>Make Revisions & Resubmit
                    </a>
                    <button
                        onclick="window.scrollTo({top: document.querySelector('.tracks-container').offsetTop - 100, behavior: 'smooth'})"
                        class="btn btn-outline-amber">
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
    <div class="mb-8">
        <h4 class="text-xl font-semibold mb-4 flex items-center">
            <i class="fas fa-history mr-2 text-purple-500"></i>Submission History
        </h4>
        <div class="space-y-3">
            @foreach($snapshots as $snapshot)
            <div
                class="flex items-center justify-between p-4 bg-base-100 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <div class="flex-1">
                    <div class="flex items-center">
                        <i class="fas fa-version mr-2 text-gray-400"></i>
                        <a href="{{ route('pitches.showSnapshot', [$pitch->id, $snapshot->id]) }}"
                            class="font-medium hover:text-blue-600 transition-colors">
                            Version {{ $snapshot->snapshot_data['version'] }}
                        </a>
                        <span class="text-sm text-gray-500 ml-3">
                            {{ $snapshot->created_at->format('M d, Y H:i') }}
                        </span>
                    </div>
                    <div class="mt-1">
                        <span class="px-2 py-1 rounded-full text-sm {{
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
                        class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye mr-1"></i>View
                    </a>
                    <button wire:click="deleteSnapshot({{ $snapshot->id }})"
                        wire:confirm="Are you sure you want to delete this version?"
                        class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- File Management Section -->
    <div class="mb-8 tracks-container" x-data="{ 
            isUploading: false, 
            progress: 0,
            deleteModal: {
                isOpen: false,
                fileId: null,
                fileName: ''
            }
        }" x-on:livewire:initialized="
            Livewire.on('upload:start', () => { isUploading = true; })
            Livewire.on('upload:finish', () => { isUploading = false; })
            Livewire.on('upload:error', () => { isUploading = false; })
            Livewire.on('upload:progress', (progress) => { progress = progress; })"
        x-on:new-files-added.window="setTimeout(() => { $wire.clearHighlights() }, 2000)"
        x-on:new-uploads-completed.window="setTimeout(() => { $wire.clearUploadHighlights() }, 2000)">
        <h4 class="text-xl font-semibold mb-4 flex items-center">
            <i class="fas fa-folder-open mr-2 text-green-500"></i>File Management
        </h4>

        <div class="mb-4">
            <div class="flex flex-col">
                <label class="mb-2 text-gray-700">Upload new files</label>
                <div class="flex flex-col">
                    <div class="flex flex-col sm:flex-row gap-2 mb-2">
                        <div class="flex-grow min-w-0 overflow-hidden">
                            <label for="newUploadedFiles"
                                class="flex flex-col items-center justify-center w-full h-16 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 mr-2"></i>
                                    <span class="text-sm text-gray-500">
                                        Click to add files
                                    </span>
                                </div>
                                <input type="file" wire:model.live="newUploadedFiles" id="newUploadedFiles"
                                    class="hidden"
                                    accept="audio/mpeg,audio/wav,audio/mp3,audio/aac,audio/ogg,application/pdf,image/jpeg,image/png,image/gif,application/zip"
                                     />
                            </label>
                            @error('tempUploadedFiles.*') <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="flex-shrink-0">
                            <button wire:click="uploadFiles" wire:loading.attr="disabled"
                                class="btn btn-sm btn-primary hover:bg-primary-focus text-white w-full h-16 sm:h-full"
                                @if(empty($tempUploadedFiles)) disabled @endif>
                                <i class="fas fa-upload mr-2"></i> Upload
                            </button>
                        </div>
                    </div>

                    @if(count($tempUploadedFiles) > 0)
                    <div class="bg-base-200/50 p-3 rounded-lg mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium">Files to upload ({{ count($tempUploadedFiles) }})</h4>
                            <button wire:click="$set('tempUploadedFiles', []); $set('fileSizes', []);"
                                class="text-red-500 hover:text-red-700 transition-colors text-sm">
                                Clear All
                            </button>
                        </div>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach($tempUploadedFiles as $key => $file)
                            <div class="flex items-center justify-between bg-white p-2 rounded-md transition-all duration-500
                                @if(in_array($key, $newlyAddedFileKeys)) animate-fade-in @endif">
                                <div class="flex items-center flex-1 min-w-0">
                                    <i class="fas fa-file text-blue-500 mr-2"></i>
                                    <div class="truncate flex-1">
                                        {{ $file->getClientOriginalName() }}
                                        <span class="text-xs text-gray-500 ml-1">{{ $fileSizes[$key] ?? '' }}</span>
                                    </div>
                                </div>
                                <button wire:click="removeUploadedFile({{ $key }})"
                                    class="text-red-500 hover:text-red-700 transition-colors ml-2">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div x-show="isUploading" class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                        <div class="bg-primary h-2.5 rounded-full" x-bind:style="'width: ' + progress + '%'">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Uploaded Files List -->
        @if($existingFiles->isEmpty())
        <div class="bg-base-200/50 p-4 rounded-lg text-center text-gray-500">
            <i class="fas fa-inbox text-3xl mb-2"></i>
            <p>No files uploaded yet</p>
        </div>
        @else
        <div class="border border-base-300 rounded-lg overflow-hidden">
            <div class="bg-base-300/50 p-2 flex items-center text-sm font-medium">
                <div class="flex-1 px-2">Filename</div>
                <div class="w-24 text-right">Size</div>
                <div class="w-32 text-center">Actions</div>
            </div>
            @foreach ($existingFiles as $file)
            <div class="flex items-center justify-between p-3 even:bg-base-200/30 hover:bg-base-100 transition-colors
                @if(in_array($file->id, $newlyUploadedFileIds)) bg-green-100 @endif">
                <div class="flex-1 truncate px-2">
                    <i class="fas fa-file-alt text-gray-400 mr-2"></i>
                    {{ $file->file_name ?? basename($file->file_path) }}
                </div>
                <div class="w-24 text-right text-sm text-gray-500">
                    {{ $this->formatFileSize($file->size ?? 0) }}
                </div>
                <div class="w-32 text-center space-x-2">
                    <button wire:click="downloadFile('{{ $file->id }}')" class="btn btn-xs btn-outline-primary">
                        <i class="fas fa-download"></i>
                    </button>
                    <button
                        @click="deleteModal.isOpen = true; deleteModal.fileId = '{{ $file->id }}'; deleteModal.fileName = '{{ $file->file_name ?? basename($file->file_path) }}'"
                        class="btn btn-xs btn-outline-danger">
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
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <!-- Modal Panel -->
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-exclamation-triangle text-red-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Delete File
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Are you sure you want to delete <span class="font-semibold"
                                            x-text="deleteModal.fileName"></span>? This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="deleteFile(deleteModal.fileId)"
                            @click="deleteModal.isOpen = false"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Delete
                        </button>
                        <button type="button" @click="deleteModal.isOpen = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
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
    <div class="mt-4 flex flex-col md:flex-row justify-end items-center">
        @error('acceptedTerms')
        <span class="text-red-500 text-sm mx-1">{{ $message }}</span>
        @enderror
        <div class="flex items-center mb-2 md:mb-0 md:mr-4">
            <input type="checkbox" id="terms" class="form-checkbox h-5 w-5 text-green-600"
                wire:model.defer="acceptedTerms">
            <label for="terms" class="px-2 text-sm text-gray-700">I accept the <a href="/terms" target="_blank"
                    class="text-blue-500 hover:underline">terms and conditions</a></label>
        </div>

        <button wire:click="submitForReview" wire:confirm="Are you sure you want to Submit your Pitch?"
            class="bg-green-500 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded"
            :disabled="!acceptedTerms">
            <i class="fas fa-check pr-2"></i>
            {{ $pitch->status == 'denied' || $pitch->status == 'revisions_requested' ? 'Resubmit Pitch' : 'Ready To
            Submit' }}
        </button>
    </div>
    @endif

    <!-- Cancel submission button section -->
    @if($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW && auth()->id() === $pitch->user_id)
    <div class="mt-4 flex justify-end">
        <button wire:click="cancelPitchSubmission"
            wire:confirm="Are you sure you want to cancel your submission? This will return your pitch to 'In Progress' status and delete the current pending snapshot."
            class="bg-red-500 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
            <i class="fas fa-xmark pr-2"></i>
            Cancel Submission
        </button>
    </div>
    @endif
</div>