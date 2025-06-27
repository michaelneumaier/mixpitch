<time 
    datetime="{{ $isoDate ?? $date->toISOString() }}" 
    class="{{ $class }}"
    @if($tooltip)
        title="{{ $formattedDate ?? $date->format('M d, Y g:i A') }}"
    @endif
>
    @if($relative)
        {{ $relativeDate ?? $date->diffForHumans() }}
    @else
        {{ $formattedDate ?? $date->format('M d, Y g:i A') }}
    @endif
</time> 