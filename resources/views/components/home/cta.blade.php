<!-- Next-Level Call to Action Section -->
<div class="relative overflow-hidden py-24 bg-gradient-to-b from-slate-900 via-purple-900 to-slate-900">
    <!-- Enhanced Background Effects -->
    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 via-purple-600/20 to-pink-600/20 animate-gradient-x"></div>
    
    <!-- Floating Particles Background -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-2 h-2 bg-blue-400 rounded-full animate-float opacity-60"></div>
        <div class="absolute top-1/3 right-1/3 w-1 h-1 bg-purple-400 rounded-full animate-float opacity-40" style="animation-delay: 1s;"></div>
        <div class="absolute bottom-1/4 left-1/3 w-3 h-3 bg-pink-400 rounded-full animate-float opacity-50" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 right-1/4 w-1.5 h-1.5 bg-indigo-400 rounded-full animate-float opacity-70" style="animation-delay: 3s;"></div>
        <div class="absolute bottom-1/3 right-1/2 w-2.5 h-2.5 bg-cyan-400 rounded-full animate-float opacity-45" style="animation-delay: 4s;"></div>
    </div>

    <!-- Decorative Sound Waves -->
    <div class="absolute inset-0 overflow-hidden opacity-10">
        <svg class="absolute -bottom-10 left-0 w-full" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0,128L48,138.7C96,149,192,171,288,165.3C384,160,480,128,576,133.3C672,139,768,181,864,181.3C960,181,1056,139,1152,133.3C1248,128,1344,160,1392,176L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z" fill="currentColor" class="text-white"></path>
        </svg>
        <svg class="absolute -top-10 right-0 w-full transform rotate-180" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0,128L48,138.7C96,149,192,171,288,165.3C384,160,480,128,576,133.3C672,139,768,181,864,181.3C960,181,1056,139,1152,133.3C1248,128,1344,160,1392,176L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z" fill="currentColor" class="text-white"></path>
        </svg>
    </div>

    <!-- Content Container -->
    <div class="relative mx-auto px-4 sm:px-6 lg:px-8 text-center z-10">
        <!-- Badge -->
        <div class="inline-flex items-center px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white/90 text-sm font-medium mb-8 animate-fade-in-up">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Your Complete Audio Platform
        </div>

        <!-- Main Heading -->
        <div class="animate-fade-in-up" style="animation-delay: 0.2s;">
            <h2 class="text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-6 leading-tight">
                Ready to Transform
                <br class="hidden sm:block">
                <span class="bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                    Your Audio Workflow?
                </span>
            </h2>
        </div>

        <!-- Subtitle -->
        <div class="animate-fade-in-up" style="animation-delay: 0.4s;">
            <p class="text-xl md:text-2xl text-white/90 max-w-4xl mx-auto mb-12 leading-relaxed">
                Whether you're finding talent through our marketplace, running mixing contests, or managing client projects professionally—
                <span class="text-blue-300 font-semibold">MixPitch gives you the tools to work smarter, get paid fairly, and focus on what you do best.</span>
            </p>
        </div>

        <!-- Enhanced Statistics -->
        <div class="grid hidden grid-cols-1 md:grid-cols-3 gap-8 mb-16 animate-fade-in-up" style="animation-delay: 0.6s;">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 hover:bg-white/15 transition-[transform,colors,shadow] duration-300 hover:scale-105">
                <div class="text-3xl md:text-4xl font-bold text-white mb-2">10K+</div>
                <div class="text-white/80">Active Creators</div>
            </div>
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 hover:bg-white/15 transition-[transform,colors,shadow] duration-300 hover:scale-105">
                <div class="text-3xl md:text-4xl font-bold text-white mb-2">50K+</div>
                <div class="text-white/80">Projects Completed</div>
            </div>
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 hover:bg-white/15 transition-[transform,colors,shadow] duration-300 hover:scale-105">
                <div class="text-3xl md:text-4xl font-bold text-white mb-2">98%</div>
                <div class="text-white/80">Satisfaction Rate</div>
            </div>
        </div>

        <!-- Call to Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-center gap-6 mb-12 animate-fade-in-up" style="animation-delay: 0.8s;">
            @auth
            <a href="{{ route('projects.create') }}"
                class="group relative overflow-hidden bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 px-10 rounded-xl transition-transform duration-300 transform hover:scale-105 hover:shadow-2xl">
                <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                <span class="relative flex items-center justify-center text-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Your Project
                </span>
            </a>
            @else
            <a href="{{ route('register') }}"
                class="group relative overflow-hidden bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 px-10 rounded-xl transition-[transform,colors,shadow] duration-300 transform hover:scale-105 hover:shadow-2xl">
                <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                <span class="relative flex items-center justify-center text-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Sign Up Free
                </span>
            </a>
            @endauth

            <a href="{{ route('projects.index') }}"
                class="group relative overflow-hidden bg-white/10 backdrop-blur-md border border-white/20 hover:bg-white/20 text-white font-bold py-4 px-10 rounded-xl transition-[transform,colors,shadow] duration-300 transform hover:scale-105 hover:shadow-2xl">
                <span class="relative flex items-center justify-center text-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Explore Projects
                </span>
            </a>
        </div>

        <!-- Trust Indicators -->
        <div class="flex flex-wrap justify-center items-center gap-8 text-white/60 text-sm animate-fade-in-up" style="animation-delay: 1s;">
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Global Talent Marketplace
            </div>
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
                Contest Opportunities
            </div>
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Client Management Tools
            </div>
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Secure Payments
            </div>
        </div>

        <!-- Final Encouragement -->
        <div class="mt-16 animate-fade-in-up" style="animation-delay: 1.2s;">
            <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-8 max-w-3xl mx-auto">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <p class="text-white/90 text-lg leading-relaxed">
                    <span class="font-semibold text-blue-300">Join artists, engineers, and studios</span> who are finding the perfect collaborators through our marketplace, growing their portfolios with contest wins, and managing clients professionally with automated workflows. Your audio career starts here.
                </p>
            </div>
        </div>
    </div>
</div>