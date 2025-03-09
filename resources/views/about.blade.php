@extends('components.layouts.app')

@section('content')
<div class="pt-4">
    <!-- Minimal Hero Section -->
    <div class="bg-gradient-to-r from-base-300 to-base-100 rounded-xl shadow-lg mx-4 md:mx-12 mb-8">
        <div class="relative overflow-hidden">
            <!-- Decorative sound waves -->
            <div class="absolute inset-0 flex items-center justify-center opacity-10 overflow-hidden">
                <div class="wave-container">
                    <div class="wave wave1"></div>
                    <div class="wave wave2"></div>
                </div>
            </div>

            <div class="relative z-10 py-12 px-6 md:px-20 text-center">
                <h1 class="text-3xl md:text-5xl text-primary font-bold mb-4">
                    About MixPitch
                </h1>
                <p class="md:mt-4 text-lg md:text-2xl text-secondary max-w-3xl mx-auto">
                    Empowering musicians and audio professionals to create, collaborate, and innovate in the world of
                    music.
                </p>
            </div>
        </div>
    </div>

    <!-- Our Mission - Modernized -->
    <div class="mx-4 md:mx-12 mb-12">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-8 md:p-10">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h2 class="font-bold text-2xl md:text-3xl">Our Mission</h2>
                </div>
                <p class="text-lg md:text-xl leading-relaxed text-base-content/80 max-w-4xl">
                    MixPitch is dedicated to revolutionizing the music industry by connecting talented musicians with
                    skilled audio professionals. We aim to foster creativity, facilitate collaboration, and provide a
                    platform where musical visions come to life.
                </p>
            </div>
        </div>
    </div>

    <!-- Our Story - Modernized -->
    <div class="mx-4 md:mx-12 mb-12">
        <div class="bg-gradient-to-br from-base-100 to-base-200 rounded-xl shadow-md overflow-hidden">
            <div class="p-8 md:p-10">
                <div class="flex items-center mb-8">
                    <div class="w-12 h-12 bg-accent/10 rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-accent" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h2 class="font-bold text-2xl md:text-3xl">Our Story</h2>
                </div>

                <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
                    <div class="md:w-1/2">
                        <div class="space-y-4 text-lg text-base-content/80">
                            <p>
                                MixPitch was born from the frustration of countless audio engineers struggling to find
                                real-world projects to work on. We saw talented professionals spending more time
                                searching for opportunities than actually creating music.
                            </p>
                            <p>
                                At the same time, we noticed artists hesitating to work with unknown engineers, often
                                sticking to their existing networks or big names in the industry. This created a barrier
                                for new talent to break into the field and for artists to discover fresh perspectives.
                            </p>
                            <p>
                                MixPitch bridges this gap. We've created a platform where audio engineers can showcase
                                their skills on real projects, building their portfolios and gaining valuable
                                experience. For artists, it's an opportunity to take a chance on new talent, potentially
                                discovering their next go-to engineer at an affordable price point.
                            </p>
                        </div>
                    </div>
                    <div class="md:w-1/2">
                        <div class="bg-white rounded-xl shadow-md overflow-hidden h-64 relative group">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-primary/20 to-accent/20 flex items-center justify-center">
                                <div class="text-center p-6">
                                    <div
                                        class="w-16 h-16 mx-auto mb-4 bg-white/80 rounded-full flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold">MixPitch Team</h3>
                                    <p class="mt-2 text-sm">Working together to revolutionize music collaboration</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- What We Offer - Modernized with Cards -->
    <div class="mx-4 md:mx-12 mb-12">
        <h2 class="font-bold text-3xl md:text-4xl text-center mb-2">What We <span class="text-primary">Offer</span></h2>
        <p class="text-center text-base-content/70 mb-10 max-w-3xl mx-auto">Solutions designed for both sides of the
            musical collaboration</p>

        <div class="grid md:grid-cols-2 gap-8">
            <!-- For Musicians -->
            <div
                class="bg-white rounded-xl shadow-md overflow-hidden transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                <div class="h-2 bg-primary"></div>
                <div class="p-8">
                    <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                        </svg>
                    </div>

                    <h3 class="font-semibold text-2xl mb-4 text-primary">For Musicians</h3>

                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary mt-1 mr-2 flex-shrink-0"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span><strong>Access to a global network</strong> of audio professionals</span>
                        </li>
                        <li class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary mt-1 mr-2 flex-shrink-0"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span><strong>Multiple perspectives</strong> on your music</span>
                        </li>
                        <li class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary mt-1 mr-2 flex-shrink-0"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span><strong>Affordable mixing and mastering</strong> services</span>
                        </li>
                        <li class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary mt-1 mr-2 flex-shrink-0"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span><strong>Secure platform</strong> for file sharing and communication</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- For Audio Professionals -->
            <div
                class="bg-white rounded-xl shadow-md overflow-hidden transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                <div class="h-2 bg-accent"></div>
                <div class="p-8">
                    <div class="w-14 h-14 rounded-full bg-accent/10 flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-accent" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </div>

                    <h3 class="font-semibold text-2xl mb-4 text-accent">For Audio Professionals</h3>

                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent mt-1 mr-2 flex-shrink-0"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span><strong>Diverse projects</strong> to work on and expand your portfolio</span>
                        </li>
                        <li class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent mt-1 mr-2 flex-shrink-0"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span><strong>Opportunity to showcase</strong> your skills to a wide audience</span>
                        </li>
                        <li class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent mt-1 mr-2 flex-shrink-0"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span><strong>Fair compensation</strong> for your expertise</span>
                        </li>
                        <li class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent mt-1 mr-2 flex-shrink-0"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span><strong>Networking</strong> with artists and other professionals</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Our Values - Modernized -->
    <div class="mx-4 md:mx-12 mb-12">
        <h2 class="font-bold text-3xl md:text-4xl text-center mb-2">Our <span class="text-primary">Values</span></h2>
        <p class="text-center text-base-content/70 mb-10 max-w-3xl mx-auto">The principles that guide everything we do
            at MixPitch</p>

        <div class="grid md:grid-cols-3 gap-6">
            <div
                class="bg-white rounded-xl shadow-md p-8 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-handshake text-3xl text-primary"></i>
                </div>
                <h3 class="font-semibold text-xl mb-3">Collaboration</h3>
                <p class="text-base-content/80">We believe in the power of working together to create something
                    extraordinary.</p>
            </div>

            <div
                class="bg-white rounded-xl shadow-md p-8 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                <div class="w-16 h-16 bg-accent/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-lightbulb text-3xl text-accent"></i>
                </div>
                <h3 class="font-semibold text-xl mb-3">Innovation</h3>
                <p class="text-base-content/80">We constantly strive to push the boundaries of what's possible in music
                    collaboration.</p>
            </div>

            <div
                class="bg-white rounded-xl shadow-md p-8 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                <div class="w-16 h-16 bg-secondary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-users text-3xl text-secondary"></i>
                </div>
                <h3 class="font-semibold text-xl mb-3">Community</h3>
                <p class="text-base-content/80">We foster a supportive environment where creativity thrives and
                    relationships flourish.</p>
            </div>
        </div>
    </div>

    <!-- Join Us - Modernized -->
    <div class="mx-4 md:mx-12 mb-12 relative overflow-hidden">
        <div class="bg-gradient-to-r from-accent via-primary to-secondary rounded-xl shadow-lg overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute inset-0 bg-pattern opacity-10"></div>
            <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white opacity-10 blur-xl"></div>
            <div class="absolute -left-16 -bottom-16 h-64 w-64 rounded-full bg-white opacity-10 blur-xl"></div>

            <div class="relative z-10 p-8 md:p-14 text-center">
                <h2 class="font-bold text-3xl md:text-4xl mb-6 text-white">Join the MixPitch Community</h2>
                <p class="text-xl max-w-3xl mx-auto mb-8 text-white/90">
                    Whether you're a seasoned professional or just starting your musical journey, MixPitch is here to
                    help you connect, create, and succeed.
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
                            Start Collaborating
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
                            Join MixPitch
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
</div>

<style>
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

    @keyframes wave-animation {
        0% {
            background-position-x: 0;
        }

        100% {
            background-position-x: 1440px;
        }
    }

    /* Background patterns */
    .bg-pattern {
        background-image: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%23ffffff" fill-opacity="1" fill-rule="evenodd"%3E%3C/path%3E%3C/svg%3E');
    }
</style>
@endsection