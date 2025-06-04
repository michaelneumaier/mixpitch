<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Contest Judging: {{ $project->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $contestEntries->count() }} entries submitted
                    @if($project->submission_deadline)
                        • Deadline: {{ $project->submission_deadline->format('M d, Y \a\t g:i A') }}
                    @endif
                </p>
            </div>
            <div class="flex space-x-3">
                @if($canFinalize && !$isFinalized)
                    <button onclick="openFinalizeModal()" 
                            class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-gavel mr-2"></i>
                        Finalize Judging
                    </button>
                @endif
                
                @if($project->contestResult)
                    @can('viewAnalytics', $project->contestResult)
                        <a href="{{ route('projects.contest.analytics', $project) }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Analytics
                        </a>
                    @endcan
                @else
                    @can('judgeContest', $project)
                        <a href="{{ route('projects.contest.analytics', $project) }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Analytics
                        </a>
                    @endcan
                @endif
                
                @if($isFinalized)
                    <a href="{{ route('projects.contest.results', $project) }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-trophy mr-2"></i>
                        View Results
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Contest Status -->
            <div class="mb-8">
                @if($isFinalized)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                            <div>
                                <h3 class="text-lg font-medium text-green-800">Contest Judging Finalized</h3>
                                <p class="text-sm text-green-700">
                                    Judging completed on {{ $project->judging_finalized_at->format('M d, Y \a\t g:i A') }}
                                    • All participants have been notified
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-gavel text-yellow-600 text-xl mr-3"></i>
                            <div>
                                <h3 class="text-lg font-medium text-yellow-800">Contest Judging in Progress</h3>
                                <p class="text-sm text-yellow-700">
                                    Review all entries and assign placements. Once finalized, all participants will be notified of the results.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Contest Prizes Display -->
            @if($project->hasPrizes())
                <div class="mb-8">
                    <x-contest.prize-display :project="$project" />
                </div>
            @endif

            <!-- Winners Summary (if any) -->
            @if($contestResult && $contestResult->hasWinners())
                <div class="mb-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6 bg-gradient-to-r from-yellow-50 to-amber-50 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                <i class="fas fa-trophy text-yellow-600 mr-2"></i>
                                Current Winners
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- First Place -->
                                @if($contestResult->first_place_pitch_id)
                                    @php $firstPlace = $contestEntries->firstWhere('id', $contestResult->first_place_pitch_id) @endphp
                                    @if($firstPlace)
                                        <div class="bg-white rounded-lg p-4 border-2 border-yellow-300">
                                            <div class="text-center">
                                                <div class="text-3xl mb-2">🥇</div>
                                                <h4 class="font-semibold text-gray-900">1st Place</h4>
                                                <p class="text-sm text-gray-600">{{ $firstPlace->user->name }}</p>
                                            </div>
                                        </div>
                                    @endif
                                @endif

                                <!-- Second Place -->
                                @if($contestResult->second_place_pitch_id)
                                    @php $secondPlace = $contestEntries->firstWhere('id', $contestResult->second_place_pitch_id) @endphp
                                    @if($secondPlace)
                                        <div class="bg-white rounded-lg p-4 border-2 border-gray-300">
                                            <div class="text-center">
                                                <div class="text-3xl mb-2">🥈</div>
                                                <h4 class="font-semibold text-gray-900">2nd Place</h4>
                                                <p class="text-sm text-gray-600">{{ $secondPlace->user->name }}</p>
                                            </div>
                                        </div>
                                    @endif
                                @endif

                                <!-- Third Place -->
                                @if($contestResult->third_place_pitch_id)
                                    @php $thirdPlace = $contestEntries->firstWhere('id', $contestResult->third_place_pitch_id) @endphp
                                    @if($thirdPlace)
                                        <div class="bg-white rounded-lg p-4 border-2 border-orange-300">
                                            <div class="text-center">
                                                <div class="text-3xl mb-2">🥉</div>
                                                <h4 class="font-semibold text-gray-900">3rd Place</h4>
                                                <p class="text-sm text-gray-600">{{ $thirdPlace->user->name }}</p>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <!-- Runner-ups -->
                            @if($contestResult->runner_up_pitch_ids && count($contestResult->runner_up_pitch_ids) > 0)
                                <div class="mt-4">
                                    <h4 class="font-medium text-gray-900 mb-2">
                                        <i class="fas fa-medal text-blue-600 mr-1"></i>
                                        Runner-ups
                                    </h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($contestResult->runner_up_pitch_ids as $runnerUpId)
                                            @php $runnerUp = $contestEntries->firstWhere('id', $runnerUpId) @endphp
                                            @if($runnerUp)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                                    🏅 {{ $runnerUp->user->name }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Contest Entries Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Contest Entries</h3>
                    
                    @if($contestEntries->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Participant
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Submitted
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Current Placement
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            @if(!$isFinalized)
                                                Set Placement
                                            @else
                                                Final Status
                                            @endif
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($contestEntries as $entry)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8">
                                                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                                            <span class="text-xs font-medium text-white">
                                                                {{ substr($entry->user->name, 0, 1) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ $entry->user->name }}
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            {{ $entry->user->email }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $entry->created_at->format('M d, Y') }}
                                                <div class="text-xs text-gray-500">
                                                    {{ $entry->created_at->format('g:i A') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($entry->rank)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        {{ $entry->rank === '1st' ? 'bg-yellow-100 text-yellow-800' : 
                                                           ($entry->rank === '2nd' ? 'bg-gray-100 text-gray-800' : 
                                                           ($entry->rank === '3rd' ? 'bg-orange-100 text-orange-800' : 
                                                           'bg-blue-100 text-blue-800')) }}">
                                                        {{ $entry->rank === '1st' ? '🥇' : 
                                                           ($entry->rank === '2nd' ? '🥈' : 
                                                           ($entry->rank === '3rd' ? '🥉' : '🏅')) }}
                                                        {{ ucfirst($entry->rank) }} {{ $entry->rank === 'runner-up' ? '' : 'Place' }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                        ⭕ Not Placed
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if(!$isFinalized)
                                                    <select onchange="updatePlacement({{ $entry->id }}, this.value)" 
                                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500">
                                                        <option value="">No Placement</option>
                                                        <option value="1st" {{ $entry->rank === '1st' ? 'selected' : '' }}
                                                                @if($contestResult && $contestResult->first_place_pitch_id && $contestResult->first_place_pitch_id !== $entry->id) disabled @endif>
                                                            🥇 1st Place @if($contestResult && $contestResult->first_place_pitch_id && $contestResult->first_place_pitch_id !== $entry->id)(Already Chosen)@endif
                                                        </option>
                                                        <option value="2nd" {{ $entry->rank === '2nd' ? 'selected' : '' }}
                                                                @if($contestResult && $contestResult->second_place_pitch_id && $contestResult->second_place_pitch_id !== $entry->id) disabled @endif>
                                                            🥈 2nd Place @if($contestResult && $contestResult->second_place_pitch_id && $contestResult->second_place_pitch_id !== $entry->id)(Already Chosen)@endif
                                                        </option>
                                                        <option value="3rd" {{ $entry->rank === '3rd' ? 'selected' : '' }}
                                                                @if($contestResult && $contestResult->third_place_pitch_id && $contestResult->third_place_pitch_id !== $entry->id) disabled @endif>
                                                            🥉 3rd Place @if($contestResult && $contestResult->third_place_pitch_id && $contestResult->third_place_pitch_id !== $entry->id)(Already Chosen)@endif
                                                        </option>
                                                        <option value="runner-up" {{ $entry->rank === 'runner-up' ? 'selected' : '' }}>
                                                            🏅 Runner-up
                                                        </option>
                                                    </select>
                                                @else
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ ucfirst(str_replace('_', ' ', $entry->status)) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <a href="{{ route('projects.pitches.show', [$project, $entry]) }}" 
                                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                                    View Entry
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-4xl mb-4">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Contest Entries</h3>
                            <p class="text-gray-500">No entries have been submitted to this contest yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Finalize Modal -->
    <div id="finalizeModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                    <i class="fas fa-gavel text-yellow-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">
                    Finalize Contest Judging
                </h3>
                <div class="mt-4 text-left">
                    <p class="text-sm text-gray-500 mb-4">
                        Are you sure you want to finalize the judging for this contest? This action cannot be undone and all participants will be notified of the results.
                    </p>
                    
                    <form id="finalizeForm" action="{{ route('projects.contest.finalize', $project) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="judging_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Judging Notes (Optional)
                            </label>
                            <textarea name="judging_notes" id="judging_notes" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500"
                                      placeholder="Add any notes about the judging process or criteria used..."></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeFinalizeModal()" 
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                                <i class="fas fa-gavel mr-2"></i>
                                Finalize Judging
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function openFinalizeModal() {
            document.getElementById('finalizeModal').classList.remove('hidden');
        }

        function closeFinalizeModal() {
            document.getElementById('finalizeModal').classList.add('hidden');
        }

        function updatePlacement(pitchId, placement) {
            fetch(`{{ route('projects.contest.update-placement', [$project, '__PITCH_ID__']) }}`.replace('__PITCH_ID__', pitchId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    placement: placement
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message and reload page to update UI
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to update placement');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update placement. Please try again.');
            });
        }

        // Close modal when clicking outside
        document.getElementById('finalizeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeFinalizeModal();
            }
        });
    </script>
    @endpush
</x-app-layout> 