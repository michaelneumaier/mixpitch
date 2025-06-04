<div class="flex items-center space-x-2">
    <div class="flex items-center justify-center w-8 h-8 rounded-lg {{ $colors['bg'] }} text-white">
        <i class="{{ $record->getIconClass() }} text-sm"></i>
    </div>
    <span class="font-medium">{{ $record->name }}</span>
</div> 