@props(['workflowTypes' => [], 'selectedType' => null, 'wireModel' => 'workflowType'])

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @foreach($workflowTypes as $type)
        <button type="button"
                wire:click="$set('{{ $wireModel }}', '{{ $type['value'] }}')"
                style="outline: none !important; box-shadow: none !important;"
                class="relative group text-left transition-all duration-200 ease-out focus:outline-none focus:ring-0 focus:shadow-none outline-none
                       {{ $selectedType === $type['value'] 
                          ? 'ring-2 ring-' . $type['color'] . '-500 ring-offset-2 ring-offset-white dark:ring-offset-gray-800' 
                          : '' }}">
            
            <flux:card class="h-full p-6 hover:shadow-lg transition-all duration-200 focus:outline-none
                             {{ $selectedType === $type['value'] 
                                ? 'bg-' . $type['color'] . '-25 dark:bg-' . $type['color'] . '-950/20 border-' . $type['color'] . '-200 dark:border-' . $type['color'] . '-800/50' 
                                : 'hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                
                <!-- Selected Badge -->
                @if($selectedType === $type['value'])
                    <div class="absolute -top-2 -right-2 z-10">
                        <div class="w-8 h-8 rounded-full bg-{{ $type['color'] }}-500 flex items-center justify-center shadow-lg">
                            <flux:icon name="check" class="w-5 h-5 text-white" />
                        </div>
                    </div>
                @endif
                
                <div class="flex items-start gap-4">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-lg bg-{{ $type['color'] }}-100 dark:bg-{{ $type['color'] }}-900 flex items-center justify-center">
                            <flux:icon name="{{ $type['icon'] }}" class="w-6 h-6 text-{{ $type['color'] }}-600 dark:text-{{ $type['color'] }}-400" />
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <flux:heading size="lg" class="text-gray-900 dark:text-gray-100 mb-2">
                            {{ $type['name'] }}
                        </flux:heading>
                        <flux:text class="text-gray-600 dark:text-gray-400 mb-4">
                            {{ $type['description'] }}
                        </flux:text>
                        
                        @if(isset($type['features']) && count($type['features']) > 0)
                            <div class="space-y-2">
                                @foreach($type['features'] as $feature)
                                    <div class="flex items-start gap-2">
                                        <flux:icon name="check-circle" class="w-4 h-4 text-{{ $type['color'] }}-500 dark:text-{{ $type['color'] }}-400 mt-0.5 flex-shrink-0" />
                                        <flux:text size="sm" class="text-gray-700 dark:text-gray-300">
                                            {{ $feature }}
                                        </flux:text>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        @if(isset($type['badge']))
                            <div class="mt-4">
                                <flux:badge :color="$type['color']" size="sm">
                                    {{ $type['badge'] }}
                                </flux:badge>
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>
        </button>
    @endforeach
</div> 