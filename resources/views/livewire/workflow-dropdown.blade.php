<div class="{{ $fullWidth ? 'w-full' : '' }}">
    <flux:dropdown align="{{ $align }}">
        @if($color && $size)
            <flux:button variant="{{ $variant }}" color="{{ $color }}" size="{{ $size }}" icon="plus" class="{{ $fullWidth ? 'w-full justify-center' : '' }}">
                {{ $label }}
            </flux:button>
        @elseif($color)
            <flux:button variant="{{ $variant }}" color="{{ $color }}" icon="plus" class="{{ $fullWidth ? 'w-full justify-center' : '' }}">
                {{ $label }}
            </flux:button>
        @elseif($size)
            <flux:button variant="{{ $variant }}" size="{{ $size }}" icon="plus" class="{{ $fullWidth ? 'w-full justify-center' : '' }}">
                {{ $label }}
            </flux:button>
        @else
            <flux:button variant="{{ $variant }}" icon="plus" class="{{ $fullWidth ? 'w-full justify-center' : '' }}">
                {{ $label }}
            </flux:button>
        @endif

        <flux:menu class="min-w-80">
            @foreach($this->workflowTypes as $workflow)
                <flux:menu.item wire:click="selectWorkflow('{{ $workflow['value'] }}')" icon="{{ $workflow['icon'] }}">
                    <div class="flex flex-col">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $workflow['name'] }}</span>
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ $workflow['description'] }}</span>
                    </div>
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>
</div>
