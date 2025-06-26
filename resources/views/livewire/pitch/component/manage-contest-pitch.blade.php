@php 
    use Illuminate\Support\Str; 
@endphp

<div class="w-full">
    {{-- Load necessary Font Awesome icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    {{-- Contest Entry Header - Only show if not submitted --}}
    @if(!$pitch->submitted_at)
        <div class="bg-gradient-to-br from-yellow-50/90 to-amber-50/90 backdrop-blur-sm border border-yellow-200/50 rounded-2xl shadow-lg overflow-hidden mb-6">
            <div class="p-6 bg-gradient-to-r from-yellow-100/80 to-amber-100/80 backdrop-blur-sm border-b border-yellow-200/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl mr-3">
                            <i class="fas fa-trophy text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-yellow-800">Contest Entry Management</h2>
                            <p class="text-sm text-yellow-700">Upload and manage your contest entry files</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 border border-blue-200">
                            {{ $pitch->readable_status }}
                        </span>
                    </div>
                </div>
            </div>
            
            {{-- Contest Info Grid --}}
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white/60 backdrop-blur-sm border border-yellow-200/30 rounded-xl p-4">
                        <dt class="text-sm font-medium text-yellow-700 mb-2">Contest</dt>
                        <dd class="text-lg font-bold text-yellow-900">{{ $project->name }}</dd>
                    </div>
                    
                    <div class="bg-white/60 backdrop-blur-sm border border-yellow-200/30 rounded-xl p-4">
                        <dt class="text-sm font-medium text-yellow-700 mb-2">Files Uploaded</dt>
                        <dd class="text-lg font-bold text-yellow-900">{{ $files->total() }} {{ Str::plural('file', $files->total()) }}</dd>
                    </div>
                    
                    @if($project->submission_deadline)
                        <div class="bg-white/60 backdrop-blur-sm border border-yellow-200/30 rounded-xl p-4">
                            <dt class="text-sm font-medium text-yellow-700 mb-2">Deadline</dt>
                            <dd class="text-sm font-medium text-yellow-900">
                                <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" />
                                <div class="text-xs text-yellow-700 mt-1">
                                    <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" />
                                </div>
                                <span class="text-xs text-yellow-700"><x-datetime :date="$project->submission_deadline" relative="true" /></span>
                            </dd>
                        </div>
                    @endif
                    
                    <div class="bg-white/60 backdrop-blur-sm border border-yellow-200/30 rounded-xl p-4">
                        <dt class="text-sm font-medium text-yellow-700 mb-2">Submission Status</dt>
                        <dd class="text-sm font-medium">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                                <i class="fas fa-clock mr-1"></i>
                                Draft
                            </span>
                        </dd>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Contest Instructions --}}
    @if($pitch->submitted_at)
        <div class="bg-gradient-to-r from-green-50/80 to-emerald-50/80 backdrop-blur-sm border border-green-200/50 rounded-xl p-4 mb-6">
            <div class="flex items-start">
                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mr-3 flex-shrink-0">
                    <i class="fas fa-check-circle text-white text-sm"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-green-800 mb-2">Contest Entry Submitted Successfully!</h4>
                    <ul class="text-sm text-green-700 space-y-1">
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 text-green-600 mt-0.5 text-xs"></i>
                            Your contest entry was submitted on {{ $pitch->submitted_at->format('M d, Y \a\t H:i') }}
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 text-green-600 mt-0.5 text-xs"></i>
                            Files can no longer be uploaded or modified
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 text-green-600 mt-0.5 text-xs"></i>
                            You can still download your submitted files
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-trophy mr-2 text-green-600 mt-0.5 text-xs"></i>
                            Results will be announced after the contest closes
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    @else
        <div class="bg-gradient-to-r from-blue-50/80 to-indigo-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 mb-6">
            <div class="flex items-start">
                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-3 flex-shrink-0">
                    <i class="fas fa-info-circle text-white text-sm"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-blue-800 mb-2">Contest Entry Guidelines</h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 text-blue-600 mt-0.5 text-xs"></i>
                            Upload your best work - this is your one submission for the contest
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 text-blue-600 mt-0.5 text-xs"></i>
                            You can update or replace files anytime before submitting
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 text-blue-600 mt-0.5 text-xs"></i>
                            Click "Submit Contest Entry" when you're ready to finalize your submission
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-upload mr-2 text-blue-600 mt-0.5 text-xs"></i>
                            Storage limit: 100MB - Maximum file size: 200MB
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Project Files Download Section --}}
    @if($project->files->count() > 0)
        <div class="bg-gradient-to-br from-green-50/90 to-emerald-50/90 backdrop-blur-sm border border-green-200/50 rounded-2xl shadow-lg overflow-hidden mb-6">
            <div class="p-4 bg-gradient-to-r from-green-100/80 to-emerald-100/80 backdrop-blur-sm border-b border-green-200/50">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mr-3">
                        <i class="fas fa-download text-white text-sm"></i>
                    </div>
                    <h3 class="font-bold text-green-800">Download Project Files</h3>
                </div>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($project->files as $projectFile)
                        <div class="flex items-center justify-between p-3 bg-green-50/50 rounded-lg border border-green-200/30">
                            <div class="flex items-center min-w-0 flex-1">
                                <i class="fas fa-file-alt mr-3 text-green-600 flex-shrink-0"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-green-900 font-medium truncate">{{ $projectFile->file_name }}</p>
                                    <p class="text-xs text-green-700">{{ $this->formatFileSize($projectFile->size) }}</p>
                                </div>
                            </div>
                            <a href="{{ route('projects.files.download', ['project' => $project, 'file' => $projectFile]) }}"
                               class="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-100 to-emerald-100 hover:from-green-200 hover:to-emerald-200 text-green-600 rounded-lg transition-all duration-200 hover:scale-105 flex-shrink-0 ml-2">
                                <i class="fas fa-download text-sm"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- File Upload Management Section --}}
    <div class="bg-gradient-to-br from-white/95 to-purple-50/50 backdrop-blur-sm border border-purple-200/30 rounded-2xl shadow-lg overflow-hidden mb-6">
        <div class="p-6 bg-gradient-to-r from-purple-100/60 to-indigo-100/60 backdrop-blur-sm border-b border-purple-200/30">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-3">
                    <i class="fas fa-file-upload text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-purple-800">Contest Entry Files</h3>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- Storage Usage Display --}}
            <div class="bg-gradient-to-br from-blue-50/90 to-indigo-50/90 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-sm font-bold text-blue-800">Storage Used: {{ $storageLimitMessage }}</span>
                    <span class="text-xs text-blue-600 bg-blue-100/50 px-2 py-1 rounded-lg">{{ $this->formatFileSize($storageRemaining) }} remaining</span>
                </div>
                <div class="w-full bg-blue-200/50 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full transition-all duration-500 {{ $storageUsedPercentage > 90 ? 'bg-gradient-to-r from-red-500 to-red-600' : ($storageUsedPercentage > 70 ? 'bg-gradient-to-r from-amber-500 to-yellow-500' : 'bg-gradient-to-r from-blue-500 to-indigo-600') }}"
                        style="width: {{ $storageUsedPercentage }}%"></div>
                </div>
                <div class="mt-2 flex items-center text-xs text-blue-600">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Contest limit: 100MB total storage. Maximum file size: 200MB.
                </div>
            </div>

            {{-- File Upload Section --}}
            @if(!$pitch->submitted_at)
                <div class="bg-gradient-to-br from-white/95 to-purple-50/50 backdrop-blur-sm border border-purple-200/30 rounded-xl shadow-sm overflow-hidden">
                    <div class="p-4 bg-gradient-to-r from-purple-100/60 to-indigo-100/60 backdrop-blur-sm border-b border-purple-200/30">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg mr-3">
                                <i class="fas fa-cloud-upload-alt text-white text-sm"></i>
                            </div>
                            <div>
                                <h5 class="font-bold text-purple-800">Upload Contest Entry Files</h5>
                                <p class="text-xs text-purple-600 mt-1">Upload audio, PDFs, or images for your contest entry</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <livewire:file-uploader :model="$pitch" wire:key="'contest-uploader-' . $pitch->id" />
                    </div>
                </div>
            @else
                <div class="bg-gradient-to-br from-green-50/90 to-emerald-50/90 backdrop-blur-sm border border-green-200/50 rounded-xl shadow-sm overflow-hidden">
                    <div class="p-4 bg-gradient-to-r from-green-100/60 to-emerald-100/60 backdrop-blur-sm border-b border-green-200/30">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mr-3">
                                <i class="fas fa-check-circle text-white text-sm"></i>
                            </div>
                            <div>
                                <h5 class="font-bold text-green-800">Contest Entry Submitted</h5>
                                <p class="text-xs text-green-600 mt-1">Your entry has been submitted and can no longer be modified</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center justify-center py-8 text-green-700">
                            <div class="text-center">
                                <i class="fas fa-trophy text-3xl text-green-600 mb-3"></i>
                                <p class="font-medium">Entry successfully submitted!</p>
                                <p class="text-sm text-green-600 mt-1">Submitted on {{ $pitch->submitted_at->format('M d, Y \a\t H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Current Files List --}}
            @if($files->count() > 0)
                <div class="bg-gradient-to-br from-white/95 to-purple-50/50 backdrop-blur-sm border border-purple-200/30 rounded-xl shadow-sm overflow-hidden">
                    <div class="p-4 bg-gradient-to-r from-purple-100/60 to-indigo-100/60 backdrop-blur-sm border-b border-purple-200/30">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg mr-3">
                                <i class="fas fa-folder text-white text-sm"></i>
                            </div>
                            <h5 class="font-bold text-purple-800">Your Contest Entry Files</h5>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="space-y-3">
                            @foreach($files as $file)
                                <div class="flex items-center justify-between p-3 bg-purple-50/50 rounded-lg border border-purple-200/30 hover:bg-purple-50/70 transition-all duration-200">
                                    <div class="flex items-center min-w-0 flex-1">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-lg mr-3 flex-shrink-0">
                                            @if(str_starts_with($file->mime_type, 'audio/'))
                                                <i class="fas fa-music text-purple-600"></i>
                                            @elseif(str_starts_with($file->mime_type, 'image/'))
                                                <i class="fas fa-image text-purple-600"></i>
                                            @elseif($file->mime_type === 'application/pdf')
                                                <i class="fas fa-file-pdf text-purple-600"></i>
                                            @else
                                                <i class="fas fa-file text-purple-600"></i>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-purple-900 font-medium truncate">{{ $file->file_name }}</p>
                                            <div class="flex items-center text-xs text-purple-600 mt-1">
                                                <span>{{ $this->formatFileSize($file->size) }}</span>
                                                <span class="mx-2">•</span>
                                                <span>{{ $file->created_at->format('M d, Y H:i') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 flex-shrink-0 ml-2">
                                        <button wire:click="downloadFile({{ $file->id }})"
                                            class="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-100 to-indigo-100 hover:from-blue-200 hover:to-indigo-200 text-blue-600 rounded-lg transition-all duration-200 hover:scale-105">
                                            <i class="fas fa-download text-sm"></i>
                                        </button>
                                        @if(!$pitch->submitted_at)
                                            <button wire:click="confirmDeleteFile({{ $file->id }})"
                                                class="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-br from-red-100 to-pink-100 hover:from-red-200 hover:to-pink-200 text-red-600 rounded-lg transition-all duration-200 hover:scale-105">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Pagination --}}
                        @if($files->hasPages())
                            <div class="mt-4">
                                {{ $files->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-gradient-to-br from-gray-50/90 to-gray-100/90 backdrop-blur-sm border border-gray-200/50 rounded-xl p-8 text-center">
                    <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-200 to-gray-300 rounded-full mx-auto mb-4">
                        <i class="fas fa-folder-open text-gray-500 text-xl"></i>
                    </div>
                    <h4 class="text-gray-700 font-medium mb-2">No files uploaded yet</h4>
                    <p class="text-gray-600 text-sm">Upload your contest entry files using the upload section above.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Contest Entry Actions --}}
    <div class="bg-gradient-to-br from-gray-50/90 to-gray-100/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 bg-gradient-to-r from-gray-100/80 to-gray-200/80 backdrop-blur-sm border-b border-gray-200/50">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl mr-3">
                    <i class="fas fa-cog text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Contest Entry Actions</h3>
            </div>
        </div>
        <div class="p-6">
            <div class="flex flex-col sm:flex-row gap-4">
                @if(!$pitch->submitted_at)
                    <button wire:click="submitEntry" 
                        wire:confirm="Are you sure you want to submit your contest entry? Once submitted, you cannot upload or modify files."
                        class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Submit Contest Entry
                    </button>
                @endif
                
                @if(!$pitch->submitted_at)
                    <button wire:click="deletePitch" 
                        wire:confirm="Are you sure you want to delete your contest entry? This action cannot be undone."
                        class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Contest Entry
                    </button>
                @endif
                
                <a href="{{ route('projects.show', $project) }}"
                   class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Contest
                </a>
            </div>
        </div>
    </div>

    {{-- Delete File Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click="$set('showDeleteModal', false)">
            <div class="bg-white rounded-2xl p-6 max-w-md mx-4 shadow-2xl" wire:click.stop>
                <div class="flex items-center mb-4">
                    <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-full mr-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Delete File</h3>
                </div>
                <p class="text-gray-600 mb-6">Are you sure you want to delete this file? This action cannot be undone.</p>
                <div class="flex justify-end space-x-3">
                    <button wire:click="$set('showDeleteModal', false)" 
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button wire:click="deleteFile" 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                        Delete File
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    // Add browser event listeners to ensure storage updates
    window.addEventListener('filesUploaded', function() {
        window.Livewire.find('{{ $this->getId() }}').call('refreshContestData');
    });
    
    // Also listen for Livewire custom events
    document.addEventListener('livewire:init', function () {
        Livewire.on('filesUploaded', function () {
            window.Livewire.find('{{ $this->getId() }}').call('refreshContestData');
        });
        
        Livewire.on('refreshContestData', function () {
            window.Livewire.find('{{ $this->getId() }}').call('refreshContestData');
        });
    });
</script> 