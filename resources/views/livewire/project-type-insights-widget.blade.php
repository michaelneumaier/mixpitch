<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Project Type Insights</h3>
        <a href="{{ route('analytics.project-types') }}" 
           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            View Details →
        </a>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $this->quickStats['total_projects'] }}</div>
            <div class="text-xs text-gray-600">Total Projects</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ $this->quickStats['active_types'] }}</div>
            <div class="text-xs text-gray-600">Active Types</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $this->quickStats['recent_projects'] }}</div>
            <div class="text-xs text-gray-600">This Week</div>
        </div>
    </div>

    <!-- Most Popular Type -->
    @if($this->mostPopularType)
    <div class="mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Most Popular Type</h4>
        <div class="flex items-center p-3 bg-{{ $this->mostPopularType->color }}-50 rounded-lg">
            <div class="flex items-center justify-center w-8 h-8 bg-{{ $this->mostPopularType->color }}-500 text-white rounded-lg mr-3">
                <i class="{{ $this->mostPopularType->getIconClass() }} text-sm"></i>
            </div>
            <div>
                <div class="font-medium text-{{ $this->mostPopularType->color }}-800">{{ $this->mostPopularType->name }}</div>
                <div class="text-xs text-{{ $this->mostPopularType->color }}-600">{{ $this->mostPopularType->projects_count }} projects</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Trends -->
    @if($this->recentTrends->count() > 0)
    <div>
        <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Trends (30 days)</h4>
        <div class="space-y-2">
            @foreach($this->recentTrends as $trend)
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-6 h-6 bg-{{ $trend['type']->color }}-500 text-white rounded mr-2">
                        <i class="{{ $trend['type']->getIconClass() }} text-xs"></i>
                    </div>
                    <span class="text-sm text-gray-700">{{ $trend['type']->name }}</span>
                </div>
                <span class="text-sm font-medium text-{{ $trend['type']->color }}-600">{{ $trend['count'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
