<!-- Immersive Hero Section -->
<div
    class="relative overflow-hidden bg-gradient-to-br from-base-300 to-base-100 rounded-xl shadow-lg mx-4 md:mx-12 mb-12">
    <!-- Audio visualization background -->
    <div class="absolute inset-0 z-0 opacity-20">
        <div id="audio-visualizer" class="h-full w-full"></div>
    </div>

    <!-- Decorative sound waves -->
    <div class="absolute inset-0 flex items-center justify-center opacity-10 overflow-hidden">
        <div class="wave-container">
            <div class="wave wave1"></div>
            <div class="wave wave2"></div>
            <div class="wave wave3"></div>
            <div class="wave wave4"></div>
        </div>
    </div>

    <div class="relative z-10 py-16 md:py-24 px-6 md:px-20">
        <!-- Interactive heading with perspective effect -->
        <div class="perspective-text text-center mb-12">
            <h1 class="text-3xl md:text-5xl lg:text-7xl text-secondary text-center grid grid-cols-2 gap-x-2 mb-4">
                <div class="text-right reveal-text">
                    <b>Empower</b><br>
                    <b>Elevate</b><br>
                    <b>Unleash</b>
                </div>
                <div class="text-left reveal-text" style="animation-delay: 0.3s;">
                    Musicians.<br>
                    Mixers.<br>
                    Creativity.
                </div>
            </h1>
        </div>

        <!-- Enhanced Introduction with role selector -->
        <div class="max-w-4xl mx-auto text-center mb-10">
            <div class="inline-block mb-6">
                <div class="role-toggle-container p-1 bg-base-300 rounded-lg shadow-inner">
                    <div class="relative flex">
                        <button id="artist-toggle"
                            class="role-toggle-btn active py-2 px-3 md:px-5 rounded-md text-xs md:text-sm font-medium transition-colors duration-200 z-10 text-white">
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5 mr-1 md:mr-2"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                </svg>
                                I'm an Artist
                            </span>
                        </button>
                        <button id="producer-toggle"
                            class="role-toggle-btn py-2 px-3 md:px-5 rounded-md text-xs md:text-sm font-medium transition-colors duration-200 z-10">
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5 mr-1 md:mr-2"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                </svg>
                                I'm an Audio Pro
                            </span>
                        </button>
                        <div
                            class="toggle-indicator absolute top-0 left-0 h-full bg-primary rounded-md transition-transform duration-200 ease-in-out shadow-md">
                        </div>
                    </div>
                </div>
            </div>

            <div id="artist-content" class="role-content active">
                <p class="text-xl md:text-2xl text-secondary text-center leading-relaxed mb-6 animate-fade-in">
                    Upload your tracks and connect with talented audio professionals who will bring your music to
                    life. Pay only for the mix that matches your vision.
                </p>
            </div>

            <div id="producer-content" class="role-content hidden">
                <p class="text-xl md:text-2xl text-secondary text-center leading-relaxed mb-6 animate-fade-in">
                    Showcase your mixing and production skills, build your portfolio, and earn income by
                    collaborating with musicians from around the world.
                </p>
            </div>
        </div>

        <!-- Call to Action Buttons -->
        <div class="max-w-5xl mx-auto">
            <div class="flex flex-col md:flex-row justify-center my-6 md:space-x-6 px-6 md:px-4">
                <div class="w-full md:w-1/2 lg:w-1/3 mb-4 md:mb-0">
                    @auth
                    <a href="{{ route('projects.create') }}"
                        class="transition-all transform hover:scale-105 block bg-accent hover:bg-accent-focus text-xl text-center font-bold w-full py-4 px-4 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded-lg whitespace-nowrap">
                        <span class="flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Submit Your Music
                        </span>
                    </a>
                    @else
                    <a href="{{ route('login') }}"
                        class="transition-all transform hover:scale-105 block bg-accent hover:bg-accent-focus text-xl text-center font-bold w-full py-4 px-4 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded-lg whitespace-nowrap">
                        <span class="flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Submit Your Music
                        </span>
                    </a>
                    @endauth
                </div>
                <div class="w-full md:w-1/2 lg:w-1/3">
                    <a href="{{ route('projects.index') }}"
                        class="transition-all transform hover:scale-105 block bg-button hover:bg-buttonFocus text-xl font-bold text-center w-full py-4 px-4 shadow-lightGlow shadow-button hover:shadow-focus rounded-lg">
                        <span class="flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0118 0z" />
                            </svg>
                            Browse Projects
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>