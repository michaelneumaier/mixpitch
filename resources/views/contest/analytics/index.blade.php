<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Contest Analytics: {{ $project->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Comprehensive insights and data analysis for your contest
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('projects.contest.judging', $project) }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-gavel mr-2"></i>
                    Back to Judging
                </a>
                
                @if($analytics['is_finalized'])
                    <a href="{{ route('projects.contest.results', $project) }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-trophy mr-2"></i>
                        View Results
                    </a>
                @endif

                <a href="{{ route('projects.contest.export', $project) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-download mr-2"></i>
                    Export Data
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Entries -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-users text-blue-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Entries</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $analytics['total_entries'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Placed Entries -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-trophy text-yellow-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Placed Entries</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $analytics['placed_entries'] }}</p>
                                @if($analytics['total_entries'] > 0)
                                    <p class="text-xs text-gray-500">
                                        {{ round(($analytics['placed_entries'] / $analytics['total_entries']) * 100, 1) }}% of total
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contest Duration -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-calendar-alt text-green-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Contest Duration</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $analytics['contest_duration'] }}</p>
                                <p class="text-xs text-gray-500">days</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 {{ $analytics['is_finalized'] ? 'bg-green-100' : 'bg-orange-100' }} rounded-lg flex items-center justify-center">
                                    <i class="fas {{ $analytics['is_finalized'] ? 'fa-check-circle text-green-600' : 'fa-clock text-orange-600' }} text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Contest Status</p>
                                <p class="text-lg font-bold {{ $analytics['is_finalized'] ? 'text-green-700' : 'text-orange-700' }}">
                                    {{ $analytics['is_finalized'] ? 'Finalized' : 'In Progress' }}
                                </p>
                                @if($analytics['is_finalized'] && $analytics['finalized_at'])
                                    <p class="text-xs text-gray-500">
                                        {{ $analytics['finalized_at']->format('M d, Y') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prize Analytics Section -->
            @if($project->hasPrizes())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">
                            <i class="fas fa-trophy text-amber-600 mr-2"></i>
                            Prize Analytics
                        </h3>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Prize Summary Stats -->
                            <div class="lg:col-span-1">
                                <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-6 border border-amber-200">
                                    <h4 class="text-sm font-medium text-amber-800 mb-4">Prize Pool Summary</h4>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-amber-700">Total Cash Prizes:</span>
                                            <span class="font-bold text-amber-900">${{ number_format($project->getTotalPrizeBudget()) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-amber-700">Total Prize Value:</span>
                                            <span class="font-bold text-amber-900">${{ number_format($project->getTotalPrizeValue()) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-amber-700">Prize Tiers:</span>
                                            <span class="font-bold text-amber-900">{{ $project->contestPrizes()->count() }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-amber-700">Value per Entry:</span>
                                            <span class="font-bold text-amber-900">
                                                @if($analytics['total_entries'] > 0)
                                                    ${{ number_format($project->getTotalPrizeValue() / $analytics['total_entries'], 2) }}
                                                @else
                                                    $0.00
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Prize Breakdown -->
                            <div class="lg:col-span-2">
                                <h4 class="text-sm font-medium text-gray-700 mb-4">Prize Breakdown by Placement</h4>
                                <div class="space-y-3">
                                    @foreach($project->getPrizeSummary() as $prize)
                                        <div class="bg-white/60 backdrop-blur-sm border border-amber-200/50 rounded-lg p-3">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <span class="text-lg mr-2">{{ $prize['emoji'] ?? '🏆' }}</span>
                                                    <div>
                                                        <h5 class="font-medium text-gray-900">{{ $prize['placement'] ?? 'Prize' }}</h5>
                                                        @if(isset($prize['title']) && $prize['title'])
                                                        <p class="text-xs text-gray-600">{{ $prize['title'] }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="font-bold text-gray-900">{{ $prize['display_value'] ?? 'N/A' }}</div>
                                                    
                                                    <!-- Winner Display -->
                                                    @php
                                                        $winner = null;
                                                        if(($prize['placement'] ?? '') === '1st Place' && $contestResult->first_place_pitch_id) {
                                                            $winner = $contestResult->firstPlacePitch->user ?? null;
                                                        } elseif(($prize['placement'] ?? '') === '2nd Place' && $contestResult->second_place_pitch_id) {
                                                            $winner = $contestResult->secondPlacePitch->user ?? null;
                                                        } elseif(($prize['placement'] ?? '') === '3rd Place' && $contestResult->third_place_pitch_id) {
                                                            $winner = $contestResult->thirdPlacePitch->user ?? null;
                                                        }
                                                    @endphp
                                                    
                                                    @if($winner)
                                                    <div class="text-xs text-green-600 font-medium mt-1">
                                                        <i class="fas fa-crown mr-1"></i>
                                                        {{ $winner->name }}
                                                    </div>
                                                    @php
                                                        // Check payment status for this winner
                                                        $paymentStatus = $project->getContestPaymentStatus();
                                                        $winnerStatus = collect($paymentStatus['winners_with_status'])
                                                            ->firstWhere('user.id', $winner->id);
                                                        $isPaid = $winnerStatus && $winnerStatus['is_paid'];
                                                        $isCashPrize = isset($prize['type']) && $prize['type'] === 'cash';
                                                    @endphp
                                                    @if($isCashPrize)
                                                        @if($isPaid)
                                                            <div class="text-xs text-green-600 mt-1">
                                                                <i class="fas fa-check-circle mr-1"></i>Prize Paid
                                                            </div>
                                                        @else
                                                            <div class="text-xs text-yellow-600 mt-1">
                                                                <i class="fas fa-clock mr-1"></i>Payment Pending
                                                            </div>
                                                        @endif
                                                    @endif
                                                    @else
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        No winner selected
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Submissions Timeline Chart -->
            @if($analytics['entries_by_date']->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                            Submissions Timeline
                        </h3>
                        <div class="h-64">
                            <canvas id="submissionsChart"></canvas>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Placement Distribution -->
            @if($contestResult && $contestResult->hasWinners())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">
                            <i class="fas fa-medal text-yellow-600 mr-2"></i>
                            Placement Distribution
                        </h3>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Pie Chart -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-4">Placement Overview</h4>
                                <div class="h-48">
                                    <canvas id="placementChart"></canvas>
                                </div>
                            </div>
                            
                            <!-- Placement Details -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-4">Winners Details</h4>
                                <div class="space-y-3">
                                    @if($contestResult->first_place_pitch_id)
                                        @php $firstPlace = $contestEntries->firstWhere('id', $contestResult->first_place_pitch_id) @endphp
                                        @if($firstPlace)
                                            <div class="flex items-center p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                                <div class="text-2xl mr-3">🥇</div>
                                                <div>
                                                    <p class="font-medium text-gray-900">{{ $firstPlace->user->name }}</p>
                                                    <p class="text-sm text-gray-600">1st Place</p>
                                                </div>
                                            </div>
                                        @endif
                                    @endif

                                    @if($contestResult->second_place_pitch_id)
                                        @php $secondPlace = $contestEntries->firstWhere('id', $contestResult->second_place_pitch_id) @endphp
                                        @if($secondPlace)
                                            <div class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                <div class="text-2xl mr-3">🥈</div>
                                                <div>
                                                    <p class="font-medium text-gray-900">{{ $secondPlace->user->name }}</p>
                                                    <p class="text-sm text-gray-600">2nd Place</p>
                                                </div>
                                            </div>
                                        @endif
                                    @endif

                                    @if($contestResult->third_place_pitch_id)
                                        @php $thirdPlace = $contestEntries->firstWhere('id', $contestResult->third_place_pitch_id) @endphp
                                        @if($thirdPlace)
                                            <div class="flex items-center p-3 bg-orange-50 rounded-lg border border-orange-200">
                                                <div class="text-2xl mr-3">🥉</div>
                                                <div>
                                                    <p class="font-medium text-gray-900">{{ $thirdPlace->user->name }}</p>
                                                    <p class="text-sm text-gray-600">3rd Place</p>
                                                </div>
                                            </div>
                                        @endif
                                    @endif

                                    @if($contestResult->runner_up_pitch_ids && count($contestResult->runner_up_pitch_ids) > 0)
                                        <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                            <div class="flex items-center mb-2">
                                                <div class="text-xl mr-2">🏅</div>
                                                <p class="font-medium text-gray-900">Runner-ups ({{ count($contestResult->runner_up_pitch_ids) }})</p>
                                            </div>
                                            <div class="ml-6 space-y-1">
                                                @foreach($contestResult->runner_up_pitch_ids as $runnerUpId)
                                                    @php $runnerUp = $contestEntries->firstWhere('id', $runnerUpId) @endphp
                                                    @if($runnerUp)
                                                        <p class="text-sm text-gray-600">{{ $runnerUp->user->name }}</p>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Participant Details Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-table text-gray-600 mr-2"></i>
                            Participant Details
                        </h3>
                        <div class="text-sm text-gray-500">
                            {{ $contestEntries->count() }} total participants
                        </div>
                    </div>
                    
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
                                            Placement
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Days Since Submission
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($contestEntries->sortBy('created_at') as $entry)
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
                                                {{ $entry->created_at_for_user->format('M d, Y') }}
                                                <div class="text-xs text-gray-500">
                                                    {{ $entry->created_at_for_user->format('g:i A') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($contestResult && $contestResult->hasPlacement($entry->id))
                                                    @php $placement = $contestResult->hasPlacement($entry->id) @endphp
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        {{ $placement === '1st' ? 'bg-yellow-100 text-yellow-800' : 
                                                           ($placement === '2nd' ? 'bg-gray-100 text-gray-800' : 
                                                           ($placement === '3rd' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800')) }}">
                                                        {{ $placement === '1st' ? '🥇 1st Place' : 
                                                           ($placement === '2nd' ? '🥈 2nd Place' : 
                                                           ($placement === '3rd' ? '🥉 3rd Place' : '🏅 Runner-up')) }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-500">Not placed</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    {{ $entry->status === 'contest_winner' ? 'bg-green-100 text-green-800' :
                                                       ($entry->status === 'contest_runner_up' ? 'bg-blue-100 text-blue-800' :
                                                       ($entry->status === 'contest_not_selected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
                                                    {{ ucfirst(str_replace('_', ' ', $entry->status)) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $entry->created_at->diffInDays(now()) }} days
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No contest entries yet</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Contest Summary Statistics -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">
                        <i class="fas fa-chart-pie text-green-600 mr-2"></i>
                        Contest Summary
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Participation Rate</h4>
                            @if($analytics['total_entries'] > 0)
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ round(($analytics['placed_entries'] / $analytics['total_entries']) * 100, 1) }}%
                                </p>
                                <p class="text-xs text-gray-500">of entries received placement</p>
                            @else
                                <p class="text-2xl font-bold text-gray-400">0%</p>
                                <p class="text-xs text-gray-500">No entries yet</p>
                            @endif
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Average Submission Time</h4>
                            @if($contestEntries->count() > 0)
                                @php
                                    $avgDaysFromStart = $contestEntries->avg(function($entry) use ($project) {
                                        return $project->created_at->diffInDays($entry->created_at);
                                    });
                                @endphp
                                <p class="text-2xl font-bold text-gray-900">{{ round($avgDaysFromStart, 1) }}</p>
                                <p class="text-xs text-gray-500">days from contest start</p>
                            @else
                                <p class="text-2xl font-bold text-gray-400">0</p>
                                <p class="text-xs text-gray-500">No data available</p>
                            @endif
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Time to Judging</h4>
                            @if($analytics['is_finalized'])
                                <p class="text-2xl font-bold text-gray-900">{{ $analytics['judging_duration'] }}</p>
                                <p class="text-xs text-gray-500">days from deadline to finalization</p>
                            @else
                                <p class="text-2xl font-bold text-orange-600">{{ now()->diffInDays($project->submission_deadline) }}</p>
                                <p class="text-xs text-gray-500">days since deadline (in progress)</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Submissions Timeline Chart
        @if($analytics['entries_by_date']->count() > 0)
            const submissionsCtx = document.getElementById('submissionsChart').getContext('2d');
            new Chart(submissionsCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($analytics['entries_by_date']->keys()) !!},
                    datasets: [{
                        label: 'Submissions per Day',
                        data: {!! json_encode($analytics['entries_by_date']->values()) !!},
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        @endif

        // Placement Distribution Pie Chart
        @if($contestResult && $contestResult->hasWinners())
            const placementCtx = document.getElementById('placementChart').getContext('2d');
            
            let placementData = [];
            let placementLabels = [];
            let placementColors = [];
            
            @if($contestResult->first_place_pitch_id)
                placementLabels.push('1st Place');
                placementData.push(1);
                placementColors.push('#fbbf24');
            @endif
            
            @if($contestResult->second_place_pitch_id)
                placementLabels.push('2nd Place');
                placementData.push(1);
                placementColors.push('#9ca3af');
            @endif
            
            @if($contestResult->third_place_pitch_id)
                placementLabels.push('3rd Place');
                placementData.push(1);
                placementColors.push('#fb923c');
            @endif
            
            @if($contestResult->runner_up_pitch_ids && count($contestResult->runner_up_pitch_ids) > 0)
                placementLabels.push('Runner-ups');
                placementData.push({{ count($contestResult->runner_up_pitch_ids) }});
                placementColors.push('#60a5fa');
            @endif
            
            const unplacedCount = {{ $analytics['unplaced_entries'] }};
            if (unplacedCount > 0) {
                placementLabels.push('Not Placed');
                placementData.push(unplacedCount);
                placementColors.push('#e5e7eb');
            }

            new Chart(placementCtx, {
                type: 'doughnut',
                data: {
                    labels: placementLabels,
                    datasets: [{
                        data: placementData,
                        backgroundColor: placementColors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    }
                }
            });
        @endif
    </script>
</x-app-layout> 