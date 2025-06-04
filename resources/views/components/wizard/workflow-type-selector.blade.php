@props(['workflowTypes' => [], 'selectedType' => null, 'wireModel' => 'workflowType'])

<div class="space-y-6">
    <!-- Enhanced Info Box -->
    <div class="relative bg-white/90 backdrop-blur-sm border border-white/30 rounded-xl p-6 shadow-lg">
        <!-- Background Effects -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50/50 to-indigo-50/50 rounded-xl"></div>
        <div class="absolute top-2 right-2 w-12 h-12 bg-blue-400/10 rounded-full blur-lg"></div>
        
        <div class="relative">
            <h4 class="font-bold text-lg bg-gradient-to-r from-blue-900 to-indigo-900 bg-clip-text text-transparent mb-3 flex items-center">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-2 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                    <i class="fas fa-lightbulb text-white"></i>
                </div>
                Choose Your Project Workflow
            </h4>
            <p class="text-gray-700 font-medium">Select the workflow type that best matches how you want to manage this project. This will determine the available features and collaboration options.</p>
        </div>
    </div>

    <!-- Enhanced Workflow Type Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($workflowTypes as $type)
        <div wire:click="$set('{{ $wireModel }}', '{{ $type['value'] }}')"
             class="group relative bg-white/90 backdrop-blur-sm border-2 rounded-xl p-6 cursor-pointer shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 overflow-hidden
             {{ $selectedType === $type['value'] ? 'border-' . $type['color'] . '-500 bg-gradient-to-br from-' . $type['color'] . '-50/80 to-white/90 shadow-xl' : 'border-white/30 hover:border-' . $type['color'] . '-300' }}">
            
            <!-- Background Effects -->
            <div class="absolute inset-0 bg-gradient-to-br from-{{ $type['color'] }}-50/20 to-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <div class="absolute top-4 right-4 w-16 h-16 bg-{{ $type['color'] }}-400/10 rounded-full blur-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            
            <div class="relative flex items-start space-x-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-{{ $type['color'] }}-500 to-{{ $type['color'] }}-600 flex items-center justify-center flex-shrink-0 shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-110">
                    <i class="{{ $type['icon'] }} text-white text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-lg font-bold bg-gradient-to-r from-gray-900 to-{{ $type['color'] }}-800 bg-clip-text text-transparent mb-3">{{ $type['name'] }}</h4>
                    <p class="text-gray-600 font-medium mb-4 leading-relaxed">{{ $type['description'] }}</p>
                    
                    @if(isset($type['features']) && count($type['features']) > 0)
                    <div class="space-y-2">
                        <p class="text-sm font-bold text-{{ $type['color'] }}-700 mb-2 flex items-center">
                            <i class="fas fa-star mr-2"></i>
                            Key Features:
                        </p>
                        <ul class="text-sm text-gray-700 space-y-2">
                            @foreach($type['features'] as $feature)
                            <li class="flex items-center">
                                <div class="w-5 h-5 rounded-full bg-gradient-to-r from-{{ $type['color'] }}-500 to-{{ $type['color'] }}-600 flex items-center justify-center mr-3 shadow-sm">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="font-medium">{{ $feature }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    @if(isset($type['badge']))
                    <div class="mt-4">
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold bg-gradient-to-r from-{{ $type['color'] }}-500 to-{{ $type['color'] }}-600 text-white shadow-lg">
                            {{ $type['badge'] }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Enhanced Selection Indicator -->
            @if($selectedType === $type['value'])
            <div class="absolute top-4 right-4 z-10">
                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-{{ $type['color'] }}-500 to-{{ $type['color'] }}-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-check text-white text-sm"></i>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-{{ $type['color'] }}-200/50">
                <div class="flex items-center text-{{ $type['color'] }}-600">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span class="font-bold">Selected</span>
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div> 