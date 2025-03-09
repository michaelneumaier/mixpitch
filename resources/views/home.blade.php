@extends('components.layouts.app')

@section('content')
<div class="pt-4">
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
                <h1 class="text-3xl md:text-7xl text-secondary text-center grid grid-cols-2 gap-x-2 mb-4">
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
                                class="role-toggle-btn active py-2 px-5 rounded-md text-sm font-medium transition-colors duration-200 z-10">
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                    </svg>
                                    I'm an Artist
                                </span>
                            </button>
                            <button id="producer-toggle"
                                class="role-toggle-btn py-2 px-5 rounded-md text-sm font-medium transition-colors duration-200 z-10">
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
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
                    <p
                        class="text-xl md:text-2xl text-secondary text-center leading-relaxed mb-6 opacity-0 animate-fade-in">
                        Upload your tracks and connect with talented audio professionals who will bring your music to
                        life. Pay only for the mix that matches your vision.
                    </p>
                </div>

                <div id="producer-content" class="role-content hidden">
                    <p
                        class="text-xl md:text-2xl text-secondary text-center leading-relaxed mb-6 opacity-0 animate-fade-in">
                        Showcase your mixing and production skills, build your portfolio, and earn income by
                        collaborating with musicians from around the world.
                    </p>
                </div>
            </div>

            <!-- Interactive CTA area with audio sample player -->
            <div class="max-w-5xl mx-auto">
                <!-- Visual Audio Transformation Graphic -->
                <div class="rounded-lg bg-base-300/30 backdrop-blur-sm p-6 mb-8">
                    <div class="flex flex-col md:flex-row items-center justify-center">
                        <div class="mb-6 md:mb-0 md:mr-8 text-center md:text-left">
                            <h3 class="text-xl md:text-2xl font-semibold text-primary mb-3">Transform Your Sound</h3>
                            <p class="text-sm md:text-base opacity-80 max-w-md">MixPitch connects artists with audio
                                professionals to take your music from raw potential to polished perfection.</p>
                        </div>
                        <div class="flex-shrink-0 relative">
                            <!-- Visual audio waveform transformation graphic -->
                            <div class="relative flex items-center justify-center">
                                <div
                                    class="w-56 md:w-72 h-20 bg-base-200 rounded-lg overflow-hidden flex items-center justify-center relative">
                                    <!-- Before waveform (smaller amplitude) -->
                                    <div class="waveform-container w-full h-full flex items-center justify-center">
                                        <div class="waveform-bars waveform-before">
                                            <!-- Generated bars will be inserted via JS -->
                                        </div>
                                    </div>

                                    <!-- Transformation arrow -->
                                    <div
                                        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-accent shadow-lg flex items-center justify-center z-10">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>

                                <div
                                    class="w-56 md:w-72 h-20 bg-base-200 rounded-lg overflow-hidden flex items-center justify-center relative ml-4">
                                    <!-- After waveform (larger amplitude, more defined) -->
                                    <div class="waveform-container w-full h-full flex items-center justify-center">
                                        <div class="waveform-bars waveform-after">
                                            <!-- Generated bars will be inserted via JS -->
                                        </div>
                                    </div>

                                    <!-- Professional badge -->
                                    <div
                                        class="absolute -top-2 -right-2 bg-primary text-white text-xs font-bold px-2 py-1 rounded shadow-md">
                                        Professional
                                    </div>
                                </div>
                            </div>

                            <!-- Labels -->
                            <div class="flex justify-between mt-2 text-xs text-center">
                                <div class="w-56 md:w-72">Original Track</div>
                                <div class="w-56 md:w-72">Professionally Mixed</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Call to Action Buttons -->
                <div class="flex flex-col md:flex-row justify-center my-6 md:space-x-6 px-6 md:px-4">
                    <div class="w-full md:w-1/2 lg:w-1/3 mb-4 md:mb-0">
                        @auth
                        <a href="{{ route('projects.create') }}"
                            class="transition-all transform hover:scale-105 block bg-accent hover:bg-accent-focus text-xl text-center font-bold w-full py-4 px-4 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded-lg whitespace-nowrap">
                            <span class="flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0118 0z" />
                                </svg>
                                Browse Projects
                            </span>
                        </a>
                    </div>
                </div>

                <!-- User stats -->
                <div class="flex justify-center mt-8">
                    <div class="stats-counter flex space-x-6 md:space-x-12 text-center text-sm">
                        <div>
                            <div class="font-bold text-xl md:text-2xl text-accent count-up" data-target="5000">0</div>
                            <div class="opacity-70">Active Users</div>
                        </div>
                        <div>
                            <div class="font-bold text-xl md:text-2xl text-primary count-up" data-target="1200">0</div>
                            <div class="opacity-70">Projects</div>
                        </div>
                        <div>
                            <div class="font-bold text-xl md:text-2xl text-secondary count-up" data-target="12000">0
                            </div>
                            <div class="opacity-70">Collaborations</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Highlights - Modernized -->
    <div class="mx-4 md:mx-12 mb-12">
        <div class="py-8 md:py-12 px-4 md:px-8">
            <h2 class="font-bold text-3xl md:text-4xl text-center mb-3">Why Choose <span
                    class="text-primary">MixPitch</span>?</h2>
            <p class="text-center text-base-content/70 mb-12 max-w-3xl mx-auto">The perfect platform where musical
                talent meets technical expertise, creating endless possibilities for collaboration</p>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- For Artists -->
                <div
                    class="bg-white rounded-xl shadow-md overflow-hidden transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group">
                    <div class="h-2 bg-primary"></div>
                    <div class="p-6">
                        <div
                            class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-2xl mb-4 flex items-center text-primary">
                            For Artists
                            <span
                                class="ml-2 text-xs uppercase tracking-wider py-1 px-2 rounded-full bg-primary/10 font-normal">Upload</span>
                        </h3>
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-1 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <span class="font-medium">Unlock Global Talent</span>
                                    <p class="text-sm text-gray-600 mt-1">Upload your tracks and receive contributions
                                        from multiple audio engineers and producers worldwide.</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-1 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <span class="font-medium">Pay for Perfection</span>
                                    <p class="text-sm text-gray-600 mt-1">Only invest in the work that resonates with
                                        your vision and meets your artistic standards.</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-1 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <span class="font-medium">Diverse Perspectives</span>
                                    <p class="text-sm text-gray-600 mt-1">Experience fresh takes on your music from
                                        different producers to elevate your sound.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- For Audio Professionals -->
                <div
                    class="bg-white rounded-xl shadow-md overflow-hidden transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group">
                    <div class="h-2 bg-accent"></div>
                    <div class="p-6">
                        <div
                            class="w-14 h-14 rounded-full bg-accent/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-accent" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-2xl mb-4 flex items-center text-accent">
                            For Audio Professionals
                            <span
                                class="ml-2 text-xs uppercase tracking-wider py-1 px-2 rounded-full bg-accent/10 font-normal">Create</span>
                        </h3>
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-accent mt-1 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <span class="font-medium">Expand Your Portfolio</span>
                                    <p class="text-sm text-gray-600 mt-1">Work on a diverse range of projects across
                                        genres to showcase your skills and versatility.</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-accent mt-1 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <span class="font-medium">Gain Real Experience</span>
                                    <p class="text-sm text-gray-600 mt-1">Collaborate with artists on real projects and
                                        receive valuable feedback that helps you grow.</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-accent mt-1 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <span class="font-medium">Earn Opportunities</span>
                                    <p class="text-sm text-gray-600 mt-1">Get compensated when your work aligns with the
                                        artist's needs and vision.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- MixPitch Advantages -->
                <div
                    class="bg-white rounded-xl shadow-md overflow-hidden transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group">
                    <div class="h-2 bg-secondary"></div>
                    <div class="p-6">
                        <div
                            class="w-14 h-14 rounded-full bg-secondary/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-secondary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-2xl mb-4 flex items-center text-secondary">
                            MixPitch Advantages
                            <span
                                class="ml-2 text-xs uppercase tracking-wider py-1 px-2 rounded-full bg-secondary/10 font-normal">Connect</span>
                        </h3>
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-secondary mt-1 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <span class="font-medium">Collaborative Environment</span>
                                    <p class="text-sm text-gray-600 mt-1">Multiple creatives can work on the same
                                        project simultaneously, creating a diverse range of options.</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-secondary mt-1 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <span class="font-medium">Risk-Free Exploration</span>
                                    <p class="text-sm text-gray-600 mt-1">Artists can explore different mixing styles
                                        and approaches without upfront costs.</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-secondary mt-1 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <span class="font-medium">Community Growth</span>
                                    <p class="text-sm text-gray-600 mt-1">Join a supportive network aimed at mutual
                                        growth, learning, and creative success.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- How It Works - Modern Timeline Design -->
    <div class="mx-4 md:mx-12 mb-12 bg-base-100 rounded-xl shadow-md overflow-hidden">
        <div class="py-10 px-6 md:px-10">
            <h2 class="font-bold text-3xl md:text-4xl text-center mb-2">How It <span class="text-primary">Works</span>
            </h2>
            <p class="text-center text-base-content/70 mb-12 max-w-3xl mx-auto">A simple yet powerful process that
                brings artists and audio professionals together</p>

            <div class="grid md:grid-cols-2 gap-x-12 gap-y-8">
                <!-- For Artists Timeline -->
                <div class="relative p-4">
                    <div
                        class="absolute top-6 bottom-6 left-12 w-1 bg-gradient-to-b from-primary/50 to-primary/10 rounded-full">
                    </div>

                    <h3 class="font-semibold text-2xl mb-10 flex items-center text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                        </svg>
                        For Artists
                    </h3>

                    <div class="space-y-12">
                        <!-- Step 1 -->
                        <div class="relative pl-20 timeline-step">
                            <div
                                class="absolute left-0 top-0 bg-primary text-white rounded-full h-10 w-10 flex items-center justify-center font-bold shadow-md">
                                1</div>
                            <div class="glassmorphism bg-white/30 backdrop-blur-sm p-4 rounded-lg shadow-sm">
                                <h4 class="font-medium text-lg mb-2 text-primary">Submit Your Track</h4>
                                <p class="text-base-content/80">Upload your music and provide details about your
                                    project's needs and vision. Set your budget and timeline.</p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="relative pl-20 timeline-step">
                            <div
                                class="absolute left-0 top-0 bg-primary text-white rounded-full h-10 w-10 flex items-center justify-center font-bold shadow-md">
                                2</div>
                            <div class="glassmorphism bg-white/30 backdrop-blur-sm p-4 rounded-lg shadow-sm">
                                <h4 class="font-medium text-lg mb-2 text-primary">Review Submissions</h4>
                                <p class="text-base-content/80">Listen to different versions created by audio
                                    professionals and provide feedback as needed.</p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="relative pl-20 timeline-step">
                            <div
                                class="absolute left-0 top-0 bg-primary text-white rounded-full h-10 w-10 flex items-center justify-center font-bold shadow-md">
                                3</div>
                            <div class="glassmorphism bg-white/30 backdrop-blur-sm p-4 rounded-lg shadow-sm">
                                <h4 class="font-medium text-lg mb-2 text-primary">Select & Finalize</h4>
                                <p class="text-base-content/80">Choose the mix that best matches your vision, complete
                                    payment, and download your professionally enhanced track.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- For Audio Professionals Timeline -->
                <div class="relative p-4">
                    <div
                        class="absolute top-6 bottom-6 left-12 w-1 bg-gradient-to-b from-accent/50 to-accent/10 rounded-full">
                    </div>

                    <h3 class="font-semibold text-2xl mb-10 flex items-center text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                        For Audio Professionals
                    </h3>

                    <div class="space-y-12">
                        <!-- Step 1 -->
                        <div class="relative pl-20 timeline-step">
                            <div
                                class="absolute left-0 top-0 bg-accent text-white rounded-full h-10 w-10 flex items-center justify-center font-bold shadow-md">
                                1</div>
                            <div class="glassmorphism bg-white/30 backdrop-blur-sm p-4 rounded-lg shadow-sm">
                                <h4 class="font-medium text-lg mb-2 text-accent">Discover Projects</h4>
                                <p class="text-base-content/80">Browse available projects that match your skills and
                                    interests from our diverse marketplace.</p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="relative pl-20 timeline-step">
                            <div
                                class="absolute left-0 top-0 bg-accent text-white rounded-full h-10 w-10 flex items-center justify-center font-bold shadow-md">
                                2</div>
                            <div class="glassmorphism bg-white/30 backdrop-blur-sm p-4 rounded-lg shadow-sm">
                                <h4 class="font-medium text-lg mb-2 text-accent">Create Your Version</h4>
                                <p class="text-base-content/80">Download the artist's files, apply your unique skills
                                    and style, then submit your version.</p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="relative pl-20 timeline-step">
                            <div
                                class="absolute left-0 top-0 bg-accent text-white rounded-full h-10 w-10 flex items-center justify-center font-bold shadow-md">
                                3</div>
                            <div class="glassmorphism bg-white/30 backdrop-blur-sm p-4 rounded-lg shadow-sm">
                                <h4 class="font-medium text-lg mb-2 text-accent">Get Selected & Paid</h4>
                                <p class="text-base-content/80">If your version is chosen, you'll receive payment and
                                    recognition for your work.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Video Tour Preview -->
            <div class="mt-12 text-center">
                <button class="inline-flex items-center text-primary hover:text-primary-focus transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Watch a quick tour of the MixPitch platform</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Enhanced CTA - Join the Movement -->
    <div class="mx-4 md:mx-12 mb-12 relative overflow-hidden">
        <div class="bg-gradient-to-r from-accent via-primary to-secondary rounded-xl shadow-lg overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute inset-0 bg-pattern opacity-10"></div>
            <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white opacity-10 blur-xl"></div>
            <div class="absolute -left-16 -bottom-16 h-64 w-64 rounded-full bg-white opacity-10 blur-xl"></div>

            <div class="relative z-10 p-8 md:p-14 text-center">
                <h2 class="font-bold text-3xl md:text-5xl mb-6 text-white">Join the MixPitch Movement</h2>
                <p class="text-xl md:text-2xl mb-10 text-white/90 max-w-4xl mx-auto leading-relaxed">
                    Ready to take your music or audio career to the next level? Join thousands of creators elevating
                    their craft through collaboration.
                </p>

                <div class="flex flex-col md:flex-row items-center justify-center gap-6">
                    @auth
                    <a href="{{ route('projects.create') }}"
                        class="transition-all transform hover:scale-105 inline-block bg-white text-primary text-xl text-center font-bold py-4 px-10 border-b-4 border-white/80 shadow-lg shadow-primary/30 rounded-lg">
                        <span class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Create Your First Project
                        </span>
                    </a>
                    @else
                    <a href="{{ route('register') }}"
                        class="transition-all transform hover:scale-105 inline-block bg-white text-primary text-xl text-center font-bold py-4 px-10 border-b-4 border-white/80 shadow-lg shadow-primary/30 rounded-lg">
                        <span class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Sign Up Free
                        </span>
                    </a>
                    @endauth

                    <a href="{{ route('projects.index') }}"
                        class="transition-all transform hover:scale-105 inline-block bg-transparent text-white text-xl text-center font-bold py-4 px-10 border-2 border-white shadow-lg shadow-primary/10 rounded-lg">
                        <span class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Explore Projects
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Testimonials with Modern Design -->
    <div class="mx-4 md:mx-12 mb-12">
        <h2 class="font-bold text-3xl md:text-4xl text-center mb-3">What Our Community <span
                class="text-primary">Says</span></h2>
        <p class="text-center text-base-content/70 mb-12 max-w-3xl mx-auto">Hear from the creative minds who have
            experienced the MixPitch advantage</p>

        <div class="grid md:grid-cols-3 gap-6">
            <!-- Testimonial 1 -->
            <div class="relative transform transition-all hover:-translate-y-1 duration-300">
                <div class="bg-white rounded-xl shadow-md p-6 relative overflow-hidden z-10">
                    <div class="absolute -right-2 -top-2 text-primary/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
                        </svg>
                    </div>
                    <div class="relative z-10">
                        <div class="flex mb-6">
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>

                        <p class="text-lg mb-6">"MixPitch has transformed the way I collaborate with audio engineers. I
                            received multiple perspectives on my track and found the perfect mixing engineer for my
                            project."</p>

                        <div class="flex items-center mt-6">
                            <div class="flex-shrink-0">
                                <div
                                    class="h-12 w-12 rounded-full bg-gradient-to-r from-primary to-accent flex items-center justify-center text-white font-bold">
                                    AR
                                </div>
                            </div>
                            <div class="ml-3">
                                <h4 class="font-semibold text-lg">Alex Rodriguez</h4>
                                <p class="text-sm text-base-content/60">Indie Artist, Los Angeles</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="absolute inset-0 bg-gradient-to-r from-primary/5 to-primary/10 rounded-xl -z-10 transform translate-y-3 translate-x-3 blur-sm">
                </div>
            </div>

            <!-- Testimonial 2 -->
            <div class="relative transform transition-all hover:-translate-y-1 duration-300">
                <div class="bg-white rounded-xl shadow-md p-6 relative overflow-hidden z-10">
                    <div class="absolute -right-2 -top-2 text-accent/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
                        </svg>
                    </div>
                    <div class="relative z-10">
                        <div class="flex mb-6">
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>

                        <p class="text-lg mb-6">"As an audio engineer, MixPitch has provided me with endless
                            opportunities to showcase my skills and connect with artists who value my work and
                            expertise."</p>

                        <div class="flex items-center mt-6">
                            <div class="flex-shrink-0">
                                <div
                                    class="h-12 w-12 rounded-full bg-gradient-to-r from-accent to-secondary flex items-center justify-center text-white font-bold">
                                    JL
                                </div>
                            </div>
                            <div class="ml-3">
                                <h4 class="font-semibold text-lg">Jamie Lee</h4>
                                <p class="text-sm text-base-content/60">Mixing Engineer, London</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="absolute inset-0 bg-gradient-to-r from-accent/5 to-accent/10 rounded-xl -z-10 transform translate-y-3 translate-x-3 blur-sm">
                </div>
            </div>

            <!-- Testimonial 3 -->
            <div class="relative transform transition-all hover:-translate-y-1 duration-300">
                <div class="bg-white rounded-xl shadow-md p-6 relative overflow-hidden z-10">
                    <div class="absolute -right-2 -top-2 text-secondary/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
                        </svg>
                    </div>
                    <div class="relative z-10">
                        <div class="flex mb-6">
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="text-accent h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>

                        <p class="text-lg mb-6">"The collaborative environment on MixPitch is unparalleled. I've
                            connected with talented artists worldwide and it's become an essential part of my production
                            workflow."</p>

                        <div class="flex items-center mt-6">
                            <div class="flex-shrink-0">
                                <div
                                    class="h-12 w-12 rounded-full bg-gradient-to-r from-secondary to-primary flex items-center justify-center text-white font-bold">
                                    TM
                                </div>
                            </div>
                            <div class="ml-3">
                                <h4 class="font-semibold text-lg">Taylor Morgan</h4>
                                <p class="text-sm text-base-content/60">Music Producer, Nashville</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="absolute inset-0 bg-gradient-to-r from-secondary/5 to-secondary/10 rounded-xl -z-10 transform translate-y-3 translate-x-3 blur-sm">
                </div>
            </div>
        </div>

        <!-- View More Testimonials Button -->
        <div class="text-center mt-8">
            <button class="inline-flex items-center font-medium text-primary hover:underline">
                <span>View more success stories</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Add required CSS for the new elements -->
<style>
    /* CSS custom properties for colors */
    :root {
        --color-primary-rgb: 59, 130, 246;
        /* Default blue if theme doesn't set one */
        --color-accent-rgb: 252, 211, 77;
        /* Default yellow/amber if theme doesn't set one */
        --color-secondary-rgb: 139, 92, 246;
        /* Default purple if theme doesn't set one */
    }

    /* Audio visualization waves */
    .wave-container {
        position: relative;
        width: 100%;
        height: 300px;
    }

    .wave {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 100px;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.5" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
        background-size: 1440px 100px;
        background-repeat: repeat-x;
        animation: wave-animation 20s linear infinite;
    }

    .wave1 {
        opacity: 0.3;
        animation-duration: 20s;
        animation-delay: 0s;
    }

    .wave2 {
        opacity: 0.2;
        animation-duration: 17s;
        animation-delay: -2s;
    }

    .wave3 {
        opacity: 0.15;
        animation-duration: 15s;
        animation-delay: -4s;
    }

    .wave4 {
        opacity: 0.1;
        animation-duration: 13s;
        animation-delay: -6s;
    }

    @keyframes wave-animation {
        0% {
            background-position-x: 0;
        }

        100% {
            background-position-x: 1440px;
        }
    }

    /* Visual audio waveforms */
    .waveform-container {
        position: relative;
        padding: 0 5px;
        width: 100%;
        height: 100%;
    }

    .waveform-bars {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        width: 100%;
        gap: 2px;
    }

    .waveform-before .bar {
        width: 2px;
        background-color: rgba(var(--color-primary-rgb), 0.7);
        border-radius: 1px;
        /* Heights will be set dynamically via JS */
    }

    .waveform-after .bar {
        width: 2px;
        background-color: rgba(var(--color-primary-rgb), 0.9);
        border-radius: 1px;
        /* Heights will be set dynamically via JS */
    }

    /* Animated text reveal */
    .reveal-text {
        opacity: 0;
        animation: reveal 1s ease forwards;
    }

    @keyframes reveal {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Role toggle styling - new version */
    .role-toggle-container {
        position: relative;
        overflow: hidden;
        min-width: 360px;
    }

    .role-toggle-btn {
        position: relative;
        flex: 1;
        text-align: center;
        color: var(--color-base-content, #4B5563);
        z-index: 10;
    }

    .role-toggle-btn.active {
        color: white;
    }

    .toggle-indicator {
        width: 50%;
        transition: transform 0.3s ease-in-out;
    }

    /* For JavaScript */
    .toggle-indicator.move-right {
        transform: translateX(100%);
    }

    /* Content fade-in animation */
    .role-content {
        display: none;
    }

    .role-content.active {
        display: block;
    }

    .animate-fade-in {
        animation: fadeIn 0.5s ease forwards;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Counters */
    .count-up {
        transition: all 0.3s ease;
    }

    /* Background patterns */
    .bg-pattern {
        background-image: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%23ffffff" fill-opacity="1" fill-rule="evenodd"%3E%3C/path%3E%3C/svg%3E');
    }
</style>

<!-- Add required JavaScript for the hero section interactions -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Generate dynamic waveform bars
        generateWaveforms();

        // Role toggle functionality
        const artistToggle = document.getElementById('artist-toggle');
        const producerToggle = document.getElementById('producer-toggle');
        const toggleIndicator = document.querySelector('.toggle-indicator');
        const artistContent = document.getElementById('artist-content');
        const producerContent = document.getElementById('producer-content');

        if (artistToggle && producerToggle && toggleIndicator) {
            artistToggle.addEventListener('click', function () {
                // Update button states
                artistToggle.classList.add('active');
                producerToggle.classList.remove('active');

                // Move indicator
                toggleIndicator.classList.remove('move-right');

                // Switch content
                artistContent.classList.add('active');
                artistContent.classList.remove('hidden');
                producerContent.classList.remove('active');
                producerContent.classList.add('hidden');

                // Reset animation
                const p = artistContent.querySelector('p');
                p.style.animation = 'none';
                p.offsetHeight; // Trigger reflow
                p.style.animation = null;
                p.classList.remove('animate-fade-in');
                void p.offsetWidth; // Trigger reflow
                p.classList.add('animate-fade-in');
            });

            producerToggle.addEventListener('click', function () {
                // Update button states
                producerToggle.classList.add('active');
                artistToggle.classList.remove('active');

                // Move indicator
                toggleIndicator.classList.add('move-right');

                // Switch content
                producerContent.classList.add('active');
                producerContent.classList.remove('hidden');
                artistContent.classList.remove('active');
                artistContent.classList.add('hidden');

                // Reset animation
                const p = producerContent.querySelector('p');
                p.style.animation = 'none';
                p.offsetHeight; // Trigger reflow
                p.style.animation = null;
                p.classList.remove('animate-fade-in');
                void p.offsetWidth; // Trigger reflow
                p.classList.add('animate-fade-in');
            });
        }

        // Audio visualizer placeholder (would be replaced with real audio visualization)
        const visualizer = document.getElementById('audio-visualizer');
        if (visualizer) {
            // This is just a placeholder for the real audio visualization
            visualizer.innerHTML = '<div class="h-full w-full opacity-30"></div>';
        }

        // Counters animation
        const counters = document.querySelectorAll('.count-up');
        const speed = 200; // The lower the faster

        counters.forEach(counter => {
            const animate = () => {
                const value = +counter.getAttribute('data-target');
                const data = +counter.innerText;

                const time = value / speed;
                if (data < value) {
                    counter.innerText = Math.ceil(data + time);
                    setTimeout(animate, 1);
                } else {
                    counter.innerText = value;
                }
            }

            // Only start the animation when the element is in viewport
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animate();
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            observer.observe(counter);
        });

        // Function to generate waveform bars with realistic varying heights
        function generateWaveforms() {
            const beforeWaveform = document.querySelector('.waveform-before');
            const afterWaveform = document.querySelector('.waveform-after');

            if (beforeWaveform && afterWaveform) {
                // Create waveform patterns
                const beforePattern = generateWaveformPattern(40, 2, 20, false);
                const afterPattern = generateWaveformPattern(40, 2, 60, true);

                // Apply patterns to waveforms
                renderWaveform(beforeWaveform, beforePattern);
                renderWaveform(afterWaveform, afterPattern);

                // Add animation by periodically updating the waveforms
                setInterval(() => {
                    // Slight variations over time
                    const updatedBeforePattern = beforePattern.map(h => {
                        const variance = Math.random() * 4 - 2;
                        return Math.max(2, Math.min(20, h + variance));
                    });

                    const updatedAfterPattern = afterPattern.map(h => {
                        const variance = Math.random() * 6 - 3;
                        return Math.max(5, Math.min(60, h + variance));
                    });

                    renderWaveform(beforeWaveform, updatedBeforePattern);
                    renderWaveform(afterWaveform, updatedAfterPattern);
                }, 1500);
            }
        }

        // Generate a random waveform pattern with realistic audio characteristics
        function generateWaveformPattern(numBars, minHeight, maxHeight, isDynamic) {
            const heights = [];

            if (isDynamic) {
                // More dynamic variations for "after" waveform
                // Creating a pattern that looks like music waveform with peaks and valleys
                let phase = 0;
                const step = (2 * Math.PI) / numBars;

                for (let i = 0; i < numBars; i++) {
                    // Base sine wave
                    let height = Math.sin(phase) * (maxHeight * 0.5) + (maxHeight * 0.5);

                    // Add some randomness for more natural look
                    height += (Math.random() * 15) - 5;

                    // Ensure within bounds
                    height = Math.max(minHeight, Math.min(maxHeight, height));

                    heights.push(height);
                    phase += step;
                }
            } else {
                // Simpler, less dynamic pattern for "before" waveform
                // Still with some variance but generally more uniform
                for (let i = 0; i < numBars; i++) {
                    // Random heights but with less variation
                    const mid = (minHeight + maxHeight) / 2;
                    const variance = maxHeight * 0.3;
                    const height = mid + (Math.random() * variance * 2) - variance;

                    heights.push(height);
                }
            }

            return heights;
        }

        // Render a waveform with the given heights
        function renderWaveform(container, heights) {
            container.innerHTML = '';

            heights.forEach(height => {
                const bar = document.createElement('div');
                bar.className = 'bar';
                bar.style.height = height + 'px';
                container.appendChild(bar);
            });
        }
    });
</script>
@endsection