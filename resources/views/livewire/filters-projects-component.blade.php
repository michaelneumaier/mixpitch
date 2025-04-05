<div>
    <form wire:submit="render" class="space-y-5">
        <!-- Genre Dropdown -->
        <div x-data="{ open: true }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-primary" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                    </svg>
                    Genre
                </span>
                <svg class="h-5 w-5 transform transition-transform" :class="{'rotate-180': open}"
                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100" class="mt-2 space-y-1 pl-2">
                @foreach(['Pop','Rock','Country','Hip Hop','Jazz','Electronic','Classical','R&B','Folk'] as $genre)
                <label class="flex items-center py-1 px-2 rounded-md hover:bg-gray-100">
                    <input type="checkbox" wire:model.live="genres" value="{{ $genre }}"
                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" />
                    <span class="ml-2 text-sm text-gray-700">{{ $genre }}</span>
                </label>
                @endforeach {{-- @formatter:on --}}
            </div>
        </div>

        <!-- Project Type Dropdown -->
        <div x-data="{ open: true }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-primary" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Project Type
                </span>
                <svg class="h-5 w-5 transform transition-transform" :class="{'rotate-180': open}"
                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100" class="mt-2 space-y-1 pl-2">
                @php
                $projectTypeLabels = [
                'single' => 'Single',
                'album' => 'Album',
                'ep' => 'EP',
                'mixtape' => 'Mixtape',
                'remix' => 'Remix',
                'cover' => 'Cover',
                'soundtrack' => 'Soundtrack',
                'other' => 'Other'
                ];
                @endphp

                @foreach(array_keys($projectTypeLabels) as $projectType)
                <label class="flex items-center py-1 px-2 rounded-md hover:bg-gray-100">
                    <input type="checkbox" wire:model.live="projectTypes" value="{{ $projectType }}"
                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" />
                    <span class="ml-2 text-sm text-gray-700">
                        {{ $projectTypeLabels[$projectType] }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Status Dropdown -->
        <div x-data="{ open: true }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-primary" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Status
                </span>
                <svg class="h-5 w-5 transform transition-transform" :class="{'rotate-180': open}"
                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100" class="mt-2 space-y-1 pl-2">
                @php
                $statusLabels = [
                'open' => 'Open for Pitches',
                'in_progress' => 'In Progress',
                'completed' => 'Completed'
                ];

                $statusColors = [
                'open' => 'text-green-600',
                'in_progress' => 'text-blue-600',
                'completed' => 'text-purple-600'
                ];
                @endphp

                @foreach(['open', 'in_progress', 'completed'] as $status)
                <label class="flex items-center py-1 px-2 rounded-md hover:bg-gray-100">
                    <input type="checkbox" wire:model.live="statuses" value="{{ $status }}"
                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" />
                    <span class="ml-2 text-sm {{ $statusColors[$status] ?? 'text-gray-700' }}">
                        {{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Clear Filters Button -->
        <div class="pt-2">
            <button type="button" wire:click="clearFilters"
                class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Clear All Filters
            </button>
        </div>
    </form>
</div>