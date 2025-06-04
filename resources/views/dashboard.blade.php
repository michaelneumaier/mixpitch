@extends('components.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Enhanced Header -->
            <div class="relative mb-8">
                <!-- Header Content -->
                <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <!-- Title Section -->
                        <div>
                            <h1 class="text-4xl lg:text-5xl font-bold bg-gradient-to-r from-gray-900 via-blue-800 to-purple-800 bg-clip-text text-transparent mb-2">
                                Dashboard
                            </h1>
                            <p class="text-lg text-gray-600 font-medium">Manage your projects, pitches, and collaborations</p>
                            </div>
                            
                        <!-- Action Button -->
                        <div class="flex-shrink-0">
                            <a href="{{ route('projects.create') }}"
                               class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                <i class="fas fa-plus mr-2 group-hover:scale-110 transition-transform"></i>
                                Create Project
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Work Section -->
            <div class="relative">
                <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl p-6 lg:p-8" x-data="{ filter: 'all' }">
                    <!-- Section Header with Filters -->
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
                        <div>
                            <h2 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-2">
                                My Work
                            </h2>
                            <p class="text-gray-600">Track and manage all your active projects and collaborations</p>
                        </div>
                        
                        <!-- Enhanced Filter System -->
                        <div class="flex flex-wrap gap-1.5">
                            <button @click="filter = 'all'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-md scale-105': filter === 'all', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'all' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-th-large mr-1.5 text-xs"></i>All
                            </button>
                            <button @click="filter = 'project'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md scale-105': filter === 'project', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'project' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-folder mr-1.5 text-xs"></i>Projects
                            </button>
                            <button @click="filter = 'contest'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-yellow-500 to-orange-600 text-white shadow-md scale-105': filter === 'contest', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'contest' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-trophy mr-1.5 text-xs"></i>Contests
                            </button>
                            <button @click="filter = 'client'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow-md scale-105': filter === 'client', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'client' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-briefcase mr-1.5 text-xs"></i>Client Projects
                            </button>
                            <button @click="filter = 'pitch'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-indigo-500 to-indigo-600 text-white shadow-md scale-105': filter === 'pitch', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'pitch' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-paper-plane mr-1.5 text-xs"></i>Pitches
                            </button>
                            <button @click="filter = 'order'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-green-500 to-green-600 text-white shadow-md scale-105': filter === 'order', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'order' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-shopping-cart mr-1.5 text-xs"></i>Orders
                            </button>
                            <button @click="filter = 'service'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-md scale-105': filter === 'service', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'service' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-box mr-1.5 text-xs"></i>Services
                            </button>
                        </div>
                    </div>

                    @if ($workItems->isEmpty())
                        <!-- Enhanced Empty State -->
                        <div class="text-center py-16">
                            <div class="mx-auto w-32 h-32 bg-gradient-to-br from-blue-100 to-purple-100 rounded-3xl flex items-center justify-center mb-8 shadow-lg">
                                <i class="fas fa-rocket text-5xl text-blue-500"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-4">Ready to Start Creating?</h3>
                            <p class="text-gray-600 mb-8 max-w-md mx-auto leading-relaxed">
                                You don't have any active work items yet. Create your first project or find exciting collaborations to get started on your musical journey.
                            </p>
                            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                <a href="{{ route('projects.create') }}" 
                                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                    <i class="fas fa-plus mr-2"></i>Create Project
                                </a>
                                <a href="{{ route('projects.index') }}" 
                                   class="inline-flex items-center px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-xl border-2 border-gray-200 hover:border-gray-300 shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105">
                                    <i class="fas fa-search mr-2"></i>Browse Projects
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- Work Items Grid -->
                        <div class="grid gap-6">
                            @foreach ($workItems as $item)
                                @php
                                    $itemType = 'unknown';
                                    if ($item instanceof \App\Models\Project) { 
                                        // Check if this is a contest project
                                        if ($item->isContest()) {
                                            $itemType = 'contest';
                                        } else {
                                        $itemType = 'project'; 
                                        }
                                    }
                                    elseif ($item instanceof \App\Models\Pitch) { 
                                        // Check if this is a client management pitch
                                        if ($item->project && $item->project->isClientManagement()) {
                                            $itemType = 'client';
                                        } else {
                                            $itemType = 'pitch';
                                        }
                                    }
                                    elseif ($item instanceof \App\Models\Order) { 
                                        $itemType = 'order'; 
                                    }
                                    elseif ($item instanceof \App\Models\ServicePackage) { 
                                        $itemType = 'service'; 
                                    }
                                @endphp
                                
                                <div x-show="filter === 'all' || filter === '{{ $itemType }}'" 
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 transform scale-95"
                                     x-transition:enter-end="opacity-100 transform scale-100"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100 transform scale-100"
                                     x-transition:leave-end="opacity-0 transform scale-95">
                                    {{-- Determine item type and include specific card --}}
                                    @if ($itemType === 'project')
                                        @include('dashboard.cards._project_card', ['project' => $item])
                                    @elseif ($itemType === 'contest')
                                        @include('dashboard.cards._project_card', ['project' => $item])
                                    @elseif ($itemType === 'pitch')
                                        @include('dashboard.cards._pitch_card', ['pitch' => $item])
                                    @elseif ($itemType === 'client')
                                        @include('dashboard.cards._pitch_card', ['pitch' => $item])
                                    @elseif ($itemType === 'order')
                                        @include('dashboard.cards._order_card', ['order' => $item])
                                    @elseif ($itemType === 'service')
                                        @include('dashboard.cards._service_package_card', ['package' => $item])
                                    @endif
                                 </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection