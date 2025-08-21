<x-layouts.app-sidebar title="Client Dashboard - MIXPITCH">
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <!-- Header -->
    <div class="bg-black/20 backdrop-blur-sm border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Client Dashboard</h1>
                    <p class="text-purple-200 mt-1">Welcome back, {{ auth()->user()->name }}</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="bg-gradient-to-r from-purple-500/20 to-pink-500/20 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/10">
                        <span class="text-purple-300 text-sm font-medium">Client Account</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Projects -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 hover:bg-white/15 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-200 text-sm font-medium">Total Projects</p>
                        <p class="text-3xl font-bold text-white mt-1">{{ $stats['total_projects'] }}</p>
                    </div>
                    <div class="bg-purple-500/20 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Projects -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 hover:bg-white/15 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-200 text-sm font-medium">Active Projects</p>
                        <p class="text-3xl font-bold text-white mt-1">{{ $stats['active_projects'] }}</p>
                    </div>
                    <div class="bg-blue-500/20 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Completed Projects -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 hover:bg-white/15 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-200 text-sm font-medium">Completed</p>
                        <p class="text-3xl font-bold text-white mt-1">{{ $stats['completed_projects'] }}</p>
                    </div>
                    <div class="bg-green-500/20 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Spent -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 hover:bg-white/15 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-200 text-sm font-medium">Total Spent</p>
                        <p class="text-3xl font-bold text-white mt-1">${{ number_format($stats['total_spent'], 2) }}</p>
                    </div>
                    <div class="bg-yellow-500/20 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Projects List -->
            <div class="lg:col-span-2">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 overflow-hidden">
                    <div class="p-6 border-b border-white/10">
                        <h2 class="text-xl font-bold text-white">Your Projects</h2>
                        <p class="text-purple-200 text-sm mt-1">Manage and track your project progress</p>
                    </div>

                    <div class="divide-y divide-white/10">
                        @forelse($projects as $project)
                            @php
                                $pitch = $project->pitches->first();
                                $statusColor = match($project->status) {
                                    'open' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                                    'in_progress' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
                                    'completed' => 'bg-green-500/20 text-green-300 border-green-500/30',
                                    default => 'bg-gray-500/20 text-gray-300 border-gray-500/30'
                                };
                            @endphp
                            <div class="p-6 hover:bg-white/5 transition-colors duration-200">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h3 class="text-lg font-semibold text-white">{{ $project->title }}</h3>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium border {{ $statusColor }}">
                                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                            </span>
                                        </div>
                                        
                                        @if($project->description)
                                            <p class="text-purple-200 text-sm mb-3">{{ Str::limit($project->description, 100) }}</p>
                                        @endif

                                        <div class="flex items-center space-x-6 text-sm text-purple-300">
                                            <span>Created {{ $project->created_at->diffForHumans() }}</span>
                                            @if($pitch)
                                                <span>Producer: {{ $pitch->user->name ?? 'Assigned' }}</span>
                                                @if($pitch->payment_amount > 0)
                                                    <span class="text-yellow-300">${{ number_format($pitch->payment_amount, 2) }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-3 ml-4">
                                        @if($project->status === 'completed' && $pitch && $pitch->payment_status === 'paid')
                                            <a href="{{ URL::temporarySignedRoute('client.portal.invoice', now()->addDays(7), ['project' => $project->id]) }}" 
                                               class="px-4 py-2 bg-purple-500/20 text-purple-300 rounded-lg hover:bg-purple-500/30 transition-colors text-sm font-medium border border-purple-500/30">
                                                View Invoice
                                            </a>
                                            <a href="{{ URL::temporarySignedRoute('client.portal.deliverables', now()->addDays(7), ['project' => $project->id]) }}" 
                                               class="px-4 py-2 bg-green-500/20 text-green-300 rounded-lg hover:bg-green-500/30 transition-colors text-sm font-medium border border-green-500/30">
                                                Deliverables
                                            </a>
                                        @endif
                                        
                                        <a href="{{ URL::temporarySignedRoute('client.portal.view', now()->addDays(7), ['project' => $project->id]) }}" 
                                           class="px-4 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-colors text-sm font-medium border border-white/20">
                                            View Project
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-12 text-center">
                                <div class="bg-purple-500/10 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-white mb-2">No Projects Yet</h3>
                                <p class="text-purple-200 text-sm">Your projects will appear here once they're created.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="lg:col-span-1">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 overflow-hidden">
                    <div class="p-6 border-b border-white/10">
                        <h2 class="text-xl font-bold text-white">Recent Activity</h2>
                        <p class="text-purple-200 text-sm mt-1">Latest updates on your projects</p>
                    </div>

                    <div class="divide-y divide-white/10 max-h-96 overflow-y-auto">
                        @forelse($recentActivity as $activity)
                            @php
                                $eventIcon = match($activity->event_type) {
                                    'pitch_submitted' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-1.586l-4 4z',
                                    'pitch_approved' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                                    'pitch_completed' => 'M5 13l4 4L19 7',
                                    'payment_completed' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
                                    'revisions_requested' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
                                    default => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                                };
                                $eventColor = match($activity->event_type) {
                                    'pitch_submitted' => 'text-blue-400',
                                    'pitch_approved' => 'text-green-400',
                                    'pitch_completed' => 'text-green-400',
                                    'payment_completed' => 'text-yellow-400',
                                    'revisions_requested' => 'text-orange-400',
                                    default => 'text-purple-400'
                                };
                            @endphp
                            <div class="p-4 hover:bg-white/5 transition-colors duration-200">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 mt-1">
                                        <svg class="w-5 h-5 {{ $eventColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $eventIcon }}"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-white text-sm font-medium">
                                            {{ ucfirst(str_replace('_', ' ', $activity->event_type)) }}
                                        </p>
                                        <p class="text-purple-200 text-xs mt-1">
                                            {{ $activity->project->title }}
                                        </p>
                                        <p class="text-purple-300 text-xs mt-1">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center">
                                <div class="bg-purple-500/10 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <p class="text-purple-200 text-sm">No recent activity</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-layouts.app-sidebar> 