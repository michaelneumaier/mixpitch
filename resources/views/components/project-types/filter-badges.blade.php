@props([
    'projectTypes',
    'selectedTypes' => [],
    'wireModel' => 'selectedProjectTypes'
])

<div class="space-y-3">
    <label class="block text-sm font-medium text-gray-700">
        <i class="fas fa-filter mr-2"></i>
        Filter by Project Type
    </label>
    
    <div class="flex flex-wrap gap-2">
        <!-- All Types -->
        <button 
            type="button" 
            wire:click="$set('{{ $wireModel }}', [])"
            class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                {{ empty($selectedTypes) 
                    ? 'bg-gray-900 text-white shadow-lg' 
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200' 
                }}">
            <i class="fas fa-globe mr-2"></i>
            All Types
        </button>
        
        @foreach($projectTypes as $projectType)
            @php
                $isSelected = in_array($projectType->slug, $selectedTypes);
                $colors = $projectType->getColorClasses();
            @endphp
            
            <button 
                type="button" 
                wire:click="toggleProjectType('{{ $projectType->slug }}')"
                class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:scale-105
                    {{ $isSelected 
                        ? 'bg-' . $projectType->color . '-500 text-white shadow-lg ring-2 ring-' . $projectType->color . '-500/30' 
                        : 'bg-white text-gray-700 border border-gray-300 hover:border-' . $projectType->color . '-300 hover:bg-' . $projectType->color . '-50' 
                    }}">
                <i class="{{ $projectType->getIconClass() }} mr-2"></i>
                {{ $projectType->name }}
                @if($isSelected)
                    <i class="fas fa-check ml-1 text-xs"></i>
                @endif
            </button>
        @endforeach
    </div>
    
    @if(count($selectedTypes) > 0)
        <div class="text-sm text-gray-600">
            <span class="font-medium">{{ count($selectedTypes) }}</span> type{{ count($selectedTypes) > 1 ? 's' : '' }} selected
        </div>
    @endif
</div> 