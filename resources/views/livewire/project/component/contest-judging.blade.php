@props(['workflowColors' => [], 'semanticColors' => []])

<div class="space-y-6">
    {{-- Contest Judging Header --}}
    <flux:card class="bg-gradient-to-br {{ $workflowColors['bg'] ?? 'from-orange-50/90 to-amber-50/90 dark:from-orange-950/90 dark:to-amber-950/90' }} border {{ $workflowColors['border'] ?? 'border-orange-200/50 dark:border-orange-800/50' }}">
        <div class="flex items-center gap-3 mb-6">
            <flux:icon name="scale" variant="solid" class="{{ $workflowColors['icon'] ?? 'text-orange-600 dark:text-orange-400' }} h-8 w-8" />
            <div>
                <flux:heading size="lg" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">Contest Judging</flux:heading>
                <flux:subheading class="{{ $workflowColors['text_muted'] ?? 'text-orange-600 dark:text-orange-400' }}">Judge contest entries and finalize results</flux:subheading>
            </div>
        </div>
        
        {{-- Status Badge --}}
        <div class="flex justify-end">
            @if($isFinalized)
                <flux:badge color="green" size="sm" icon="check-circle">Judging Finalized</flux:badge>
            @elseif($canFinalize)
                <flux:badge color="blue" size="sm" icon="clock">Ready to Finalize</flux:badge>
            @else
                <flux:badge color="zinc" size="sm" icon="clock">Judging in Progress</flux:badge>
            @endif
        </div>

        {{-- Contest Info --}}
        <flux:separator class="my-6" />
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-white/50 dark:bg-gray-800/50 rounded-xl border {{ $workflowColors['accent_border'] ?? 'border-orange-200/30 dark:border-orange-700/30' }}">
                <flux:heading size="xl" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">{{ $contestEntries->count() }}</flux:heading>
                <flux:text size="sm" class="{{ $workflowColors['text_secondary'] ?? 'text-orange-600 dark:text-orange-400' }}">Total Entries</flux:text>
            </div>
            <div class="text-center p-4 bg-white/50 dark:bg-gray-800/50 rounded-xl border {{ $workflowColors['accent_border'] ?? 'border-orange-200/30 dark:border-orange-700/30' }}">
                <flux:heading size="xl" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                    {{ $contestResult && $contestResult->hasWinners() ? count(array_filter([$contestResult->first_place_pitch_id, $contestResult->second_place_pitch_id, $contestResult->third_place_pitch_id])) + count($contestResult->runner_up_pitch_ids ?? []) : 0 }}
                </flux:heading>
                <flux:text size="sm" class="{{ $workflowColors['text_secondary'] ?? 'text-orange-600 dark:text-orange-400' }}">Placed Entries</flux:text>
            </div>
            <div class="text-center p-4 bg-white/50 dark:bg-gray-800/50 rounded-xl border {{ $workflowColors['accent_border'] ?? 'border-orange-200/30 dark:border-orange-700/30' }}">
                <flux:heading size="xl" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                    @if($project->submission_deadline)
                        <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M j, Y" />
                    @else
                        No Deadline
                    @endif
                </flux:heading>
                <flux:text size="sm" class="{{ $workflowColors['text_secondary'] ?? 'text-orange-600 dark:text-orange-400' }}">Submission Deadline</flux:text>
            </div>
        </div>
    </flux:card>

    {{-- Winners Summary (if judging is finalized) --}}
    @if($isFinalized && $winnersSummary)
        <flux:card class="bg-gradient-to-br {{ $semanticColors['success']['bg'] ?? 'from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950' }} border {{ $semanticColors['success']['border'] ?? 'border-green-200 dark:border-green-800' }}">
            <div class="flex items-center gap-3 mb-6">
                <flux:icon name="trophy" variant="solid" class="text-yellow-500 dark:text-yellow-400 h-6 w-6" />
                <flux:heading size="lg" class="{{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }}">Contest Results</flux:heading>
            </div>
            
            <div class="grid gap-4">
                {{-- Podium Places --}}
                @if($winnersSummary['first_place'] || $winnersSummary['second_place'] || $winnersSummary['third_place'])
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        {{-- 1st Place --}}
                        @if($winnersSummary['first_place'])
                            <div class="bg-gradient-to-br from-yellow-100 to-amber-100 dark:from-yellow-900 dark:to-amber-900 border-2 border-yellow-300 dark:border-yellow-700 rounded-xl p-4 text-center">
                                <div class="text-3xl mb-2">🥇</div>
                                <flux:heading size="base" class="text-gray-900 dark:text-gray-100">1st Place</flux:heading>
                                <flux:text class="text-gray-700 dark:text-gray-300">{{ $winnersSummary['first_place']->user->name }}</flux:text>
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">{{ $winnersSummary['first_place']->title ?: 'Contest Entry' }}</flux:text>
                            </div>
                        @endif

                        {{-- 2nd Place --}}
                        @if($winnersSummary['second_place'])
                            <div class="bg-gradient-to-br from-gray-100 to-slate-100 dark:from-gray-800 dark:to-slate-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl p-4 text-center">
                                <div class="text-3xl mb-2">🥈</div>
                                <flux:heading size="base" class="text-gray-900 dark:text-gray-100">2nd Place</flux:heading>
                                <flux:text class="text-gray-700 dark:text-gray-300">{{ $winnersSummary['second_place']->user->name }}</flux:text>
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">{{ $winnersSummary['second_place']->title ?: 'Contest Entry' }}</flux:text>
                            </div>
                        @endif

                        {{-- 3rd Place --}}
                        @if($winnersSummary['third_place'])
                            <div class="bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900 dark:to-amber-900 border-2 border-orange-300 dark:border-orange-700 rounded-xl p-4 text-center">
                                <div class="text-3xl mb-2">🥉</div>
                                <flux:heading size="base" class="text-gray-900 dark:text-gray-100">3rd Place</flux:heading>
                                <flux:text class="text-gray-700 dark:text-gray-300">{{ $winnersSummary['third_place']->user->name }}</flux:text>
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">{{ $winnersSummary['third_place']->title ?: 'Contest Entry' }}</flux:text>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Runner-ups --}}
                @if(!empty($winnersSummary['runner_ups']))
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <flux:icon name="star" class="text-blue-500 dark:text-blue-400 h-5 w-5" />
                            <flux:heading size="base" class="text-gray-900 dark:text-gray-100">Runner-ups</flux:heading>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($winnersSummary['runner_ups'] as $runnerUp)
                                <div class="bg-white/80 dark:bg-gray-800/80 border border-blue-200 dark:border-blue-700 rounded-lg p-3 text-center">
                                    <flux:text class="font-semibold text-gray-900 dark:text-gray-100">{{ $runnerUp->user->name }}</flux:text>
                                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400">{{ $runnerUp->title ?: 'Contest Entry' }}</flux:text>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </flux:card>
    @endif

    {{-- Contest Entries Section --}}
    <flux:card class="bg-gradient-to-br {{ $workflowColors['bg'] ?? 'from-orange-50/30 to-amber-50/30 dark:from-orange-950/30 dark:to-amber-950/30' }} border {{ $workflowColors['border'] ?? 'border-orange-200/50 dark:border-orange-800/50' }}">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <flux:icon name="trophy" variant="solid" class="{{ $workflowColors['icon'] ?? 'text-orange-600 dark:text-orange-400' }} h-8 w-8" />
                <div>
                    <flux:heading size="lg" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">Contest Entries</flux:heading>
                    <div class="flex items-center gap-3">
                        <flux:subheading class="{{ $workflowColors['text_muted'] ?? 'text-orange-600 dark:text-orange-400' }}">{{ $contestEntries->count() }} {{ Str::plural('entry', $contestEntries->count()) }} submitted</flux:subheading>
                        <x-contest.payment-status-badge :project="$project" compact="true" />
                    </div>
                </div>
            </div>
            
            {{-- Finalize Button --}}
            @if($canFinalize && !$isFinalized)
                <flux:button variant="primary" wire:click="openFinalizeModal" icon="flag">
                    Finalize Judging
                </flux:button>
            @endif
        </div>

        @if($contestEntries->isEmpty())
            <!-- Empty State -->
            <div class="text-center py-12">
                <flux:icon name="trophy" size="lg" class="mx-auto mb-4 {{ $workflowColors['icon'] ?? 'text-orange-400 dark:text-orange-500' }}" />
                <flux:heading size="sm" class="{{ $workflowColors['text_primary'] ?? 'text-orange-800 dark:text-orange-200' }} mb-2">No Entries Yet</flux:heading>
                <flux:text class="{{ $workflowColors['text_secondary'] ?? 'text-orange-600 dark:text-orange-400' }}">Contest entries will appear here as producers submit their work.</flux:text>
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
                                            <flux:badge color="amber" size="sm" icon="trophy">1st Place</flux:badge>
                                        @elseif($entry->rank === 'second' || $entry->rank === '2nd')
                                            <flux:badge color="zinc" size="sm" icon="star">2nd Place</flux:badge>
                                        @elseif($entry->rank === 'third' || $entry->rank === '3rd')
                                            <flux:badge color="orange" size="sm" icon="star">3rd Place</flux:badge>
                                        @elseif($entry->rank === 'runner-up')
                                            <flux:badge color="blue" size="sm" icon="star">Runner-up</flux:badge>
                                        @elseif($entry->rank)
                                            <flux:badge color="blue" size="sm" icon="star">{{ $entry->getPlacementLabel() }}</flux:badge>
                                        @else
                                            @if($entry->submitted_at)
                                                <flux:badge color="purple" size="sm" icon="paper-airplane">Entry</flux:badge>
                                            @else
                                                <flux:badge color="zinc" size="sm" icon="clock">Draft</flux:badge>
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
                                        <flux:button 
                                            variant="outline" 
                                            size="sm" 
                                            icon="eye" 
                                            href="{{ route('projects.pitches.snapshots.show', ['project' => $project, 'pitch' => $entry, 'snapshot' => $entry->current_snapshot_id]) }}"
                                        >
                                            View Submitted Entry
                                        </flux:button>
                                    @elseif(!$entry->submitted_at)
                                        <div class="flex items-center text-sm text-gray-500 bg-gray-100/80 rounded-lg px-3 py-2 border border-gray-200">
                                            <flux:icon name="clock" class="mr-2 h-4 w-4" />
                                            Not Submitted Yet
                                        </div>
                                    @endif
                                    
                                    <!-- Files Count -->
                                    @if($entry->files->count() > 0)
                                        <div class="flex items-center text-sm text-gray-600 bg-white/80 rounded-lg px-3 py-2 border border-gray-200">
                                            <flux:icon name="musical-note" class="mr-2 h-4 w-4" />
                                            {{ $entry->files->count() }} {{ Str::plural('file', $entry->files->count()) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
        @endif
    </flux:card>

    {{-- Finalization Modal --}}
    <flux:modal name="finalize-contest" :show="$showFinalizeModal" class="max-w-2xl">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <flux:icon name="flag" class="text-amber-600 h-6 w-6" />
                <flux:heading size="lg">Finalize Contest Judging</flux:heading>
            </div>
            
            <flux:text class="text-gray-600 dark:text-gray-400 mb-6">
                Are you sure you want to finalize the judging for this contest? This action cannot be undone and all participants will be notified of the results.
            </flux:text>
            
            <flux:field>
                <flux:label>Judging Notes (Optional)</flux:label>
                <flux:textarea 
                    wire:model="finalizationNotes"
                    rows="3" 
                    placeholder="Add any notes about the judging process or criteria used..."
                />
            </flux:field>
            
            <div class="flex justify-end gap-3 mt-6">
                <flux:button variant="ghost" wire:click="closeFinalizeModal">Cancel</flux:button>
                <flux:button variant="primary" wire:click="finalizeJudging" icon="check">
                    Finalize Judging
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
