@php 
    use Illuminate\Support\Str; 
@endphp
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
                            <span class="inline-block">Current Status:</span>
                            <span class="inline-block ml-1 font-bold">
                            {{ match($pitch->status) {
                            'pending' => 'Awaiting Project Owner Access',
                            'ready_for_review' => 'Pitch Under Review',
                            'pending_review' => 'Response Requires Review',
                            'denied' => 'Pitch Not Accepted',
                            'approved' => 'Pitch Approved!',
                            'revisions_requested' => 'Revisions Requested',
                            'completed' => 'Pitch Successfully Completed',
                            'in_progress' => 'In Progress',
                            default => ucfirst(str_replace('_', ' ', $pitch->status))
                            } }}
                            </span>
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
                <h4 class="text-base sm:text-lg font-semibold text-red-800 mb-1.5 sm:mb-2">Your Pitch Has Been Denied
                </h4>
                <p class="text-sm text-red-700 mb-3 sm:mb-4">
                    The project owner has reviewed your pitch and has decided not to proceed with it at this time. You
                    can view their feedback below, make changes to your files, and resubmit if appropriate.
                </p>

                @if($snapshots->isNotEmpty())
                <div class="bg-white border border-red-200 rounded-lg p-3 sm:p-4 mb-3 sm:mb-4">
                    <h5 class="font-medium text-sm sm:text-base text-red-800 mb-1.5 sm:mb-2">Feedback from Project Owner
                    </h5>
                    <div class="text-xs sm:text-sm text-gray-700">
                        @if (!empty($statusFeedbackMessage))
                            {!! nl2br(e($statusFeedbackMessage)) !!}
                        @else
                            <span class="italic text-gray-500">No specific feedback was provided. Please review your pitch and consider making improvements before resubmitting.</span>
                        @endif
                    </div>
                    <div class="mt-3">
                        @if($pitch->currentSnapshot)
                        <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $pitch->currentSnapshot->id]) }}"
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
                <h4 class="text-base sm:text-lg font-semibold text-amber-800 mb-1.5 sm:mb-2">Revisions Have Been
                    Requested</h4>
                <p class="text-sm text-amber-700 mb-3 sm:mb-4">
                    The project owner has reviewed your pitch and requested some changes. Please review the latest
                    snapshot and their feedback,
                    then make the necessary revisions and submit your updated pitch for review.
                </p>

                @if($snapshots->isNotEmpty())
                <div class="bg-white border border-amber-200 rounded-lg p-3 sm:p-4 mb-3 sm:mb-4">
                    <h5 class="font-medium text-sm sm:text-base text-amber-800 mb-1.5 sm:mb-2">Feedback from Project
                        Owner</h5>
                    <div class="text-xs sm:text-sm text-gray-700 prose max-w-none prose-sm">
                         @if (!empty($statusFeedbackMessage))
                            {!! nl2br(e($statusFeedbackMessage)) !!}
                        @else
                            <span class="italic text-gray-500">No specific feedback was provided. Please review the latest snapshot for details.</span>
                        @endif
                    </div>
                    <div class="mt-3">
                        @if($latestSnapshot)
                            <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $latestSnapshot->id]) }}"
                                class="btn btn-sm btn-amber hover:bg-amber-600 text-xs sm:text-sm py-1.5">
                                <i class="fas fa-eye mr-1"></i>View Snapshot Details
                            </a>
                        @else
                            <span class="text-gray-500 text-xs sm:text-sm italic">No snapshot available</span>
                        @endif
                    </div>
                </div>
                @endif
                {{-- Start: Add Revision Form Here --}}
                @if($pitch->status === 'revisions_requested')
                    <div class="mt-6 border-t border-amber-200 pt-6">
                        <h5 class="font-semibold text-base text-amber-800 mb-3">Respond to Feedback & Resubmit</h5>
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text text-sm font-medium text-gray-700">Your Response</span>
                                <span class="label-text-alt text-amber-600 text-xs"><i class="fas fa-info-circle mr-1"></i>This message will be visible to the project owner</span>
                            </label>
                            <div class="bg-amber-50 border border-amber-200 p-2 rounded-t-lg">
                                <p class="text-amber-800 text-xs mb-1"><i class="fas fa-comment-dots mr-1"></i>Your response will appear in the feedback conversation history.</p>
                            </div>
                            <textarea wire:model.lazy="responseToFeedback" rows="5"
                                class="textarea textarea-bordered w-full bg-white border-amber-200 rounded-t-none text-sm"
                                placeholder="Explain what changes you've made in response to the feedback..."></textarea>
                            {{-- @error('responseToFeedback') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror --}}
                            {{-- Note: Livewire validation errors are typically handled differently, often shown automatically or via $errors bag --}}
                        </div>

                        <div class="bg-gray-50 p-3 rounded-lg mb-4">
                            <h6 class="text-sm font-medium mb-2">Before Resubmitting:</h6>
                            <ul class="list-disc pl-4 space-y-1 text-gray-700 text-xs">
                                <li>Ensure you have uploaded any necessary new files above.</li>
                                <li>Explain the changes you made in the response field.</li>
                            </ul>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 mt-2">
                            {{-- Removed the incorrect <a> tag link --}}
                            <button wire:click="submitForReview" wire:loading.attr="disabled"
                                class="btn bg-amber-500 hover:bg-amber-600 text-white text-sm py-2.5 sm:py-2">
                                <span wire:loading wire:target="submitForReview" class="loading loading-spinner loading-xs mr-2"></span>
                                <i wire:loading.remove wire:target="submitForReview" class="fas fa-paper-plane mr-2"></i>
                                Submit Revisions
                            </button>
                             <button
                                onclick="window.scrollTo({top: document.querySelector('.tracks-container').offsetTop - 100, behavior: 'smooth'})"
                                class="btn btn-outline-amber text-sm py-2.5 sm:py-2">
                                <i class="fas fa-upload mr-1"></i>Upload New Files
                            </button>
                        </div>
                    </div>
                @endif
                 {{-- End: Add Revision Form Here --}}
            </div>
        </div>
    </div>
    @endif

    <!-- Completed Pitch Feedback Section -->
    @if($pitch->status == 'completed' && !empty($pitch->completion_feedback))
    <div class="mb-4 sm:mb-8 bg-success/10 border border-success/30 rounded-lg p-3 sm:p-6">
        <div class="flex items-start space-x-3 sm:space-x-4">
            <div class="flex-shrink-0 bg-success/20 rounded-full p-1.5 sm:p-2">
                <i class="fas fa-trophy text-success text-lg sm:text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="text-base sm:text-lg font-semibold text-success mb-1.5 sm:mb-2">Completion Feedback</h4>
                <p class="text-sm text-gray-700 mb-3 sm:mb-4">
                    The project owner provided the following feedback when completing your pitch:
                </p>

                <div class="bg-white border border-success/30 rounded-lg p-3 sm:p-4">
                    <div class="text-xs sm:text-sm text-gray-700 whitespace-pre-wrap">
                        {{ $pitch->completion_feedback }}
                    </div>
                </div>
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
                        <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $snapshot->id]) }}"
                            class="font-medium hover:text-blue-600 transition-colors text-sm">
                            Version {{ $snapshot->snapshot_data['version'] ?? 1 }}
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
                    <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $snapshot->id]) }}"
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
    @if($pitch->status !== \App\Models\Pitch::STATUS_PENDING)
    <div class="mb-4 sm:mb-8 tracks-container">
        <h4 class="text-lg sm:text-xl font-semibold mb-2.5 sm:mb-4 flex items-center">
            <i class="fas fa-file-upload text-purple-500 mr-2"></i>Upload Files
        </h4>

        <!-- Storage usage display -->
        <div class="mb-4 bg-base-200/50 p-3 rounded-lg">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium">Storage Used: {{ $storageLimitMessage }}</span>
                <span class="text-xs text-gray-500">{{ $this->formatFileSize($storageRemaining) }} remaining</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-primary h-2.5 rounded-full transition-all duration-500 {{ $storageUsedPercentage > 90 ? 'bg-red-500' : ($storageUsedPercentage > 70 ? 'bg-amber-500' : 'bg-primary') }}"
                    style="width: {{ $storageUsedPercentage }}%"></div>
            </div>
            <div class="mt-2 text-xs text-gray-500">
                <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                Maximum file size: 200MB. Total storage limit: 1GB.
            </div>
        </div>

        <!-- File Upload Section -->
        <div class="bg-white rounded-lg border border-base-300 shadow-sm overflow-hidden mb-6">
            <div class="p-4 border-b border-base-200 bg-base-100/50">
                <h5 class="font-medium text-base">Upload New Files</h5>
                <p class="text-xs text-gray-500 mt-1">Upload audio, PDFs, or images to include with your pitch</p>
            </div>
            <div class="p-4">
                <livewire:file-uploader :model="$pitch" wire:key="'pitch-uploader-' . $pitch->id" />
            </div>
        </div>

        <!-- Existing Files List -->
        <div class="bg-white rounded-lg border border-base-300 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-base-200 bg-base-100/50 flex justify-between items-center">
                <h5 class="font-medium text-base">Pitch Files ({{ $pitch->files->count() }})</h5>
                @if($pitch->files->count() > 0)
                <span class="text-xs text-gray-500">Total: {{ $this->formatFileSize($pitch->files->sum('size')) }}</span>
                @endif
            </div>
            
            <div class="divide-y divide-base-200">
                @forelse($pitch->files as $file)
                <div class="flex items-center justify-between py-3 px-4 hover:bg-base-100/50 transition-all duration-300 track-item
                    @if(in_array($file->id, $newlyUploadedFileIds ?? [])) animate-fade-in @endif">
                    <div class="flex items-center overflow-hidden flex-1 pr-2">
                        <div
                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex-shrink-0 flex items-center justify-center bg-base-200 text-gray-500 mr-3">
                            {{-- Adjust icon based on file type --}}
                            @if (Str::startsWith($file->mime_type, 'audio/'))
                                <i class="fas fa-music text-sm sm:text-base"></i>
                            @elseif ($file->mime_type == 'application/pdf')
                                <i class="fas fa-file-pdf text-sm sm:text-base text-red-500"></i>
                            @elseif (Str::startsWith($file->mime_type, 'image/'))
                                <i class="fas fa-file-image text-sm sm:text-base text-blue-500"></i>
                            @else
                                <i class="fas fa-file-alt text-sm sm:text-base"></i>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-medium truncate text-sm sm:text-base">{{ $file->file_name }}</div>
                            <div class="flex items-center text-xs text-gray-500">
                                <span>{{ $file->created_at->format('M d, Y') }}</span>
                                <span class="mx-1.5">•</span>
                                <span>{{ $this->formatFileSize($file->size) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1 sm:space-x-2">
                        {{-- Download Button --}}
                        <button wire:click="downloadFile({{ $file->id }})"
                            class="btn btn-sm btn-ghost text-gray-600 hover:text-blue-600">
                            <i class="fas fa-download"></i>
                        </button>
                        {{-- Delete Button --}}
                        <button wire:click="setFileToDelete({{ $file->id }})" 
                                x-on:click="$dispatch('open-modal', 'delete-file-modal')"
                                class="btn btn-sm btn-ghost text-gray-600 hover:text-red-600">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                @empty
                <div class="p-8 sm:p-10 text-center text-gray-500 italic">
                    <i class="fas fa-folder-open text-4xl sm:text-5xl text-gray-300 mb-3"></i>
                    <p class="text-base sm:text-lg">No files uploaded yet</p>
                    <p class="text-xs sm:text-sm mt-2">Upload files to include with your pitch</p>
                </div>
                @endforelse
            </div>
        </div>
        
        <!-- File Delete Confirmation Modal -->
        <div x-data="{ fileToDelete: null }" x-on:close-modal.window="fileToDelete = null">
            <x-modal name="delete-file-modal" :show="false" maxWidth="md">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                        Delete File
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Are you sure you want to delete this file? This action cannot be undone.
                    </p>
                    <div class="mt-6 flex justify-end">
                        <button x-on:click="$dispatch('close-modal')"
                            class="mr-3 btn btn-sm btn-ghost">
                            Cancel
                        </button>
                        <button wire:click="deleteSelectedFile"
                            x-on:click="$dispatch('close-modal')"
                            class="btn btn-sm btn-red">
                            <i class="fas fa-trash-alt mr-1.5"></i>
                            Delete File
                        </button>
                    </div>
                </div>
            </x-modal>
        </div>
    </div>
    @endif

    <!-- Submit for review button section -->
    @if(in_array($pitch->status, [
        \App\Models\Pitch::STATUS_IN_PROGRESS, 
        \App\Models\Pitch::STATUS_REVISIONS_REQUESTED, 
        \App\Models\Pitch::STATUS_DENIED
    ]))
    <div class="mt-4 sm:mt-6 flex flex-col sm:flex-row justify-end items-start sm:items-center space-y-2 sm:space-y-0">
        @error('acceptedTerms')
        <span class="text-red-500 text-xs sm:text-sm">{{ $message }}</span>
        @enderror
        <div class="flex items-center w-full sm:w-auto mb-2 sm:mb-0 sm:mr-4">
            <input type="checkbox" id="terms" class="form-checkbox h-4 w-4 sm:h-5 sm:w-5 text-green-600"
                wire:model.defer="acceptedTerms">
            <label for="terms" class="px-2 text-xs sm:text-sm text-gray-700">I accept the <a href="/terms"
                    target="_blank" class="text-blue-500 hover:underline">terms and conditions</a></label>
        </div>

        <button wire:click="submitForReview" wire:confirm="Are you sure you want to Submit your Pitch?"
            class="w-full sm:w-auto bg-green-500 hover:bg-green-700 text-white text-sm font-semibold py-2.5 sm:py-2 px-4 rounded"
            :disabled="!acceptedTerms">
            <i class="fas fa-check pr-1.5 sm:pr-2"></i>
            {{ $pitch->status == 'denied' || $pitch->status == 'revisions_requested' ? 'Resubmit Pitch' : 'Ready
            To Submit' }}
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