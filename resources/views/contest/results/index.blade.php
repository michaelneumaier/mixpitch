<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Contest Results: {{ $project->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $contestEntries->count() }} total entries
                    @if($project->judging_finalized_at)
                        • Judging finalized {{ $project->judging_finalized_at->format('M d, Y') }}
                    @endif
                </p>
            </div>
            <div class="flex space-x-3">
                @can('judgeContest', $project)
                    <a href="{{ route('projects.contest.judging', $project) }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-gavel mr-2"></i>
                        Judging Dashboard
                    </a>
                @endcan
                
                @can('export', $project->contestResult)
                    <a href="{{ route('projects.contest.export', $project) }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-download mr-2"></i>
                        Export Results
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Contest Status -->
            <div class="mb-8">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-trophy text-green-600 text-xl mr-3"></i>
                        <div>
                            <h3 class="text-lg font-medium text-green-800">Contest Complete</h3>
                            <p class="text-sm text-green-700">
                                Judging finalized on {{ $project->judging_finalized_at->format('M d, Y \a\t g:i A') }}
                                @if($project->judging_notes)
                                    <br>
                                    <span class="italic">"{{ $project->judging_notes }}"</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contest Prizes Display -->
            @if($project->hasPrizes())
                <div class="mb-8">
                    <x-contest.prize-display :project="$project" />
                </div>
            @endif

            @if($contestResult && $contestResult->hasWinners())
                <!-- Winners Podium -->
                <div class="mb-8">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg border border-gray-200">
                        <div class="p-8 bg-gradient-to-br from-yellow-50 via-amber-50 to-orange-50">
                            <div class="text-center mb-8">
                                <h3 class="text-3xl font-bold text-gray-900 mb-2">
                                    <i class="fas fa-crown text-yellow-500 mr-3"></i>
                                    Contest Winners
                                </h3>
                                <p class="text-gray-600">Congratulations to our top performers!</p>
                            </div>
                            
                            <!-- Podium Layout -->
                            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-center lg:space-x-8 space-y-6 lg:space-y-0">
                                
                                <!-- Second Place (Left) -->
                                @if($contestResult->second_place_pitch_id)
                                    @php $secondPlace = $contestEntries->firstWhere('id', $contestResult->second_place_pitch_id) @endphp
                                    @if($secondPlace)
                                        <div class="order-2 lg:order-1">
                                            <div class="bg-white rounded-xl shadow-lg p-6 border-4 border-gray-300 transform hover:scale-105 transition-transform duration-200">
                                                <div class="text-center">
                                                    <div class="w-20 h-20 bg-gradient-to-br from-gray-400 to-gray-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                                                        <span class="text-3xl">🥈</span>
                                                    </div>
                                                    <div class="bg-gray-100 rounded-lg px-3 py-1 text-sm font-medium text-gray-700 mb-3">
                                                        2nd Place
                                                    </div>
                                                    <h4 class="text-xl font-bold text-gray-900 mb-2">{{ $secondPlace->user->name }}</h4>
                                                    <p class="text-sm text-gray-600">{{ $secondPlace->created_at->format('M d, Y') }}</p>
                                                    @can('viewContestEntry', $secondPlace)
                                                        <a href="{{ route('projects.pitches.show', [$project, $secondPlace]) }}" 
                                                           class="inline-block mt-3 text-blue-600 hover:text-blue-800 font-medium text-sm">
                                                            View Entry →
                                                        </a>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif

                                <!-- First Place (Center) -->
                                @if($contestResult->first_place_pitch_id)
                                    @php $firstPlace = $contestEntries->firstWhere('id', $contestResult->first_place_pitch_id) @endphp
                                    @if($firstPlace)
                                        <div class="order-1 lg:order-2">
                                            <div class="bg-white rounded-xl shadow-xl p-8 border-4 border-yellow-400 transform hover:scale-105 transition-transform duration-200 lg:mt-[-2rem]">
                                                <div class="text-center">
                                                    <div class="w-24 h-24 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                                                        <span class="text-4xl">🥇</span>
                                                    </div>
                                                    <div class="bg-yellow-100 rounded-lg px-4 py-2 text-base font-bold text-yellow-800 mb-4">
                                                        🏆 1st Place Winner
                                                    </div>
                                                    <h4 class="text-2xl font-bold text-gray-900 mb-2">{{ $firstPlace->user->name }}</h4>
                                                    <p class="text-gray-600 mb-3">{{ $firstPlace->created_at->format('M d, Y') }}</p>
                                                    @if($project->hasPrizes())
                                                        @php $firstPrize = $project->getPrizeForPlacement('1st'); @endphp
                                                        @if($firstPrize)
                                                            <div class="bg-green-100 rounded-lg px-3 py-2 text-sm font-medium text-green-800 mb-3">
                                                                🏆 Prize: {{ $firstPrize->getDisplayValue() }}
                                                            </div>
                                                        @endif
                                                    @elseif($project->prize_amount > 0)
                                                        <div class="bg-green-100 rounded-lg px-3 py-2 text-sm font-medium text-green-800 mb-3">
                                                            Prize: {{ $project->prize_currency }} {{ number_format($project->prize_amount, 2) }}
                                                        </div>
                                                    @endif
                                                    @can('viewContestEntry', $firstPlace)
                                                        <a href="{{ route('projects.pitches.show', [$project, $firstPlace]) }}" 
                                                           class="inline-block mt-2 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-medium text-sm">
                                                            View Winning Entry →
                                                        </a>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif

                                <!-- Third Place (Right) -->
                                @if($contestResult->third_place_pitch_id)
                                    @php $thirdPlace = $contestEntries->firstWhere('id', $contestResult->third_place_pitch_id) @endphp
                                    @if($thirdPlace)
                                        <div class="order-3">
                                            <div class="bg-white rounded-xl shadow-lg p-6 border-4 border-orange-300 transform hover:scale-105 transition-transform duration-200">
                                                <div class="text-center">
                                                    <div class="w-20 h-20 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                                                        <span class="text-3xl">🥉</span>
                                                    </div>
                                                    <div class="bg-orange-100 rounded-lg px-3 py-1 text-sm font-medium text-orange-700 mb-3">
                                                        3rd Place
                                                    </div>
                                                    <h4 class="text-xl font-bold text-gray-900 mb-2">{{ $thirdPlace->user->name }}</h4>
                                                    <p class="text-sm text-gray-600">{{ $thirdPlace->created_at->format('M d, Y') }}</p>
                                                    @can('viewContestEntry', $thirdPlace)
                                                        <a href="{{ route('projects.pitches.show', [$project, $thirdPlace]) }}" 
                                                           class="inline-block mt-3 text-blue-600 hover:text-blue-800 font-medium text-sm">
                                                            View Entry →
                                                        </a>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Runner-ups Section -->
                @if($contestResult->runner_up_pitch_ids && count($contestResult->runner_up_pitch_ids) > 0)
                    <div class="mb-8">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                            <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                                <h3 class="text-xl font-medium text-gray-900 mb-4">
                                    <i class="fas fa-medal text-blue-600 mr-2"></i>
                                    Runner-ups
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($contestResult->runner_up_pitch_ids as $runnerUpId)
                                        @php $runnerUp = $contestEntries->firstWhere('id', $runnerUpId) @endphp
                                        @if($runnerUp)
                                            <div class="bg-white rounded-lg p-4 border border-blue-200 hover:border-blue-400 transition-colors duration-200">
                                                <div class="flex items-center space-x-3">
                                                    <div class="flex-shrink-0">
                                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                                                            <span class="text-xl">🏅</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <h4 class="text-lg font-medium text-gray-900 truncate">{{ $runnerUp->user->name }}</h4>
                                                        <p class="text-sm text-gray-600">{{ $runnerUp->created_at->format('M d, Y') }}</p>
                                                        @can('viewContestEntry', $runnerUp)
                                                            <a href="{{ route('projects.pitches.show', [$project, $runnerUp]) }}" 
                                                               class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                                                View Entry →
                                                            </a>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            <!-- All Participants -->
            @can('viewContestEntries', $project)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">All Participants</h3>
                        
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
                                                Final Placement
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($contestEntries->sortBy(function($entry) {
                                            return match($entry->rank) {
                                                '1st' => 1,
                                                '2nd' => 2,
                                                '3rd' => 3,
                                                'runner-up' => 4,
                                                default => 5
                                            };
                                        }) as $entry)
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
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                                            {{ $entry->rank === '1st' ? 'bg-yellow-100 text-yellow-800 border border-yellow-300' : 
                                                               ($entry->rank === '2nd' ? 'bg-gray-100 text-gray-800 border border-gray-300' : 
                                                               ($entry->rank === '3rd' ? 'bg-orange-100 text-orange-800 border border-orange-300' : 
                                                               'bg-blue-100 text-blue-800 border border-blue-300')) }}">
                                                            {{ $entry->rank === '1st' ? '🥇' : 
                                                               ($entry->rank === '2nd' ? '🥈' : 
                                                               ($entry->rank === '3rd' ? '🥉' : '🏅')) }}
                                                            {{ ucfirst($entry->rank) }} {{ $entry->rank === 'runner-up' ? '' : 'Place' }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                                                            Participant
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @can('viewContestEntry', $entry)
                                                        <a href="{{ route('projects.pitches.show', [$project, $entry]) }}" 
                                                           class="text-blue-600 hover:text-blue-800 font-medium">
                                                            View Entry
                                                        </a>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="text-gray-400 text-4xl mb-4">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Participants</h3>
                                <p class="text-gray-500">No entries were submitted to this contest.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endcan
        </div>
    </div>

    @push('scripts')
    <script>
        // Add any interactive elements if needed
        console.log('Contest results page loaded');
    </script>
    @endpush
</x-app-layout> 