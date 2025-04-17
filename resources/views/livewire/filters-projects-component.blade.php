<div>
    <form wire:submit="render" class="space-y-5">
        <!-- Genre Dropdown -->
        <div x-data="{ open: {{ !empty($genres) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
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
        <div x-data="{ open: {{ !empty($projectTypes) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
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
        <div x-data="{ open: {{ !empty($statuses) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
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

        <!-- Budget Range -->
        <div x-data="{ open: {{ !is_null($min_budget) || !is_null($max_budget) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <span class="flex items-center">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-2 text-primary">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Budget
                </span>
                <svg class="h-5 w-5 transform transition-transform" :class="{'rotate-180': open}"
                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="open" x-transition class="mt-2 space-y-2 px-4">
                <div class="flex items-center space-x-2">
                     <span class="text-sm text-gray-500 w-8">Min:</span>
                     <input type="number" wire:model.live.debounce.500ms="$parent.min_budget" placeholder="Any" min="0"
                         class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-primary focus:border-primary" />
                </div>
                 <div class="flex items-center space-x-2">
                     <span class="text-sm text-gray-500 w-8">Max:</span>
                     <input type="number" wire:model.live.debounce.500ms="$parent.max_budget" placeholder="Any" min="0"
                         class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-primary focus:border-primary" />
                </div>
            </div>
        </div>
        
        <!-- Deadline Range -->
        <div x-data="{ open: {{ !is_null($deadline_start) || !is_null($deadline_end) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-2 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                    </svg>
                    Deadline
                </span>
                 <svg class="h-5 w-5 transform transition-transform" :class="{'rotate-180': open}"
                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="open" x-transition class="mt-2 space-y-2 px-4">
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500 w-8">Start:</span>
                    <input type="date" wire:model.live="$parent.deadline_start"
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-primary focus:border-primary" />
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500 w-8">End:</span>
                    <input type="date" wire:model.live="$parent.deadline_end"
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-primary focus:border-primary" />
                </div>
            </div>
        </div>

        <!-- Collaboration Type Dropdown -->
        <div x-data="{ open: {{ !empty($selected_collaboration_types) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 text-primary">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.94-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.06-3.17M12 12.75a5.995 5.995 0 015.06 3.17M12 12.75A5.995 5.995 0 016.94 15.92m5.06-3.17c.045-.147.083-.296.098-.445l.015-.15H12a5.97 5.97 0 01-1.074.084m1.074-.084L12 12.75m0 0l-.074.042a5.971 5.971 0 00-1.141-.096m1.141.096c-.045.147-.083.295-.098.445L12 12.75m0 0l.074-.042a5.971 5.971 0 011.141.096m-1.141-.096a1.62 1.62 0 01-.55.062m.55-.062a1.62 1.62 0 00-.55.062m0 0a5.995 5.995 0 01-5.06-3.17M12 3c2.755 0 5.197.845 7.126 2.28M12 3c-2.755 0-5.197.845-7.126 2.28m14.252 0l.016.006a4.977 4.977 0 01-.767 5.665 4.98 4.98 0 01-7.1 0 4.977 4.977 0 01-.767-5.665l.016-.006M12 3c-3.866 0-7 3.134-7 7 0 1.708.613 3.28 1.616 4.53M12 3c3.866 0 7 3.134 7 7 0 1.708-.613 3.28-1.616 4.53" />
                    </svg>
                    Collaboration Type
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
                @foreach($availableCollaborationTypes as $collabType)
                <label class="flex items-center py-1 px-2 rounded-md hover:bg-gray-100">
                    <input type="checkbox" wire:model.live="selected_collaboration_types" value="{{ $collabType }}"
                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" />
                    <span class="ml-2 text-sm text-gray-700">{{ $collabType }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Clear Filters Button -->
        <div class="pt-2">
            <button type="button" wire:click="clearAllFilters"
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