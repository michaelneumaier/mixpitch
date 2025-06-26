<time 
    datetime="{{ $this->getIsoDate() }}" 
    class="{{ $class }}"
    @if($tooltip)
        title="{{ $this->getFormattedDate() }}"
    @endif
>
    @if($relative)
        {{ $this->getRelativeDate() }}
    @else
        {{ $this->getFormattedDate() }}
    @endif
</time> 