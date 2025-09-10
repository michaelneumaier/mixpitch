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
            Ready to Transform Your Music?
        </div>

        <!-- Main Heading -->
        <div class="animate-fade-in-up" style="animation-delay: 0.2s;">
            <h2 class="text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-6 leading-tight">
                Join the 
                <span class="bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                    MixPitch
                </span>
                <br class="hidden sm:block">
                Revolution
            </h2>
        </div>

        <!-- Subtitle -->
        <div class="animate-fade-in-up" style="animation-delay: 0.4s;">
            <p class="text-xl md:text-2xl text-white/90 max-w-4xl mx-auto mb-12 leading-relaxed">
                Connect with talented creators worldwide and transform your sound through the power of global collaboration. 
                <span class="text-blue-300 font-semibold">Your next breakthrough is just one project away.</span>
            </p>
        </div>

        <!-- Enhanced Statistics -->
        <div class="grid hidden grid-cols-1 md:grid-cols-3 gap-8 mb-16 animate-fade-in-up" style="animation-delay: 0.6s;">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 hover:bg-white/15 transition-all duration-300 hover:scale-105">
                <div class="text-3xl md:text-4xl font-bold text-white mb-2">10K+</div>
                <div class="text-white/80">Active Creators</div>
            </div>
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 hover:bg-white/15 transition-all duration-300 hover:scale-105">
                <div class="text-3xl md:text-4xl font-bold text-white mb-2">50K+</div>
                <div class="text-white/80">Projects Completed</div>
            </div>
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 hover:bg-white/15 transition-all duration-300 hover:scale-105">
                <div class="text-3xl md:text-4xl font-bold text-white mb-2">98%</div>
                <div class="text-white/80">Satisfaction Rate</div>
            </div>
        </div>

        <!-- Call to Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-center gap-6 mb-12 animate-fade-in-up" style="animation-delay: 0.8s;">
            @auth
            <a href="{{ route('projects.create') }}"
                class="group relative overflow-hidden bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 px-10 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
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
                class="group relative overflow-hidden bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 px-10 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
                <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                <span class="relative flex items-center justify-center text-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Start Free Today
                </span>
            </a>
            @endauth

            <a href="{{ route('projects.index') }}"
                class="group relative overflow-hidden bg-white/10 backdrop-blur-md border border-white/20 hover:bg-white/20 text-white font-bold py-4 px-10 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Secure & Protected
            </div>
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Lightning Fast Setup
            </div>
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Global Community
            </div>
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
                No Hidden Fees
            </div>
        </div>

        <!-- Final Encouragement -->
        <div class="mt-16 animate-fade-in-up" style="animation-delay: 1.2s;">
            <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-8 max-w-3xl mx-auto">
                <div class="flex items-center justify-center mb-4">
                    <div class="flex -space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full border-2 border-white flex items-center justify-center text-white font-bold text-sm">A</div>
                        <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full border-2 border-white flex items-center justify-center text-white font-bold text-sm">M</div>
                        <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-full border-2 border-white flex items-center justify-center text-white font-bold text-sm">S</div>
                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-teal-500 rounded-full border-2 border-white flex items-center justify-center text-white font-bold text-sm">+</div>
                    </div>
                </div>
                <p class="text-white/90 text-lg leading-relaxed">
                    <span class="font-semibold text-blue-300">Join thousands of artists and audio professionals</span> who have already discovered the power of collaborative music creation. Your creative journey starts here.
                </p>
            </div>
        </div>
    </div>
</div>