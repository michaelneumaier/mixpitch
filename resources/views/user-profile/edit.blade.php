<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Profile') }}
            </h2>
            @if($user->username)
                <a href="{{ route('profile.public', ['username' => $user->username]) }}" class="inline-flex items-center px-3 py-1 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                    <i class="fas fa-eye mr-1"></i> View Public Profile
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 sm:px-8 bg-white border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-6">
                        {{ __('Profile Information') }}
                    </h2>

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

                    <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Username -->
                        <div>
                            <x-label for="username" value="{{ __('Username') }}" />
                            <x-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username', $user->username)" required autofocus />
                            <p class="mt-1 text-sm text-gray-500">This will be used for your public profile URL: {{ url('/profile/') }}/<span class="font-medium">username</span></p>
                            @error('username')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bio -->
                        <div>
                            <x-label for="bio" value="{{ __('Bio') }}" />
                            <textarea id="bio" name="bio" rows="4" class="border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full">{{ old('bio', $user->bio) }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">Tell others about yourself and your music experience.</p>
                            @error('bio')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Website -->
                        <div>
                            <x-label for="website" value="{{ __('Website') }}" />
                            <x-input id="website" class="block mt-1 w-full" type="text" name="website" :value="old('website', $user->website)" placeholder="yourwebsite.com" />
                            <p class="mt-1 text-sm text-gray-500">Enter your website URL. The https:// prefix will be added automatically if missing.</p>
                            @error('website')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Location -->
                        <div>
                            <x-label for="location" value="{{ __('Location') }}" />
                            <x-input id="location" class="block mt-1 w-full" type="text" name="location" :value="old('location', $user->location)" placeholder="City, Country" />
                            @error('location')
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
                                        <x-input id="social_links_twitter" class="rounded-none rounded-r-md flex-1" type="text" name="social_links[twitter]" 
                                            :value="old('social_links.twitter', $this->getSocialUsername($user->social_links['twitter'] ?? '', 'twitter'))" 
                                            placeholder="username" />
                                    </div>
                                </div>
                                
                                <!-- Instagram -->
                                <div>
                                    <x-label for="social_links_instagram" value="{{ __('Instagram') }}" />
                                    <div class="flex mt-1">
                                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">
                                            <i class="fab fa-instagram"></i>
                                        </span>
                                        <x-input id="social_links_instagram" class="rounded-none rounded-r-md flex-1" type="text" name="social_links[instagram]" 
                                            :value="old('social_links.instagram', $this->getSocialUsername($user->social_links['instagram'] ?? '', 'instagram'))" 
                                            placeholder="username" />
                                    </div>
                                </div>
                                
                                <!-- SoundCloud -->
                                <div>
                                    <x-label for="social_links_soundcloud" value="{{ __('SoundCloud') }}" />
                                    <div class="flex mt-1">
                                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">
                                            <i class="fab fa-soundcloud"></i>
                                        </span>
                                        <x-input id="social_links_soundcloud" class="rounded-none rounded-r-md flex-1" type="text" name="social_links[soundcloud]" 
                                            :value="old('social_links.soundcloud', $this->getSocialUsername($user->social_links['soundcloud'] ?? '', 'soundcloud'))" 
                                            placeholder="username" />
                                    </div>
                                </div>
                                
                                <!-- Spotify -->
                                <div>
                                    <x-label for="social_links_spotify" value="{{ __('Spotify') }}" />
                                    <div class="flex mt-1">
                                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">
                                            <i class="fab fa-spotify"></i>
                                        </span>
                                        <x-input id="social_links_spotify" class="rounded-none rounded-r-md flex-1" type="text" name="social_links[spotify]" 
                                            :value="old('social_links.spotify', $this->getSocialUsername($user->social_links['spotify'] ?? '', 'spotify'))" 
                                            placeholder="artist ID" />
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
                                        <x-input id="social_links_youtube" class="rounded-none rounded-r-md flex-1" type="text" name="social_links[youtube]" 
                                            :value="old('social_links.youtube', $this->getSocialUsername($user->social_links['youtube'] ?? '', 'youtube'))" 
                                            placeholder="channel name" />
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">For YouTube, use your channel name (found after "/c/" in your channel URL).</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-button>
                                {{ __('Save') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
