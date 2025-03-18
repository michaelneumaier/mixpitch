<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Profile content container -->
            <div class="bg-white shadow-xl sm:rounded-lg overflow-hidden">
                <!-- Avatar and basic info section -->
                <div class="px-4 sm:px-8 py-6 relative z-10">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start">
                        <!-- Avatar -->
                        <div class="flex-shrink-0 mb-4 sm:mb-0 sm:mr-6">
                            <img class="h-32 w-32 rounded-full ring-4 ring-white object-cover shadow-md"
                                src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                        </div>

                        <!-- Basic info -->
                        <div class="text-center sm:text-left flex-grow">
                            <h1 class="text-2xl font-bold text-gray-900">
                                {{ $user->name }}
                            </h1>

                            <div class="text-primary text-lg font-medium">
                                @<span>{{ $user->username }}</span>
                            </div>

                            @if($user->headline)
                            <div class="mt-2 text-gray-600">
                                {{ $user->headline }}
                            </div>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center justify-center sm:justify-start gap-3">
                                @if($user->location)
                                <div class="flex items-center text-gray-500">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    <span>{{ $user->location }}</span>
                                </div>
                                @endif

                                @if($user->website)
                                <div class="flex items-center text-gray-500">
                                    <i class="fas fa-link mr-1"></i>
                                    <a href="{{ $user->website }}" target="_blank"
                                        class="hover:underline hover:text-primary">
                                        {{ parse_url($user->website, PHP_URL_HOST) }}
                                    </a>
                                </div>
                                @endif

                                @if($canEdit)
                                <a href="{{ route('profile.edit') }}"
                                    class="inline-flex items-center px-3 py-1 bg-gray-100 border border-gray-300 rounded-full text-sm text-gray-700 hover:bg-gray-200">
                                    <i class="fas fa-pencil-alt mr-1"></i>
                                    Edit Profile
                                </a>
                                @endif
                            </div>
                        </div>

                        <!-- Social links -->
                        <div class="mt-4 sm:mt-0 flex flex-wrap justify-center sm:justify-end gap-3">
                            @if(isset($user->social_links['instagram']) && $user->social_links['instagram'])
                            <a href="https://instagram.com/{{ $user->social_links['instagram'] }}" target="_blank"
                                class="text-pink-500 hover:text-pink-600 transition-colors" title="Instagram">
                                <i class="fab fa-instagram text-2xl"></i>
                            </a>
                            @endif

                            @if(isset($user->social_links['twitter']) && $user->social_links['twitter'])
                            <a href="https://twitter.com/{{ $user->social_links['twitter'] }}" target="_blank"
                                class="text-blue-400 hover:text-blue-500 transition-colors" title="Twitter">
                                <i class="fab fa-twitter text-2xl"></i>
                            </a>
                            @endif

                            @if(isset($user->social_links['facebook']) && $user->social_links['facebook'])
                            <a href="https://facebook.com/{{ $user->social_links['facebook'] }}" target="_blank"
                                class="text-blue-600 hover:text-blue-700 transition-colors" title="Facebook">
                                <i class="fab fa-facebook text-2xl"></i>
                            </a>
                            @endif

                            @if(isset($user->social_links['soundcloud']) && $user->social_links['soundcloud'])
                            <a href="https://soundcloud.com/{{ $user->social_links['soundcloud'] }}" target="_blank"
                                class="text-orange-500 hover:text-orange-600 transition-colors" title="SoundCloud">
                                <i class="fab fa-soundcloud text-2xl"></i>
                            </a>
                            @endif

                            @if(isset($user->social_links['spotify']) && $user->social_links['spotify'])
                            <a href="https://open.spotify.com/artist/{{ $user->social_links['spotify'] }}"
                                target="_blank" class="text-green-500 hover:text-green-600 transition-colors"
                                title="Spotify">
                                <i class="fab fa-spotify text-2xl"></i>
                            </a>
                            @endif

                            @if(isset($user->social_links['youtube']) && $user->social_links['youtube'])
                            <a href="https://youtube.com/{{ $user->social_links['youtube'] }}" target="_blank"
                                class="text-red-600 hover:text-red-700 transition-colors" title="YouTube">
                                <i class="fab fa-youtube text-2xl"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Bio and Skills -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Bio Section -->
                    @if($user->bio)
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">About</h2>
                        <div class="prose max-w-none">
                            <p class="text-gray-600 whitespace-pre-line">{{ $user->bio }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Skills and Expertise -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Skills & Expertise</h2>

                        @if(count($user->skills ?? []) > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-800 mb-3">Skills</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($user->skills as $skill)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    {{ $skill }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if(count($user->equipment ?? []) > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-800 mb-3">Equipment</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($user->equipment as $item)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    {{ $item }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if(count($user->specialties ?? []) > 0)
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-3">Specialties</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($user->specialties as $specialty)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                    {{ $specialty }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if(empty($user->skills) && empty($user->equipment) && empty($user->specialties))
                        <p class="text-gray-500 italic">No skills or expertise listed yet.</p>
                        @endif
                    </div>
                </div>

                <!-- Right Column: Projects and Completed Pitches -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- User's Projects -->
                    @if(count($projects) > 0)
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Projects</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($projects as $project)
                            <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition">
                                <a href="{{ route('projects.show', $project) }}" class="block">
                                    @if($project->image_path)
                                    <div class="h-40 bg-gray-200 overflow-hidden">
                                        <img src="{{ $project->imageUrl }}" alt="{{ $project->name }}"
                                            class="w-full h-full object-cover">
                                    </div>
                                    @else
                                    <div
                                        class="h-40 bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                                        <span class="text-white text-lg font-medium">{{ $project->name }}</span>
                                    </div>
                                    @endif

                                    <div class="p-4">
                                        <h3 class="font-medium text-gray-900 hover:text-primary">
                                            {{ $project->name }}
                                        </h3>

                                        <div class="flex justify-between items-center mt-2">
                                            <span class="text-sm text-gray-600">
                                                {{ $project->genre }}
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($project->status === 'completed') bg-green-100 text-green-800 
                                                @elseif($project->status === 'open') bg-blue-100 text-blue-800 
                                                @elseif($project->status === 'in_progress') bg-yellow-100 text-yellow-800 
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Completed Pitches -->
                    @if(count($completedPitches) > 0)
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Completed Projects & Pitches</h2>

                        <div class="space-y-4">
                            @foreach($completedPitches as $pitch)
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium text-gray-900">
                                            <a href="{{ route('projects.show', $pitch->project) }}"
                                                class="hover:text-primary">
                                                {{ $pitch->project->name }}
                                            </a>
                                        </h3>

                                        <div class="text-sm text-gray-600 mt-1">
                                            @if($pitch->project->user_id == $user->id)
                                            <span class="font-medium">Project Owner</span>
                                            @if($pitch->user && $pitch->user->id != $user->id)
                                            - Pitch by
                                            <a href="{{ route('profile.username', $pitch->user->username) }}"
                                                class="font-medium hover:underline">
                                                {{ $pitch->user->name }}
                                            </a>
                                            @endif
                                            @else
                                            <span class="font-medium">Pitch Submitter</span>
                                            @if($pitch->project->user)
                                            - Project by
                                            <a href="{{ route('profile.username', $pitch->project->user->username) }}"
                                                class="font-medium hover:underline">
                                                {{ $pitch->project->user->name }}
                                            </a>
                                            @endif
                                            @endif
                                        </div>

                                        @if($pitch->completion_date)
                                        <div class="text-xs text-gray-500 mt-1">
                                            <span class="font-medium">Completed on</span>
                                            {{ is_string($pitch->completion_date) ? $pitch->completion_date :
                                            $pitch->completion_date->format('M d, Y') }}
                                        </div>
                                        @endif
                                    </div>

                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Completed
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Profile completeness notification for the profile owner -->
            @if($canEdit && !$user->profile_completed)
            <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Your profile is not complete. Add more information to improve your visibility and chances of
                            getting hired.
                            <a href="{{ route('profile.edit') }}"
                                class="font-medium underline text-yellow-700 hover:text-yellow-600">
                                Complete your profile now
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

</x-app-layout>