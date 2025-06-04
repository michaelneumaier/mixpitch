{{-- Contest Snapshot Judging Component --}}
@if($project->isContest())
    <div class="bg-gradient-to-br from-yellow-50/90 to-amber-50/90 backdrop-blur-sm border border-yellow-200/50 rounded-2xl shadow-lg overflow-hidden mb-6">
        <div class="p-6 bg-gradient-to-r from-yellow-100/80 to-amber-100/80 backdrop-blur-sm border-b border-yellow-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-r from-yellow-400 to-amber-500 rounded-xl mr-4 shadow-lg">
                        <i class="fas fa-gavel text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Contest Entry Judging</h3>
                        <p class="text-gray-600 text-sm">Review and place this contest entry</p>
                    </div>
                </div>
                
                {{-- Current Placement Badge --}}
                @if($currentPlacement)
                    <div class="flex items-center px-4 py-2 border rounded-xl {{ $this->getPlacementBadgeClass() }}">
                        <span class="text-xl mr-2">{{ $this->getPlacementIcon() }}</span>
                        <span class="font-semibold">{{ $pitch->getPlacementLabel() }}</span>
                    </div>
                @else
                    <div class="flex items-center px-4 py-2 bg-gray-100 border border-gray-200 rounded-xl">
                        <i class="fas fa-question-circle text-gray-600 mr-2"></i>
                        <span class="font-semibold text-gray-800">Not Placed</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-6">
            {{-- Contest Entry Info --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Contestant</h4>
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8">
                            <div class="h-8 w-8 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center text-white text-sm font-semibold">
                                {{ substr($pitch->user->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">{{ $pitch->user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $pitch->user->email }}</div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Submission Date</h4>
                    @if($pitch->submitted_at)
                        <div class="text-sm text-gray-900">{{ $pitch->submitted_at->format('M j, Y g:i A') }}</div>
                        <div class="text-xs text-gray-500">{{ $pitch->submitted_at->diffForHumans() }}</div>
                    @else
                        <div class="text-sm text-gray-500">Not submitted</div>
                    @endif
                </div>
            </div>

            {{-- Judging Controls --}}
            @if($canJudge && !$isFinalized)
                <div class="border-t border-yellow-200/50 pt-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Contest Placement</h4>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <select 
                                wire:change="updatePlacement($event.target.value)"
                                class="block w-full pl-3 pr-10 py-3 text-base border border-gray-300 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500 rounded-lg"
                            >
                                @foreach($availablePlacements as $value => $label)
                                    <option 
                                        value="{{ $value }}" 
                                        {{ $currentPlacement === $value ? 'selected' : '' }}
                                        {{ strpos($label, 'Already Chosen') !== false ? 'disabled' : '' }}
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        {{-- Quick Action Buttons --}}
                        <div class="flex gap-2">
                            @if($currentPlacement !== \App\Models\Pitch::RANK_FIRST && !in_array(\App\Models\Pitch::RANK_FIRST, array_keys(array_filter($availablePlacements, fn($label) => strpos($label, 'Already Chosen') !== false))))
                                <button 
                                    wire:click="updatePlacement('{{ \App\Models\Pitch::RANK_FIRST }}')"
                                    class="px-4 py-2 bg-gradient-to-r from-yellow-400 to-amber-500 hover:from-yellow-500 hover:to-amber-600 text-white font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105"
                                >
                                    🥇 1st
                                </button>
                            @endif
                            
                            @if($currentPlacement !== \App\Models\Pitch::RANK_RUNNER_UP)
                                <button 
                                    wire:click="updatePlacement('{{ \App\Models\Pitch::RANK_RUNNER_UP }}')"
                                    class="px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105"
                                >
                                    🏅 Runner-up
                                </button>
                            @endif
                            
                            @if($currentPlacement)
                                <button 
                                    wire:click="updatePlacement('')"
                                    class="px-4 py-2 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105"
                                >
                                    <i class="fas fa-times mr-1"></i> Clear
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mt-3 text-xs text-gray-500">
                        💡 Tip: You can change placements anytime before finalizing the contest judging.
                    </div>
                </div>
            @elseif($isFinalized)
                {{-- Show finalized message --}}
                <div class="border-t border-yellow-200/50 pt-6">
                    <div class="flex items-center justify-center p-6 bg-green-50 border border-green-200 rounded-xl">
                        <div class="text-center">
                            <i class="fas fa-check-circle text-green-600 text-2xl mb-2"></i>
                            <div class="text-sm font-medium text-green-800">Contest Judging Finalized</div>
                            <div class="text-xs text-green-600 mt-1">
                                @if($project->judging_finalized_at)
                                    Completed on {{ $project->judging_finalized_at->format('M j, Y g:i A') }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @elseif(!$canJudge)
                {{-- Show message for non-judges --}}
                <div class="border-t border-yellow-200/50 pt-6">
                    <div class="flex items-center justify-center p-6 bg-blue-50 border border-blue-200 rounded-xl">
                        <div class="text-center">
                            <i class="fas fa-info-circle text-blue-600 text-2xl mb-2"></i>
                            <div class="text-sm font-medium text-blue-800">Contest Entry</div>
                            <div class="text-xs text-blue-600 mt-1">
                                This entry is part of the contest judging process
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
