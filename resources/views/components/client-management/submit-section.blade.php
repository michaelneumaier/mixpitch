@props(['pitch', 'component', 'type', 'title', 'description', 'buttonText', 'infoText'])

@php
    $producerFiles = $component->producerFiles ?? collect();
    $hasFiles = $producerFiles->count() > 0;
    $colorScheme = $type === 'revisions' ? 'amber' : 'purple';
@endphp

<div class="bg-gradient-to-br from-{{ $colorScheme }}-50/90 to-orange-50/90 backdrop-blur-sm border border-{{ $colorScheme }}-200/50 rounded-xl p-6 shadow-lg">
    <div class="flex items-center mb-4">
        <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-{{ $colorScheme }}-500 to-orange-600 rounded-xl mr-4 shadow-lg">
            <i class="fas fa-paper-plane text-white"></i>
        </div>
        <div>
            <h4 class="text-xl font-bold text-{{ $colorScheme }}-800">{{ $title }}</h4>
            <p class="text-{{ $colorScheme }}-600 text-sm">{{ $description }}</p>
        </div>
    </div>

    @if($hasFiles)
        <button wire:click="submitForReview" 
                wire:loading.attr="disabled"
                class="w-full inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-{{ $colorScheme }}-600 to-orange-600 hover:from-{{ $colorScheme }}-700 hover:to-orange-700 text-white rounded-xl font-bold text-lg transition-all duration-200 hover:scale-105 hover:shadow-xl disabled:opacity-50 disabled:transform-none">
            <span wire:loading wire:target="submitForReview" class="inline-block w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin mr-3"></span>
            <i wire:loading.remove wire:target="submitForReview" class="fas fa-paper-plane mr-3"></i>
            {{ $buttonText }}
        </button>
    @else
        <button disabled class="w-full inline-flex items-center justify-center px-6 py-4 bg-gray-400 text-white rounded-xl font-bold text-lg opacity-50 cursor-not-allowed">
            <i class="fas fa-upload mr-3"></i>Upload Files First
        </button>
    @endif

    <div class="mt-4 text-center">
        <p class="text-sm text-{{ $colorScheme }}-600">
            <i class="fas fa-info-circle mr-1"></i>
            {{ $infoText }}
        </p>
    </div>
</div>