<time 
    datetime="{{ $isoDate }}" 
    class="{{ $class }}"
    @if($tooltip)
        title="{{ $formattedDate }}"
    @endif
>
    @if($relative)
        {{ $relativeDate }}
    @else
        {{ $formattedDate }}
    @endif
</time> 