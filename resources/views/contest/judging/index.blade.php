<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Contest Judging: {{ $project->title }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $contestEntries->count() }} entries submitted
                    @if($project->submission_deadline)
                        • Deadline: {{ $project->submission_deadline->format('M d, Y \a\t g:i A') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Back to Manage Contest Button -->
                <a href="{{ route('projects.manage', $project) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Manage Contest
                </a>
                
                @if($isFinalized)
                    @php
                        // Check if there are cash prizes to pay
                        $cashPrizes = $project->contestPrizes()
                            ->where('prize_type', 'cash')
                            ->where('cash_amount', '>', 0)
                            ->get();
                        $hasCashPrizes = $cashPrizes->isNotEmpty();
                        
                        // Check if prizes have been paid
                        $contestResult = $project->contestResult;
                        $prizesPaid = false;
                        if ($hasCashPrizes && $contestResult) {
                            $prizesPaid = true;
                            foreach ($cashPrizes as $prize) {
                                $winnerPitch = $contestResult->getWinnerForPlacement($prize->placement);
                                if ($winnerPitch && $winnerPitch->payment_status !== 'paid') {
                                    $prizesPaid = false;
                                    break;
                                }
                            }
                        }
                    @endphp
                    
                    @if($hasCashPrizes && !$prizesPaid)
                        <a href="{{ route('contest.prizes.overview', $project) }}" 
                           class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-dollar-sign mr-2"></i>
                            Pay Contest Prizes
                        </a>
                    @elseif($hasCashPrizes && $prizesPaid)
                        <a href="{{ route('contest.prizes.receipt', $project) }}" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-receipt mr-2"></i>
                            View Receipt
                        </a>
                    @endif
                @endif
                
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

    <div class="py-12" x-data="contestJudging()">
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

            <!-- Contest Prize Payment Status -->
            @if($isFinalized)
                @php
                    // Check if there are cash prizes to pay
                    $cashPrizes = $project->contestPrizes()
                        ->where('prize_type', 'cash')
                        ->where('cash_amount', '>', 0)
                        ->get();
                    $hasCashPrizes = $cashPrizes->isNotEmpty();
                    
                    if ($hasCashPrizes) {
                        // Check if prizes have been paid
                        $contestResult = $project->contestResult;
                        $prizesPaid = false;
                        $totalPrizeAmount = $cashPrizes->sum('cash_amount');
                        
                        if ($contestResult) {
                            $prizesPaid = true;
                            foreach ($cashPrizes as $prize) {
                                $winnerPitch = $contestResult->getWinnerForPlacement($prize->placement);
                                if ($winnerPitch && $winnerPitch->payment_status !== 'paid') {
                                    $prizesPaid = false;
                                    break;
                                }
                            }
                        }
                    }
                @endphp
                
                @if($hasCashPrizes)
                    <div class="mb-8">
                        @if($prizesPaid)
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                                        <div>
                                            <h3 class="text-lg font-medium text-green-800">Contest Prizes Paid</h3>
                                            <p class="text-sm text-green-700">
                                                All contest prizes (${{ number_format($totalPrizeAmount, 2) }}) have been successfully paid to winners.
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ route('contest.prizes.receipt', $project) }}" 
                                       class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                        <i class="fas fa-receipt mr-2"></i>
                                        View Receipt
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-dollar-sign text-purple-600 text-xl mr-3"></i>
                                        <div>
                                            <h3 class="text-lg font-medium text-purple-800">Contest Prizes Ready for Payment</h3>
                                            <p class="text-sm text-purple-700">
                                                Total prize amount: ${{ number_format($totalPrizeAmount, 2) }} • Winners must have valid Stripe Connect accounts
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ route('contest.prizes.overview', $project) }}" 
                                       class="inline-flex items-center px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                        <i class="fas fa-dollar-sign mr-2"></i>
                                        Pay Contest Prizes
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @endif

            <!-- Contest Prizes Display -->
            @if($project->hasPrizes())
                <div class="mb-8">
                    <x-contest.prize-display :project="$project" context="judging" />
                </div>
            @endif

            <!-- Winners Summary (always show if there are contest entries to display placeholders) -->
            @if($contestEntries->count() > 0)
                <div class="mb-8" id="current-winners-section">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6 bg-gradient-to-r from-yellow-50 to-amber-50 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                <i class="fas fa-trophy text-yellow-600 mr-2"></i>
                                Current Winners
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="winners-grid">
                                <!-- First Place -->
                                <div id="first-place-card" class="winner-card" data-placement="1st">
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
                                        @else
                                            <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                                                <div class="text-center">
                                                    <div class="text-3xl mb-2 opacity-50">🥇</div>
                                                    <h4 class="font-semibold text-gray-500">1st Place</h4>
                                                    <p class="text-sm text-gray-400">Not assigned yet</p>
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                                            <div class="text-center">
                                                <div class="text-3xl mb-2 opacity-50">🥇</div>
                                                <h4 class="font-semibold text-gray-500">1st Place</h4>
                                                <p class="text-sm text-gray-400">Not assigned yet</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Second Place -->
                                <div id="second-place-card" class="winner-card" data-placement="2nd">
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
                                        @else
                                            <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                                                <div class="text-center">
                                                    <div class="text-3xl mb-2 opacity-50">🥈</div>
                                                    <h4 class="font-semibold text-gray-500">2nd Place</h4>
                                                    <p class="text-sm text-gray-400">Not assigned yet</p>
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                                            <div class="text-center">
                                                <div class="text-3xl mb-2 opacity-50">🥈</div>
                                                <h4 class="font-semibold text-gray-500">2nd Place</h4>
                                                <p class="text-sm text-gray-400">Not assigned yet</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Third Place -->
                                <div id="third-place-card" class="winner-card" data-placement="3rd">
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
                                        @else
                                            <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                                                <div class="text-center">
                                                    <div class="text-3xl mb-2 opacity-50">🥉</div>
                                                    <h4 class="font-semibold text-gray-500">3rd Place</h4>
                                                    <p class="text-sm text-gray-400">Not assigned yet</p>
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                                            <div class="text-center">
                                                <div class="text-3xl mb-2 opacity-50">🥉</div>
                                                <h4 class="font-semibold text-gray-500">3rd Place</h4>
                                                <p class="text-sm text-gray-400">Not assigned yet</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Runner-ups -->
                            <div id="runner-ups-section" class="mt-4">
                                @if($contestResult->runner_up_pitch_ids && count($contestResult->runner_up_pitch_ids) > 0)
                                    <h4 class="font-medium text-gray-900 mb-2">
                                        <i class="fas fa-medal text-blue-600 mr-1"></i>
                                        Runner-ups
                                    </h4>
                                    <div class="flex flex-wrap gap-2" id="runner-ups-list">
                                        @foreach($contestResult->runner_up_pitch_ids as $runnerUpId)
                                            @php $runnerUp = $contestEntries->firstWhere('id', $runnerUpId) @endphp
                                            @if($runnerUp)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800" data-pitch-id="{{ $runnerUp->id }}">
                                                    🏅 {{ $runnerUp->user->name }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
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
                                                @if($entry->submitted_at)
                                                    {{ $entry->submitted_at->format('M d, Y') }}
                                                    <div class="text-xs text-gray-500">
                                                        {{ $entry->submitted_at->format('g:i A') }}
                                                    </div>
                                                @else
                                                    <span class="text-gray-500">Not submitted</span>
                                                    <div class="text-xs text-gray-400">
                                                        Created {{ $entry->created_at->format('M d, Y') }}
                                                    </div>
                                                @endif
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
                                                    <select onchange="updatePlacement('{{ $entry->slug }}', this.value)" 
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
                                                @if($entry->submitted_at && $entry->current_snapshot_id)
                                                    <a href="{{ route('projects.pitches.snapshots.show', [$project, $entry, $entry->current_snapshot_id]) }}" 
                                                       class="text-blue-600 hover:text-blue-800 font-medium">
                                                        View Submitted Entry
                                                    </a>
                                                @elseif(!$entry->submitted_at)
                                                    <span class="text-gray-400 text-sm">
                                                        Not submitted yet
                                                    </span>
                                                @endif
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
        function contestJudging() {
            return {
                loading: {},
                
                init() {
                    // Listen for Livewire events if any components emit them
                    window.addEventListener('placement-updated', (event) => {
                        this.handlePlacementUpdate(event.detail);
                    });
                },
                
                handlePlacementUpdate(data) {
                    console.log('Placement updated via event:', data);
                    // Update UI based on event data
                    if (data.pitch_slug && data.placement !== undefined) {
                        updatePlacementUI(data.pitch_slug, data.placement, data);
                    }
                }
            };
        }
        
        function openFinalizeModal() {
            document.getElementById('finalizeModal').classList.remove('hidden');
        }

        function closeFinalizeModal() {
            document.getElementById('finalizeModal').classList.add('hidden');
        }

        function updatePlacement(pitchSlug, placement) {
            console.log('Updating placement for pitch:', pitchSlug, 'to:', placement);
            console.log('Project slug:', '{{ $project->slug }}');
            
            // Show loading state
            const selectElement = document.querySelector(`select[onchange*="${pitchSlug}"]`);
            const originalValue = selectElement ? selectElement.value : '';
            
            if (selectElement) {
                selectElement.disabled = true;
                selectElement.style.opacity = '0.6';
                
                // Add loading indicator
                const loadingSpinner = document.createElement('div');
                loadingSpinner.className = 'absolute inset-0 flex items-center justify-center bg-white/80 rounded';
                loadingSpinner.innerHTML = '<i class="fas fa-spinner fa-spin text-gray-500"></i>';
                loadingSpinner.id = `loading-${pitchSlug}`;
                
                const selectContainer = selectElement.parentElement;
                selectContainer.style.position = 'relative';
                selectContainer.appendChild(loadingSpinner);
            }
            
            // Build the URL using route helper with proper parameter substitution
            const routeTemplate = '{{ route("projects.contest.update-placement", [$project->slug, "PITCH_SLUG_PLACEHOLDER"]) }}';
            const updateUrl = routeTemplate.replace('PITCH_SLUG_PLACEHOLDER', pitchSlug);
            
            console.log('Update URL:', updateUrl);
            
            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    placement: placement
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response URL:', response.url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.success) {
                    // Update UI dynamically without page refresh
                    updatePlacementUI(pitchSlug, placement, data);
                    
                    // Show success message
                    showNotification('success', data.message || 'Placement updated successfully');
                    
                    // Update other selects to reflect new availability
                    if (data.availablePlacements) {
                        updateAllSelectOptions(data.availablePlacements);
                    }
                    
                    // Update Current Winners section
                    if (data.currentWinners) {
                        updateCurrentWinners(data.currentWinners);
                    }
                    
                } else {
                    throw new Error(data.message || 'Failed to update placement');
                }
            })
            .catch(error => {
                console.error('Error updating placement:', error);
                
                // Revert select to original value
                if (selectElement) {
                    selectElement.value = originalValue;
                }
                
                // Show error message
                showNotification('error', 'Failed to update placement: ' + error.message);
            })
            .finally(() => {
                // Remove loading state
                if (selectElement) {
                    selectElement.disabled = false;
                    selectElement.style.opacity = '1';
                    
                    const loadingSpinner = document.getElementById(`loading-${pitchSlug}`);
                    if (loadingSpinner) {
                        loadingSpinner.remove();
                    }
                }
            });
        }
        
        function updatePlacementUI(pitchSlug, placement, data) {
            // Find the row for this pitch
            const selectElement = document.querySelector(`select[onchange*="${pitchSlug}"]`);
            if (!selectElement) return;
            
            const row = selectElement.closest('tr');
            if (!row) return;
            
            // Update the placement badge in the same row
            const placementCell = row.querySelector('td:nth-child(4)'); // Assuming placement is 4th column
            if (placementCell) {
                const placementBadge = placementCell.querySelector('span');
                if (placementBadge) {
                    if (placement) {
                        // Update badge with new placement
                        const placementInfo = getPlacementInfo(placement);
                        placementBadge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${placementInfo.bgClass}`;
                        placementBadge.innerHTML = `${placementInfo.emoji} ${placementInfo.label}`;
                    } else {
                        // Show "Not Placed"
                        placementBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600';
                        placementBadge.innerHTML = '⭕ Not Placed';
                    }
                    
                    // Add success animation
                    placementBadge.style.transform = 'scale(1.1)';
                    placementBadge.style.transition = 'transform 0.2s ease';
                    setTimeout(() => {
                        placementBadge.style.transform = 'scale(1)';
                    }, 200);
                }
            }
        }
        
        function getPlacementInfo(placement) {
            const placements = {
                '1st': {
                    label: '1st Place',
                    emoji: '🥇',
                    bgClass: 'bg-yellow-100 text-yellow-800'
                },
                '2nd': {
                    label: '2nd Place', 
                    emoji: '🥈',
                    bgClass: 'bg-gray-100 text-gray-800'
                },
                '3rd': {
                    label: '3rd Place',
                    emoji: '🥉', 
                    bgClass: 'bg-orange-100 text-orange-800'
                },
                'runner-up': {
                    label: 'Runner-up',
                    emoji: '🏅',
                    bgClass: 'bg-blue-100 text-blue-800'
                }
            };
            
            return placements[placement] || {
                label: 'Not Placed',
                emoji: '⭕',
                bgClass: 'bg-gray-100 text-gray-600'
            };
        }
        
        function updateAllSelectOptions(availablePlacements) {
            // Update all select elements with new availability
            const allSelects = document.querySelectorAll('select[onchange*="updatePlacement"]');
            
            allSelects.forEach(select => {
                const currentValue = select.value;
                
                // Update options
                Array.from(select.options).forEach(option => {
                    if (option.value === '') return; // Skip "No Placement" option
                    
                    const isCurrentlySelected = option.value === currentValue;
                    const isAvailable = availablePlacements[option.value] && !availablePlacements[option.value].includes('Already Chosen');
                    
                    option.disabled = !isCurrentlySelected && !isAvailable;
                    
                    // Update option text to show availability
                    if (option.value in availablePlacements) {
                        option.textContent = availablePlacements[option.value];
                    }
                });
            });
        }
        
        function updateCurrentWinners(currentWinners) {
            console.log('Updating Current Winners section:', currentWinners);
            
            // Always show the Current Winners section (with placeholders if needed)
            let winnersSection = document.getElementById('current-winners-section');
            
            // Create the section if it doesn't exist
            if (!winnersSection) {
                createCurrentWinnersSection();
                winnersSection = document.getElementById('current-winners-section');
            }
            
            // Always show the section
            if (winnersSection) {
                winnersSection.style.display = 'block';
            }
            
            // Update individual placement cards (will show placeholders if no winner)
            updatePlacementCard('first-place-card', currentWinners.first_place, '1st', '🥇', 'border-yellow-300');
            updatePlacementCard('second-place-card', currentWinners.second_place, '2nd', '🥈', 'border-gray-300');
            updatePlacementCard('third-place-card', currentWinners.third_place, '3rd', '🥉', 'border-orange-300');
            
            // Update runner-ups section (only show if there are runner-ups)
            updateRunnerUps(currentWinners.runner_ups);
        }
        
        function createCurrentWinnersSection() {
            // Find the insertion point (after Contest Prizes Display)
            const contestPrizesSection = document.querySelector('.mb-8:has(x-contest\\.prize-display)') || 
                                        document.querySelector('[class*="prize"]').closest('.mb-8');
            
            if (!contestPrizesSection) return;
            
            // Create the Current Winners section HTML with placeholders
            const winnersHTML = `
                <div class="mb-8" id="current-winners-section">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6 bg-gradient-to-r from-yellow-50 to-amber-50 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                <i class="fas fa-trophy text-yellow-600 mr-2"></i>
                                Current Winners
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="winners-grid">
                                <div id="first-place-card" class="winner-card" data-placement="1st">
                                    <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                                        <div class="text-center">
                                            <div class="text-3xl mb-2 opacity-50">🥇</div>
                                            <h4 class="font-semibold text-gray-500">1st Place</h4>
                                            <p class="text-sm text-gray-400">Not assigned yet</p>
                                        </div>
                                    </div>
                                </div>
                                <div id="second-place-card" class="winner-card" data-placement="2nd">
                                    <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                                        <div class="text-center">
                                            <div class="text-3xl mb-2 opacity-50">🥈</div>
                                            <h4 class="font-semibold text-gray-500">2nd Place</h4>
                                            <p class="text-sm text-gray-400">Not assigned yet</p>
                                        </div>
                                    </div>
                                </div>
                                <div id="third-place-card" class="winner-card" data-placement="3rd">
                                    <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                                        <div class="text-center">
                                            <div class="text-3xl mb-2 opacity-50">🥉</div>
                                            <h4 class="font-semibold text-gray-500">3rd Place</h4>
                                            <p class="text-sm text-gray-400">Not assigned yet</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="runner-ups-section" class="mt-4"></div>
                        </div>
                    </div>
                </div>
            `;
            
            // Insert after the contest prizes section
            contestPrizesSection.insertAdjacentHTML('afterend', winnersHTML);
        }
        
        function updatePlacementCard(cardId, winnerData, placement, emoji, borderClass) {
            const card = document.getElementById(cardId);
            if (!card) return;
            
            if (winnerData) {
                // Show winner
                card.innerHTML = `
                    <div class="bg-white rounded-lg p-4 border-2 ${borderClass}">
                        <div class="text-center">
                            <div class="text-3xl mb-2">${emoji}</div>
                            <h4 class="font-semibold text-gray-900">${placement} Place</h4>
                            <p class="text-sm text-gray-600">${winnerData.user_name}</p>
                        </div>
                    </div>
                `;
                
                // Add animation
                card.style.transform = 'scale(1.05)';
                card.style.transition = 'transform 0.3s ease';
                setTimeout(() => {
                    card.style.transform = 'scale(1)';
                }, 300);
            } else {
                // Show placeholder
                card.innerHTML = `
                    <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                        <div class="text-center">
                            <div class="text-3xl mb-2 opacity-50">${emoji}</div>
                            <h4 class="font-semibold text-gray-500">${placement} Place</h4>
                            <p class="text-sm text-gray-400">Not assigned yet</p>
                        </div>
                    </div>
                `;
                
                // Add subtle animation for placeholder
                card.style.transform = 'scale(1.02)';
                card.style.transition = 'transform 0.2s ease';
                setTimeout(() => {
                    card.style.transform = 'scale(1)';
                }, 200);
            }
        }
        
        function updateRunnerUps(runnerUps) {
            const runnerUpsSection = document.getElementById('runner-ups-section');
            if (!runnerUpsSection) return;
            
            if (runnerUps.length > 0) {
                runnerUpsSection.innerHTML = `
                    <h4 class="font-medium text-gray-900 mb-2">
                        <i class="fas fa-medal text-blue-600 mr-1"></i>
                        Runner-ups
                    </h4>
                    <div class="flex flex-wrap gap-2" id="runner-ups-list">
                        ${runnerUps.map(runnerUp => `
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800" data-pitch-id="${runnerUp.id}">
                                🏅 ${runnerUp.user_name}
                            </span>
                        `).join('')}
                    </div>
                `;
                
                // Add animation to new runner-ups
                const runnerUpsList = document.getElementById('runner-ups-list');
                if (runnerUpsList) {
                    runnerUpsList.style.opacity = '0';
                    runnerUpsList.style.transform = 'translateY(10px)';
                    runnerUpsList.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    
                    setTimeout(() => {
                        runnerUpsList.style.opacity = '1';
                        runnerUpsList.style.transform = 'translateY(0)';
                    }, 100);
                }
            } else {
                runnerUpsSection.innerHTML = '';
            }
        }
        
        function showNotification(type, message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(full)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
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