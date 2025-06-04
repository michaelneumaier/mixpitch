<div class="space-y-6">
    <div class="text-center">
        <h4 class="text-lg font-bold bg-gradient-to-r from-gray-900 to-green-800 bg-clip-text text-transparent">
            Notification Preferences
        </h4>
        <p class="text-sm text-gray-600 mt-2">
            Choose how you would like to receive notifications for different events.
        </p>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-green-100/80 backdrop-blur-sm border border-green-200/50 rounded-xl shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 mr-3"></i>
                <span class="text-green-800 font-medium">{{ session('message') }}</span>
            </div>
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="p-4 bg-red-100/80 backdrop-blur-sm border border-red-200/50 rounded-xl shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                <span class="text-red-800 font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($notificationTypes as $type => $label)
            <div class="bg-white/80 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-gray-900 flex items-center">
                        <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                            <i class="fas fa-bell text-white text-xs"></i>
                        </div>
                        {{ $label }}
                    </span>
                </div>
                
                <div class="flex items-center justify-start space-x-6">
                    {{-- Loop through available channels --}}
                    @foreach ($channels as $channel)
                        <div class="flex items-center">
                            <label for="preference-{{ $type }}-{{ $channel }}" class="flex items-center cursor-pointer group notification-toggle-group">
                                <!-- Enhanced Toggle Switch -->
                                <div class="relative">
                                    <input 
                                        type="checkbox" 
                                        id="preference-{{ $type }}-{{ $channel }}" 
                                        class="sr-only notification-toggle-input" 
                                        wire:model.live="preferences.{{ $type }}.{{ $channel }}" 
                                    />
                                    <!-- Switch Background -->
                                    <div class="notification-toggle-bg block bg-gray-300 w-12 h-6 rounded-full transition-all duration-200 group-hover:shadow-md {{ (isset($preferences[$type][$channel]) && $preferences[$type][$channel]) ? 'bg-gradient-to-r from-green-400 to-green-500' : '' }}"></div>
                                    <!-- Switch Dot -->
                                    <div class="notification-toggle-dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all duration-200 shadow-md {{ (isset($preferences[$type][$channel]) && $preferences[$type][$channel]) ? 'translate-x-6 shadow-lg' : '' }}"></div>
                                </div>
                                <!-- Channel Label -->
                                <span class="ml-3 text-sm font-medium text-gray-700 capitalize group-hover:text-gray-900 transition-colors duration-200">
                                    {{ $channel }}
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-8">
                <div class="bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-6">
                    <i class="fas fa-bell-slash text-gray-400 text-2xl mb-3"></i>
                    <p class="text-sm text-gray-500">No notification types available to configure.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
