<x-layouts.app-sidebar>
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Enhanced Profile Header Container -->
        <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden mb-6">
            <!-- Avatar and basic info section -->
            <div class="relative px-4 sm:px-8 py-6 z-10">
                <!-- Main Profile Layout -->
                <div class="flex flex-col space-y-6">
                    <!-- Top Row: Avatar + Basic Info + Social Links -->
                    <div class="flex flex-col md:flex-row md:items-start gap-6">
                        <!-- Avatar -->
                        <div class="flex-shrink-0 self-center md:self-start">
                            <div class="relative group">
                                <div class="relative bg-white/90 backdrop-blur-sm border-4 border-white/50 rounded-full p-1 shadow-xl group-hover:shadow-2xl transition-all duration-300">
                                    <img class="h-32 w-32 rounded-full object-cover"
                                        src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                                </div>
                                <!-- Subtle hover overlay -->
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-purple-500/5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </div>
                        </div>

                        <!-- Basic Info (flexible center section) -->
                        <div class="flex-grow text-center md:text-left min-w-0">
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 via-blue-800 to-purple-800 bg-clip-text text-transparent">
                                {{ $user->name }}
                            </h1>

                            <div class="text-blue-600 text-xl font-semibold mt-1">
                                @<span>{{ $user->username }}</span>
                            </div>

                            {{-- Enhanced Average Rating Display --}}
                            <div class="mt-3 flex items-center justify-center md:justify-start">
                                @if(isset($ratingData) && $ratingData['count'] > 0)
                                    @php
                                        $average = $ratingData['average'] ?? 0;
                                        $count = $ratingData['count'] ?? 0;
                                    @endphp
                                    
                                    <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl px-3 py-2 shadow-lg">
                                        <span class="text-orange-500 font-bold text-lg">{{ number_format($average, 1) }} ★</span>
                                        <span class="ml-2 text-sm text-gray-600">({{ $count }} {{ Str::plural('rating', $count) }})</span>
                                    </div>
                                @else
                                    <div class="bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 rounded-xl px-3 py-2">
                                        <span class="text-sm text-gray-500 italic">Not rated (0 ratings)</span>
                                    </div>
                                @endif
                            </div>

                            @if($user->headline)
                            <div class="mt-3 text-gray-700 text-lg font-medium bg-white/60 backdrop-blur-sm border border-white/30 rounded-xl px-4 py-2 shadow-sm">
                                {{ $user->headline }}
                            </div>
                            @endif
                        </div>

                        <!-- Social Links (fixed width right section) -->
                        <div class="flex-shrink-0 w-full md:w-auto">
                            <div class="flex flex-wrap justify-center md:justify-end gap-2 sm:gap-3 max-w-xs md:max-w-none mx-auto md:mx-0">
                                @if(isset($user->social_links['instagram']) && $user->social_links['instagram'])
                                <a href="https://instagram.com/{{ $user->social_links['instagram'] }}" target="_blank"
                                    class="group bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl p-2.5 sm:p-3 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110" title="Instagram">
                                    <i class="fab fa-instagram text-xl sm:text-2xl text-pink-500 group-hover:text-pink-600 transition-colors"></i>
                                </a>
                                @endif

                                @if(isset($user->social_links['twitter']) && $user->social_links['twitter'])
                                <a href="https://twitter.com/{{ $user->social_links['twitter'] }}" target="_blank"
                                    class="group bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl p-2.5 sm:p-3 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110" title="Twitter">
                                    <i class="fab fa-twitter text-xl sm:text-2xl text-blue-400 group-hover:text-blue-500 transition-colors"></i>
                                </a>
                                @endif

                                @if(isset($user->social_links['facebook']) && $user->social_links['facebook'])
                                <a href="https://facebook.com/{{ $user->social_links['facebook'] }}" target="_blank"
                                    class="group bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl p-2.5 sm:p-3 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110" title="Facebook">
                                    <i class="fab fa-facebook text-xl sm:text-2xl text-blue-600 group-hover:text-blue-700 transition-colors"></i>
                                </a>
                                @endif

                                @if(isset($user->social_links['soundcloud']) && $user->social_links['soundcloud'])
                                <a href="https://soundcloud.com/{{ $user->social_links['soundcloud'] }}" target="_blank"
                                    class="group bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl p-2.5 sm:p-3 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110" title="SoundCloud">
                                    <i class="fab fa-soundcloud text-xl sm:text-2xl text-orange-500 group-hover:text-orange-600 transition-colors"></i>
                                </a>
                                @endif

                                @if(isset($user->social_links['spotify']) && $user->social_links['spotify'])
                                <a href="https://open.spotify.com/artist/{{ $user->social_links['spotify'] }}"
                                    target="_blank" class="group bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl p-2.5 sm:p-3 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110"
                                    title="Spotify">
                                    <i class="fab fa-spotify text-xl sm:text-2xl text-green-500 group-hover:text-green-600 transition-colors"></i>
                                </a>
                                @endif

                                @if(isset($user->social_links['youtube']) && $user->social_links['youtube'])
                                <a href="https://youtube.com/{{ $user->social_links['youtube'] }}" target="_blank"
                                    class="group bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl p-2.5 sm:p-3 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110" title="YouTube">
                                    <i class="fab fa-youtube text-xl sm:text-2xl text-red-600 group-hover:text-red-700 transition-colors"></i>
                                </a>
                                @endif
                                
                                @if($user->tipjar_link)
                                <a href="{{ $user->tipjar_link }}" target="_blank"
                                    class="group bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl p-2.5 sm:p-3 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110" title="Support {{ $user->name }}">
                                    <i class="fas fa-donate text-xl sm:text-2xl text-green-600 group-hover:text-green-700 transition-colors"></i>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Row: Location/Website Info + Action Buttons -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <!-- Location and Website Info -->
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                            @if($user->location)
                            <div class="flex items-center text-gray-600 bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl px-3 py-2 shadow-sm">
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                    <i class="fas fa-map-marker-alt text-white text-xs"></i>
                                </div>
                                <span class="font-medium">{{ $user->location }}</span>
                            </div>
                            @endif

                            @if($user->website)
                            <div class="flex items-center text-gray-600 bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl px-3 py-2 shadow-sm">
                                <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                    <i class="fas fa-link text-white text-xs"></i>
                                </div>
                                <a href="{{ $user->website }}" target="_blank"
                                    class="hover:underline hover:text-blue-600 font-medium transition-colors duration-200">
                                    {{ parse_url($user->website, PHP_URL_HOST) }}
                                </a>
                            </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        @if($canEdit)
                        <div class="flex flex-col sm:flex-row gap-3 justify-center md:justify-end">
                            <a href="{{ route('profile.edit') }}"
                                class="group inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 min-w-[140px]">
                                <i class="fas fa-pencil-alt mr-2 group-hover:scale-110 transition-transform"></i>
                                <span class="whitespace-nowrap">Edit Profile</span>
                            </a>
                            <a href="{{ route('profile.portfolio') }}"
                                class="group inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 min-w-[140px]">
                                <i class="fas fa-images mr-2 group-hover:scale-110 transition-transform"></i>
                                <span class="whitespace-nowrap">Manage Portfolio</span>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Bio and Skills -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Enhanced Bio Section -->
                @if($user->bio)
                <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg">
                    <!-- Section Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50/20 to-indigo-50/20 rounded-2xl"></div>
                    <div class="absolute top-4 right-4 w-16 h-16 bg-blue-400/10 rounded-full blur-lg"></div>
                    
                    <div class="relative">
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-blue-800 bg-clip-text text-transparent mb-4 flex items-center">
                            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            About
                        </h2>
                        <div class="prose max-w-none">
                            <p class="text-gray-700 whitespace-pre-line leading-relaxed">{{ $user->bio }}</p>
                        </div>
                        
                        <!-- Enhanced Tipjar section when bio exists -->
                        @if($user->tipjar_link)
                        <div class="mt-6 pt-4 border-t border-white/30">
                            <a href="{{ $user->tipjar_link }}" target="_blank"
                                class="group inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                <i class="fas fa-donate mr-2 group-hover:scale-110 transition-transform"></i> 
                                Support {{ $user->name }}
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Enhanced Skills and Expertise -->
                <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg">
                    <!-- Section Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-50/20 to-indigo-50/20 rounded-2xl"></div>
                    <div class="absolute top-4 right-4 w-20 h-20 bg-purple-400/10 rounded-full blur-xl"></div>
                    <div class="absolute bottom-4 left-4 w-12 h-12 bg-indigo-400/10 rounded-full blur-lg"></div>
                    
                    <div class="relative">
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-purple-800 bg-clip-text text-transparent mb-6 flex items-center">
                            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-tags text-white text-sm"></i>
                            </div>
                            Skills & Expertise
                        </h2>
                        
                        @php
                            // Prepare tag groups from the controller data
                            $skillTags = $userTagsGrouped['skill'] ?? collect();
                            $equipmentTags = $userTagsGrouped['equipment'] ?? collect();
                            $specialtyTags = $userTagsGrouped['specialty'] ?? collect();
                        @endphp

                        @if($skillTags->isNotEmpty() && $skillTags->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                    <i class="fas fa-cogs text-white text-xs"></i>
                                </div>
                                Skills
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                {{-- Loop through skill tags --}}
                                @foreach($skillTags as $tag)
                                <span class="inline-flex items-center px-3 py-2 rounded-xl text-sm font-semibold bg-blue-100/80 backdrop-blur-sm border border-blue-200/50 text-blue-800 shadow-sm hover:shadow-md transition-all duration-200 hover:scale-105">
                                    {{ $tag->name }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($equipmentTags->isNotEmpty() && $equipmentTags->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                                <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                    <i class="fas fa-microphone text-white text-xs"></i>
                                </div>
                                Equipment
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                {{-- Loop through equipment tags --}}
                                @foreach($equipmentTags as $tag)
                                <span class="inline-flex items-center px-3 py-2 rounded-xl text-sm font-semibold bg-green-100/80 backdrop-blur-sm border border-green-200/50 text-green-800 shadow-sm hover:shadow-md transition-all duration-200 hover:scale-105">
                                    {{ $tag->name }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($specialtyTags->isNotEmpty() && $specialtyTags->count() > 0)
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                                <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                                    <i class="fas fa-star text-white text-xs"></i>
                                </div>
                                Specialties
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                {{-- Loop through specialty tags --}}
                                @foreach($specialtyTags as $tag)
                                <span class="inline-flex items-center px-3 py-2 rounded-xl text-sm font-semibold bg-amber-100/80 backdrop-blur-sm border border-amber-200/50 text-amber-800 shadow-sm hover:shadow-md transition-all duration-200 hover:scale-105">
                                    {{ $tag->name }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Enhanced empty state --}}
                        @if(($skillTags->isEmpty() || $skillTags->count() === 0) && 
                            ($equipmentTags->isEmpty() || $equipmentTags->count() === 0) && 
                            ($specialtyTags->isEmpty() || $specialtyTags->count() === 0))
                        <div class="text-center py-8">
                            <div class="bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-6">
                                <i class="fas fa-tags text-gray-400 text-2xl mb-3"></i>
                                <p class="text-gray-500 italic">No skills or expertise listed yet.</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column: Projects and Completed Pitches -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Enhanced Portfolio Items -->
                @if(isset($portfolioItems) && count($portfolioItems) > 0)
                <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg">
                    <!-- Section Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/20 to-purple-50/20 rounded-2xl"></div>
                    <div class="absolute top-4 right-4 w-16 h-16 bg-indigo-400/10 rounded-full blur-lg"></div>
                    
                    <div class="relative">
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent mb-6 flex items-center">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-images text-white text-sm"></i>
                            </div>
                            Portfolio
                        </h2>
                        
                        <div class="space-y-6">
                            @foreach($portfolioItems as $item)
                            <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-6 hover:bg-white/80 hover:shadow-lg transition-all duration-300 hover:scale-[1.02]">
                                <h3 class="font-bold text-gray-900 text-xl mb-3 bg-gradient-to-r from-gray-900 to-indigo-700 bg-clip-text text-transparent">{{ $item->title }}</h3>
                                
                                @if($item->description)
                                <p class="text-gray-700 mb-4 leading-relaxed">{{ $item->description }}</p>
                                @endif
                                
                                <div class="mt-4">
                                    @if($item->item_type === \App\Models\PortfolioItem::TYPE_AUDIO && $item->file_path)
                                        <div class="mt-3 audio-container bg-gradient-to-r from-gray-100/80 to-gray-200/80 backdrop-blur-sm border border-gray-200/50 rounded-xl min-h-[54px] shadow-sm">
                                            <audio class="w-full" data-portfolio-audio="{{ $item->id }}">
                                                Your browser does not support the audio element.
                                            </audio>
                                        </div>
                                    @elseif($item->item_type === \App\Models\PortfolioItem::TYPE_YOUTUBE && $item->video_id)
                                        <div class="mt-3 relative w-full pt-[56.25%] bg-white/80 backdrop-blur-sm border border-white/40 rounded-xl overflow-hidden shadow-lg">
                                            <iframe class="absolute top-0 left-0 w-full h-full"
                                                    src="https://www.youtube.com/embed/{{ $item->video_id }}" 
                                                    title="YouTube video player for {{ $item->title }}" 
                                                    frameborder="0" 
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                                    allowfullscreen>
                                            </iframe>
                                        </div>
                                    @elseif($item->item_type === 'external_link' && $item->external_url)
                                        <a href="{{ $item->external_url }}" target="_blank" class="group inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                            <i class="fas fa-external-link-alt mr-2 group-hover:scale-110 transition-transform"></i> 
                                            Visit Link
                                        </a>
                                    @elseif($item->item_type === 'mixpitch_project_link' && $item->linkedProject)
                                        <a href="{{ route('projects.show', $item->linkedProject) }}" class="group inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                            <i class="fas fa-project-diagram mr-2 group-hover:scale-110 transition-transform"></i> 
                                            View Project: {{ $item->linkedProject->name }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                {{-- Enhanced Client Activity Section --}}
                <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden">
                    <!-- Section Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-green-50/20 to-teal-50/20 rounded-2xl"></div>
                    <div class="absolute top-4 right-4 w-16 h-16 bg-green-400/10 rounded-full blur-lg"></div>
                    
                    <div class="relative p-6">
                        <livewire:profile.client-activity-summary :client="$user" />
                    </div>
                </div>

                <!-- Enhanced User's Projects -->
                @if($user->role === \App\Models\User::ROLE_CLIENT && $user->projects->count() > 0)
                <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg">
                    <!-- Section Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50/20 to-cyan-50/20 rounded-2xl"></div>
                    <div class="absolute top-4 right-4 w-20 h-20 bg-blue-400/10 rounded-full blur-xl"></div>
                    <div class="absolute bottom-4 left-4 w-12 h-12 bg-cyan-400/10 rounded-full blur-lg"></div>
                    
                    <div class="relative">
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-blue-800 bg-clip-text text-transparent mb-6 flex items-center">
                            <div class="bg-gradient-to-r from-blue-500 to-cyan-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-project-diagram text-white text-sm"></i>
                            </div>
                            Projects
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($user->projects as $project)
                            <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl overflow-hidden hover:shadow-lg hover:bg-white/80 transition-all duration-300 hover:scale-[1.02] group">
                                <a href="{{ route('projects.show', $project) }}" class="block">
                                    @if($project->image_path)
                                    <div class="h-40 bg-gray-200/80 backdrop-blur-sm overflow-hidden">
                                        <img src="{{ $project->imageUrl }}" alt="{{ $project->name }}"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    </div>
                                    @else
                                    <div class="h-40 bg-gradient-to-r from-blue-500/80 to-indigo-600/80 backdrop-blur-sm flex items-center justify-center group-hover:from-blue-600/80 group-hover:to-indigo-700/80 transition-all duration-300">
                                        <span class="text-white text-lg font-semibold">{{ $project->name }}</span>
                                    </div>
                                    @endif

                                    <div class="p-4">
                                        <h3 class="font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-200">
                                            {{ $project->name }}
                                        </h3>

                                        <div class="flex justify-between items-center mt-3">
                                            <span class="text-sm text-gray-600 font-medium">
                                                {{ $project->genre }}
                                            </span>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm border shadow-sm
                                                @if($project->status === 'completed') bg-green-100/80 border-green-200/50 text-green-800 
                                                @elseif($project->status === 'open') bg-blue-100/80 border-blue-200/50 text-blue-800 
                                                @elseif($project->status === 'in_progress') bg-yellow-100/80 border-yellow-200/50 text-yellow-800 
                                                @else bg-gray-100/80 border-gray-200/50 text-gray-800 @endif">
                                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Enhanced Completed Pitches -->
                @if(count($completedPitches) > 0)
                <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg">
                    <!-- Section Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/20 to-green-50/20 rounded-2xl"></div>
                    <div class="absolute top-4 right-4 w-16 h-16 bg-emerald-400/10 rounded-full blur-lg"></div>
                    <div class="absolute bottom-4 left-4 w-12 h-12 bg-green-400/10 rounded-full blur-lg"></div>
                    
                    <div class="relative">
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-emerald-800 bg-clip-text text-transparent mb-6 flex items-center">
                            <div class="bg-gradient-to-r from-emerald-500 to-green-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-check-circle text-white text-sm"></i>
                            </div>
                            Completed Pitches
                        </h2>

                        <div class="space-y-4">
                            @foreach($completedPitches as $pitch)
                            <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-6 hover:bg-white/80 hover:shadow-lg transition-all duration-300 hover:scale-[1.01]">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-gray-900 text-lg mb-2">
                                            <a href="{{ route('projects.show', $pitch->project) }}"
                                                class="hover:text-emerald-600 transition-colors duration-200">
                                                {{ $pitch->project->name }}
                                            </a>
                                        </h3>

                                        <div class="text-sm text-gray-600 mb-3">
                                            @if($pitch->project->user_id == $user->id)
                                            <span class="font-semibold text-blue-600">Project Owner</span>
                                            @if($pitch->user && $pitch->user->username && $pitch->user->id != $user->id)
                                            - Pitch by
                                            <a href="{{ route('profile.username', $pitch->user->username) }}"
                                                class="font-semibold hover:underline text-emerald-600">
                                                {{ $pitch->user->name }}
                                            </a>
                                            @elseif($pitch->user && $pitch->user->id != $user->id)
                                                - Pitch by {{ $pitch->user->name }} (Profile unavailable)
                                            @endif
                                            @else
                                            <span class="font-semibold text-emerald-600">Pitch Submitter</span>
                                            @if($pitch->project->user && $pitch->project->user->username)
                                            - Project by
                                            <a href="{{ route('profile.username', $pitch->project->user->username) }}"
                                                class="font-semibold hover:underline text-blue-600">
                                                {{ $pitch->project->user->name }}
                                            </a>
                                            @elseif($pitch->project->user)
                                                - Project by {{ $pitch->project->user->name }} (Profile unavailable)
                                            @endif
                                            @endif
                                        </div>

                                        {{-- Enhanced Individual Pitch Rating --}}
                                        <div class="mt-3">
                                            @php $rating = $pitch->getCompletionRating(); @endphp
                                            @if($rating)
                                                <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-lg px-3 py-2 inline-block shadow-sm">
                                                    <span class="text-orange-500 font-bold">{{ number_format($rating, 1) }} ★</span>
                                                </div>
                                            @else
                                                <div class="bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 rounded-lg px-3 py-2 inline-block">
                                                    <span class="text-gray-500 italic text-sm">Not rated</span>
                                                </div>
                                            @endif
                                        </div>

                                        @if($pitch->completion_date)
                                        <div class="text-xs text-gray-500 mt-3 bg-gray-50/80 backdrop-blur-sm border border-gray-200/50 rounded-lg px-3 py-2 inline-block">
                                            <span class="font-medium">Completed on</span>
                                            {{ is_string($pitch->completion_date) ? $pitch->completion_date :
                                            $pitch->completion_date->format('M d, Y') }}
                                        </div>
                                        @endif
                                    </div>

                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100/80 backdrop-blur-sm border border-green-200/50 text-green-800 shadow-sm">
                                        Completed
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Enhanced Profile completeness notification for the profile owner -->
        @if($canEdit && !$user->profile_completed)
        <div class="relative bg-white/90 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg overflow-hidden">
            <!-- Background Effects -->
            <div class="absolute inset-0 bg-gradient-to-br from-yellow-50/30 to-orange-50/30 rounded-2xl"></div>
            <div class="absolute top-4 right-4 w-16 h-16 bg-yellow-400/10 rounded-full blur-lg"></div>
            <div class="absolute bottom-4 left-4 w-12 h-12 bg-orange-400/10 rounded-full blur-lg"></div>
            
            <div class="relative flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="bg-gradient-to-r from-yellow-500 to-orange-600 rounded-xl p-3 shadow-lg">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold bg-gradient-to-r from-gray-900 to-yellow-800 bg-clip-text text-transparent mb-2">
                        Complete Your Profile
                    </h3>
                    <p class="text-gray-700 mb-4 leading-relaxed">
                        Your profile is not complete. Add more information to improve your visibility and chances of getting hired.
                    </p>
                    <a href="{{ route('profile.edit') }}"
                        class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-600 hover:from-yellow-600 hover:to-orange-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                        <i class="fas fa-edit mr-2 group-hover:scale-110 transition-transform"></i>
                        Complete Profile Now
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- TODO: Add Service Packages Section (when servicePackages relationship is implemented) --}}
        {{-- 
        @if($servicePackages->isNotEmpty())
        <div class="mt-10 sm:mt-0 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    Service Packages
                </h3>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($servicePackages as $package)
                        <div class="flex flex-col bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md overflow-hidden transform transition duration-500 hover:scale-105">
                            <div class="p-4 flex flex-col flex-grow">
                                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-2">{{ $package->title }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 flex-grow">{{ Str::limit($package->description, 80) }}</p>
                                
                                <div class="flex justify-between items-center mt-auto pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ Number::currency($package->price, $package->currency) }}</span>
                                    <span class="inline-flex items-center px-3 py-1 border border-transparent rounded-md font-semibold text-xs text-gray-400 uppercase tracking-widest bg-gray-100 dark:bg-gray-700 cursor-not-allowed">View Package</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        --}}
    </div>
</div>
</x-layouts.app-sidebar>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Find all audio elements that need pre-signed URLs
        const audioElements = document.querySelectorAll('[data-portfolio-audio]');
        
        audioElements.forEach(function(audioElement) {
            const portfolioItemId = audioElement.getAttribute('data-portfolio-audio');
            let urlFetched = false;
            
            // Add loading state styling
            audioElement.classList.add('cursor-pointer');
            
            // Add enhanced play button overlay with glass morphism
            const container = audioElement.parentElement;
            const playButton = document.createElement('div');
            playButton.className = 'flex items-center justify-center absolute inset-0';
            playButton.innerHTML = '<button class="bg-white/90 backdrop-blur-sm border border-white/30 hover:bg-white/95 text-blue-600 rounded-full p-4 shadow-xl hover:shadow-2xl transition-all duration-200 hover:scale-110 group">' +
                                  '<i class="fas fa-play text-xl group-hover:scale-110 transition-transform"></i></button>';
                                      
            container.style.position = 'relative';
            container.appendChild(playButton);
            
            // Hide the audio controls initially
            audioElement.controls = false;
            
            // Function to load the audio
            const loadAudio = function() {
                if (urlFetched) return;
                
                // Show enhanced loading spinner
                playButton.innerHTML = '<div class="bg-white/90 backdrop-blur-sm border border-white/30 rounded-full p-4 shadow-xl">' +
                                      '<div class="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-600"></div></div>';
                
                // Fetch the pre-signed URL
                fetch(`/audio-file/${portfolioItemId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.url) {
                            // Set the src attribute with the pre-signed URL
                            audioElement.src = data.url;
                            audioElement.controls = true;
                            audioElement.load(); // Important to reload with the new source
                            audioElement.play(); // Auto-play after loading
                            
                            // Remove the play button overlay
                            container.removeChild(playButton);
                            urlFetched = true;
                        } else {
                            console.error('Failed to get audio URL:', data.error);
                            playButton.innerHTML = '<div class="bg-red-100/90 backdrop-blur-sm border border-red-200/50 text-red-600 px-4 py-2 rounded-xl text-sm font-medium shadow-lg">Error loading audio</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching audio URL:', error);
                        playButton.innerHTML = '<div class="bg-red-100/90 backdrop-blur-sm border border-red-200/50 text-red-600 px-4 py-2 rounded-xl text-sm font-medium shadow-lg">Error loading audio</div>';
                    });
            };
            
            // Add click events to load the audio
            playButton.addEventListener('click', loadAudio);
            audioElement.addEventListener('click', loadAudio);
        });
    });
</script>
@endpush