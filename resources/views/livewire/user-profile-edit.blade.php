<div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
    <div class="p-6 sm:px-8 bg-gradient-to-r from-gray-50 to-white">
        
        <!-- Profile Form Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Portfolio Profile</h2>
            <p class="text-gray-600">Customize your profile to showcase your work and talents.</p>
            <div class="mt-3">
                <a href="{{ route('profile.portfolio') }}" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                    <i class="fas fa-images mr-2"></i> Manage Portfolio Items
                </a>
            </div>
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
        
        <form 
            wire:submit.prevent="save"
            name="profile-edit-form"
            id="profile-edit-form"
            class="space-y-8">
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
                
                <!-- Tipjar Link -->
                <div>
                    <label for="tipjar_link" class="block text-sm font-medium text-gray-700">
                        Tip Jar Link
                    </label>
                    <div class="mt-1">
                        <input type="text" wire:model="tipjar_link" id="tipjar_link" 
                               class="shadow-sm block w-full border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 rounded-md"
                               placeholder="paypal.me/yourusername">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Supported services: PayPal.me, Ko-fi, Buy Me A Coffee, Patreon, Venmo, CashApp, Stripe, GoFundMe, etc.
                    </p>
                    @error('tipjar_link') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <!-- Professional Skills / Tags -->
            <div class="bg-gray-50 rounded-lg p-6 space-y-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-gray-200">
                    <i class="fas fa-tags mr-2"></i> Skills, Equipment & Specialties
                </h3>
            
                <!-- Alpine/Choices.js component for selects -->
                @php
                // Define tags by type for the select components
                $tagsByType = \App\Models\Tag::all()->groupBy('type')->toArray();
                @endphp
                
                <div 
                    x-data="tagSelects({
                        allTags: {{ json_encode($tagsByType) }},
                        currentSkills: {{ json_encode(array_map('strval', $skills ?? [])) }},
                        currentEquipment: {{ json_encode(array_map('strval', $equipment ?? [])) }},
                        currentSpecialties: {{ json_encode(array_map('strval', $specialties ?? [])) }}
                    })"
                    x-init="initChoices()"
                    >
                    
                    <!-- Skills -->
                    <div class="mb-6">
                        <label for="skills-select" class="block text-sm font-medium text-gray-700 mb-1">
                            Skills <span class="text-gray-400 text-xs">(Production, Mixing, Mastering, etc.)</span>
                        </label>
                        <div wire:ignore class="mt-1 border border-gray-300 bg-white rounded-md shadow-sm focus-within:border-primary focus-within:ring-1 focus-within:ring-primary focus-within:ring-opacity-50">
                            <select id="skills-select" multiple="multiple" x-ref="skillsSelect" class="block w-full border-0 focus:ring-0"></select>
                        </div>
                        @error('skills') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        @error('skills.*') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
            
                    <!-- Equipment -->
                    <div class="mb-6">
                        <label for="equipment-select" class="block text-sm font-medium text-gray-700 mb-1">
                            Equipment <span class="text-gray-400 text-xs">(DAW, Hardware, etc.)</span>
                        </label>
                        <div wire:ignore class="mt-1 border border-gray-300 bg-white rounded-md shadow-sm focus-within:border-primary focus-within:ring-1 focus-within:ring-primary focus-within:ring-opacity-50">
                            <select id="equipment-select" multiple="multiple" x-ref="equipmentSelect" class="block w-full border-0 focus:ring-0"></select>
                        </div>
                        @error('equipment') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        @error('equipment.*') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
            
                    <!-- Specialties -->
                    <div>
                        <label for="specialties-select" class="block text-sm font-medium text-gray-700 mb-1">
                            Specialties <span class="text-gray-400 text-xs">(Genres, Styles, etc.)</span>
                        </label>
                        <div wire:ignore class="mt-1 border border-gray-300 bg-white rounded-md shadow-sm focus-within:border-primary focus-within:ring-1 focus-within:ring-primary focus-within:ring-opacity-50">
                            <select id="specialties-select" multiple="multiple" x-ref="specialtiesSelect" class="block w-full border-0 focus:ring-0"></select>
                        </div>
                        @error('specialties') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        @error('specialties.*') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
            
                </div>
                {{-- End Alpine Component --}}
            
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

@push('scripts')
<script>
    // Ensure the function doesn't get defined multiple times
    if (typeof window.tagSelects === 'undefined') {
        window.tagSelects = function(config) {
            return {
                allTags: config.allTags || {},
                skills: Array.isArray(config.currentSkills) ? config.currentSkills.map(String) : [],
                equipment: Array.isArray(config.currentEquipment) ? config.currentEquipment.map(String) : [],
                specialties: Array.isArray(config.currentSpecialties) ? config.currentSpecialties.map(String) : [],
                choicesInstances: {},
                debounceTimeout: null, // Added for debouncing
    
                initChoices() {
                    // Convert allTags if needed
                    if (Array.isArray(this.allTags)) {
                        const tagsMap = {};
                        this.allTags.forEach(tag => {
                            if (!tagsMap[tag.type]) tagsMap[tag.type] = [];
                            tagsMap[tag.type].push(tag);
                        });
                        this.allTags = tagsMap;
                    }
                    
                    this.$nextTick(() => { // Ensure elements are available
                        this.choicesInstances.skills = this.createChoices(this.$refs.skillsSelect, 'skill', this.skills);
                        this.choicesInstances.equipment = this.createChoices(this.$refs.equipmentSelect, 'equipment', this.equipment);
                        this.choicesInstances.specialties = this.createChoices(this.$refs.specialtiesSelect, 'specialty', this.specialties);
                    });
                },
    
                createChoices(element, type, selectedValues) {
                    if (!element) {
                        return null;
                    }
    
                    // Ensure selected values are treated as strings
                    const selectedStringValues = Array.isArray(selectedValues) ? selectedValues.map(String) : [];

                    const availableTags = this.allTags[type] || [];
                    
                    // Pre-check which values should be selected
                    const choicesOptions = [];
                    
                    // Add available tags and mark them as selected if they're in the selectedStringValues
                    availableTags.forEach(tag => {
                        const tagId = String(tag.id);
                        const isSelected = selectedStringValues.includes(tagId);
                        
                        choicesOptions.push({
                            value: tagId,
                            label: tag.name,
                            selected: isSelected
                        });
                    });
                    
                    const choices = new Choices(element, {
                        removeItemButton: true,
                        allowHTML: false,
                        placeholder: true,
                        placeholderValue: 'Select tags...',
                        choices: choicesOptions.sort((a, b) => a.label.localeCompare(b.label)),
                    });
                    
                    // Double-check that selections are applied
                    setTimeout(() => {
                        const currentSelections = choices.getValue(true);
                        
                        // Force selection if needed
                        if (selectedStringValues.length > 0 && currentSelections.length === 0) {
                            selectedStringValues.forEach(id => {
                                choices.setChoiceByValue(id);
                            });
                        }
                    }, 100);
    
                    // Debounce function definition
                    const debounce = (func, delay) => {
                        clearTimeout(this.debounceTimeout);
                        this.debounceTimeout = setTimeout(func, delay);
                    };
                    
                    element.addEventListener('change', () => {
                        // Get the selected values as strings
                        const selectedIds = choices.getValue(true).map(String);
                        
                        // Use the correct property names when setting Livewire data
                        const propertyMap = {
                            'skill': 'skills',
                            'equipment': 'equipment',
                            'specialty': 'specialties'
                        };
                        
                        const propertyName = propertyMap[type] || (type + 's');
                        
                        // Debounce the Livewire update
                        debounce(() => {
                            this.$wire.set(propertyName, selectedIds);
                        }, 250); // 250ms debounce delay
                        
                    }, false);
    
                    return choices;
                }
            }
        }
    }
</script>
@endpush
