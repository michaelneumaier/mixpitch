<div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden">
    <!-- Header Background Effects -->
    <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-blue-50/30 via-purple-50/20 to-pink-50/30"></div>
    <div class="absolute top-4 left-4 w-24 h-24 bg-blue-400/10 rounded-full blur-xl"></div>
    <div class="absolute top-4 right-4 w-16 h-16 bg-purple-400/10 rounded-full blur-lg"></div>
    
    <div class="relative p-6 sm:p-8">
        
        <!-- Enhanced Profile Form Header -->
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                <!-- Title Section -->
                <div class="flex-1">
                    <h1 class="text-3xl lg:text-4xl font-bold bg-gradient-to-r from-gray-900 via-blue-800 to-purple-800 bg-clip-text text-transparent mb-2">
                        Portfolio Profile
                    </h1>
                    <p class="text-lg text-gray-600 font-medium mb-4">Customize your profile to showcase your work and talents</p>
                    
                    <!-- Portfolio Management Button -->
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('profile.portfolio') }}" 
                           class="group inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                            <i class="fas fa-images mr-2 group-hover:scale-110 transition-transform"></i>
                            Manage Portfolio Items
                        </a>
                    </div>
                </div>
                
                <!-- Profile Completion Indicator -->
                <div class="flex-shrink-0">
                    <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg">
                        <div class="text-center">
                            <div class="relative inline-flex items-center justify-center">
                                <!-- Circular Progress Ring -->
                                <svg class="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36">
                                    <!-- Background circle -->
                                    <path class="text-gray-200" stroke="currentColor" stroke-width="3" fill="none" 
                                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                    <!-- Progress circle -->
                                    <path class="text-blue-500" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round"
                                          stroke-dasharray="{{ $profile_completion_percentage }}, 100"
                                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                </svg>
                                <!-- Percentage Text -->
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-lg font-bold text-gray-800">{{ $profile_completion_percentage }}%</span>
                                </div>
                            </div>
            <div class="mt-3">
                                <p class="text-sm font-medium text-gray-700">Profile Complete</p>
                                @if($profile_completion_percentage < 70)
                                    <p class="text-xs text-amber-600 mt-1">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        {{ 70 - $profile_completion_percentage }}% more to complete
                                    </p>
                                @else
                                    <p class="text-xs text-green-600 mt-1">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Profile completed!
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Enhanced Flash Messages -->
        @if (session('success'))
            <div class="mb-6 p-4 bg-green-100/80 backdrop-blur-sm border border-green-200/50 rounded-xl shadow-lg" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <span class="text-green-800 font-medium">{{ session('success') }}</span>
                </div>
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
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 :class="error ? 'bg-red-100/80 border-red-200/50 text-red-800' : 'bg-green-100/80 border-green-200/50 text-green-800'"
                 class="mb-6 p-4 backdrop-blur-sm border rounded-xl shadow-lg" 
                 role="alert">
                <div class="flex items-center">
                    <i :class="error ? 'fas fa-exclamation-circle text-red-600' : 'fas fa-check-circle text-green-600'" class="mr-3"></i>
                    <span x-text="message" class="font-medium"></span>
                </div>
            </div>
        </div>
        
        <form 
            wire:submit.prevent="save"
            name="profile-edit-form"
            id="profile-edit-form"
            class="space-y-8">
            <!-- Enhanced Profile Photo Section -->
            <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg mb-8">
                <!-- Section Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50/20 to-purple-50/20 rounded-2xl"></div>
                
                <div class="relative flex flex-col sm:flex-row sm:items-start gap-6">
                    <!-- Profile Photo Display -->
                    <div class="flex-shrink-0">
                        <div class="relative group">
                            <!-- Photo Container with Glass Effect -->
                            <div class="relative bg-white/90 backdrop-blur-sm border-4 border-white/50 rounded-full p-1 shadow-xl group-hover:shadow-2xl transition-all duration-300">
                        <div wire:loading.remove wire:target="profilePhoto">
                            @if($profilePhoto)
                                        <img class="h-32 w-32 rounded-full object-cover" 
                                     src="{{ $profilePhoto->temporaryUrl() }}" 
                                     alt="{{ auth()->user()->name }}" />
                            @else
                                        <img class="h-32 w-32 rounded-full object-cover" 
                                     src="{{ auth()->user()->profile_photo_url }}" 
                                     alt="{{ auth()->user()->name }}" />
                            @endif
                        </div>
                                <div wire:loading wire:target="profilePhoto" class="h-32 w-32 rounded-full flex items-center justify-center bg-gradient-to-br from-blue-100 to-purple-100">
                                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                                </div>
                            </div>
                            
                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 bg-black/20 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                <i class="fas fa-camera text-white text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Photo Controls -->
                    <div class="flex-1">
                        <h3 class="text-xl font-bold bg-gradient-to-r from-gray-900 to-blue-800 bg-clip-text text-transparent mb-2">
                            Profile Photo
                        </h3>
                        <p class="text-gray-600 mb-4">Upload a professional photo to represent your brand</p>
                        
                        <div class="space-y-3">
                            <!-- Upload Button -->
                    <div>
                            <input type="file" wire:model="profilePhoto" class="hidden" id="photo" accept="image/*" />
                                <label for="photo" class="group inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 cursor-pointer">
                                    <i class="fas fa-camera mr-2 group-hover:scale-110 transition-transform"></i>
                                    Change Photo
                            </label>
                        </div>
                            
                            <!-- Loading State -->
                            <div wire:loading wire:target="profilePhoto" class="flex items-center text-blue-600">
                                <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-sm font-medium">Uploading...</span>
                            </div>
                            
                            <!-- Error Message -->
                            @error('profilePhoto') 
                                <div class="flex items-center text-red-600 bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <span class="text-sm font-medium">{{ $message }}</span>
                                </div>
                            @enderror
                            
                            <!-- Help Text -->
                            <p class="text-xs text-gray-500 bg-gray-50/80 backdrop-blur-sm border border-gray-200/50 rounded-lg px-3 py-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Maximum file size: 1MB. Supported formats: JPG, PNG, GIF.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Basic Profile Info -->
            <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 lg:p-8 shadow-lg">
                <!-- Section Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50/20 to-indigo-50/20 rounded-2xl"></div>
                <div class="absolute top-4 right-4 w-16 h-16 bg-blue-400/10 rounded-full blur-lg"></div>
                
                <div class="relative space-y-6">
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-blue-800 bg-clip-text text-transparent mb-6 flex items-center">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        Basic Information
                </h3>
                
                <!-- Name -->
                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-semibold text-gray-700">
                            Full Name <span class="text-red-500">*</span>
                    </label>
                        <div class="relative">
                        <input type="text" wire:model="name" id="name" 
                                   class="w-full px-4 py-3 bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all duration-200 placeholder-gray-400"
                                   placeholder="Enter your full name">
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                        </div>
                        @error('name') 
                            <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                    </div>
                        @enderror
                </div>
                
                <!-- Username -->
                    <div class="space-y-2">
                        <label for="username" class="block text-sm font-semibold text-gray-700">
                        Username <span class="text-red-500">*</span>
                        @if($username_locked)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100/80 text-yellow-800 border border-yellow-200/50 ml-2">
                                    <i class="fas fa-lock mr-1"></i> Locked
                            </span>
                        @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100/80 text-amber-800 border border-amber-200/50 ml-2">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Will lock once set
                            </span>
                        @endif
                    </label>
                        <div class="relative">
                            <div class="flex rounded-xl shadow-sm overflow-hidden">
                                <span class="inline-flex items-center px-4 bg-gray-100/80 backdrop-blur-sm border border-r-0 border-gray-200/50 text-gray-600 text-sm font-medium">
                            @
                        </span>
                        <input type="text" wire:model="username" id="username" 
                                       class="flex-1 px-4 py-3 bg-white/90 backdrop-blur-sm border border-gray-200/50 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all duration-200 placeholder-gray-400 {{ $username_locked ? 'bg-gray-50/80 text-gray-500' : '' }}"
                               placeholder="username"
                               {{ $username_locked ? 'disabled' : '' }}>
                    </div>
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                        </div>
                        <p class="text-xs text-gray-500 bg-gray-50/80 backdrop-blur-sm border border-gray-200/50 rounded-lg px-3 py-2">
                            <i class="fas fa-link mr-1"></i>
                            Your profile URL: {{ config('app.url') }}/@{{ $username ?: 'username' }}
                        </p>
                        @error('username') 
                            <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                            </div>
                        @enderror
                </div>
                
                <!-- Email -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-semibold text-gray-700">
                            Email Address <span class="text-red-500">*</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100/80 text-gray-600 border border-gray-200/50 ml-2">
                                <i class="fas fa-eye-slash mr-1"></i> Private
                            </span>
                    </label>
                        <div class="relative">
                        <input type="email" wire:model="email" id="email" 
                                   class="w-full px-4 py-3 bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all duration-200 placeholder-gray-400"
                                   placeholder="your.email@example.com">
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                        </div>
                        @error('email') 
                            <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                    </div>
                        @enderror
                </div>
                
                <!-- Headline -->
                    <div class="space-y-2">
                        <label for="headline" class="block text-sm font-semibold text-gray-700">
                            Professional Headline
                            <span class="text-gray-500 text-xs font-normal">(One-line description)</span>
                    </label>
                        <div class="relative">
                        <input type="text" wire:model="headline" id="headline" 
                                   class="w-full px-4 py-3 bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all duration-200 placeholder-gray-400"
                               placeholder="e.g. Mixing Engineer & Producer based in LA">
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                        </div>
                        @error('headline') 
                            <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                    </div>
                        @enderror
                </div>
                
                <!-- Bio -->
                    <div class="space-y-2">
                        <label for="bio" class="block text-sm font-semibold text-gray-700">
                            Biography
                            <span class="text-gray-500 text-xs font-normal">(Tell us about yourself)</span>
                    </label>
                        <div class="relative">
                        <textarea wire:model="bio" id="bio" rows="4" 
                                      class="w-full px-4 py-3 bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all duration-200 placeholder-gray-400 resize-none"
                                      placeholder="Share your experience, background, and what makes you unique..."></textarea>
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                        </div>
                        @error('bio') 
                            <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                    </div>
                        @enderror
                </div>
                
                    <!-- Location & Website Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Location -->
                        <div class="space-y-2">
                            <label for="location" class="block text-sm font-semibold text-gray-700">
                        Location
                    </label>
                            <div class="relative">
                        <input type="text" wire:model="location" id="location" 
                                       class="w-full px-4 py-3 bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all duration-200 placeholder-gray-400"
                               placeholder="e.g. Los Angeles, CA">
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                            </div>
                            @error('location') 
                                <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                    </div>
                            @enderror
                </div>
                
                <!-- Website -->
                        <div class="space-y-2">
                            <label for="website" class="block text-sm font-semibold text-gray-700">
                        Website
                    </label>
                            <div class="relative">
                        <input type="text" wire:model="website" id="website" 
                                       class="w-full px-4 py-3 bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all duration-200 placeholder-gray-400"
                               placeholder="yourwebsite.com">
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                    </div>
                            <p class="text-xs text-gray-500 bg-gray-50/80 backdrop-blur-sm border border-gray-200/50 rounded-lg px-3 py-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Your personal website or portfolio URL (https:// will be added automatically)
                            </p>
                            @error('website') 
                                <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>
                </div>
                
                <!-- Tipjar Link -->
                    <div class="space-y-2">
                        <label for="tipjar_link" class="block text-sm font-semibold text-gray-700">
                        Tip Jar Link
                            <span class="text-gray-500 text-xs font-normal">(Optional)</span>
                    </label>
                        <div class="relative">
                        <input type="text" wire:model="tipjar_link" id="tipjar_link" 
                                   class="w-full px-4 py-3 bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all duration-200 placeholder-gray-400"
                               placeholder="paypal.me/yourusername">
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                        </div>
                        <p class="text-xs text-gray-500 bg-gray-50/80 backdrop-blur-sm border border-gray-200/50 rounded-lg px-3 py-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Supported services: PayPal.me, Ko-fi, Buy Me A Coffee, Patreon, Venmo, CashApp, Stripe, GoFundMe, etc.
                        </p>
                        @error('tipjar_link') 
                            <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Professional Skills / Tags -->
            <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 lg:p-8 shadow-lg">
                <!-- Section Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-purple-50/20 to-indigo-50/20 rounded-2xl"></div>
                <div class="absolute top-4 right-4 w-20 h-20 bg-purple-400/10 rounded-full blur-xl"></div>
                <div class="absolute bottom-4 left-4 w-12 h-12 bg-indigo-400/10 rounded-full blur-lg"></div>
                
                <div class="relative space-y-6">
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-purple-800 bg-clip-text text-transparent mb-6 flex items-center">
                        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                            <i class="fas fa-tags text-white text-sm"></i>
                        </div>
                        Skills, Equipment & Specialties
                </h3>
                    
                    <p class="text-gray-600 bg-purple-50/50 backdrop-blur-sm border border-purple-200/50 rounded-xl px-4 py-3">
                        <i class="fas fa-lightbulb text-purple-600 mr-2"></i>
                        Choose up to 6 items in each category to showcase your expertise and attract the right collaborations.
                    </p>
            
                <!-- Alpine/Choices.js component for selects -->
                @php
                // Define tags by type for the select components
                $tagsByType = \App\Models\Tag::all()->groupBy('type')->toArray();
                $maxTags = 6; // Define the maximum number of tags
                @endphp
                
                <div 
                    x-data="tagSelects({
                        allTags: {{ json_encode($allTagsForJs) }},
                        currentSkills: {{ json_encode(array_map('strval', $skills ?? [])) }},
                        currentEquipment: {{ json_encode(array_map('strval', $equipment ?? [])) }},
                        currentSpecialties: {{ json_encode(array_map('strval', $specialties ?? [])) }},
                        maxItems: {{ $maxTags }}
                    })"
                    x-init="initChoices()"
                        class="space-y-8"
                >
                    
                    <!-- Skills -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label for="skills-select" class="block text-sm font-semibold text-gray-700">
                                    <div class="flex items-center">
                                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                            <i class="fas fa-cogs text-white text-xs"></i>
                                        </div>
                                        Skills & Abilities
                                    </div>
                                    <span class="text-gray-500 text-xs font-normal block mt-1">Production, Mixing, Mastering, etc.</span>
                        </label>
                                <div class="flex items-center space-x-2">
                                    <div class="bg-blue-100/80 backdrop-blur-sm border border-blue-200/50 rounded-full px-3 py-1">
                                        <span class="text-xs font-semibold text-blue-800">{{ count($skills ?? []) }}/6</span>
                                    </div>
                                </div>
                            </div>
                            <div wire:ignore class="relative">
                                <div class="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 transition-all duration-200">
                                    <select id="skills-select" multiple="multiple" x-ref="skillsSelect" class="block w-full border-0 focus:ring-0 rounded-xl"></select>
                                </div>
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                            </div>
                            @error('skills') 
                                <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </div>
                            @enderror
                            @error('skills.*') 
                                <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                        </div>
                            @enderror
                    </div>
            
                    <!-- Equipment -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label for="equipment-select" class="block text-sm font-semibold text-gray-700">
                                    <div class="flex items-center">
                                        <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                            <i class="fas fa-microphone text-white text-xs"></i>
                                        </div>
                                        Equipment & Tools
                                    </div>
                                    <span class="text-gray-500 text-xs font-normal block mt-1">DAWs, Instruments, Hardware, etc.</span>
                        </label>
                                <div class="flex items-center space-x-2">
                                    <div class="bg-green-100/80 backdrop-blur-sm border border-green-200/50 rounded-full px-3 py-1">
                                        <span class="text-xs font-semibold text-green-800">{{ count($equipment ?? []) }}/6</span>
                                    </div>
                                </div>
                            </div>
                            <div wire:ignore class="relative">
                                <div class="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus-within:border-green-500 focus-within:ring-2 focus-within:ring-green-500/20 transition-all duration-200">
                                    <select id="equipment-select" multiple="multiple" x-ref="equipmentSelect" class="block w-full border-0 focus:ring-0 rounded-xl"></select>
                                </div>
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-green-500/5 to-teal-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                            </div>
                            @error('equipment') 
                                <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </div>
                            @enderror
                            @error('equipment.*') 
                                <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                        </div>
                            @enderror
                    </div>
            
                    <!-- Specialties -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label for="specialties-select" class="block text-sm font-semibold text-gray-700">
                                    <div class="flex items-center">
                                        <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                            <i class="fas fa-star text-white text-xs"></i>
                                        </div>
                                        Specialties & Genres
                                    </div>
                                    <span class="text-gray-500 text-xs font-normal block mt-1">Genres, Vocal Tuning, etc.</span>
                        </label>
                                <div class="flex items-center space-x-2">
                                    <div class="bg-amber-100/80 backdrop-blur-sm border border-amber-200/50 rounded-full px-3 py-1">
                                        <span class="text-xs font-semibold text-amber-800">{{ count($specialties ?? []) }}/6</span>
                                    </div>
                                </div>
                            </div>
                            <div wire:ignore class="relative">
                                <div class="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-500/20 transition-all duration-200">
                                    <select id="specialties-select" multiple="multiple" x-ref="specialtiesSelect" class="block w-full border-0 focus:ring-0 rounded-xl"></select>
                                </div>
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-amber-500/5 to-orange-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                            </div>
                            @error('specialties') 
                                <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </div>
                            @enderror
                            @error('specialties.*') 
                                <div class="flex items-center text-red-600 text-sm bg-red-50/80 backdrop-blur-sm border border-red-200/50 rounded-lg px-3 py-2">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>
                
                    </div>
                    {{-- End Alpine Component --}}
                </div>
            </div>
            
            <!-- Enhanced Social Media Links -->
            <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 lg:p-8 shadow-lg">
                <!-- Section Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-pink-50/20 to-blue-50/20 rounded-2xl"></div>
                <div class="absolute top-4 right-4 w-16 h-16 bg-pink-400/10 rounded-full blur-lg"></div>
                <div class="absolute bottom-4 left-4 w-20 h-20 bg-blue-400/10 rounded-full blur-xl"></div>
                
                <div class="relative space-y-6">
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-pink-800 bg-clip-text text-transparent mb-6 flex items-center">
                        <div class="bg-gradient-to-r from-pink-500 to-blue-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                            <i class="fas fa-share-alt text-white text-sm"></i>
                        </div>
                        Social Media
                </h3>
                    
                    <p class="text-gray-600 bg-blue-50/50 backdrop-blur-sm border border-blue-200/50 rounded-xl px-4 py-3">
                        <i class="fas fa-link text-blue-600 mr-2"></i>
                        Connect your social media profiles to build your network and showcase your work across platforms.
                    </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Instagram -->
                        <div class="space-y-2">
                            <label for="instagram" class="block text-sm font-semibold text-gray-700">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-r from-pink-500 to-purple-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                        <i class="fab fa-instagram text-white text-xs"></i>
                                    </div>
                                    Instagram
                                </div>
                        </label>
                                                        <div class="relative">
                                <div class="flex rounded-xl shadow-sm overflow-hidden border border-pink-200/50 focus-within:border-pink-500 focus-within:ring-2 focus-within:ring-pink-500/20 transition-all duration-200">
                                    <span class="inline-flex items-center px-4 bg-pink-100/80 backdrop-blur-sm text-pink-700 text-sm font-medium">
                                instagram.com/
                            </span>
                            <input type="text" wire:model="social_links.instagram" id="instagram" placeholder="username" 
                                           class="flex-1 px-4 py-3 bg-white/90 backdrop-blur-sm border-0 focus:ring-0 focus:bg-white transition-all duration-200 placeholder-gray-400">
                                </div>
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-pink-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                        </div>
                    </div>
                    
                    <!-- Facebook -->
                        <div class="space-y-2">
                            <label for="facebook" class="block text-sm font-semibold text-gray-700">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                        <i class="fab fa-facebook text-white text-xs"></i>
                                    </div>
                                    Facebook
                                </div>
                        </label>
                                                        <div class="relative">
                                <div class="flex rounded-xl shadow-sm overflow-hidden border border-blue-200/50 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 transition-all duration-200">
                                    <span class="inline-flex items-center px-4 bg-blue-100/80 backdrop-blur-sm text-blue-700 text-sm font-medium">
                                facebook.com/
                            </span>
                            <input type="text" wire:model="social_links.facebook" id="facebook" placeholder="username" 
                                           class="flex-1 px-4 py-3 bg-white/90 backdrop-blur-sm border-0 focus:ring-0 focus:bg-white transition-all duration-200 placeholder-gray-400">
                                </div>
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-blue-600/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                        </div>
                    </div>
                    
                    <!-- Twitter -->
                        <div class="space-y-2">
                            <label for="twitter" class="block text-sm font-semibold text-gray-700">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-r from-sky-400 to-blue-500 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                        <i class="fab fa-twitter text-white text-xs"></i>
                                    </div>
                                    Twitter
                                </div>
                        </label>
                                                        <div class="relative">
                                <div class="flex rounded-xl shadow-sm overflow-hidden border border-sky-200/50 focus-within:border-sky-500 focus-within:ring-2 focus-within:ring-sky-500/20 transition-all duration-200">
                                    <span class="inline-flex items-center px-4 bg-sky-100/80 backdrop-blur-sm text-sky-700 text-sm font-medium">
                                twitter.com/
                            </span>
                            <input type="text" wire:model="social_links.twitter" id="twitter" placeholder="username" 
                                           class="flex-1 px-4 py-3 bg-white/90 backdrop-blur-sm border-0 focus:ring-0 focus:bg-white transition-all duration-200 placeholder-gray-400">
                                </div>
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-sky-500/5 to-blue-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                        </div>
                    </div>
                    
                    <!-- YouTube -->
                        <div class="space-y-2">
                            <label for="youtube" class="block text-sm font-semibold text-gray-700">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                        <i class="fab fa-youtube text-white text-xs"></i>
                                    </div>
                                    YouTube
                                </div>
                        </label>
                                                        <div class="relative">
                                <div class="flex rounded-xl shadow-sm overflow-hidden border border-red-200/50 focus-within:border-red-500 focus-within:ring-2 focus-within:ring-red-500/20 transition-all duration-200">
                                    <span class="inline-flex items-center px-4 bg-red-100/80 backdrop-blur-sm text-red-700 text-sm font-medium">
                                youtube.com/
                            </span>
                            <input type="text" wire:model="social_links.youtube" id="youtube" placeholder="channel" 
                                           class="flex-1 px-4 py-3 bg-white/90 backdrop-blur-sm border-0 focus:ring-0 focus:bg-white transition-all duration-200 placeholder-gray-400">
                                </div>
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-red-500/5 to-red-600/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                        </div>
                    </div>
                    
                    <!-- SoundCloud -->
                        <div class="space-y-2">
                            <label for="soundcloud" class="block text-sm font-semibold text-gray-700">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                        <i class="fab fa-soundcloud text-white text-xs"></i>
                                    </div>
                                    SoundCloud
                                </div>
                        </label>
                                                        <div class="relative">
                                <div class="flex rounded-xl shadow-sm overflow-hidden border border-orange-200/50 focus-within:border-orange-500 focus-within:ring-2 focus-within:ring-orange-500/20 transition-all duration-200">
                                    <span class="inline-flex items-center px-4 bg-orange-100/80 backdrop-blur-sm text-orange-700 text-sm font-medium">
                                soundcloud.com/
                            </span>
                            <input type="text" wire:model="social_links.soundcloud" id="soundcloud" placeholder="username" 
                                           class="flex-1 px-4 py-3 bg-white/90 backdrop-blur-sm border-0 focus:ring-0 focus:bg-white transition-all duration-200 placeholder-gray-400">
                                </div>
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-orange-500/5 to-orange-600/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                        </div>
                    </div>
                    
                    <!-- Spotify -->
                        <div class="space-y-2">
                            <label for="spotify" class="block text-sm font-semibold text-gray-700">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                        <i class="fab fa-spotify text-white text-xs"></i>
                                    </div>
                                    Spotify
                                </div>
                        </label>
                                                        <div class="relative">
                                <div class="flex rounded-xl shadow-sm overflow-hidden border border-green-200/50 focus-within:border-green-500 focus-within:ring-2 focus-within:ring-green-500/20 transition-all duration-200">
                                    <span class="inline-flex items-center px-4 bg-green-100/80 backdrop-blur-sm text-green-700 text-sm font-medium">
                                open.spotify.com/artist/
                            </span>
                            <input type="text" wire:model="social_links.spotify" id="spotify" placeholder="ID" 
                                           class="flex-1 px-4 py-3 bg-white/90 backdrop-blur-sm border-0 focus:ring-0 focus:bg-white transition-all duration-200 placeholder-gray-400">
                                </div>
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-green-500/5 to-green-600/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Notification Settings -->
            <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 lg:p-8 shadow-lg" x-data="{ notificationsExpanded: false }">
                <!-- Section Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-green-50/20 to-teal-50/20 rounded-2xl"></div>
                <div class="absolute top-4 right-4 w-16 h-16 bg-green-400/10 rounded-full blur-lg"></div>
                <div class="absolute bottom-4 left-4 w-12 h-12 bg-teal-400/10 rounded-full blur-lg"></div>
                
                <div class="relative space-y-6">
                    <!-- Collapsible Header -->
                    <div class="flex items-center justify-between cursor-pointer" @click="notificationsExpanded = !notificationsExpanded">
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-green-800 bg-clip-text text-transparent flex items-center">
                            <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-bell text-white text-sm"></i>
                            </div>
                            Notification Settings
                        </h3>
                        
                        <!-- Toggle Button -->
                        <button type="button" class="flex items-center space-x-2 px-4 py-2 bg-green-100/80 backdrop-blur-sm border border-green-200/50 rounded-xl hover:bg-green-200/80 transition-all duration-200 group">
                            <span class="text-sm font-medium text-green-800" x-text="notificationsExpanded ? 'Collapse' : 'Expand'"></span>
                            <i class="fas fa-chevron-down text-green-600 transition-transform duration-200 group-hover:scale-110" 
                               :class="{ 'rotate-180': notificationsExpanded }"></i>
                        </button>
                    </div>
                    
                    <!-- Collapsed State Info -->
                    <div x-show="!notificationsExpanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <p class="text-gray-600 bg-green-50/50 backdrop-blur-sm border border-green-200/50 rounded-xl px-4 py-3">
                            <i class="fas fa-info-circle text-green-600 mr-2"></i>
                            Click "Expand" to customize how and when you receive notifications for different events.
                        </p>
                    </div>
                    
                    <!-- Expanded Content -->
                    <div x-show="notificationsExpanded" 
                         x-transition:enter="transition ease-out duration-300" 
                         x-transition:enter-start="opacity-0 transform scale-95" 
                         x-transition:enter-end="opacity-100 transform scale-100" 
                         x-transition:leave="transition ease-in duration-200" 
                         x-transition:leave-start="opacity-100 transform scale-100" 
                         x-transition:leave-end="opacity-0 transform scale-95"
                         class="space-y-4">
                        
                        <p class="text-gray-600 bg-green-50/50 backdrop-blur-sm border border-green-200/50 rounded-xl px-4 py-3">
                            <i class="fas fa-cog text-green-600 mr-2"></i>
                            Customize how and when you receive notifications to stay informed without being overwhelmed.
                        </p>
                        
                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4">
                <livewire:user.notification-preferences />
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced License Templates Section -->
            <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg mb-8" 
                 x-data="{ licenseExpanded: false }">
                <!-- Section Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-purple-50/20 to-indigo-50/20 rounded-2xl"></div>
                <div class="absolute top-4 right-4 w-16 h-16 bg-purple-400/10 rounded-full blur-lg"></div>
                <div class="absolute bottom-4 left-4 w-12 h-12 bg-indigo-400/10 rounded-full blur-lg"></div>
                
                <div class="relative space-y-6 p-6 lg:p-8">
                    <!-- Collapsible Header -->
                    <div class="flex items-center justify-between cursor-pointer" @click="licenseExpanded = !licenseExpanded">
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-purple-800 bg-clip-text text-transparent flex items-center">
                            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-file-contract text-white text-sm"></i>
                            </div>
                            License Templates
                        </h3>
                        
                        <!-- Toggle Button -->
                        <button type="button" class="flex items-center space-x-2 px-4 py-2 bg-purple-100/80 backdrop-blur-sm border border-purple-200/50 rounded-xl hover:bg-purple-200/80 transition-all duration-200 group">
                            <span class="text-sm font-medium text-purple-800" x-text="licenseExpanded ? 'Collapse' : 'Expand'"></span>
                            <i class="fas fa-chevron-down text-purple-600 transition-transform duration-200 group-hover:scale-110" 
                               :class="{ 'rotate-180': licenseExpanded }"></i>
                        </button>
                    </div>
                    
                    <!-- Collapsed State Info -->
                    <div x-show="!licenseExpanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <p class="text-gray-600 bg-purple-50/50 backdrop-blur-sm border border-purple-200/50 rounded-xl px-4 py-3">
                            <i class="fas fa-info-circle text-purple-600 mr-2"></i>
                            Click "Expand" to manage your custom license templates for projects. Create, edit, and organize your legal agreements for collaborations.
                        </p>
                    </div>
                    
                    <!-- Expanded Content -->
                    <div x-show="licenseExpanded" 
                         x-transition:enter="transition ease-out duration-300" 
                         x-transition:enter-start="opacity-0 transform scale-95" 
                         x-transition:enter-end="opacity-100 transform scale-100" 
                         x-transition:leave="transition ease-in duration-200" 
                         x-transition:leave-start="opacity-100 transform scale-100" 
                         x-transition:leave-end="opacity-0 transform scale-95"
                         class="space-y-4">
                        
                        <p class="text-gray-600 bg-purple-50/50 backdrop-blur-sm border border-purple-200/50 rounded-xl px-4 py-3">
                            <i class="fas fa-cog text-purple-600 mr-2"></i>
                            Create and manage custom license templates for your music projects. Define terms, permissions, and restrictions for your collaborations.
                        </p>
                        
                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4">
                            <livewire:user.manage-license-templates />
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Save Button -->
            <div class="relative">
                <!-- Background Effects -->
                <div class="absolute inset-0 bg-gradient-to-r from-blue-50/30 to-purple-50/30 rounded-2xl blur-sm"></div>
                
                <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Save Status Info -->
                        <div class="flex-1">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                Your changes will be saved automatically and reflected across your profile immediately.
                            </p>
            </div>
            
            <!-- Save Button -->
                        <div class="flex-shrink-0">
                            <button type="submit" 
                                    class="group relative inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                                <!-- Button Background Effect -->
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-400/20 to-purple-400/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                
                                <!-- Button Content -->
                                <div class="relative flex items-center">
                                    <i class="fas fa-save mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                                    <span>Save Profile</span>
                                </div>
                                
                                <!-- Loading State -->
                                <div wire:loading wire:target="save" class="absolute inset-0 flex items-center justify-center bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl">
                                    <svg class="animate-spin h-5 w-5 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-white font-medium">Saving...</span>
                                </div>
                </button>
                        </div>
                    </div>
                </div>
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
                maxItems: config.maxItems || 6, // Default to 6 if not provided
                choicesInstances: {},
                debounceTimeout: null, // Added for debouncing
    
                initChoices() {
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
                        maxItemCount: this.maxItems, // Limit the number of items
                        maxItemText: (maxItemCount) => { // Custom message when limit is reached
                            return `Only ${maxItemCount} items can be selected`;
                        },
                        classNames: {
                            containerOuter: 'choices',
                            containerInner: 'choices__inner',
                            input: 'choices__input',
                            inputCloned: 'choices__input--cloned',
                            list: 'choices__list',
                            listItems: 'choices__list--multiple',
                            listSingle: 'choices__list--single',
                            listDropdown: 'choices__list--dropdown',
                            item: 'choices__item',
                            itemSelectable: 'choices__item--selectable',
                            itemDisabled: 'choices__item--disabled',
                            itemChoice: 'choices__item--choice',
                            placeholder: 'choices__placeholder',
                            group: 'choices__group',
                            groupHeading: 'choices__heading',
                            button: 'choices__button',
                            activeState: 'is-active',
                            focusState: 'is-focused',
                            openState: 'is-open',
                            disabledState: 'is-disabled',
                            highlightedState: 'is-highlighted',
                            selectedState: 'is-selected',
                            flippedState: 'is-flipped',
                            loadingState: 'is-loading',
                            noResults: 'has-no-results',
                            noChoices: 'has-no-choices'
                        }
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

{{-- Enhanced Choices.js Styling --}}
<style>
    /* Glass Morphism Choices.js Styling */
    .choices {
        position: relative;
        margin-bottom: 0;
        font-size: 14px;
    }
    
    .choices__inner {
        background-color: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(229, 231, 235, 0.5);
        border-radius: 12px;
        color: #374151;
        cursor: text;
        display: inline-block;
        font-size: inherit;
        min-height: 44px;
        overflow: hidden;
        padding: 8px 12px 4px;
        position: relative;
        vertical-align: top;
        width: 100%;
        transition: all 0.2s ease;
    }
    
    .choices.is-focused .choices__inner {
        border-color: rgba(59, 130, 246, 0.5);
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        background-color: rgba(255, 255, 255, 0.95);
    }
    
    .choices__list--multiple .choices__item {
        background-color: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 8px;
        color: #1e40af;
        display: inline-block;
        font-size: 12px;
        font-weight: 500;
        margin-bottom: 4px;
        margin-right: 4px;
        padding: 4px 8px;
        word-break: break-all;
        backdrop-filter: blur(4px);
        transition: all 0.2s ease;
    }
    
    .choices__list--multiple .choices__item:hover {
        background-color: rgba(59, 130, 246, 0.15);
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .choices__list--multiple .choices__item.is-highlighted {
        background-color: rgba(239, 68, 68, 0.1);
        border-color: rgba(239, 68, 68, 0.3);
        color: #dc2626;
    }
    
    .choices__button {
        background-image: url("data:image/svg+xml,%3csvg width='14' height='14' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='m11.596.782 2.122 2.122L9.12 7.499l4.597 4.597-2.122 2.122L7 9.62l-4.595 4.597-2.122-2.122L4.878 7.5.282 2.904 2.404.782l4.595 4.596L11.596.782Z' fill='%23667eea' fill-rule='evenodd'/%3e%3c/svg%3e");
        background-size: 8px;
        border: 0;
        border-left: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 0 8px 8px 0;
        cursor: pointer;
        height: 100%;
        padding: 0;
        position: absolute;
        right: 0;
        top: 0;
        width: 20px;
        opacity: 0.7;
        transition: opacity 0.2s ease;
    }
    
    .choices__button:hover {
        opacity: 1;
    }
    
    .choices__list--dropdown {
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(229, 231, 235, 0.5);
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        margin-top: 4px;
        overflow: hidden;
        position: absolute;
        top: 100%;
        visibility: hidden;
        width: 100%;
        z-index: 1000;
    }
    
    .choices.is-open .choices__list--dropdown {
        visibility: visible;
    }
    
    .choices__list--dropdown .choices__item {
        color: #374151;
        cursor: pointer;
        font-size: 14px;
        padding: 12px 16px;
        transition: all 0.2s ease;
    }
    
    .choices__list--dropdown .choices__item:hover,
    .choices__list--dropdown .choices__item.is-highlighted {
        background-color: rgba(59, 130, 246, 0.1);
        color: #1e40af;
    }
    
    .choices__input {
        background-color: transparent;
        border: 0;
        color: #374151;
        font-size: inherit;
        margin-bottom: 4px;
        margin-right: 4px;
        max-width: 100%;
        outline: 0;
        padding: 4px 0;
    }
    
    .choices__placeholder {
        color: #9ca3af;
        opacity: 1;
    }
    
    /* Equipment specific styling (green theme) */
    #equipment-select + .choices .choices__list--multiple .choices__item {
        background-color: rgba(34, 197, 94, 0.1);
        border-color: rgba(34, 197, 94, 0.3);
        color: #166534;
    }
    
    #equipment-select + .choices .choices__list--multiple .choices__item:hover {
        background-color: rgba(34, 197, 94, 0.15);
    }
    
    #equipment-select + .choices.is-focused .choices__inner {
        border-color: rgba(34, 197, 94, 0.5);
        box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
    }
    
    /* Specialties specific styling (amber theme) */
    #specialties-select + .choices .choices__list--multiple .choices__item {
        background-color: rgba(245, 158, 11, 0.1);
        border-color: rgba(245, 158, 11, 0.3);
        color: #92400e;
    }
    
    #specialties-select + .choices .choices__list--multiple .choices__item:hover {
        background-color: rgba(245, 158, 11, 0.15);
    }
    
    #specialties-select + .choices.is-focused .choices__inner {
        border-color: rgba(245, 158, 11, 0.5);
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
    }
    
    /* Notification Toggle Styles */
    .notification-toggle-input:checked ~ .notification-toggle-dot {
        transform: translateX(1.5rem);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .notification-toggle-input:checked ~ .notification-toggle-bg {
        background: linear-gradient(to right, #34d399, #10b981);
    }
    
    .notification-toggle-group:hover .notification-toggle-dot {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .notification-toggle-group:hover .notification-toggle-bg {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
</style>
@endpush
