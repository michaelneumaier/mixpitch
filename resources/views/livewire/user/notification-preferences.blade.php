<div>
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Notification Preferences</h3>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        Choose which notifications you would like to receive.
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
            <div class="flex items-center justify-between">
                <span class="flex flex-grow flex-col">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</span>
                    {{-- Optional: Add a description for each type if needed --}}
                    {{-- <span class="text-sm text-gray-500">Description for {{ $label }}</span> --}}
                </span>
                <label for="preference-{{ $type }}" class="flex items-center cursor-pointer">
                     <!-- Tailwind Toggle Switch -->
                    <div class="relative">
                        <input 
                            type="checkbox" 
                            id="preference-{{ $type }}" 
                            class="sr-only" 
                            wire:model.live="preferences.{{ $type }}" 
                        />
                        <div class="block bg-gray-300 dark:bg-gray-600 w-10 h-6 rounded-full transition"></div>
                        <div 
                            class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform "
                            :class="{ 'translate-x-full !bg-indigo-500': @json($preferences[$type]) }"
                        ></div>
                    </div>
                    <!-- End Toggle Switch -->
                    
                    {{-- Fallback Checkbox (uncomment if toggle is problematic) --}}
                    {{-- <input 
                        id="preference-{{ $type }}" 
                        type="checkbox" 
                        class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                        wire:model.live="preferences.{{ $type }}"
                        >
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Enable</span> --}}
                </label>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">No notification types available to configure.</p>
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
