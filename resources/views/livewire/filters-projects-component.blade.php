<div>
    <form wire:submit="render" class="space-y-5">
        <!-- Genre Dropdown -->
        <div x-data="{ open: {{ !empty($genres) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
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
                <label class="flex items-center py-1 px-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                    <input type="checkbox" wire:model.live="genres" value="{{ $genre }}"
                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded" />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $genre }}</span>
                </label>
                @endforeach {{-- @formatter:on --}}
            </div>
        </div>

        <!-- Workflow Type Dropdown -->
        <div x-data="{ open: {{ !empty($projectTypes) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-2 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                    </svg>
                    Workflow Type
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
                // Use the constants from the Project model
                $workflowTypeOptions = [
                    \App\Models\Project::WORKFLOW_TYPE_STANDARD => 'Standard',
                    \App\Models\Project::WORKFLOW_TYPE_CONTEST => 'Contest',
                    // Add others here if they become publicly filterable
                ];
                @endphp

                @foreach($workflowTypeOptions as $value => $label)
                <label class="flex items-center py-1 px-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                    <input type="checkbox" wire:model.live="projectTypes" value="{{ $value }}"
                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded" />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        {{ $label }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Status Dropdown -->
        <div x-data="{ open: {{ !empty($statuses) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
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
                'open' => 'text-green-600 dark:text-green-400',
                'in_progress' => 'text-blue-600 dark:text-blue-400',
                'completed' => 'text-purple-600 dark:text-purple-400'
                ];
                @endphp

                @foreach(['open', 'in_progress', 'completed'] as $status)
                <label class="flex items-center py-1 px-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                    <input type="checkbox" wire:model.live="statuses" value="{{ $status }}"
                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded" />
                    <span class="ml-2 text-sm {{ $statusColors[$status] ?? 'text-gray-700 dark:text-gray-300' }}">
                        {{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Budget Range -->
        <div x-ref="budgetFilter"
            x-data="{ 
                open: {{ !is_null($min_budget) || !is_null($max_budget) ? 'true' : 'false' }},
                minBudget: {{ $min_budget === '' || $min_budget === null ? 'null' : $min_budget }},
                maxBudget: {{ $max_budget === '' || $max_budget === null ? 'null' : $max_budget }},
                displayMin: {{ $min_budget && $min_budget !== '' ? $min_budget : 0 }},
                displayMax: {{ $max_budget && $max_budget !== '' ? $max_budget : 1000 }},
                maxSliderValue: 1000,
                
                formatCurrency(val) {
                    return '$' + val;
                },
                
                // Apply changes to Livewire component
                applyChanges() {
                    // Ensure proper handling of null vs empty string
                    $wire.min_budget = this.minBudget === 0 || this.minBudget === '' ? null : this.minBudget;
                    $wire.max_budget = this.maxBudget >= this.maxSliderValue || this.maxBudget === '' ? null : this.maxBudget;
                    $wire.dispatchFiltersUpdated();
                },
                
                // Toggle preset or set new values
                setPreset(min, max) {
                    if (this.minBudget === min && this.maxBudget === max) {
                        // Toggle off if same values
                        this.minBudget = null;
                        this.maxBudget = null;
                        this.displayMin = 0;
                        this.displayMax = this.maxSliderValue;
                    } else {
                        // Set new values
                        this.minBudget = min;
                        this.maxBudget = max;
                        this.displayMin = min === null ? 0 : min;
                        this.displayMax = max === null ? this.maxSliderValue : max;
                    }
                    this.applyChanges();
                }
            }"
            x-init="
                $watch('minBudget', () => applyChanges());
                $watch('maxBudget', () => applyChanges());
                
                // Listen for the filters-reset event
                $wire.$on('filters-reset', () => {
                    minBudget = null;
                    maxBudget = null;
                    displayMin = 0;
                    displayMax = maxSliderValue;
                    open = false;
                });
            "
        >
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
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
            <div x-show="open" x-transition class="mt-2 space-y-4 px-2 pt-2 pb-4">
                <!-- Budget Range Display -->
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="minBudget === null ? 'Any' : formatCurrency(minBudget)"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">to</span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="maxBudget === null || maxBudget >= maxSliderValue ? 'Any' : formatCurrency(maxBudget)"></span>
                </div>
                
                <!-- Range Slider -->
                <div class="relative pt-1">
                    <div class="h-1 bg-gray-200 dark:bg-gray-600 rounded-full">
                        <div class="absolute h-1 rounded-full bg-primary" 
                            :style="`left: ${(displayMin / maxSliderValue) * 100}%; right: ${100 - (displayMax / maxSliderValue) * 100}%`"></div>
                    </div>
                    
                    <input type="range" 
                        x-model="displayMin"
                        min="0" 
                        :max="maxSliderValue"
                        @change="minBudget = displayMin === 0 ? null : parseInt(displayMin); applyChanges()"
                        @keydown.arrow-right.prevent="displayMin = Math.min(parseInt(displayMin) + 10, displayMax)"
                        @keydown.arrow-left.prevent="displayMin = Math.max(parseInt(displayMin) - 10, 0)"
                        @touchend="minBudget = displayMin === 0 ? null : parseInt(displayMin); applyChanges()"
                        aria-label="Minimum budget"
                        class="absolute w-full h-1 touch-action-none cursor-pointer appearance-none bg-transparent pointer-events-none outline-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:h-5 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-primary [&::-webkit-slider-thumb]:shadow-md" />
                    
                    <input type="range" 
                        x-model="displayMax"
                        min="0" 
                        :max="maxSliderValue"
                        @change="maxBudget = displayMax >= maxSliderValue ? null : parseInt(displayMax); applyChanges()"
                        @keydown.arrow-right.prevent="displayMax = Math.min(parseInt(displayMax) + 10, maxSliderValue)"
                        @keydown.arrow-left.prevent="displayMax = Math.max(parseInt(displayMax) - 10, displayMin)"
                        @touchend="maxBudget = displayMax >= maxSliderValue ? null : parseInt(displayMax); applyChanges()"
                        aria-label="Maximum budget"
                        class="absolute w-full h-1 touch-action-none cursor-pointer appearance-none bg-transparent pointer-events-none outline-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:h-5 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-primary [&::-webkit-slider-thumb]:shadow-md" />
                </div>
                
                <!-- Preset Buttons -->
                <div class="grid grid-cols-2 gap-2 mt-4 sm:grid-cols-3">
                    <button type="button" @click="setPreset(null, 10)" 
                        class="group px-3 py-2 text-xs font-medium bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm hover:shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500/25"
                        :class="{'bg-gradient-to-r from-blue-500 to-purple-600 text-white border-transparent shadow-lg': minBudget === null && maxBudget === 10, 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white/90 dark:bg-gray-800/90 border-gray-200/50 dark:border-gray-600/50': !(minBudget === null && maxBudget === 10)}">
                        Under $10
                    </button>
                    <button type="button" @click="setPreset(10, 50)" 
                        class="group px-3 py-2 text-xs font-medium bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm hover:shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500/25"
                        :class="{'bg-gradient-to-r from-blue-500 to-purple-600 text-white border-transparent shadow-lg': minBudget === 10 && maxBudget === 50, 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white/90 dark:bg-gray-800/90 border-gray-200/50 dark:border-gray-600/50': !(minBudget === 10 && maxBudget === 50)}">
                        $10 - $50
                    </button>
                    <button type="button" @click="setPreset(50, 100)" 
                        class="group px-3 py-2 text-xs font-medium bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm hover:shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500/25"
                        :class="{'bg-gradient-to-r from-blue-500 to-purple-600 text-white border-transparent shadow-lg': minBudget === 50 && maxBudget === 100, 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white/90 dark:bg-gray-800/90 border-gray-200/50 dark:border-gray-600/50': !(minBudget === 50 && maxBudget === 100)}">
                        $50 - $100
                    </button>
                    <button type="button" @click="setPreset(100, 200)"  
                        class="group px-3 py-2 text-xs font-medium bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm hover:shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500/25"
                        :class="{'bg-gradient-to-r from-blue-500 to-purple-600 text-white border-transparent shadow-lg': minBudget === 100 && maxBudget === 200, 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white/90 dark:bg-gray-800/90 border-gray-200/50 dark:border-gray-600/50': !(minBudget === 100 && maxBudget === 200)}">
                        $100 - $200
                    </button>
                    <button type="button" @click="setPreset(200, 500)" 
                        class="group px-3 py-2 text-xs font-medium bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm hover:shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500/25"
                        :class="{'bg-gradient-to-r from-blue-500 to-purple-600 text-white border-transparent shadow-lg': minBudget === 200 && maxBudget === 500, 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white/90 dark:bg-gray-800/90 border-gray-200/50 dark:border-gray-600/50': !(minBudget === 200 && maxBudget === 500)}">
                        $200 - $500
                    </button>
                    <button type="button" @click="setPreset(500, null)" 
                        class="group px-3 py-2 text-xs font-medium bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm hover:shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500/25"
                        :class="{'bg-gradient-to-r from-blue-500 to-purple-600 text-white border-transparent shadow-lg': minBudget === 500 && maxBudget === null, 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white/90 dark:bg-gray-800/90 border-gray-200/50 dark:border-gray-600/50': !(minBudget === 500 && maxBudget === null)}">
                        $500+
                    </button>
                </div>
                
                <!-- Manual Input -->
                <div class="flex items-center space-x-2 mt-4">
                    <div class="w-1/2">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Min ($)</label>
                        <input type="number" 
                               x-model="minBudget"
                               @change="applyChanges()"
                               min="0"
                               placeholder="Any" 
                               class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md focus:ring-primary focus:border-primary" />
                    </div>
                    <div class="w-1/2">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Max ($)</label>
                        <input type="number" 
                               x-model="maxBudget"
                               @change="applyChanges()"
                               min="0"
                               placeholder="Any" 
                               class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md focus:ring-primary focus:border-primary" />
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Deadline Range -->
        <div x-ref="deadlineFilter"
            x-data="{ 
                open: {{ !is_null($deadline_start) || !is_null($deadline_end) ? 'true' : 'false' }},
                deadlineStart: '{{ $deadline_start === '' || $deadline_start === null ? '' : $deadline_start }}',
                deadlineEnd: '{{ $deadline_end === '' || $deadline_end === null ? '' : $deadline_end }}',
                selectedPreset: '',
                
                applyChanges() {
                    // Ensure proper handling of null vs empty string
                    $wire.deadline_start = this.deadlineStart === '' ? null : this.deadlineStart;
                    $wire.deadline_end = this.deadlineEnd === '' ? null : this.deadlineEnd;
                    $wire.dispatchFiltersUpdated();
                },
                
                formatDate(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                },
                
                getRelativeDateDescription() {
                    if (!this.deadlineStart && !this.deadlineEnd) return 'Any deadline';
                    
                    if (this.deadlineStart && !this.deadlineEnd) {
                        return `After ${this.formatDate(this.deadlineStart)}`;
                    } else if (!this.deadlineStart && this.deadlineEnd) {
                        return `Before ${this.formatDate(this.deadlineEnd)}`;
                    } else {
                        return `${this.formatDate(this.deadlineStart)} to ${this.formatDate(this.deadlineEnd)}`;
                    }
                },
                
                setPreset(preset) {
                    // Toggle off if already selected
                    if (this.selectedPreset === preset) {
                        this.deadlineStart = '';
                        this.deadlineEnd = '';
                        this.selectedPreset = '';
                        this.applyChanges();
                        return;
                    }
                    
                    // Set new preset
                    this.selectedPreset = preset;
                    const today = new Date();
                    
                    switch(preset) {
                        case 'next7days':
                            this.deadlineStart = today.toISOString().split('T')[0];
                            const next7days = new Date(today);
                            next7days.setDate(today.getDate() + 7);
                            this.deadlineEnd = next7days.toISOString().split('T')[0];
                            break;
                        case 'next30days':
                            this.deadlineStart = today.toISOString().split('T')[0];
                            const next30days = new Date(today);
                            next30days.setDate(today.getDate() + 30);
                            this.deadlineEnd = next30days.toISOString().split('T')[0];
                            break;
                        case 'next3months':
                            this.deadlineStart = today.toISOString().split('T')[0];
                            const next3months = new Date(today);
                            next3months.setMonth(today.getMonth() + 3);
                            this.deadlineEnd = next3months.toISOString().split('T')[0];
                            break;
                        case 'custom':
                            // Keep current dates, just switch to custom mode
                            break;
                        case 'upcoming':
                            this.deadlineStart = today.toISOString().split('T')[0];
                            this.deadlineEnd = '';
                            break;
                        case 'clear':
                            this.deadlineStart = '';
                            this.deadlineEnd = '';
                            this.selectedPreset = '';
                            break;
                    }
                    
                    this.applyChanges();
                }
            }"
            x-init="
                $watch('deadlineStart', () => {
                    applyChanges();
                    if (deadlineStart || deadlineEnd) {
                        selectedPreset = 'custom';
                    }
                });
                
                $watch('deadlineEnd', () => {
                    applyChanges();
                    if (deadlineStart || deadlineEnd) {
                        selectedPreset = 'custom';
                    }
                });
                
                // Listen for the filters-reset event
                $wire.$on('filters-reset', () => {
                    deadlineStart = '';
                    deadlineEnd = '';
                    selectedPreset = '';
                    open = false;
                });
            ">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
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
            <div x-show="open" x-transition class="mt-2 space-y-4 px-2 pt-2 pb-4">
                <!-- Current Selection Display -->
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="getRelativeDateDescription()"></span>
                </div>
                
                <!-- Preset Buttons -->
                <div class="flex space-x-2">
                    <button type="button" @click="setPreset('next7days')" 
                        class="group flex-1 px-3 py-2 text-xs font-medium bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm hover:shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500/25"
                        :class="{'bg-gradient-to-r from-blue-500 to-purple-600 text-white border-transparent shadow-lg': selectedPreset === 'next7days', 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white/90 dark:bg-gray-800/90 border-gray-200/50 dark:border-gray-600/50': selectedPreset !== 'next7days'}">
                        Next 7 days
                    </button>
                    <button type="button" @click="setPreset('next30days')" 
                        class="group flex-1 px-3 py-2 text-xs font-medium bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm hover:shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500/25"
                        :class="{'bg-gradient-to-r from-blue-500 to-purple-600 text-white border-transparent shadow-lg': selectedPreset === 'next30days', 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white/90 dark:bg-gray-800/90 border-gray-200/50 dark:border-gray-600/50': selectedPreset !== 'next30days'}">
                        Next 30 days
                    </button>
                    <button type="button" @click="setPreset('next3months')" 
                        class="group flex-1 px-3 py-2 text-xs font-medium bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm hover:shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500/25"
                        :class="{'bg-gradient-to-r from-blue-500 to-purple-600 text-white border-transparent shadow-lg': selectedPreset === 'next3months', 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white/90 dark:bg-gray-800/90 border-gray-200/50 dark:border-gray-600/50': selectedPreset !== 'next3months'}">
                        Next 3 months
                    </button>
                </div>
                
                <!-- Upcoming Button on its own line -->
                <div class="mt-2">
                    <button type="button" @click="setPreset('upcoming')" 
                        class="group w-full px-3 py-2 text-xs font-medium bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm hover:shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500/25"
                        :class="{'bg-gradient-to-r from-blue-500 to-purple-600 text-white border-transparent shadow-lg': selectedPreset === 'upcoming', 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white/90 dark:bg-gray-800/90 border-gray-200/50 dark:border-gray-600/50': selectedPreset !== 'upcoming'}">
                        Upcoming (all)
                    </button>
                </div>
                
                <!-- Custom Date Range Selection -->
                <div x-show="selectedPreset === 'custom' || selectedPreset === '' || deadlineStart || deadlineEnd">
                    <p class="text-xs text-gray-500 mb-2 font-medium">
                        <button type="button" @click="setPreset('custom')" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition-all duration-200">
                            Custom Date Range
                        </button>
                    </p>
                    
                    <div class="space-y-2">
                        <div class="sm:flex-1">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Start Date:</label>
                            <input type="date" 
                                   x-model="deadlineStart"
                                   @change="applyChanges()"
                                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-primary focus:border-primary" />
                        </div>
                        <div class="sm:flex-1">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">End Date:</label>
                            <input type="date" 
                                   x-model="deadlineEnd"
                                   @change="applyChanges()"
                                   :min="deadlineStart || undefined"
                                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-primary focus:border-primary" />
                        </div>
                    </div>
                </div>
                
                <!-- Clear Button -->
                <div class="pt-2" x-show="deadlineStart || deadlineEnd">
                    <button type="button" @click="setPreset('clear')"
                        class="group w-full inline-flex justify-center items-center px-3 py-2 bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm hover:shadow-lg text-xs font-medium text-gray-700 hover:bg-gray-50 transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500/25">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-2 group-hover:scale-110 transition-transform duration-200" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear Dates
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Collaboration Type Dropdown -->
        <div x-data="{ open: {{ !empty($selected_collaboration_types) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full px-1 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
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
                <label class="flex items-center py-1 px-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                    <input type="checkbox" wire:model.live="selected_collaboration_types" value="{{ $collabType }}"
                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded" />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $collabType }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Clear Filters Button -->
        <div class="pt-2">
            <button type="button" wire:click="clearAllFilters"
                class="group w-full inline-flex justify-center items-center px-4 py-3 bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-[transform,colors,shadow] duration-200 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-red-500/25">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 group-hover:scale-110 transition-transform duration-200" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Clear All Filters
            </button>
        </div>
    </form>
</div>