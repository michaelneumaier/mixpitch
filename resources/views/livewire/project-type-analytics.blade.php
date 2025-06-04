<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Project Type Analytics</h2>
                <p class="text-gray-600">Insights and statistics about project type performance</p>
            </div>
            
            <!-- Period Selector -->
            <div class="mt-4 md:mt-0">
                <select wire:model.live="selectedPeriod" 
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">Last year</option>
                    <option value="all">All time</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Overall Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-project-diagram text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Projects</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($this->totalStats['total_projects']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Budget</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($this->totalStats['total_budget'], 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Avg Budget</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($this->totalStats['avg_budget'], 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-lg">
                    <i class="fas fa-clock text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Active</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($this->totalStats['active_projects']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-emerald-100 rounded-lg">
                    <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Completion Rate</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $this->totalStats['completion_rate'] }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Type Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($this->projectTypeStats as $stat)
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header with Icon and Color -->
            <div class="bg-gradient-to-r from-{{ $stat['color'] }}-500 to-{{ $stat['color'] }}-600 px-6 py-4">
                <div class="flex items-center text-white">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <i class="{{ $stat['icon'] }} text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-bold">{{ $stat['name'] }}</h3>
                        <p class="text-{{ $stat['color'] }}-100 text-sm">{{ $stat['description'] }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['project_count'] }}</p>
                        <p class="text-sm text-gray-600">Projects</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['completion_rate'] }}%</p>
                        <p class="text-sm text-gray-600">Completed</p>
                    </div>
                </div>
                
                @if($stat['total_budget'] > 0)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total Budget:</span>
                        <span class="font-semibold text-{{ $stat['color'] }}-600">${{ number_format($stat['total_budget']) }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-sm text-gray-600">Avg Budget:</span>
                        <span class="font-semibold text-{{ $stat['color'] }}-600">${{ number_format($stat['avg_budget']) }}</span>
                    </div>
                </div>
                @endif

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Active:</span>
                        <span class="font-semibold text-orange-600">{{ $stat['active_projects'] }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-sm text-gray-600">Completed:</span>
                        <span class="font-semibold text-green-600">{{ $stat['completed_projects'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Popularity Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Project Type Popularity</h3>
            @if(array_sum($this->popularityChartData['data']) > 0)
                <div class="h-64">
                    <canvas id="popularityChart"></canvas>
                </div>
            @else
                <div class="h-64 flex items-center justify-center text-gray-500">
                    <div class="text-center">
                        <i class="fas fa-chart-pie text-4xl mb-4"></i>
                        <p>No data available for selected period</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Budget Distribution Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Budget Distribution</h3>
            @if(array_sum($this->budgetChartData['data']) > 0)
                <div class="h-64">
                    <canvas id="budgetChart"></canvas>
                </div>
            @else
                <div class="h-64 flex items-center justify-center text-gray-500">
                    <div class="text-center">
                        <i class="fas fa-chart-bar text-4xl mb-4"></i>
                        <p>No budget data available for selected period</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('livewire:navigated', function() {
    initCharts();
});

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
});

function initCharts() {
    // Popularity Chart
    const popularityCtx = document.getElementById('popularityChart');
    if (popularityCtx) {
        // Destroy existing chart if it exists
        if (window.popularityChart) {
            window.popularityChart.destroy();
        }
        
        const popularityData = {!! json_encode($this->popularityChartData) !!};
        if (popularityData.data.some(value => value > 0)) {
            window.popularityChart = new Chart(popularityCtx, {
                type: 'doughnut',
                data: {
                    labels: popularityData.labels,
                    datasets: [{
                        data: popularityData.data,
                        backgroundColor: popularityData.colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
    
    // Budget Chart
    const budgetCtx = document.getElementById('budgetChart');
    if (budgetCtx) {
        // Destroy existing chart if it exists
        if (window.budgetChart) {
            window.budgetChart.destroy();
        }
        
        const budgetData = {!! json_encode($this->budgetChartData) !!};
        if (budgetData.data.some(value => value > 0)) {
            window.budgetChart = new Chart(budgetCtx, {
                type: 'bar',
                data: {
                    labels: budgetData.labels,
                    datasets: [{
                        label: 'Total Budget ($)',
                        data: budgetData.data,
                        backgroundColor: budgetData.colors,
                        borderWidth: 0,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    }
}

// Re-initialize charts when Livewire updates
document.addEventListener('livewire:updated', function() {
    setTimeout(initCharts, 100);
});
</script>
@endpush
