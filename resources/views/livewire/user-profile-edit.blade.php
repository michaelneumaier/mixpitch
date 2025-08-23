<div>
    <flux:card class="mb-2 bg-white/50 dark:bg-gray-800/50 border border-slate-200 dark:border-slate-700">
        <div class="">

            <!-- Profile Form Header -->
            <div class="mb-4 md:mb-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <!-- Title Section -->
                    <div class="flex-1">
                        <flux:heading size="xl"
                            class="mb-2 bg-gradient-to-r from-gray-900 via-blue-800 to-purple-800 bg-clip-text text-transparent dark:from-gray-100 dark:via-blue-300 dark:to-purple-300">
                            Portfolio Profile
                        </flux:heading>
                        <flux:subheading class="mb-4 text-slate-600 dark:text-slate-400">Customize your profile to showcase your work and talents
                        </flux:subheading>

                        <!-- Portfolio Management Button -->
                        <div class="flex flex-wrap gap-3">
                            <flux:button href="{{ route('profile.portfolio') }}" icon="photo" variant="filled"
                                size="sm">
                                Manage Portfolio Items
                            </flux:button>
                        </div>
                    </div>

                    <!-- Profile Completion Indicator -->
                    <div class="flex-shrink-0">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                            <div class="text-center">
                                <div class="relative inline-flex items-center justify-center">
                                    <!-- Circular Progress Ring -->
                                    <svg class="h-20 w-20 -rotate-90 transform" viewBox="0 0 36 36">
                                        <!-- Background circle -->
                                        <path class="text-gray-200 dark:text-gray-700" stroke="currentColor"
                                            stroke-width="3" fill="none"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        <!-- Progress circle -->
                                        <path class="text-blue-500" stroke="currentColor" stroke-width="3"
                                            fill="none" stroke-linecap="round"
                                            stroke-dasharray="{{ $profile_completion_percentage }}, 100"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    </svg>
                                    <!-- Percentage Text -->
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <flux:text size="lg" class="font-bold">
                                            {{ $profile_completion_percentage }}%</flux:text>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <flux:text size="sm" class="font-medium text-gray-900 dark:text-gray-100">Profile Complete</flux:text>
                                    @if ($profile_completion_percentage < 70)
                                        <flux:badge color="amber" size="sm" icon="exclamation-triangle"
                                            class="mt-1">
                                            {{ 70 - $profile_completion_percentage }}% more to complete
                                        </flux:badge>
                                    @else
                                        <flux:badge color="green" size="sm" icon="check-circle" class="mt-1">
                                            Profile completed!
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            @if (session('success'))
                <flux:callout icon="check-circle" color="green" class="mb-6">
                    <flux:callout.text>{{ session('success') }}</flux:callout.text>
                </flux:callout>
            @endif

            <div x-data="{ shown: false, message: '', error: false }" x-init="Livewire.on('profile-updated', (data) => {
                message = data.message || data.error || '';
                error = data.hasOwnProperty('error');
                shown = true;
                setTimeout(() => shown = false, 5000);
            });">
                <div x-show="shown" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95" class="mb-6">
                    <flux:callout x-bind:color="error ? 'red' : 'green'"
                        x-bind:icon="error ? 'exclamation-circle' : 'check-circle'">
                        <flux:callout.text x-text="message"></flux:callout.text>
                    </flux:callout>
                </div>
            </div>

            <form wire:submit.prevent="save" name="profile-edit-form" id="profile-edit-form" class="space-y-8" 
                @submit="console.log('Form is submitting...')"
                x-data>
                <!-- Profile Photo Section -->
                <flux:card class="mb-4 md:mb-8 bg-white/80 dark:bg-gray-800/80 border border-slate-200 dark:border-slate-700">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                        <!-- Profile Photo Display -->
                        <div class="flex-shrink-0">
                            <div class="group relative">
                                <div wire:loading.remove wire:target="profilePhoto">
                                    @if ($profilePhoto)
                                        <flux:avatar size="xl" src="{{ $profilePhoto->temporaryUrl() }}"
                                            alt="{{ auth()->user()->name }}" />
                                    @else
                                        <flux:avatar size="xl" src="{{ auth()->user()->profile_photo_url }}"
                                            alt="{{ auth()->user()->name }}" />
                                    @endif
                                </div>
                                <div wire:loading wire:target="profilePhoto"
                                    class="flex h-32 w-32 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                    <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Photo Controls -->
                        <div class="flex-1">
                            <flux:heading size="lg" class="mb-2 text-slate-800 dark:text-slate-200">
                                Profile Photo
                            </flux:heading>
                            <flux:subheading class="mb-4 text-slate-600 dark:text-slate-400">Upload a professional photo to represent your brand
                            </flux:subheading>

                            <div class="space-y-3">
                                <!-- Upload Button -->
                                <div>
                                    <input type="file" wire:model="profilePhoto" class="hidden" id="photo"
                                        accept="image/*" />
                                    <flux:button as="label" for="photo" icon="camera" variant="filled"
                                        size="sm" class="cursor-pointer">
                                        Change Photo
                                    </flux:button>
                                </div>

                                <!-- Loading State -->
                                <div wire:loading wire:target="profilePhoto" class="flex items-center text-blue-600">
                                    <svg class="mr-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <flux:text size="sm" class="font-medium">Uploading...</flux:text>
                                </div>

                                <!-- Error Message -->
                                @error('profilePhoto')
                                    <flux:callout icon="exclamation-circle" color="red">
                                        <flux:callout.text>{{ $message }}</flux:callout.text>
                                    </flux:callout>
                                @enderror

                                <!-- Help Text -->
                                <flux:callout icon="information-circle" color="zinc">
                                    <flux:callout.text>Maximum file size: 1MB. Supported formats: JPG, PNG, GIF.
                                    </flux:callout.text>
                                </flux:callout>
                            </div>
                        </div>
                    </div>
                </flux:card>

                <!-- Basic Profile Info -->
                <flux:card class="space-y-6 bg-white/80 dark:bg-gray-800/80 border border-slate-200 dark:border-slate-700">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 p-2 shadow-md">
                            <flux:icon name="user" class="text-white" size="lg" />
                        </div>
                        <flux:heading size="lg"
                            class="bg-gradient-to-r from-gray-900 to-blue-800 bg-clip-text text-transparent dark:from-gray-100 dark:via-blue-300 dark:to-indigo-300">
                            Basic Information
                        </flux:heading>
                    </div>

                    <!-- Name -->
                    <flux:field>
                        <flux:label>Full Name <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="name" placeholder="Enter your full name" />
                        <flux:error name="name" />
                    </flux:field>

                    <!-- Username -->
                    <flux:field>
                        <flux:label>
                            Username <span class="text-red-500">*</span>
                            @if ($username_locked)
                                <flux:badge color="amber" size="sm" icon="lock-closed" class="ml-2">
                                    Locked
                                </flux:badge>
                            @else
                                <flux:badge color="amber" size="sm" icon="exclamation-triangle"
                                    class="ml-2">
                                    Will lock once set
                                </flux:badge>
                            @endif
                        </flux:label>
                        <div class="flex overflow-hidden rounded-lg shadow-sm">
                            <span
                                class="inline-flex items-center border border-r-0 border-gray-300 bg-gray-100 px-4 text-sm font-medium text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                @
                            </span>
                            <flux:input wire:model="username" placeholder="username"
                                class="rounded-l-none border-l-0" :disabled="$username_locked" />
                        </div>
                        <flux:description>Your profile URL: {{ config('app.url') }}/{{ '@' . ($username ?: 'username') }}
                        </flux:description>
                        <flux:error name="username" />
                    </flux:field>

                    <!-- Email -->
                    <flux:field>
                        <flux:label>
                            Email Address <span class="text-red-500">*</span>
                            <flux:badge color="zinc" size="sm" icon="eye-slash" class="ml-2">
                                Private
                            </flux:badge>
                        </flux:label>
                        <flux:input type="email" wire:model="email" placeholder="your.email@example.com" />
                        <flux:error name="email" />
                    </flux:field>

                    <!-- Headline -->
                    <flux:field>
                        <flux:label>Professional Headline</flux:label>
                        <flux:input wire:model="headline" placeholder="e.g. Mixing Engineer & Producer based in LA" />
                        <flux:description>One-line description</flux:description>
                        <flux:error name="headline" />
                    </flux:field>

                    <!-- Bio -->
                    <flux:field>
                        <flux:label>Biography</flux:label>
                        <flux:textarea wire:model="bio" rows="4"
                            placeholder="Share your experience, background, and what makes you unique..." />
                        <flux:description>Tell us about yourself</flux:description>
                        <flux:error name="bio" />
                    </flux:field>

                    <!-- Location, Timezone & Website Row -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <!-- Location -->
                        <div class="space-y-2">
                            <label for="location" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Location
                            </label>
                            <div class="relative">
                                <input type="text" wire:model="location" id="location"
                                    class="w-full rounded-xl border border-gray-200/50 dark:border-gray-600/50 bg-white/90 dark:bg-gray-800/90 px-4 py-3 placeholder-gray-400 dark:placeholder-gray-500 shadow-sm backdrop-blur-sm transition-all duration-200 focus:border-blue-500 focus:bg-white dark:focus:bg-gray-800 focus:ring-2 focus:ring-blue-500/20 text-gray-900 dark:text-gray-100"
                                    placeholder="e.g. Los Angeles, CA">
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 opacity-0 transition-opacity duration-200 focus-within:opacity-100">
                                </div>
                            </div>
                            @error('location')
                                <div
                                    class="flex items-center rounded-lg border border-red-200/50 bg-red-50/80 px-3 py-2 text-sm text-red-600 backdrop-blur-sm">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Timezone -->
                        <div class="space-y-2">
                            <label for="timezone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <div class="flex items-center">
                                    <div
                                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-lg bg-gradient-to-r from-indigo-500 to-indigo-600 p-1.5 shadow-md">
                                        <i class="fas fa-clock text-xs text-white"></i>
                                    </div>
                                    Timezone
                                </div>
                            </label>
                            <div class="relative">
                                <select wire:model="timezone" id="timezone"
                                    class="w-full rounded-xl border border-gray-200/50 dark:border-gray-600/50 bg-white/90 dark:bg-gray-800/90 px-4 py-3 shadow-sm backdrop-blur-sm transition-all duration-200 focus:border-indigo-500 focus:bg-white dark:focus:bg-gray-800 focus:ring-2 focus:ring-indigo-500/20 text-gray-900 dark:text-gray-100">
                                    @foreach ($this->getTimezoneOptions() as $tz => $label)
                                        <option value="{{ $tz }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-r from-indigo-500/5 to-purple-500/5 opacity-0 transition-opacity duration-200 focus-within:opacity-100">
                                </div>
                            </div>
                            <p
                                class="rounded-lg border border-indigo-200/50 dark:border-indigo-700/50 bg-indigo-50/80 dark:bg-indigo-900/80 px-3 py-2 text-xs text-gray-500 dark:text-gray-400 backdrop-blur-sm">
                                <i class="fas fa-info-circle mr-1"></i>
                                All project deadlines and times will be shown in your timezone
                            </p>
                            @error('timezone')
                                <div
                                    class="flex items-center rounded-lg border border-red-200/50 bg-red-50/80 px-3 py-2 text-sm text-red-600 backdrop-blur-sm">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Website -->
                        <div class="space-y-2">
                            <label for="website" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Website
                            </label>
                            <div class="relative">
                                <input type="text" wire:model="website" id="website"
                                    class="w-full rounded-xl border border-gray-200/50 dark:border-gray-600/50 bg-white/90 dark:bg-gray-800/90 px-4 py-3 placeholder-gray-400 dark:placeholder-gray-500 shadow-sm backdrop-blur-sm transition-all duration-200 focus:border-blue-500 focus:bg-white dark:focus:bg-gray-800 focus:ring-2 focus:ring-blue-500/20 text-gray-900 dark:text-gray-100"
                                    placeholder="yourwebsite.com">
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 opacity-0 transition-opacity duration-200 focus-within:opacity-100">
                                </div>
                            </div>
                            <p
                                class="rounded-lg border border-gray-200/50 dark:border-gray-600/50 bg-gray-50/80 dark:bg-gray-700/80 px-3 py-2 text-xs text-gray-500 dark:text-gray-400 backdrop-blur-sm">
                                <i class="fas fa-info-circle mr-1"></i>
                                Your personal website or portfolio URL (https:// will be added automatically)
                            </p>
                            @error('website')
                                <div
                                    class="flex items-center rounded-lg border border-red-200/50 bg-red-50/80 px-3 py-2 text-sm text-red-600 backdrop-blur-sm">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Tipjar Link -->
                    <div class="space-y-2">
                        <label for="tipjar_link" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Tip Jar Link
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(Optional)</span>
                        </label>
                        <div class="relative">
                            <input type="text" wire:model="tipjar_link" id="tipjar_link"
                                class="w-full rounded-xl border border-gray-200/50 dark:border-gray-600/50 bg-white/90 dark:bg-gray-800/90 px-4 py-3 placeholder-gray-400 dark:placeholder-gray-500 shadow-sm backdrop-blur-sm transition-all duration-200 focus:border-blue-500 focus:bg-white dark:focus:bg-gray-800 focus:ring-2 focus:ring-blue-500/20 text-gray-900 dark:text-gray-100"
                                placeholder="paypal.me/yourusername">
                            <div
                                class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 opacity-0 transition-opacity duration-200 focus-within:opacity-100">
                            </div>
                        </div>
                        <p
                            class="rounded-lg border border-gray-200/50 bg-gray-50/80 px-3 py-2 text-xs text-gray-500 backdrop-blur-sm">
                            <i class="fas fa-info-circle mr-1"></i>
                            Supported services: PayPal.me, Ko-fi, Buy Me A Coffee, Patreon, Venmo, CashApp, Stripe,
                            GoFundMe, etc.
                        </p>
                        @error('tipjar_link')
                            <div
                                class="flex items-center rounded-lg border border-red-200/50 bg-red-50/80 px-3 py-2 text-sm text-red-600 backdrop-blur-sm">
                                <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>
                </flux:card>
        </div>
    </flux:card>

    <!-- Enhanced Professional Skills / Tags -->
    <flux:card class="mb-4 bg-white/50 dark:bg-gray-800/50 border border-slate-200 dark:border-slate-700">
        <div class="mb-6 flex items-center gap-3">
            <div class="rounded-lg bg-gradient-to-r from-purple-500 to-indigo-600 p-2 shadow-md">
                <flux:icon name="tag" class="text-white" size="lg" />
            </div>
            <flux:heading size="lg"
                class="bg-gradient-to-r from-gray-900 to-purple-800 bg-clip-text text-transparent dark:from-gray-100 dark:via-purple-300 dark:to-indigo-300">
                Skills, Equipment & Specialties
            </flux:heading>
        </div>

        <flux:callout icon="light-bulb" color="zinc" class="mb-6">
            <flux:callout.text>Choose up to 6 items in each category to showcase your expertise and attract the right
                collaborations.</flux:callout.text>
        </flux:callout>

        <!-- Alpine/Choices.js component for selects -->
        @php
            // Define tags by type for the select components
            $tagsByType = \App\Models\Tag::all()->groupBy('type')->toArray();
            $maxTags = 6; // Define the maximum number of tags
        @endphp

        <div x-data="tagSelects({
            allTags: {{ json_encode($allTagsForJs) }},
            currentSkills: {{ json_encode(array_map('strval', $skills ?? [])) }},
            currentEquipment: {{ json_encode(array_map('strval', $equipment ?? [])) }},
            currentSpecialties: {{ json_encode(array_map('strval', $specialties ?? [])) }},
            maxItems: {{ $maxTags }}
        })" x-init="initChoices()" class="space-y-8">

            <!-- Skills -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:label for="skills-select" class="flex items-start gap-2">
                        <div class="rounded-lg bg-gradient-to-r from-blue-500 to-purple-600 p-1.5 shadow-md">
                            <flux:icon name="cog-6-tooth" class="text-white" size="sm" />
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Skills & Abilities</span>
                            <flux:description class="text-xs">Production, Mixing, Mastering, etc.</flux:description>
                        </div>
                    </flux:label>
                    <flux:badge color="blue" size="sm">
                        {{ count($skills ?? []) }}/6
                    </flux:badge>
                </div>
                <div wire:ignore class="relative">
                    <div
                        class="rounded-xl border border-gray-200/50 bg-white/90 shadow-sm backdrop-blur-sm transition-all duration-200 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20">
                        <select id="skills-select" multiple="multiple" x-ref="skillsSelect"
                            class="block w-full rounded-xl border-0 focus:ring-0"></select>
                    </div>
                    <div
                        class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 opacity-0 transition-opacity duration-200 focus-within:opacity-100">
                    </div>
                </div>
                @error('skills')
                    <flux:callout icon="exclamation-circle" color="red" size="sm">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror
                @error('skills.*')
                    <flux:callout icon="exclamation-circle" color="red" size="sm">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror
            </div>

            <!-- Equipment -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:label for="equipment-select" class="flex items-start gap-2">
                        <div class="rounded-lg bg-gradient-to-r from-green-500 to-teal-600 p-1.5 shadow-md">
                            <flux:icon name="microphone" class="text-white" size="sm" />
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Equipment & Tools</span>
                            <flux:description class="text-xs">DAWs, Instruments, Hardware, etc.</flux:description>
                        </div>
                    </flux:label>
                    <flux:badge color="green" size="sm">
                        {{ count($equipment ?? []) }}/6
                    </flux:badge>
                </div>
                <div wire:ignore class="relative">
                    <div
                        class="rounded-xl border border-gray-200/50 bg-white/90 shadow-sm backdrop-blur-sm transition-all duration-200 focus-within:border-green-500 focus-within:ring-2 focus-within:ring-green-500/20">
                        <select id="equipment-select" multiple="multiple" x-ref="equipmentSelect"
                            class="block w-full rounded-xl border-0 focus:ring-0"></select>
                    </div>
                    <div
                        class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-r from-green-500/5 to-teal-500/5 opacity-0 transition-opacity duration-200 focus-within:opacity-100">
                    </div>
                </div>
                @error('equipment')
                    <flux:callout icon="exclamation-circle" color="red" size="sm">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror
                @error('equipment.*')
                    <flux:callout icon="exclamation-circle" color="red" size="sm">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror
            </div>

            <!-- Specialties -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:label for="specialties-select" class="flex items-start gap-2">
                        <div class="rounded-lg bg-gradient-to-r from-amber-500 to-orange-600 p-1.5 shadow-md">
                            <flux:icon name="star" class="text-white" size="sm" />
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Specialties & Genres</span>
                            <flux:description class="text-xs">Genres, Vocal Tuning, etc.</flux:description>
                        </div>
                    </flux:label>
                    <flux:badge color="amber" size="sm">
                        {{ count($specialties ?? []) }}/6
                    </flux:badge>
                </div>
                <div wire:ignore class="relative">
                    <div
                        class="rounded-xl border border-gray-200/50 bg-white/90 shadow-sm backdrop-blur-sm transition-all duration-200 focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-500/20">
                        <select id="specialties-select" multiple="multiple" x-ref="specialtiesSelect"
                            class="block w-full rounded-xl border-0 focus:ring-0"></select>
                    </div>
                    <div
                        class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-r from-amber-500/5 to-orange-500/5 opacity-0 transition-opacity duration-200 focus-within:opacity-100">
                    </div>
                </div>
                @error('specialties')
                    <flux:callout icon="exclamation-circle" color="red" size="sm">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror
                @error('specialties.*')
                    <flux:callout icon="exclamation-circle" color="red" size="sm">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror
            </div>

        </div>
        {{-- End Alpine Component --}}
    </flux:card>

    <!-- Enhanced Social Media Links -->
    <flux:card class="mb-4 bg-white/50 dark:bg-gray-800/50 border border-slate-200 dark:border-slate-700">
        <div class="mb-6 flex items-center gap-3">
            <div class="rounded-lg bg-gradient-to-r from-pink-500 to-blue-600 p-2 shadow-md">
                <flux:icon name="share" class="text-white" size="lg" />
            </div>
            <flux:heading size="lg"
                class="bg-gradient-to-r from-gray-900 to-pink-800 bg-clip-text text-transparent dark:from-gray-100 dark:via-pink-300 dark:to-blue-300">
                Social Media
            </flux:heading>
        </div>

        <flux:callout icon="link" color="zinc" class="mb-6">
            <flux:callout.text>Connect your social media profiles to build your network and showcase your work across
                platforms.</flux:callout.text>
        </flux:callout>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Instagram -->
            <flux:field>
                <flux:label class="flex items-center gap-2">
                    <div class="rounded-lg bg-gradient-to-r from-pink-500 to-purple-600 p-1.5 shadow-md">
                        <i class="fab fa-instagram text-xs text-white"></i>
                    </div>
                    Instagram
                </flux:label>
                <flux:input wire:model="social_links.instagram" placeholder="username">
                    <x-slot name="iconLeading">
                        <flux:text size="sm" class="font-medium text-pink-700">instagram.com/</flux:text>
                    </x-slot>
                </flux:input>
            </flux:field>

            <!-- Facebook -->
            <flux:field>
                <flux:label class="flex items-center gap-2">
                    <div class="rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 p-1.5 shadow-md">
                        <i class="fab fa-facebook text-xs text-white"></i>
                    </div>
                    Facebook
                </flux:label>
                <flux:input wire:model="social_links.facebook" placeholder="username">
                    <x-slot name="iconLeading">
                        <flux:text size="sm" class="font-medium text-blue-700">facebook.com/</flux:text>
                    </x-slot>
                </flux:input>
            </flux:field>

            <!-- Twitter -->
            <flux:field>
                <flux:label class="flex items-center gap-2">
                    <div class="rounded-lg bg-gradient-to-r from-sky-400 to-blue-500 p-1.5 shadow-md">
                        <i class="fab fa-twitter text-xs text-white"></i>
                    </div>
                    Twitter
                </flux:label>
                <flux:input wire:model="social_links.twitter" placeholder="username">
                    <x-slot name="iconLeading">
                        <flux:text size="sm" class="font-medium text-sky-700">twitter.com/</flux:text>
                    </x-slot>
                </flux:input>
            </flux:field>

            <!-- YouTube -->
            <flux:field>
                <flux:label class="flex items-center gap-2">
                    <div class="rounded-lg bg-gradient-to-r from-red-500 to-red-600 p-1.5 shadow-md">
                        <i class="fab fa-youtube text-xs text-white"></i>
                    </div>
                    YouTube
                </flux:label>
                <flux:input wire:model="social_links.youtube" placeholder="channel">
                    <x-slot name="iconLeading">
                        <flux:text size="sm" class="font-medium text-red-700">youtube.com/</flux:text>
                    </x-slot>
                </flux:input>
            </flux:field>

            <!-- SoundCloud -->
            <flux:field>
                <flux:label class="flex items-center gap-2">
                    <div class="rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 p-1.5 shadow-md">
                        <i class="fab fa-soundcloud text-xs text-white"></i>
                    </div>
                    SoundCloud
                </flux:label>
                <flux:input wire:model="social_links.soundcloud" placeholder="username">
                    <x-slot name="iconLeading">
                        <flux:text size="sm" class="font-medium text-orange-700">soundcloud.com/</flux:text>
                    </x-slot>
                </flux:input>
            </flux:field>

            <!-- Spotify -->
            <flux:field>
                <flux:label class="flex items-center gap-2">
                    <div class="rounded-lg bg-gradient-to-r from-green-500 to-green-600 p-1.5 shadow-md">
                        <i class="fab fa-spotify text-xs text-white"></i>
                    </div>
                    Spotify
                </flux:label>
                <flux:input wire:model="social_links.spotify" placeholder="ID">
                    <x-slot name="iconLeading">
                        <flux:text size="sm" class="font-medium text-green-700">open.spotify.com/artist/
                        </flux:text>
                    </x-slot>
                </flux:input>
            </flux:field>
        </div>
    </flux:card>

    <!-- Enhanced Notification Settings -->
    <flux:card class="mb-4 bg-white/50 dark:bg-gray-800/50 border border-slate-200 dark:border-slate-700" x-data="{ notificationsExpanded: false }">
        <!-- Collapsible Header -->
        <div class="flex cursor-pointer items-center justify-between"
            @click="notificationsExpanded = !notificationsExpanded">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-gradient-to-r from-green-500 to-teal-600 p-2 shadow-md">
                    <flux:icon name="bell" class="text-white" size="lg" />
                </div>
                <flux:heading size="lg"
                    class="bg-gradient-to-r from-gray-900 to-green-800 bg-clip-text text-transparent dark:from-gray-100 dark:via-green-300 dark:to-teal-300">
                    Notification Settings
                </flux:heading>
            </div>

            <!-- Toggle Button -->
            <flux:button type="button" variant="ghost" size="sm" class="group">
                <span x-text="notificationsExpanded ? 'Collapse' : 'Expand'"></span>
                <flux:icon name="chevron-down" class="ml-1 transition-transform duration-200"
                    ::class="{ 'rotate-180': notificationsExpanded }" />
            </flux:button>
        </div>

        <!-- Collapsed State Info -->
        <div x-show="!notificationsExpanded" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            <flux:callout icon="information-circle" color="zinc" class="mt-4">
                <flux:callout.text>Click "Expand" to customize how and when you receive notifications for different
                    events.
                </flux:callout.text>
            </flux:callout>
        </div>

        <!-- Expanded Content -->
        <div x-show="notificationsExpanded" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95" class="mt-4 space-y-4">

            <flux:callout icon="cog-6-tooth" color="zinc">
                <flux:callout.text>Customize how and when you receive notifications to stay informed without being
                    overwhelmed.</flux:callout.text>
            </flux:callout>

            <div class="rounded-xl border border-white/40 dark:border-gray-600/40 bg-white/60 dark:bg-gray-800/60 p-4 backdrop-blur-sm">
                <livewire:user.notification-preferences />
            </div>
        </div>
    </flux:card>

    <!-- Enhanced Save Button -->
    <div class="relative">
        <!-- Background Effects -->
        <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-blue-50/30 to-purple-50/30 blur-sm"></div>

        <div class="relative rounded-2xl border border-white/30 dark:border-gray-600/30 bg-white/80 dark:bg-gray-800/80 p-2 shadow-lg backdrop-blur-sm md:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Save Status Info -->
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                        Your changes will be saved automatically and reflected across your profile immediately.
                    </p>
                </div>

                <!-- Save Button -->
                <div class="flex-shrink-0">
                    <flux:button type="submit" icon="check" variant="filled" wire:loading.attr="disabled">
                        <span wire:loading.remove>Save Profile</span>
                        <span wire:loading>Saving...</span>
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
    </form>

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
                                this.choicesInstances.skills = this.createChoices(this.$refs.skillsSelect, 'skill',
                                    this.skills);
                                this.choicesInstances.equipment = this.createChoices(this.$refs.equipmentSelect,
                                    'equipment', this.equipment);
                                this.choicesInstances.specialties = this.createChoices(this.$refs.specialtiesSelect,
                                    'specialty', this.specialties);
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

            .dark .choices__inner {
                background-color: rgba(31, 41, 55, 0.9);
                border-color: rgba(75, 85, 99, 0.5);
                color: #f9fafb;
            }

            .choices.is-focused .choices__inner {
                border-color: rgba(59, 130, 246, 0.5);
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
                background-color: rgba(255, 255, 255, 0.95);
            }

            .dark .choices.is-focused .choices__inner {
                background-color: rgba(31, 41, 55, 0.95);
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

            .dark .choices__list--dropdown {
                background-color: rgba(31, 41, 55, 0.95);
                border-color: rgba(75, 85, 99, 0.5);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
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

            .dark .choices__list--dropdown .choices__item {
                color: #f9fafb;
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

            .dark .choices__input {
                color: #f9fafb;
            }

            .choices__placeholder {
                color: #9ca3af;
                opacity: 1;
            }

            /* Equipment specific styling (green theme) */
            #equipment-select+.choices .choices__list--multiple .choices__item {
                background-color: rgba(34, 197, 94, 0.1);
                border-color: rgba(34, 197, 94, 0.3);
                color: #166534;
            }

            #equipment-select+.choices .choices__list--multiple .choices__item:hover {
                background-color: rgba(34, 197, 94, 0.15);
            }

            #equipment-select+.choices.is-focused .choices__inner {
                border-color: rgba(34, 197, 94, 0.5);
                box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
            }

            /* Specialties specific styling (amber theme) */
            #specialties-select+.choices .choices__list--multiple .choices__item {
                background-color: rgba(245, 158, 11, 0.1);
                border-color: rgba(245, 158, 11, 0.3);
                color: #92400e;
            }

            #specialties-select+.choices .choices__list--multiple .choices__item:hover {
                background-color: rgba(245, 158, 11, 0.15);
            }

            #specialties-select+.choices.is-focused .choices__inner {
                border-color: rgba(245, 158, 11, 0.5);
                box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
            }

            /* Notification Toggle Styles */
            .notification-toggle-input:checked~.notification-toggle-dot {
                transform: translateX(1.5rem);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            .notification-toggle-input:checked~.notification-toggle-bg {
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
</div>
