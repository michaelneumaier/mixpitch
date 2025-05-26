@props(['workflowTypes' => [], 'selectedType' => null, 'wireModel' => 'workflowType'])

<div class="space-y-4">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h4 class="font-medium text-blue-900 mb-2 flex items-center">
            <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
            Choose Your Project Workflow
        </h4>
        <p class="text-sm text-blue-800">Select the workflow type that best matches how you want to manage this project. This will determine the available features and collaboration options.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($workflowTypes as $type)
        <div wire:click="$set('{{ $wireModel }}', '{{ $type['value'] }}')"
             class="bg-white border-2 rounded-lg p-4 cursor-pointer transition-all duration-200 hover:shadow-md
             {{ $selectedType === $type['value'] ? 'border-' . $type['color'] . '-500 bg-' . $type['color'] . '-50 shadow-md' : 'border-gray-200 hover:border-' . $type['color'] . '-300' }}">
            
            <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-full bg-{{ $type['color'] }}-100 flex items-center justify-center flex-shrink-0">
                    <i class="{{ $type['icon'] }} text-{{ $type['color'] }}-600 text-lg"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-base font-semibold text-gray-900 mb-2">{{ $type['name'] }}</h4>
                    <p class="text-sm text-gray-600 mb-3 leading-relaxed">{{ $type['description'] }}</p>
                    
                    @if(isset($type['features']) && count($type['features']) > 0)
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-{{ $type['color'] }}-700 mb-1">Key Features:</p>
                        <ul class="text-xs text-gray-600 space-y-1">
                            @foreach($type['features'] as $feature)
                            <li class="flex items-center">
                                <i class="fas fa-check text-{{ $type['color'] }}-500 mr-2 text-xs"></i>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    @if(isset($type['badge']))
                    <div class="mt-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $type['color'] }}-100 text-{{ $type['color'] }}-800">
                            {{ $type['badge'] }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Selection Indicator -->
            @if($selectedType === $type['value'])
            <div class="mt-3 pt-3 border-t border-{{ $type['color'] }}-200">
                <div class="flex items-center text-{{ $type['color'] }}-600">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span class="text-sm font-medium">Selected</span>
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div> 