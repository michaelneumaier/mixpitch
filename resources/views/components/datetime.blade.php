<!-- DATETIME COMPONENT RENDERED AT: {{ now()->toIso8601String() }} -->
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