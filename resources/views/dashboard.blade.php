@extends('components.layouts.app')

@section('content')

<div class="w-full mx-auto">
    <div class="flex justify-center">
        <div class="w-full max-w-6xl mx-auto">
            <div class="shadow mb-4">
                <!-- Adjusted text color for visibility -->
                <div class="py-3 px-4 text-primary text-3xl font-semibold text-center">{{ __('Dashboard') }}</div>

                <div class="p-4 md:p-6 lg:p-8" x-data="{ filter: 'all' }">
                    <div class="flex flex-wrap items-center justify-between mb-4">
                        <h3 class="text-2xl text-primary font-semibold pl-2">
                            My Work
                        </h3>
                        <div class="flex items-center space-x-2">
                             {{-- Filter Buttons --}}
                             <div class="flex space-x-1 border border-gray-300 rounded-lg p-0.5">
                                <button @click="filter = 'all'" :class="{ 'bg-primary text-white': filter === 'all', 'bg-white text-gray-700 hover:bg-gray-100': filter !== 'all' }" class="px-3 py-1.5 text-xs font-medium rounded-md transition">All</button>
                                <button @click="filter = 'project'" :class="{ 'bg-blue-600 text-white': filter === 'project', 'bg-white text-gray-700 hover:bg-gray-100': filter !== 'project' }" class="px-3 py-1.5 text-xs font-medium rounded-md transition">Projects</button>
                                <button @click="filter = 'client'" :class="{ 'bg-purple-600 text-white': filter === 'client', 'bg-white text-gray-700 hover:bg-gray-100': filter !== 'client' }" class="px-3 py-1.5 text-xs font-medium rounded-md transition">Client Projects</button>
                                <button @click="filter = 'pitch'" :class="{ 'bg-indigo-600 text-white': filter === 'pitch', 'bg-white text-gray-700 hover:bg-gray-100': filter !== 'pitch' }" class="px-3 py-1.5 text-xs font-medium rounded-md transition">Pitches</button>
                                <button @click="filter = 'order'" :class="{ 'bg-green-600 text-white': filter === 'order', 'bg-white text-gray-700 hover:bg-gray-100': filter !== 'order' }" class="px-3 py-1.5 text-xs font-medium rounded-md transition">Orders</button>
                                <button @click="filter = 'service'" :class="{ 'bg-amber-600 text-white': filter === 'service', 'bg-white text-gray-700 hover:bg-gray-100': filter !== 'service' }" class="px-3 py-1.5 text-xs font-medium rounded-md transition">Services</button>
                            </div>
                            
                            <a href="{{ route('projects.create') }}"
                               class="bg-primary hover:bg-primary-focus text-white text-sm text-center py-2 px-4 rounded-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Create Project</a>
                             {{-- Add Link to Create Service Package if user is producer --}}
                             {{-- @can('create', App\Models\ServicePackage::class)
                             <a href="{{ route('producer.services.packages.create') }}"
                                class="bg-secondary hover:bg-secondary-focus text-white text-sm text-center ml-2 py-2 px-4 rounded-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">Create Service Package</a>
                             @endcan --}}
                        </div>
                    </div>

                    @if ($workItems->isEmpty())
                        <div class="bg-base-200/50 rounded-lg p-8 text-center">
                            <i class="fas fa-folder-open text-gray-400 text-4xl mb-3"></i>
                            <p class="text-gray-600">You don't have any active work items yet.</p>
                            <p class="text-gray-500 text-sm mt-2">Create a project or find work to get started.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($workItems as $item)
                                @php
                                    $itemType = 'unknown';
                                    if ($item instanceof \App\Models\Project) { 
                                        $itemType = 'project'; 
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
                                
                                <div x-show="filter === 'all' || filter === '{{ $itemType }}'" x-transition>
                                    {{-- Determine item type and include specific card --}}
                                    @if ($itemType === 'project')
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