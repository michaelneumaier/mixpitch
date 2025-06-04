<div class="space-y-6">
    @php
        $data = $this->getViewData();
        $userMetrics = $data['userMetrics'];
        $projectMetrics = $data['projectMetrics'];
        $pitchMetrics = $data['pitchMetrics'];
        $financialMetrics = $data['financialMetrics'];
        $emailMetrics = $data['emailMetrics'];
        $growthTrends = $data['growthTrends'];
        $topPerformers = $data['topPerformers'];
    @endphp

    <!-- Key Metrics Overview -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Users -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Users</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ number_format($userMetrics['total']) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $userMetrics['verification_rate'] }}% verified • {{ $userMetrics['today'] }} new today
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Projects</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ number_format($projectMetrics['total']) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $projectMetrics['completion_rate'] }}% completion rate • {{ $projectMetrics['today'] }} new today
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Platform Value</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">${{ number_format($financialMetrics['total_value']) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        ${{ number_format($financialMetrics['this_month_revenue']) }} completed this month
                    </div>
                </div>
            </div>
        </div>

        <!-- Pitch Success -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Pitch Success Rate</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $pitchMetrics['success_rate'] }}%</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        {{ number_format($pitchMetrics['total']) }} total pitches • {{ $pitchMetrics['today'] }} new today
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Growth Trends Chart -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">30-Day Growth Trends</h3>
            <div class="mt-5">
                <canvas id="growthChart" width="800" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- User Analytics -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">User Analytics</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ number_format($userMetrics['total']) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Verified Users</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ number_format($userMetrics['verified']) }} ({{ $userMetrics['verification_rate'] }}%)</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Users with Projects</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ number_format($userMetrics['with_projects']) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Users with Pitches</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ number_format($userMetrics['with_pitches']) }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Financial Analytics -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Financial Analytics</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Platform Value</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($financialMetrics['total_value']) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed Revenue</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($financialMetrics['completed_revenue']) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Project Value</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($financialMetrics['avg_project_value']) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Conversion Rate</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $financialMetrics['conversion_rate'] }}%</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Top Project Creators -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Top Project Creators</h3>
                <div class="space-y-3">
                    @foreach($topPerformers['top_users_by_projects']->take(5) as $user)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-700">{{ substr($user->name, 0, 1) }}</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                </div>
                            </div>
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ $user->projects_count }} projects
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Most Valuable Projects -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Most Valuable Projects</h3>
                <div class="space-y-3">
                    @foreach($topPerformers['most_valuable_projects']->take(5) as $project)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $project->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">by {{ $project->user->name }}</p>
                            </div>
                            <div class="text-sm font-medium text-green-600">
                                ${{ number_format($project->budget) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('growthChart').getContext('2d');
    const growthData = @json($growthTrends);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: growthData.map(d => d.date),
            datasets: [
                {
                    label: 'Users',
                    data: growthData.map(d => d.users),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                },
                {
                    label: 'Projects',
                    data: growthData.map(d => d.projects),
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.1
                },
                {
                    label: 'Pitches',
                    data: growthData.map(d => d.pitches),
                    borderColor: 'rgb(139, 92, 246)',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            }
        }
    });
});
</script> 