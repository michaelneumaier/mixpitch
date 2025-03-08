<div>
    @if($count > 0)
        <div class="relative inline-block">
            <span class="absolute -top-1 -right-2 flex h-5 w-5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-5 w-5 bg-red-500 text-xs text-white items-center justify-center font-bold">
                    {{ $count > 99 ? '99+' : $count }}
                </span>
            </span>
            <i class="fas fa-bell"></i>
        </div>
    @else
        <i class="fas fa-bell"></i>
    @endif
</div>
