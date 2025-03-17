<div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
    <div class="p-6 sm:px-8 bg-gradient-to-r from-gray-50 to-white">
        
        <!-- Profile Form Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Portfolio Profile</h2>
            <p class="text-gray-600">Customize your profile to showcase your work and talents.</p>
        </div>
        
        <!-- Flash Messages -->
        @if (session('success'))
            <div class="mb-4 p-4 border rounded relative bg-green-100 border-green-400 text-green-700" role="alert">
                {{ session('success') }}
            </div>
        @endif
        
        <div x-data="{ shown: false, message: '', error: false }" 
             x-init="
                Livewire.on('profile-updated', (data) => {
                    message = data.message || data.error || '';
                    error = data.hasOwnProperty('error');
                    shown = true;
                    setTimeout(() => shown = false, 5000);
                });
             ">
            <div x-show="shown" 
                 x-transition
                 :class="error ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700'"
                 class="mb-4 p-4 border rounded relative" 
                 role="alert">
                <span x-text="message"></span>
            </div>
        </div>
        
        <form wire:submit.prevent="save" class="space-y-8">
            <!-- Profile Photo Section -->
            <div class="mb-8">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-6">
                        <div wire:loading.remove wire:target="profilePhoto">
                            @if($profilePhoto)
                                <img class="h-32 w-32 rounded-full object-cover border-4 border-white shadow-md" 
                                     src="{{ $profilePhoto->temporaryUrl() }}" 
                                     alt="{{ auth()->user()->name }}" />
                            @else
                                <img class="h-32 w-32 rounded-full object-cover border-4 border-white shadow-md" 
                                     src="{{ auth()->user()->profile_photo_url }}" 
                                     alt="{{ auth()->user()->name }}" />
                            @endif
                        </div>
                        <div wire:loading wire:target="profilePhoto" class="h-32 w-32 rounded-full flex items-center justify-center bg-gray-100 border-4 border-white shadow-md">
                            <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">Profile Photo</h3>
                        <div class="mb-2">
                            <input type="file" wire:model="profilePhoto" class="hidden" id="photo" accept="image/*" />
                            <label for="photo" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:shadow-outline-gray disabled:opacity-25 transition cursor-pointer">
                                <i class="fas fa-camera mr-2"></i> Change Photo
                            </label>
                        </div>
                        <div wire:loading wire:target="profilePhoto" class="text-sm text-primary">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Uploading...
                        </div>
                        @error('profilePhoto') <span class="text-red-500 text-sm mt-1 block"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-500 mt-1">Maximum file size: 1MB. Supported formats: JPG, PNG, GIF.</p>
                    </div>
                </div>
            </div>
            
            <!-- Basic Profile Info -->
            <div class="bg-gray-50 rounded-lg p-6 space-y-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-gray-200">
                    <i class="fas fa-user mr-2"></i> Basic Information
                </h3>
                
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Name
                    </label>
                    <div class="mt-1">
                        <input type="text" wire:model="name" id="name" 
                               class="shadow-sm block w-full border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 rounded-md">
                    </div>
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">
                        Username <span class="text-red-500">*</span>
                        @if($is_username_locked)
                            <span class="text-yellow-600 text-xs ml-2">
                                <i class="fas fa-lock"></i> Cannot be changed
                            </span>
                        @else
                            <span class="text-gray-500 text-xs ml-2">
                                <i class="fas fa-exclamation-circle"></i> Will be locked once set
                            </span>
                        @endif
                    </label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                            @
                        </span>
                        <input type="text" wire:model="username" id="username" 
                               class="flex-1 block w-full rounded-r-md border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50"
                               placeholder="username"
                               {{ $is_username_locked ? 'disabled' : '' }}>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        This will be your public profile URL: {{ config('app.url') }}/@{{ $username ?: 'username' }}
                    </p>
                    @error('username') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email <span class="text-gray-400 text-xs">(Not shown publicly)</span>
                    </label>
                    <div class="mt-1">
                        <input type="email" wire:model="email" id="email" 
                               class="shadow-sm block w-full border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 rounded-md">
                    </div>
                    @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                
                <!-- Headline -->
                <div>
                    <label for="headline" class="block text-sm font-medium text-gray-700">
                        Headline <span class="text-gray-400 text-xs">(One-line description)</span>
                    </label>
                    <div class="mt-1">
                        <input type="text" wire:model="headline" id="headline" 
                               class="shadow-sm block w-full border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 rounded-md"
                               placeholder="e.g. Mixing Engineer & Producer based in LA">
                    </div>
                    @error('headline') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                
                <!-- Bio -->
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700">
                        Bio <span class="text-gray-400 text-xs">(Tell us about yourself)</span>
                    </label>
                    <div class="mt-1">
                        <textarea wire:model="bio" id="bio" rows="4" 
                                  class="shadow-sm block w-full border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 rounded-md"
                                  placeholder="Share your experience, background, and what makes you unique"></textarea>
                    </div>
                    @error('bio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                
                <!-- Location -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">
                        Location
                    </label>
                    <div class="mt-1">
                        <input type="text" wire:model="location" id="location" 
                               class="shadow-sm block w-full border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 rounded-md"
                               placeholder="e.g. Los Angeles, CA">
                    </div>
                    @error('location') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                
                <!-- Website -->
                <div>
                    <label for="website" class="block text-sm font-medium text-gray-700">
                        Website
                    </label>
                    <div class="mt-1">
                        <input type="text" wire:model="website" id="website" 
                               class="shadow-sm block w-full border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 rounded-md"
                               placeholder="yourwebsite.com">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Your personal website or portfolio URL (http:// will be added automatically)
                    </p>
                    @error('website') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <!-- Professional Skills -->
            <div class="bg-gray-50 rounded-lg p-6 space-y-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-gray-200">
                    <i class="fas fa-tools mr-2"></i> Professional Skills & Equipment
                </h3>
                
                <!-- Skills -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Skills <span class="text-gray-400 text-xs">(Production, Mixing, Mastering, etc.)</span>
                    </label>
                    
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($skills as $index => $skill)
                            <div class="bg-blue-100 text-blue-800 rounded-full text-sm px-3 py-1 flex items-center">
                                <span>{{ $skill }}</span>
                                <button type="button" wire:click="removeSkill({{ $index }})" class="ml-2 text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="flex">
                        <input type="text" wire:model="newSkill" wire:keydown.enter.prevent="addSkill" placeholder="Add a skill" 
                               class="flex-grow rounded-l-md border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" />
                        <button type="button" wire:click="addSkill" 
                                class="bg-primary hover:bg-primary-focus text-white rounded-r-md px-4 py-2 text-sm">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Equipment -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Equipment <span class="text-gray-400 text-xs">(DAW, Hardware, etc.)</span>
                    </label>
                    
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($equipment as $index => $item)
                            <div class="bg-green-100 text-green-800 rounded-full text-sm px-3 py-1 flex items-center">
                                <span>{{ $item }}</span>
                                <button type="button" wire:click="removeEquipment({{ $index }})" class="ml-2 text-green-600 hover:text-green-800">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="flex">
                        <input type="text" wire:model="newEquipment" wire:keydown.enter.prevent="addEquipment" placeholder="Add equipment" 
                               class="flex-grow rounded-l-md border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" />
                        <button type="button" wire:click="addEquipment" 
                                class="bg-primary hover:bg-primary-focus text-white rounded-r-md px-4 py-2 text-sm">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Specialties -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Specialties <span class="text-gray-400 text-xs">(Genres, Styles, etc.)</span>
                    </label>
                    
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($specialties as $index => $specialty)
                            <div class="bg-purple-100 text-purple-800 rounded-full text-sm px-3 py-1 flex items-center">
                                <span>{{ $specialty }}</span>
                                <button type="button" wire:click="removeSpecialty({{ $index }})" class="ml-2 text-purple-600 hover:text-purple-800">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="flex">
                        <input type="text" wire:model="newSpecialty" wire:keydown.enter.prevent="addSpecialty" placeholder="Add specialty" 
                               class="flex-grow rounded-l-md border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" />
                        <button type="button" wire:click="addSpecialty" 
                                class="bg-primary hover:bg-primary-focus text-white rounded-r-md px-4 py-2 text-sm">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Social Media Links -->
            <div class="bg-gray-50 rounded-lg p-6 space-y-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-gray-200">
                    <i class="fas fa-share-alt mr-2"></i> Social Media
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Instagram -->
                    <div>
                        <label for="instagram" class="block text-sm font-medium text-gray-700">
                            <i class="fab fa-instagram text-pink-500 mr-2"></i> Instagram
                        </label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                instagram.com/
                            </span>
                            <input type="text" wire:model="social_links.instagram" id="instagram" placeholder="username" 
                                   class="flex-1 block w-full rounded-r-md border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                        </div>
                    </div>
                    
                    <!-- Facebook -->
                    <div>
                        <label for="facebook" class="block text-sm font-medium text-gray-700">
                            <i class="fab fa-facebook text-blue-600 mr-2"></i> Facebook
                        </label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                facebook.com/
                            </span>
                            <input type="text" wire:model="social_links.facebook" id="facebook" placeholder="username" 
                                   class="flex-1 block w-full rounded-r-md border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                        </div>
                    </div>
                    
                    <!-- Twitter -->
                    <div>
                        <label for="twitter" class="block text-sm font-medium text-gray-700">
                            <i class="fab fa-twitter text-blue-400 mr-2"></i> Twitter
                        </label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                twitter.com/
                            </span>
                            <input type="text" wire:model="social_links.twitter" id="twitter" placeholder="username" 
                                   class="flex-1 block w-full rounded-r-md border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                        </div>
                    </div>
                    
                    <!-- YouTube -->
                    <div>
                        <label for="youtube" class="block text-sm font-medium text-gray-700">
                            <i class="fab fa-youtube text-red-600 mr-2"></i> YouTube
                        </label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                youtube.com/
                            </span>
                            <input type="text" wire:model="social_links.youtube" id="youtube" placeholder="channel" 
                                   class="flex-1 block w-full rounded-r-md border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                        </div>
                    </div>
                    
                    <!-- SoundCloud -->
                    <div>
                        <label for="soundcloud" class="block text-sm font-medium text-gray-700">
                            <i class="fab fa-soundcloud text-orange-500 mr-2"></i> SoundCloud
                        </label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                soundcloud.com/
                            </span>
                            <input type="text" wire:model="social_links.soundcloud" id="soundcloud" placeholder="username" 
                                   class="flex-1 block w-full rounded-r-md border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                        </div>
                    </div>
                    
                    <!-- Spotify -->
                    <div>
                        <label for="spotify" class="block text-sm font-medium text-gray-700">
                            <i class="fab fa-spotify text-green-500 mr-2"></i> Spotify
                        </label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                open.spotify.com/artist/
                            </span>
                            <input type="text" wire:model="social_links.spotify" id="spotify" placeholder="ID" 
                                   class="flex-1 block w-full rounded-r-md border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Save Button -->
            <div class="mt-6 flex justify-end">
                <button type="submit" class="inline-flex items-center px-6 py-3 bg-primary border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-primary-focus active:bg-primary-focus focus:outline-none focus:border-primary-focus focus:shadow-outline-gray disabled:opacity-25 transition">
                    <i class="fas fa-save mr-2"></i> Save Profile
                </button>
            </div>
        </form>
    </div>
</div>
