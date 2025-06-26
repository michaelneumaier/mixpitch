<time 
    datetime="{{ $getIsoDate() }}" 
    class="{{ $class }}"
    @if($tooltip)
        title="{{ $getFormattedDate() }}"
    @endif
>
    @if($relative)
        {{ $getRelativeDate() }}
    @else
        {{ $getFormattedDate() }}
    @endif
</time> 