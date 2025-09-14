@props([
    'projectTypes',
    'selected' => null,
    'wireModel' => 'form.projectType',
    'required' => true
])

@php
    // Find the selected project type for display
    $selectedProjectType = $projectTypes->firstWhere('slug', $selected) ?? $projectTypes->first();
@endphp

<div class="space-y-4" x-data="{ selectedType: @entangle($wireModel) }">
    <label class="block text-sm font-medium text-gray-700 mb-3">
        <i class="fas fa-folder mr-2"></i>
        Project Type 
        @if($required)
            <span class="text-red-500">*</span>
        @else
            <span class="text-gray-500">(Optional)</span>
        @endif
    </label>
    
    <div class="mt-6">
        <flux:dropdown>
        <!-- Trigger Button - Use Flux Button with Custom Content -->
        @php
            // Get the selected project type for display
            $selectedProjectType = $selected ? $projectTypes->firstWhere('slug', $selected) : null;
        @endphp
        
        @php
            $projectTypesData = $projectTypes->map(function($type) {
                return [
                    'slug' => $type->slug,
                    'name' => $type->name,
                    'description' => $type->description,
                    'color' => $type->color,
                    'icon_class' => $type->getIconClass()
                ];
            });
        @endphp

        <flux:button 
            variant="ghost" 
            class="w-full !p-0 !border-0 !bg-transparent hover:!bg-transparent"
            x-data="{ 
                projectTypes: {{ $projectTypesData->toJson() }},
                get selectedProjectType() {
                    return this.projectTypes.find(type => type.slug === this.selectedType) || null;
                }
            }"
        >
            <!-- Selected State -->
            <template x-if="selectedType">
                <div class="w-full flex items-center p-4 rounded-xl border-2 transition-all duration-200"
                     :class="{
                         'bg-blue-50 border-blue-200 hover:bg-blue-100': selectedProjectType?.color === 'blue',
                         'bg-purple-50 border-purple-200 hover:bg-purple-100': selectedProjectType?.color === 'purple',
                         'bg-pink-50 border-pink-200 hover:bg-pink-100': selectedProjectType?.color === 'pink',
                         'bg-green-50 border-green-200 hover:bg-green-100': selectedProjectType?.color === 'green',
                         'bg-orange-50 border-orange-200 hover:bg-orange-100': selectedProjectType?.color === 'orange',
                         'bg-red-50 border-red-200 hover:bg-red-100': selectedProjectType?.color === 'red',
                         'bg-yellow-50 border-yellow-200 hover:bg-yellow-100': selectedProjectType?.color === 'yellow',
                         'bg-indigo-50 border-indigo-200 hover:bg-indigo-100': selectedProjectType?.color === 'indigo',
                         'bg-gray-50 border-gray-200 hover:bg-gray-100': selectedProjectType?.color === 'gray',
                         'bg-teal-50 border-teal-200 hover:bg-teal-100': selectedProjectType?.color === 'teal'
                     }"
                >
                    <!-- Icon -->
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg mr-3 transition-all duration-200 text-white"
                         :class="{
                             'bg-blue-500': selectedProjectType?.color === 'blue',
                             'bg-purple-500': selectedProjectType?.color === 'purple',
                             'bg-pink-500': selectedProjectType?.color === 'pink',
                             'bg-green-500': selectedProjectType?.color === 'green',
                             'bg-orange-500': selectedProjectType?.color === 'orange',
                             'bg-red-500': selectedProjectType?.color === 'red',
                             'bg-yellow-500': selectedProjectType?.color === 'yellow',
                             'bg-indigo-500': selectedProjectType?.color === 'indigo',
                             'bg-gray-500': selectedProjectType?.color === 'gray',
                             'bg-teal-500': selectedProjectType?.color === 'teal'
                         }"
                    >
                        <i :class="selectedProjectType?.icon_class" class="text-sm"></i>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 text-left">
                        <div class="font-semibold text-gray-900 mb-1" x-text="selectedProjectType?.name"></div>
                        <div class="text-xs text-gray-600 leading-tight" x-text="selectedProjectType?.description"></div>
                    </div>
                    
                    <!-- Chevron -->
                    <div class="ml-2">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                </div>
            </template>
            
            <!-- Default state when nothing selected -->
            <template x-if="!selectedType">
                <div class="w-full flex items-center p-4 rounded-xl border-2 border-gray-200 bg-white hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 hover:shadow-lg">
                    <!-- Default Icon -->
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg mr-3 bg-gray-100 text-gray-400">
                        <i class="fas fa-folder text-sm"></i>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 text-left">
                        <div class="font-semibold text-gray-500 mb-1">Select project type</div>
                        <div class="text-xs text-gray-400 leading-tight">Choose the type that best fits your project</div>
                    </div>
                    
                    <!-- Chevron -->
                    <div class="ml-2">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                </div>
            </template>
        </flux:button>

        <!-- Dropdown Options -->
        <flux:popover class="w-full max-w-2xl">
            <div class="">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($projectTypes as $projectType)
                        @php
                            // Create explicit color classes for each project type
                            $colorMap = [
                                'blue' => ['hover_border' => 'hover:border-blue-300', 'hover_bg' => 'hover:bg-blue-50/50', 'selected_border' => 'border-blue-500', 'selected_bg' => 'bg-blue-50', 'selected_ring' => 'ring-blue-500/20', 'icon_bg' => 'bg-blue-500'],
                                'purple' => ['hover_border' => 'hover:border-purple-300', 'hover_bg' => 'hover:bg-purple-50/50', 'selected_border' => 'border-purple-500', 'selected_bg' => 'bg-purple-50', 'selected_ring' => 'ring-purple-500/20', 'icon_bg' => 'bg-purple-500'],
                                'pink' => ['hover_border' => 'hover:border-pink-300', 'hover_bg' => 'hover:bg-pink-50/50', 'selected_border' => 'border-pink-500', 'selected_bg' => 'bg-pink-50', 'selected_ring' => 'ring-pink-500/20', 'icon_bg' => 'bg-pink-500'],
                                'green' => ['hover_border' => 'hover:border-green-300', 'hover_bg' => 'hover:bg-green-50/50', 'selected_border' => 'border-green-500', 'selected_bg' => 'bg-green-50', 'selected_ring' => 'ring-green-500/20', 'icon_bg' => 'bg-green-500'],
                                'orange' => ['hover_border' => 'hover:border-orange-300', 'hover_bg' => 'hover:bg-orange-50/50', 'selected_border' => 'border-orange-500', 'selected_bg' => 'bg-orange-50', 'selected_ring' => 'ring-orange-500/20', 'icon_bg' => 'bg-orange-500'],
                                'red' => ['hover_border' => 'hover:border-red-300', 'hover_bg' => 'hover:bg-red-50/50', 'selected_border' => 'border-red-500', 'selected_bg' => 'bg-red-50', 'selected_ring' => 'ring-red-500/20', 'icon_bg' => 'bg-red-500'],
                                'yellow' => ['hover_border' => 'hover:border-yellow-300', 'hover_bg' => 'hover:bg-yellow-50/50', 'selected_border' => 'border-yellow-500', 'selected_bg' => 'bg-yellow-50', 'selected_ring' => 'ring-yellow-500/20', 'icon_bg' => 'bg-yellow-500'],
                                'indigo' => ['hover_border' => 'hover:border-indigo-300', 'hover_bg' => 'hover:bg-indigo-50/50', 'selected_border' => 'border-indigo-500', 'selected_bg' => 'bg-indigo-50', 'selected_ring' => 'ring-indigo-500/20', 'icon_bg' => 'bg-indigo-500'],
                                'gray' => ['hover_border' => 'hover:border-gray-300', 'hover_bg' => 'hover:bg-gray-50/50', 'selected_border' => 'border-gray-500', 'selected_bg' => 'bg-gray-50', 'selected_ring' => 'ring-gray-500/20', 'icon_bg' => 'bg-gray-500'],
                                'teal' => ['hover_border' => 'hover:border-teal-300', 'hover_bg' => 'hover:bg-teal-50/50', 'selected_border' => 'border-teal-500', 'selected_bg' => 'bg-teal-50', 'selected_ring' => 'ring-teal-500/20', 'icon_bg' => 'bg-teal-500'],
                            ];
                            $colorClasses = $colorMap[$projectType->color] ?? $colorMap['blue'];
                        @endphp
                        
                        <button 
                            type="button"
                            class="flex items-center p-4 rounded-xl border-2 transition-all duration-200 border-gray-200 bg-white cursor-pointer
                                {{ $colorClasses['hover_border'] }} {{ $colorClasses['hover_bg'] }} hover:shadow-lg"
                            :class="selectedType === '{{ $projectType->slug }}' ? 
                                '{{ $colorClasses['selected_border'] }} {{ $colorClasses['selected_bg'] }} ring-2 {{ $colorClasses['selected_ring'] }}' : 
                                'border-gray-200 bg-white'"
                            wire:click="$set('{{ $wireModel }}', '{{ $projectType->slug }}')"
                            @click="$dispatch('close-popover')"
                        >
                            
                            <!-- Icon -->
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg mr-3 transition-all duration-200 text-gray-600"
                                :class="selectedType === '{{ $projectType->slug }}' ? 
                                    '{{ $colorClasses['icon_bg'] }} text-white' : 
                                    'bg-gray-100'">
                                <i class="{{ $projectType->getIconClass() }} text-sm"></i>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 text-left">
                                <div class="font-semibold text-gray-900 mb-1">{{ $projectType->name }}</div>
                                <div class="text-xs text-gray-600 leading-tight">{{ $projectType->description }}</div>
                            </div>
                            
                            <!-- Check Icon -->
                            <div class="ml-2 transition-opacity duration-200"
                                :class="selectedType === '{{ $projectType->slug }}' ? 'opacity-100' : 'opacity-0'">
                                <div class="flex items-center justify-center w-5 h-5 rounded-full {{ $colorClasses['icon_bg'] }} text-white">
                                    <i class="fas fa-check text-xs"></i>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </flux:popover>
        </flux:dropdown>
    </div>
    
    @error($wireModel)
    <p class="mt-2 text-sm text-red-600 flex items-center">
        <i class="fas fa-exclamation-circle mr-1"></i>
        {{ $message }}
    </p>
    @enderror
</div> 