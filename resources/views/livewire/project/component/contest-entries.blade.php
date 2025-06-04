<div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden">
    <!-- Background Effects -->
    <div class="absolute inset-0 bg-gradient-to-br from-purple-50/30 via-blue-50/20 to-indigo-50/30"></div>
    <div class="absolute top-4 right-4 w-20 h-20 bg-purple-400/10 rounded-full blur-xl"></div>
    <div class="absolute bottom-4 left-4 w-16 h-16 bg-blue-400/10 rounded-full blur-lg"></div>
    
    <!-- Header -->
    <div class="relative bg-gradient-to-r from-purple-100/80 to-indigo-100/80 backdrop-blur-sm border-b border-purple-200/50 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-4">
                    <i class="fas fa-trophy text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold bg-gradient-to-r from-purple-700 to-indigo-700 bg-clip-text text-transparent">
                        Contest Entries
                    </h3>
                    <p class="text-sm text-purple-600 font-medium">{{ $entries->total() }} {{ Str::plural('entry', $entries->total()) }} submitted</p>
                </div>
            </div>
            
            <!-- Contest Status Badge -->
            @if(!$canSelectWinner)
                <div class="bg-amber-100/80 backdrop-blur-sm border border-amber-200/50 rounded-xl px-4 py-2">
                    <div class="flex items-center text-amber-700">
                        <i class="fas fa-clock mr-2"></i>
                        <span class="font-medium">Submissions Open</span>
                    </div>
                </div>
            @elseif($isFinalized && !$winnerExists)
                <div class="bg-purple-100/80 backdrop-blur-sm border border-purple-200/50 rounded-xl px-4 py-2">
                    <div class="flex items-center text-purple-700">
                        <i class="fas fa-flag-checkered mr-2"></i>
                        <span class="font-medium">Judging Finalized</span>
                    </div>
                </div>
            @elseif($isFinalized && $winnerExists)
                <div class="bg-blue-100/80 backdrop-blur-sm border border-blue-200/50 rounded-xl px-4 py-2">
                    <div class="flex items-center text-blue-700">
                        <i class="fas fa-crown mr-2"></i>
                        <span class="font-medium">Winners Announced</span>
                    </div>
                </div>
            @elseif(!$winnerExists)
                <div class="bg-green-100/80 backdrop-blur-sm border border-green-200/50 rounded-xl px-4 py-2">
                    <div class="flex items-center text-green-700">
                        <i class="fas fa-gavel mr-2"></i>
                        <span class="font-medium">Ready for Judging</span>
                    </div>
                </div>
            @else
                <div class="bg-blue-100/80 backdrop-blur-sm border border-blue-200/50 rounded-xl px-4 py-2">
                    <div class="flex items-center text-blue-700">
                        <i class="fas fa-crown mr-2"></i>
                        <span class="font-medium">Winner Selected</span>
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Deadline Information -->
        @if($project->submission_deadline)
            <div class="mt-4 p-3 bg-white/60 backdrop-blur-sm border border-purple-200/50 rounded-xl">
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center text-purple-700">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <span class="font-medium">Submission Deadline:</span>
                    </div>
                    <div class="text-purple-800 font-bold">
                        {{ $project->submission_deadline->format('M d, Y \a\t H:i T') }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Content -->
    <div class="relative p-6">
        @if($entries->isEmpty())
            <!-- Empty State -->
            <div class="text-center py-16">
                <div class="mx-auto w-24 h-24 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                    <i class="fas fa-trophy text-4xl text-purple-500"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-3">No Entries Yet</h3>
                <p class="text-gray-600 max-w-md mx-auto leading-relaxed">
                    Contest entries will appear here as producers submit their work. 
                    @if(!$canSelectWinner)
                        The submission deadline is {{ $project->submission_deadline ? $project->submission_deadline->format('M d, Y') : 'not set' }}.
                    @endif
                </p>
            </div>
        @else
            <!-- Entries Grid -->
            <div class="space-y-6">
                @foreach($entries as $entry)
                    <div class="bg-white/80 backdrop-blur-sm border border-white/40 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-[1.02] 
                        @if($entry->status === \App\Models\Pitch::STATUS_CONTEST_WINNER) 
                            ring-2 ring-yellow-400/50 bg-gradient-to-r from-yellow-50/80 to-amber-50/80
                        @elseif($entry->status === \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP) 
                            ring-2 ring-blue-400/50 bg-gradient-to-r from-blue-50/80 to-indigo-50/80
                        @endif">
                        
                        <!-- Entry Header -->
                        <div class="p-6 border-b border-white/30">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <!-- Producer Avatar -->
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                        @if($entry->user->profile_photo_path)
                                            <img src="{{ $entry->user->profile_photo_url }}" alt="{{ $entry->user->name }}" class="w-full h-full rounded-xl object-cover">
                                        @else
                                            <span class="text-white font-bold text-lg">{{ substr($entry->user->name, 0, 1) }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Producer Info -->
                                    <div>
                                        <h4 class="text-base font-bold text-gray-900">{{ $entry->user->name }}</h4>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-calendar mr-2"></i>
                                            <span>Submitted {{ $entry->created_at->format('M j, Y') }}</span>
                                            @if($entry->rank)
                                                <span class="ml-4 flex items-center">
                                                    <i class="fas fa-award mr-1 text-yellow-500"></i>
                                                    Rank {{ $entry->rank }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Status Badge -->
                                <div>
                                    @if($entry->status === \App\Models\Pitch::STATUS_CONTEST_WINNER)
                                        <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-800 border border-yellow-300 shadow-md">
                                            <i class="fas fa-crown mr-2"></i>Winner
                                        </span>
                                    @elseif($entry->status === \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP)
                                        <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border border-blue-300 shadow-md">
                                            <i class="fas fa-medal mr-2"></i>Runner-up
                                        </span>
                                    @elseif($entry->status === \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED)
                                        <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium bg-gray-100/80 text-gray-700 border border-gray-200">
                                            <i class="fas fa-times-circle mr-2"></i>Not Selected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium bg-gradient-to-r from-purple-100 to-indigo-100 text-purple-800 border border-purple-200 shadow-sm">
                                            <i class="fas fa-paper-plane mr-2"></i>Entry
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="p-6 bg-gradient-to-r from-gray-50/80 to-white/80">
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Winner Selection -->
                                @if(!$winnerExists && $entry->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY && $canSelectWinner)
                                    <button wire:click="selectWinner({{ $entry->id }})" 
                                            onclick="return confirm('Are you sure you want to select this entry as the winner? This action cannot be undone.')"
                                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 shadow-lg hover:shadow-xl text-sm">
                                        <i class="fas fa-crown mr-2"></i>
                                        Select as Winner
                                    </button>
                                @endif
                                
                                <!-- Runner-up Selection -->
                                @if($winnerExists && $entry->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY)
                                    <div class="flex items-center gap-3 p-3 bg-blue-50/80 rounded-xl border border-blue-200/50">
                                        <label class="text-xs font-medium text-blue-700">Rank:</label>
                                        <input type="number" wire:model="rankToAssign" min="2" max="10" 
                                               class="w-16 px-2 py-1 text-center bg-white border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xs font-medium" />
                                        <button wire:click="selectRunnerUp({{ $entry->id }})" 
                                                onclick="return confirm('Are you sure you want to select this entry as runner-up with rank ' + document.querySelector('input[wire\\:model=rankToAssign]').value + '?')"
                                                class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 text-xs">
                                            <i class="fas fa-medal mr-1"></i>
                                            Select Runner-up
                                        </button>
                                    </div>
                                @endif
                                
                                <!-- View Entry -->
                                @if($entry->submitted_at && $entry->current_snapshot_id)
                                    <a href="{{ route('projects.pitches.snapshots.show', ['project' => $project, 'pitch' => $entry, 'snapshot' => $entry->current_snapshot_id]) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 hover:border-gray-300 rounded-xl font-medium transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg text-sm">
                                        <i class="fas fa-eye mr-2"></i>
                                        View Submitted Entry
                                    </a>
                                @else
                                    <a href="{{ route('projects.pitches.show', ['project' => $project, 'pitch' => $entry]) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 hover:border-gray-300 rounded-xl font-medium transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg text-sm">
                                        <i class="fas fa-eye mr-2"></i>
                                        View Entry (Draft)
                                    </a>
                                @endif
                                
                                <!-- Files Count -->
                                @if($entry->files->count() > 0)
                                    <div class="flex items-center text-sm text-gray-600 bg-white/80 rounded-lg px-3 py-2 border border-gray-200">
                                        <i class="fas fa-file-audio mr-2"></i>
                                        {{ $entry->files->count() }} {{ Str::plural('file', $entry->files->count()) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($entries->hasPages())
                <div class="mt-8 bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4">
                    {{ $entries->links() }}
                </div>
            @endif
        @endif
    </div>
</div> 