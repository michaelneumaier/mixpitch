<div>
    @if($count > 0)
        <div class="relative inline-block">
            <!-- Animated notification indicator -->
            <span class="absolute -top-2 -right-2 flex h-6 w-6">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gradient-to-r from-red-400 to-pink-500 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-6 w-6 bg-gradient-to-r from-red-500 to-pink-600 text-xs text-white items-center justify-center font-bold shadow-lg border-2 border-white">
                    {{ $count > 99 ? '99+' : $count }}
                </span>
            </span>
            <!-- Bell icon with enhanced styling -->
            <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-gradient-to-br from-blue-50 to-purple-50 text-gray-600 hover:text-blue-600">
                <i class="fas fa-bell text-lg"></i>
            </div>
        </div>
    @else
        <!-- Bell icon without notification -->
        <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 text-gray-500 hover:text-blue-600 hover:bg-gradient-to-br hover:from-blue-50 hover:to-purple-50">
            <i class="fas fa-bell text-lg"></i>
        </div>
    @endif
</div>
