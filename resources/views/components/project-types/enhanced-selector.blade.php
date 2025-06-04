@props([
    'projectTypes',
    'selected' => null,
    'wireModel' => 'form.projectType',
    'required' => true
])

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
    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($projectTypes as $projectType)
            @php
                // Create explicit peer-checked classes for each color
                $peerCheckedClasses = [
                    'blue' => 'peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:ring-blue-500/20',
                    'purple' => 'peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:ring-purple-500/20',
                    'pink' => 'peer-checked:border-pink-500 peer-checked:bg-pink-50 peer-checked:ring-pink-500/20',
                    'green' => 'peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:ring-green-500/20',
                    'orange' => 'peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:ring-orange-500/20',
                    'red' => 'peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:ring-red-500/20',
                    'yellow' => 'peer-checked:border-yellow-500 peer-checked:bg-yellow-50 peer-checked:ring-yellow-500/20',
                    'indigo' => 'peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:ring-indigo-500/20',
                    'gray' => 'peer-checked:border-gray-500 peer-checked:bg-gray-50 peer-checked:ring-gray-500/20',
                    'teal' => 'peer-checked:border-teal-500 peer-checked:bg-teal-50 peer-checked:ring-teal-500/20',
                ];
                
                $colorMap = [
                    'blue' => ['hover_border' => 'hover:border-blue-300', 'hover_bg' => 'hover:bg-blue-50/50', 'group_hover_icon' => 'group-hover:bg-blue-500', 'check_bg' => 'bg-blue-500', 'selected_bg' => 'bg-blue-500'],
                    'purple' => ['hover_border' => 'hover:border-purple-300', 'hover_bg' => 'hover:bg-purple-50/50', 'group_hover_icon' => 'group-hover:bg-purple-500', 'check_bg' => 'bg-purple-500', 'selected_bg' => 'bg-purple-500'],
                    'pink' => ['hover_border' => 'hover:border-pink-300', 'hover_bg' => 'hover:bg-pink-50/50', 'group_hover_icon' => 'group-hover:bg-pink-500', 'check_bg' => 'bg-pink-500', 'selected_bg' => 'bg-pink-500'],
                    'green' => ['hover_border' => 'hover:border-green-300', 'hover_bg' => 'hover:bg-green-50/50', 'group_hover_icon' => 'group-hover:bg-green-500', 'check_bg' => 'bg-green-500', 'selected_bg' => 'bg-green-500'],
                    'orange' => ['hover_border' => 'hover:border-orange-300', 'hover_bg' => 'hover:bg-orange-50/50', 'group_hover_icon' => 'group-hover:bg-orange-500', 'check_bg' => 'bg-orange-500', 'selected_bg' => 'bg-orange-500'],
                    'red' => ['hover_border' => 'hover:border-red-300', 'hover_bg' => 'hover:bg-red-50/50', 'group_hover_icon' => 'group-hover:bg-red-500', 'check_bg' => 'bg-red-500', 'selected_bg' => 'bg-red-500'],
                    'yellow' => ['hover_border' => 'hover:border-yellow-300', 'hover_bg' => 'hover:bg-yellow-50/50', 'group_hover_icon' => 'group-hover:bg-yellow-500', 'check_bg' => 'bg-yellow-500', 'selected_bg' => 'bg-yellow-500'],
                    'indigo' => ['hover_border' => 'hover:border-indigo-300', 'hover_bg' => 'hover:bg-indigo-50/50', 'group_hover_icon' => 'group-hover:bg-indigo-500', 'check_bg' => 'bg-indigo-500', 'selected_bg' => 'bg-indigo-500'],
                    'gray' => ['hover_border' => 'hover:border-gray-300', 'hover_bg' => 'hover:bg-gray-50/50', 'group_hover_icon' => 'group-hover:bg-gray-500', 'check_bg' => 'bg-gray-500', 'selected_bg' => 'bg-gray-500'],
                    'teal' => ['hover_border' => 'hover:border-teal-300', 'hover_bg' => 'hover:bg-teal-50/50', 'group_hover_icon' => 'group-hover:bg-teal-500', 'check_bg' => 'bg-teal-500', 'selected_bg' => 'bg-teal-500'],
                ];
                
                $colorClasses = $colorMap[$projectType->color] ?? $colorMap['blue'];
                $checkedClasses = $peerCheckedClasses[$projectType->color] ?? $peerCheckedClasses['blue'];
            @endphp
            
            <label class="relative cursor-pointer group">
                <input 
                    type="radio" 
                    wire:model.live="{{ $wireModel }}" 
                    value="{{ $projectType->slug }}" 
                    class="sr-only peer"
                >
                
                <div class="project-card-{{ $projectType->color }} flex items-center p-4 rounded-xl border-2 transition-all duration-200 border-gray-200 bg-white
                    {{ $colorClasses['hover_border'] }} {{ $colorClasses['hover_bg'] }}
                    {{ $checkedClasses }} peer-checked:ring-2
                    group-hover:shadow-lg">
                    
                    <!-- Icon -->
                    <div class="icon-bg flex items-center justify-center w-10 h-10 rounded-lg mr-3 transition-all duration-200 text-gray-600
                        {{ $colorClasses['group_hover_icon'] }} group-hover:text-white
                        peer-checked:text-white peer-checked:shadow-lg"
                        :class="selectedType === '{{ $projectType->slug }}' ? '{{ $colorClasses['selected_bg'] }} text-white' : 'bg-gray-100'">
                        <i class="{{ $projectType->getIconClass() }} text-sm"></i>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1">
                        <div class="font-semibold text-gray-900 mb-1">{{ $projectType->name }}</div>
                        <div class="text-xs text-gray-600 leading-tight">{{ $projectType->description }}</div>
                    </div>
                    
                    <!-- Check Icon -->
                    <div class="ml-2 transition-opacity duration-200"
                         :class="selectedType === '{{ $projectType->slug }}' ? 'opacity-100' : 'opacity-0'">
                        <div class="flex items-center justify-center w-5 h-5 rounded-full {{ $colorClasses['check_bg'] }} text-white">
                            <i class="fas fa-check text-xs"></i>
                        </div>
                    </div>
                </div>
                
                <style>
                    .peer:checked ~ .project-card-{{ $projectType->color }} .icon-bg {
                        background-color: theme('colors.{{ $projectType->color }}.500') !important;
                    }
                </style>
            </label>
        @endforeach
    </div>
    
    @error($wireModel)
    <p class="mt-2 text-sm text-red-600 flex items-center">
        <i class="fas fa-exclamation-circle mr-1"></i>
        {{ $message }}
    </p>
    @enderror
</div> 