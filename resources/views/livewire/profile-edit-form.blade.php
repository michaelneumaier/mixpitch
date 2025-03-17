<div>
    {{-- In work, do what you enjoy. --}}
    <div class="p-6 bg-white shadow-sm sm:rounded-lg">
        @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if (!$user->username)
            <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">Set up your username to create your public profile page. This will allow others to see your work and connect with you.</span>
            </div>
        @endif

        <form wire:submit.prevent="updateProfile" class="space-y-6">
            <!-- Username -->
            <div>
                <x-label for="username" value="{{ __('Username') }}" />
                <x-input id="username" class="block mt-1 w-full" type="text" wire:model="username" required autofocus />
                <p class="mt-1 text-sm text-gray-500">This will be used for your public profile URL: mixpitch.com/users/username</p>
                @error('username')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Bio -->
            <div>
                <x-label for="bio" value="{{ __('Bio') }}" />
                <textarea id="bio" class="block mt-1 w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm" wire:model="bio" rows="4"></textarea>
                @error('bio')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Location -->
            <div>
                <x-label for="location" value="{{ __('Location') }}" />
                <x-input id="location" class="block mt-1 w-full" type="text" wire:model="location" placeholder="City, Country" />
                @error('location')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Website -->
            <div>
                <x-label for="website" value="{{ __('Website') }}" />
                <x-input id="website" class="block mt-1 w-full" type="text" wire:model="website" placeholder="yourwebsite.com" />
                <p class="mt-1 text-sm text-gray-500">Enter your website URL. The https:// prefix will be added automatically if missing.</p>
                @error('website')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Social Links -->
            <div>
                <h3 class="font-medium text-gray-900 mb-3">{{ __('Social Media Links') }}</h3>
                <p class="text-sm text-gray-500 mb-4">Enter just your username or handle for each platform (without the @ symbol).</p>
                
                <div class="space-y-4">
                    <!-- Twitter -->
                    <div>
                        <x-label for="social_links_twitter" value="{{ __('Twitter') }}" />
                        <div class="flex mt-1">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">
                                <i class="fab fa-twitter"></i>
                            </span>
                            <x-input id="social_links_twitter" class="rounded-none rounded-r-md flex-1" type="text" 
                                wire:model="social_links.twitter" placeholder="username" />
                        </div>
                    </div>
                    
                    <!-- Instagram -->
                    <div>
                        <x-label for="social_links_instagram" value="{{ __('Instagram') }}" />
                        <div class="flex mt-1">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">
                                <i class="fab fa-instagram"></i>
                            </span>
                            <x-input id="social_links_instagram" class="rounded-none rounded-r-md flex-1" type="text" 
                                wire:model="social_links.instagram" placeholder="username" />
                        </div>
                    </div>
                    
                    <!-- SoundCloud -->
                    <div>
                        <x-label for="social_links_soundcloud" value="{{ __('SoundCloud') }}" />
                        <div class="flex mt-1">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">
                                <i class="fab fa-soundcloud"></i>
                            </span>
                            <x-input id="social_links_soundcloud" class="rounded-none rounded-r-md flex-1" type="text" 
                                wire:model="social_links.soundcloud" placeholder="username" />
                        </div>
                    </div>
                    
                    <!-- Spotify -->
                    <div>
                        <x-label for="social_links_spotify" value="{{ __('Spotify') }}" />
                        <div class="flex mt-1">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">
                                <i class="fab fa-spotify"></i>
                            </span>
                            <x-input id="social_links_spotify" class="rounded-none rounded-r-md flex-1" type="text" 
                                wire:model="social_links.spotify" placeholder="artist ID" />
                        </div>
                        <p class="mt-1 text-sm text-gray-500">For Spotify, use your artist ID (found in your Spotify artist URL after "artist/").</p>
                    </div>
                    
                    <!-- YouTube -->
                    <div>
                        <x-label for="social_links_youtube" value="{{ __('YouTube') }}" />
                        <div class="flex mt-1">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">
                                <i class="fab fa-youtube"></i>
                            </span>
                            <x-input id="social_links_youtube" class="rounded-none rounded-r-md flex-1" type="text" 
                                wire:model="social_links.youtube" placeholder="channel name" />
                        </div>
                        <p class="mt-1 text-sm text-gray-500">For YouTube, use your channel name (found after "/c/" in your channel URL).</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end">
                <x-button>
                    {{ __('Save Profile') }}
                </x-button>
            </div>
        </form>
    </div>
</div>
