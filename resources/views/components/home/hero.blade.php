<!-- Next-Level Immersive Hero Section -->
<div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <!-- Enhanced Audio Visualization Background -->
    <div class="absolute inset-0 z-0">
        <div id="audio-visualizer" class="h-full w-full opacity-30"></div>
        <!-- Animated gradient overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 via-purple-600/20 to-pink-600/20 animate-gradient-x"></div>
    </div>

    <!-- Floating particles background -->
    <div class="absolute inset-0 z-0 overflow-hidden">
        <div id="particles-container" class="h-full w-full opacity-20"></div>
    </div>

    <!-- Decorative sound waves with enhanced animation -->
    <div class="absolute inset-0 flex items-center justify-center opacity-5 overflow-hidden">
        <div class="wave-container">
            <div class="wave wave1 animate-pulse"></div>
            <div class="wave wave2 animate-pulse" style="animation-delay: 0.5s;"></div>
            <div class="wave wave3 animate-pulse" style="animation-delay: 1s;"></div>
            <div class="wave wave4 animate-pulse" style="animation-delay: 1.5s;"></div>
        </div>
    </div>

    <!-- Main Hero Content -->
    <div class="relative z-10 flex items-center justify-center min-h-screen px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <!-- Animated Badge -->
            <div class="mt-2 animate-fade-in-up" style="animation-delay: 0.2s;">
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white/90 text-sm font-medium">
                    <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                    <span>Collaboration Platform</span>
                    <div class="ml-2 px-2 py-1 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full text-xs font-bold">
                        NEW
                    </div>
                </div>
            </div>

            <!-- Revolutionary Heading -->
            <div class="mb-12 animate-fade-in-up" style="animation-delay: 0.4s;">
                <h1 class="text-4xl sm:text-6xl lg:text-8xl font-bold text-white mb-6 leading-relaxed pb-2">
                    <span class="block">Empower Musicians.</span>
                    <span class="block">Elevate Mixers.</span>
                    <span class="block bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent animate-gradient-x pb-2">
                        Unleash Creativity.
                    </span>
                </h1>
                <div class="max-w-4xl mx-auto">
                    <p class="text-xl sm:text-2xl text-white/80 leading-relaxed font-light">
                        Connect with world-class audio professionals and transform your musical vision into reality through the power of global collaboration.
                    </p>
                </div>
            </div>

            <!-- Interactive Role Selector with Glass Morphism -->
            <div class="mb-12 animate-fade-in-up" style="animation-delay: 0.6s;">
                <div class="inline-block p-1 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl">
                    <div class="relative flex">
                        <button id="artist-toggle"
                            class="role-toggle-btn active relative z-10 py-4 px-8 rounded-xl text-sm font-semibold transition-all duration-300 text-white">
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                </svg>
                                I'm an Artist
                            </span>
                        </button>
                        <button id="producer-toggle"
                            class="role-toggle-btn relative z-10 py-4 px-8 rounded-xl text-sm font-semibold transition-all duration-300 text-white/70 hover:text-white">
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                </svg>
                                I'm an Audio Pro
                            </span>
                        </button>
                        <div class="toggle-indicator absolute top-1 left-1 h-[calc(100%-8px)] bg-gradient-to-r from-blue-500 to-purple-500 rounded-xl transition-all duration-300 ease-out shadow-lg"></div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Content Based on Role -->
            <div class="mb-12 min-h-[120px] flex items-center justify-center">
                <div id="artist-content" class="role-content active animate-fade-in-up" style="animation-delay: 0.8s;">
                    <div class="max-w-4xl mx-auto">
                        <p class="text-xl sm:text-2xl text-white/90 leading-relaxed mb-6">
                            Upload your tracks and connect with talented audio professionals who will bring your music to life. 
                            <span class="text-blue-300 font-semibold">Pay only for the mix that matches your vision.</span>
                        </p>
                        <div class="flex flex-wrap justify-center gap-4 text-sm text-white/70">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Global talent pool
                            </div>
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Risk-free collaboration
                            </div>
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Professional results
                            </div>
                        </div>
                    </div>
                </div>

                <div id="producer-content" class="role-content hidden">
                    <div class="max-w-4xl mx-auto">
                        <p class="text-xl sm:text-2xl text-white/90 leading-relaxed mb-6">
                            Showcase your mixing and production skills, build your portfolio, and earn income by collaborating with musicians from around the world.
                            <span class="text-purple-300 font-semibold">Turn your passion into profit.</span>
                        </p>
                        <div class="flex flex-wrap justify-center gap-4 text-sm text-white/70">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Diverse projects
                            </div>
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Portfolio growth
                            </div>
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Earn opportunities
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Call to Action Buttons -->
            <div class="animate-fade-in-up" style="animation-delay: 1s;">
                <div class="flex flex-col sm:flex-row justify-center gap-6 max-w-2xl mx-auto">
                    @auth
                    <a href="{{ route('projects.create') }}"
                        class="group relative overflow-hidden bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
                        <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                        <span class="relative flex items-center justify-center text-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Start Your Project
                        </span>
                    </a>
                    @else
                    <a href="{{ route('register') }}"
                        class="group relative overflow-hidden bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
                        <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                        <span class="relative flex items-center justify-center text-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Join Free Today
                        </span>
                    </a>
                    @endauth

                    <a href="{{ route('projects.index') }}"
                        class="group relative overflow-hidden bg-white/10 backdrop-blur-md border border-white/20 hover:bg-white/20 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
                        <span class="relative flex items-center justify-center text-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Explore Projects
                        </span>
                    </a>
                </div>

                <!-- Trust indicators -->
                <div class="mt-12 flex flex-wrap justify-center items-center gap-8 text-white/60 text-sm">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Secure payments
                    </div>
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Lightning fast
                    </div>
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Global community
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>