@props(['workflowColors' => [], 'semanticColors' => []])

<div class="space-y-6">
    {{-- Contest Judging Header --}}
    <div class="bg-gradient-to-br {{ $workflowColors['bg'] ?? 'from-yellow-50/90 to-amber-50/90' }} backdrop-blur-sm border {{ $workflowColors['border'] ?? 'border-yellow-200/50' }} rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 bg-gradient-to-r {{ $workflowColors['accent_bg'] ?? 'from-yellow-100/80 to-amber-100/80' }} backdrop-blur-sm border-b {{ $workflowColors['accent_border'] ?? 'border-yellow-200/50' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-r {{ $workflowColors['icon'] ?? 'from-yellow-400 to-amber-500' }} rounded-xl mr-4 shadow-lg">
                        <i class="fas fa-gavel text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900' }}">Contest Judging</h3>
                        <p class="{{ $workflowColors['text_secondary'] ?? 'text-gray-600' }} text-sm">Judge contest entries and finalize results</p>
                    </div>
                </div>
                
                {{-- Status Badge --}}
                @if($isFinalized)
                    <div class="flex items-center px-4 py-2 {{ $semanticColors['success']['bg'] ?? 'bg-green-100 dark:bg-green-900' }} border {{ $semanticColors['success']['border'] ?? 'border-green-200 dark:border-green-800' }} rounded-xl">
                        <i class="fas fa-check-circle {{ $semanticColors['success']['icon'] ?? 'text-green-600 dark:text-green-400' }} mr-2"></i>
                        <span class="font-semibold {{ $semanticColors['success']['text'] ?? 'text-green-800 dark:text-green-200' }}">Judging Finalized</span>
                    </div>
                @elseif($canFinalize)
                    <div class="flex items-center px-4 py-2 bg-blue-100 dark:bg-blue-900 border border-blue-200 dark:border-blue-800 rounded-xl">
                        <i class="fas fa-clock text-blue-600 dark:text-blue-400 mr-2"></i>
                        <span class="font-semibold text-blue-800 dark:text-blue-200">Ready to Finalize</span>
                    </div>
                @else
                    <div class="flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                        <i class="fas fa-hourglass-half text-gray-600 dark:text-gray-400 mr-2"></i>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">Judging in Progress</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Contest Info --}}
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-white/50 dark:bg-gray-800/50 rounded-xl border {{ $workflowColors['accent_border'] ?? 'border-yellow-200/30 dark:border-gray-700/30' }}">
                    <div class="text-2xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }}">{{ $contestEntries->count() }}</div>
                    <div class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-gray-600 dark:text-gray-400' }}">Total Entries</div>
                </div>
                <div class="text-center p-4 bg-white/50 dark:bg-gray-800/50 rounded-xl border {{ $workflowColors['accent_border'] ?? 'border-yellow-200/30 dark:border-gray-700/30' }}">
                    <div class="text-2xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }}">
                        {{ $contestResult && $contestResult->hasWinners() ? count(array_filter([$contestResult->first_place_pitch_id, $contestResult->second_place_pitch_id, $contestResult->third_place_pitch_id])) + count($contestResult->runner_up_pitch_ids ?? []) : 0 }}
                    </div>
                    <div class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-gray-600 dark:text-gray-400' }}">Placed Entries</div>
                </div>
                <div class="text-center p-4 bg-white/50 dark:bg-gray-800/50 rounded-xl border {{ $workflowColors['accent_border'] ?? 'border-yellow-200/30 dark:border-gray-700/30' }}">
                    <div class="text-2xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }}">
                        @if($project->submission_deadline)
                            <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M j, Y" />
                        @else
                            No Deadline
                        @endif
                    </div>
                    <div class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-gray-600 dark:text-gray-400' }}">Submission Deadline</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Winners Summary (if judging is finalized) --}}
    @if($isFinalized && $winnersSummary)
        <div class="bg-gradient-to-br {{ $semanticColors['success']['bg'] ?? 'from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950' }} border {{ $semanticColors['success']['border'] ?? 'border-green-200 dark:border-green-800' }} rounded-2xl p-6">
            <h4 class="text-xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }} mb-4 flex items-center">
                <i class="fas fa-trophy text-yellow-500 dark:text-yellow-400 mr-2"></i>
                Contest Results
            </h4>
            
            <div class="grid gap-4">
                {{-- Podium Places --}}
                @if($winnersSummary['first_place'] || $winnersSummary['second_place'] || $winnersSummary['third_place'])
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        {{-- 1st Place --}}
                        @if($winnersSummary['first_place'])
                            <div class="bg-gradient-to-br from-yellow-100 to-amber-100 dark:from-yellow-900 dark:to-amber-900 border-2 border-yellow-300 dark:border-yellow-700 rounded-xl p-4 text-center">
                                <div class="text-3xl mb-2">🥇</div>
                                <div class="font-bold text-lg text-gray-900 dark:text-gray-100">1st Place</div>
                                <div class="text-gray-700 dark:text-gray-300">{{ $winnersSummary['first_place']->user->name }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $winnersSummary['first_place']->title ?: 'Contest Entry' }}</div>
                            </div>
                        @endif

                        {{-- 2nd Place --}}
                        @if($winnersSummary['second_place'])
                            <div class="bg-gradient-to-br from-gray-100 to-slate-100 dark:from-gray-800 dark:to-slate-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl p-4 text-center">
                                <div class="text-3xl mb-2">🥈</div>
                                <div class="font-bold text-lg text-gray-900 dark:text-gray-100">2nd Place</div>
                                <div class="text-gray-700 dark:text-gray-300">{{ $winnersSummary['second_place']->user->name }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $winnersSummary['second_place']->title ?: 'Contest Entry' }}</div>
                            </div>
                        @endif

                        {{-- 3rd Place --}}
                        @if($winnersSummary['third_place'])
                            <div class="bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900 dark:to-amber-900 border-2 border-orange-300 dark:border-orange-700 rounded-xl p-4 text-center">
                                <div class="text-3xl mb-2">🥉</div>
                                <div class="font-bold text-lg text-gray-900 dark:text-gray-100">3rd Place</div>
                                <div class="text-gray-700 dark:text-gray-300">{{ $winnersSummary['third_place']->user->name }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $winnersSummary['third_place']->title ?: 'Contest Entry' }}</div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Runner-ups --}}
                @if(!empty($winnersSummary['runner_ups']))
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                        <h5 class="font-bold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                            <i class="fas fa-medal text-blue-500 dark:text-blue-400 mr-2"></i>
                            Runner-ups
                        </h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($winnersSummary['runner_ups'] as $runnerUp)
                                <div class="bg-white/80 dark:bg-gray-800/80 border border-blue-200 dark:border-blue-700 rounded-lg p-3 text-center">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $runnerUp->user->name }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $runnerUp->title ?: 'Contest Entry' }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Contest Entries Section --}}
    <div class="relative bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm border {{ $workflowColors['border'] ?? 'border-white/20 dark:border-gray-700/20' }} rounded-2xl shadow-xl overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 {{ $workflowColors['bg'] ?? 'bg-gradient-to-br from-purple-50/30 via-blue-50/20 to-indigo-50/30 dark:from-purple-950/30 dark:via-blue-950/20 dark:to-indigo-950/30' }}"></div>
        <div class="absolute top-4 right-4 w-20 h-20 bg-purple-400/10 dark:bg-purple-600/10 rounded-full blur-xl"></div>
        <div class="absolute bottom-4 left-4 w-16 h-16 bg-blue-400/10 dark:bg-blue-600/10 rounded-full blur-lg"></div>
        
        <!-- Header -->
        <div class="relative {{ $workflowColors['accent_bg'] ?? 'bg-gradient-to-r from-purple-100/80 to-indigo-100/80 dark:from-purple-900/80 dark:to-indigo-900/80' }} backdrop-blur-sm border-b {{ $workflowColors['accent_border'] ?? 'border-purple-200/50 dark:border-purple-700/50' }} p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br {{ $workflowColors['icon'] ?? 'from-purple-500 to-indigo-600' }} rounded-xl mr-4">
                        <i class="fas fa-trophy text-white text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold {{ $workflowColors['text_primary'] ?? 'text-purple-700 dark:text-purple-300' }}">
                            Contest Entries
                        </h3>
                        <div class="flex items-center gap-3">
                            <p class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-purple-600 dark:text-purple-400' }} font-medium">{{ $contestEntries->count() }} {{ Str::plural('entry', $contestEntries->count()) }} submitted</p>
                            <x-contest.payment-status-badge :project="$project" compact="true" />
                        </div>
                    </div>
                </div>
                
                {{-- Finalize Button --}}
                @if($canFinalize && !$isFinalized)
                    <button 
                        wire:click="openFinalizeModal"
                        class="px-6 py-2 bg-gradient-to-r {{ $semanticColors['success']['accent'] ?? 'from-green-600 to-emerald-600' }} hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl transition-[transform,colors,shadow] duration-200 shadow-lg hover:shadow-xl transform hover:scale-105"
                    >
                        <i class="fas fa-flag-checkered mr-2"></i>
                        Finalize Judging
                    </button>
                @endif
            </div>
        </div>

        <!-- Content -->
        <div class="relative p-6">
            @if($contestEntries->isEmpty())
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="mx-auto w-24 h-24 {{ $workflowColors['accent_bg'] ?? 'bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900 dark:to-indigo-900' }} rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-trophy text-4xl {{ $workflowColors['icon'] ?? 'text-purple-500 dark:text-purple-400' }}"></i>
                    </div>
                    <h3 class="text-lg font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-800 dark:text-gray-200' }} mb-3">No Entries Yet</h3>
                    <p class="{{ $workflowColors['text_secondary'] ?? 'text-gray-600 dark:text-gray-400' }} max-w-md mx-auto leading-relaxed">
                        Contest entries will appear here as producers submit their work.
                    </p>
                </div>
            @else
                <!-- Entries Grid -->
                <div class="space-y-6">
                    @foreach($contestEntries as $entry)
                        <div class="bg-white/80 backdrop-blur-sm border border-white/40 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-[transform,colors,shadow] duration-300 hover:scale-[1.02] 
                            @if($entry->rank === 'first' || $entry->rank === '1st') 
                                ring-2 ring-yellow-400/50 bg-gradient-to-r from-yellow-50/80 to-amber-50/80
                            @elseif($entry->rank === 'second' || $entry->rank === '2nd') 
                                ring-2 ring-gray-400/50 bg-gradient-to-r from-gray-50/80 to-slate-50/80
                            @elseif($entry->rank === 'third' || $entry->rank === '3rd') 
                                ring-2 ring-orange-400/50 bg-gradient-to-r from-orange-50/80 to-amber-50/80
                            @elseif($entry->rank === 'runner-up') 
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
                                                @if($entry->submitted_at)
                                                    <span>Submitted {{ $entry->submitted_at->format('M j, Y') }}</span>
                                                @else
                                                    <span>Created {{ $entry->created_at->format('M j, Y') }} (Not submitted)</span>
                                                @endif
                                                @if($entry->rank)
                                                    <span class="ml-4 flex items-center">
                                                        <i class="fas fa-award mr-1 text-yellow-500"></i>
                                                        {{ $entry->getPlacementLabel() }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Status Badge -->
                                    <div>
                                        @if($entry->rank === 'first' || $entry->rank === '1st')
                                            <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-800 border border-yellow-300 shadow-md">
                                                <i class="fas fa-crown mr-2"></i>1st Place
                                            </span>
                                        @elseif($entry->rank === 'second' || $entry->rank === '2nd')
                                            <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-gray-100 to-slate-100 text-gray-800 border border-gray-300 shadow-md">
                                                <i class="fas fa-medal mr-2"></i>2nd Place
                                            </span>
                                        @elseif($entry->rank === 'third' || $entry->rank === '3rd')
                                            <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-orange-100 to-amber-100 text-orange-800 border border-orange-300 shadow-md">
                                                <i class="fas fa-medal mr-2"></i>3rd Place
                                            </span>
                                        @elseif($entry->rank === 'runner-up')
                                            <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border border-blue-300 shadow-md">
                                                <i class="fas fa-medal mr-2"></i>Runner-up
                                            </span>
                                        @elseif($entry->rank)
                                            <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border border-blue-300 shadow-md">
                                                <i class="fas fa-medal mr-2"></i>{{ $entry->getPlacementLabel() }}
                                            </span>
                                        @else
                                            @if($entry->submitted_at)
                                                <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium bg-gradient-to-r from-purple-100 to-indigo-100 text-purple-800 border border-purple-200 shadow-sm">
                                                    <i class="fas fa-paper-plane mr-2"></i>Entry
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 border border-gray-300 shadow-sm">
                                                    <i class="fas fa-clock mr-2"></i>Draft
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Judging Actions -->
                            <div class="p-6 bg-gradient-to-r from-gray-50/80 to-white/80">
                                <div class="flex flex-wrap items-center gap-3">
                                    <!-- Placement Dropdown -->
                                    @if(!$isFinalized)
                                        <div class="flex items-center gap-3 p-3 bg-yellow-50/80 rounded-xl border border-yellow-200/50">
                                            <label class="text-sm font-medium text-yellow-700">Placement:</label>
                                            <select 
                                                wire:change="updatePlacement({{ $entry->id }}, $event.target.value)"
                                                class="block pl-3 pr-10 py-2 text-base border border-yellow-300 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm rounded-lg bg-white"
                                            >
                                                @foreach($this->getAvailablePlacementsForPitch($entry->id) as $value => $label)
                                                    <option 
                                                        value="{{ $value }}" 
                                                        {{ ($placements[$entry->id] ?? '') === $value ? 'selected' : '' }}
                                                        {{ strpos($label, 'Already Chosen') !== false ? 'disabled' : '' }}
                                                    >
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    
                                    <!-- View Entry -->
                                    @if($entry->submitted_at && $entry->current_snapshot_id)
                                        <a href="{{ route('projects.pitches.snapshots.show', ['project' => $project, 'pitch' => $entry, 'snapshot' => $entry->current_snapshot_id]) }}" 
                                           class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 hover:border-gray-300 rounded-xl font-medium transition-[transform,colors,shadow] duration-200 hover:scale-105 shadow-md hover:shadow-lg text-sm">
                                            <i class="fas fa-eye mr-2"></i>
                                            View Submitted Entry
                                        </a>
                                    @elseif(!$entry->submitted_at)
                                        <div class="flex items-center text-sm text-gray-500 bg-gray-100/80 rounded-lg px-3 py-2 border border-gray-200">
                                            <i class="fas fa-clock mr-2"></i>
                                            Not Submitted Yet
                                        </div>
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
            @endif
        </div>
    </div>

    {{-- Finalization Modal --}}
    @if($showFinalizeModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeFinalizeModal"></div>

                {{-- Modal --}}
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-[transform,opacity] sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-flag-checkered text-yellow-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Finalize Contest Judging
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Are you sure you want to finalize the judging for this contest? This action cannot be undone and all participants will be notified of the results.
                                    </p>
                                    
                                    <div class="mt-4">
                                        <label for="finalizationNotes" class="block text-sm font-medium text-gray-700">
                                            Judging Notes (Optional)
                                        </label>
                                        <div class="mt-1">
                                            <textarea 
                                                wire:model="finalizationNotes"
                                                id="finalizationNotes"
                                                rows="3" 
                                                class="shadow-sm focus:ring-yellow-500 focus:border-yellow-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md"
                                                placeholder="Add any notes about the judging process or criteria used..."
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="finalizeJudging"
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            <i class="fas fa-check mr-2"></i>
                            Finalize Judging
                        </button>
                        <button 
                            wire:click="closeFinalizeModal"
                            type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
