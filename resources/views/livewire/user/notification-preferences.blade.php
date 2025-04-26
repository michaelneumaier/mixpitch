<div>
    <h3 class="text-lg font-medium text-gray-900">Notification Preferences</h3>
    <p class="mt-1 text-sm text-gray-600">
        Choose how you would like to receive notifications for different events.
    </p>

    @if (session()->has('message'))
        <div class="mt-4 p-4 bg-green-100 text-green-700 rounded-md">
            {{ session('message') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="mt-4 p-4 bg-red-100 text-red-700 rounded-md">
            {{ session('error') }}
        </div>
    @endif

    <div class="mt-6 space-y-6">
        @forelse ($notificationTypes as $type => $label)
            <div class="p-4 border rounded-md border-gray-200">
                <span class="text-sm font-medium text-gray-900 block mb-3">{{ $label }}</span>
                <div class="flex items-center justify-start space-x-6">
                    {{-- Loop through available channels --}}
                    @foreach ($channels as $channel)
                        <div class="flex items-center">
                            <label for="preference-{{ $type }}-{{ $channel }}" class="flex items-center cursor-pointer">
                                <!-- Tailwind Toggle Switch -->
                                <div class="relative">
                                    <input 
                                        type="checkbox" 
                                        id="preference-{{ $type }}-{{ $channel }}" 
                                        class="sr-only" 
                                        wire:model.live="preferences.{{ $type }}.{{ $channel }}" 
                                    />
                                    <div class="block bg-gray-300 w-10 h-6 rounded-full transition"></div>
                                    <div 
                                        {{-- Conditionally build the class string for Alpine --}}
                                        class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform {{ (isset($preferences[$type][$channel]) && $preferences[$type][$channel]) ? 'translate-x-full !bg-indigo-500' : '' }}"
                                    ></div>
                                </div>
                                <!-- End Toggle Switch -->
                                <span class="ml-3 text-sm text-gray-600">{{ ucfirst($channel) }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
                 {{-- Optional: Add description for the notification type --}}
                 {{-- <p class="mt-2 text-xs text-gray-500">Description of when '{{ $label }}' notification is sent.</p> --}}
            </div>
        @empty
            <p class="text-sm text-gray-500">No notification types available to configure.</p>
        @endforelse
    </div>
    
    {{-- Add custom styles needed for the toggle switch --}}
    <style>
        input:checked ~ .dot {
            transform: translateX(100%);
            background-color: #6366f1; /* Match indigo-500 */
        }
        input:checked ~ .block {
             background-color: #a5b4fc; /* Match indigo-300 or adjust*/
        }
    </style>
</div>
