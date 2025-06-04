<div class="space-y-6">
    {{-- Contest Judging Header --}}
    <div class="bg-gradient-to-br from-yellow-50/90 to-amber-50/90 backdrop-blur-sm border border-yellow-200/50 rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 bg-gradient-to-r from-yellow-100/80 to-amber-100/80 backdrop-blur-sm border-b border-yellow-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-r from-yellow-400 to-amber-500 rounded-xl mr-4 shadow-lg">
                        <i class="fas fa-gavel text-white text-xl"></i>
                    </div>
<div>
                        <h3 class="text-2xl font-bold text-gray-900">Contest Judging</h3>
                        <p class="text-gray-600 text-sm">Judge contest entries and finalize results</p>
                    </div>
                </div>
                
                {{-- Status Badge --}}
                @if($isFinalized)
                    <div class="flex items-center px-4 py-2 bg-green-100 border border-green-200 rounded-xl">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                        <span class="font-semibold text-green-800">Judging Finalized</span>
                    </div>
                @elseif($canFinalize)
                    <div class="flex items-center px-4 py-2 bg-blue-100 border border-blue-200 rounded-xl">
                        <i class="fas fa-clock text-blue-600 mr-2"></i>
                        <span class="font-semibold text-blue-800">Ready to Finalize</span>
                    </div>
                @else
                    <div class="flex items-center px-4 py-2 bg-gray-100 border border-gray-200 rounded-xl">
                        <i class="fas fa-hourglass-half text-gray-600 mr-2"></i>
                        <span class="font-semibold text-gray-800">Judging in Progress</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Contest Info --}}
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-white/50 rounded-xl border border-yellow-200/30">
                    <div class="text-2xl font-bold text-gray-900">{{ $contestEntries->count() }}</div>
                    <div class="text-sm text-gray-600">Total Entries</div>
                </div>
                <div class="text-center p-4 bg-white/50 rounded-xl border border-yellow-200/30">
                    <div class="text-2xl font-bold text-gray-900">
                        {{ $contestResult && $contestResult->hasWinners() ? count(array_filter([$contestResult->first_place_pitch_id, $contestResult->second_place_pitch_id, $contestResult->third_place_pitch_id])) + count($contestResult->runner_up_pitch_ids ?? []) : 0 }}
                    </div>
                    <div class="text-sm text-gray-600">Placed Entries</div>
                </div>
                <div class="text-center p-4 bg-white/50 rounded-xl border border-yellow-200/30">
                    <div class="text-2xl font-bold text-gray-900">
                        @if($project->submission_deadline)
                            {{ $project->submission_deadline->format('M j, Y') }}
                        @else
                            No Deadline
                        @endif
                    </div>
                    <div class="text-sm text-gray-600">Submission Deadline</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Winners Summary (if judging is finalized) --}}
    @if($isFinalized && $winnersSummary)
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-2xl p-6">
            <h4 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                Contest Results
            </h4>
            
            <div class="grid gap-4">
                {{-- Podium Places --}}
                @if($winnersSummary['first_place'] || $winnersSummary['second_place'] || $winnersSummary['third_place'])
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        {{-- 1st Place --}}
                        @if($winnersSummary['first_place'])
                            <div class="bg-gradient-to-br from-yellow-100 to-amber-100 border-2 border-yellow-300 rounded-xl p-4 text-center">
                                <div class="text-3xl mb-2">🥇</div>
                                <div class="font-bold text-lg text-gray-900">1st Place</div>
                                <div class="text-gray-700">{{ $winnersSummary['first_place']->user->name }}</div>
                                <div class="text-sm text-gray-600 mt-1">{{ $winnersSummary['first_place']->title ?: 'Contest Entry' }}</div>
                            </div>
                        @endif

                        {{-- 2nd Place --}}
                        @if($winnersSummary['second_place'])
                            <div class="bg-gradient-to-br from-gray-100 to-slate-100 border-2 border-gray-300 rounded-xl p-4 text-center">
                                <div class="text-3xl mb-2">🥈</div>
                                <div class="font-bold text-lg text-gray-900">2nd Place</div>
                                <div class="text-gray-700">{{ $winnersSummary['second_place']->user->name }}</div>
                                <div class="text-sm text-gray-600 mt-1">{{ $winnersSummary['second_place']->title ?: 'Contest Entry' }}</div>
                            </div>
                        @endif

                        {{-- 3rd Place --}}
                        @if($winnersSummary['third_place'])
                            <div class="bg-gradient-to-br from-orange-100 to-amber-100 border-2 border-orange-300 rounded-xl p-4 text-center">
                                <div class="text-3xl mb-2">🥉</div>
                                <div class="font-bold text-lg text-gray-900">3rd Place</div>
                                <div class="text-gray-700">{{ $winnersSummary['third_place']->user->name }}</div>
                                <div class="text-sm text-gray-600 mt-1">{{ $winnersSummary['third_place']->title ?: 'Contest Entry' }}</div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Runner-ups --}}
                @if(!empty($winnersSummary['runner_ups']))
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4">
                        <h5 class="font-bold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-medal text-blue-500 mr-2"></i>
                            Runner-ups
                        </h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($winnersSummary['runner_ups'] as $runnerUp)
                                <div class="bg-white/80 border border-blue-200 rounded-lg p-3 text-center">
                                    <div class="font-semibold text-gray-900">{{ $runnerUp->user->name }}</div>
                                    <div class="text-sm text-gray-600">{{ $runnerUp->title ?: 'Contest Entry' }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Contest Entries Table --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h4 class="text-lg font-semibold text-gray-900">Contest Entries</h4>
                
                {{-- Finalize Button --}}
                @if($canFinalize && !$isFinalized)
                    <button 
                        wire:click="openFinalizeModal"
                        class="px-6 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105"
                    >
                        <i class="fas fa-flag-checkered mr-2"></i>
                        Finalize Judging
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contestant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entry</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Placement</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($contestEntries as $entry)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            {{-- Contestant --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center text-white font-semibold">
                                            {{ substr($entry->user->name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $entry->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $entry->user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Entry Details --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $entry->title ?: 'Contest Entry' }}</div>
                                @if($entry->description)
                                    <div class="text-sm text-gray-500 max-w-xs truncate">{{ $entry->description }}</div>
                                @endif
                            </td>

                            {{-- Submitted Date --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($entry->submitted_at)
                                    {{ $entry->submitted_at->format('M j, Y') }}
                                    <div class="text-xs text-gray-400">{{ $entry->submitted_at->format('g:i A') }}</div>
                                @else
                                    <span class="text-gray-400">Not submitted</span>
                                @endif
                            </td>

                            {{-- Placement Dropdown --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($isFinalized)
                                    {{-- Show final placement as badge --}}
                                    @if($entry->rank)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                            {{ $entry->rank === '1st' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $entry->rank === '2nd' ? 'bg-gray-100 text-gray-800' : '' }}
                                            {{ $entry->rank === '3rd' ? 'bg-orange-100 text-orange-800' : '' }}
                                            {{ $entry->rank === 'runner-up' ? 'bg-blue-100 text-blue-800' : '' }}
                                        ">
                                            @if($entry->rank === '1st') 🥇 @endif
                                            @if($entry->rank === '2nd') 🥈 @endif
                                            @if($entry->rank === '3rd') 🥉 @endif
                                            @if($entry->rank === 'runner-up') 🏅 @endif
                                            {{ $entry->getPlacementLabel() }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                                            Not Placed
                                        </span>
                                    @endif
                                @else
                                    {{-- Placement dropdown --}}
                                    <select 
                                        wire:change="updatePlacement({{ $entry->id }}, $event.target.value)"
                                        class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm rounded-lg"
                                        {{ $isFinalized ? 'disabled' : '' }}
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
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                @if($entry->current_snapshot_id)
                                    <a 
                                        href="{{ route('projects.pitches.snapshots.show', ['project' => $project, 'pitch' => $entry, 'snapshot' => $entry->current_snapshot_id]) }}" 
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-150"
                                    >
                                        <i class="fas fa-eye mr-2"></i>
                                        View Entry
                                    </a>
                                @else
                                    <span class="text-gray-400 text-sm">No submission</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-trophy text-4xl mb-4 text-gray-300"></i>
                                    <p class="text-lg font-medium">No contest entries yet</p>
                                    <p class="text-sm">Contest entries will appear here once users submit them.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Finalization Modal --}}
    @if($showFinalizeModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeFinalizeModal"></div>

                {{-- Modal --}}
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
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
