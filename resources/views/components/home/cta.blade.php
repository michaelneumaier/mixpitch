<!-- Call to Action Component -->
<div class="relative overflow-hidden py-16 bg-gradient-to-r from-primary/90 to-secondary/90">
    <!-- Decorative Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <!-- Sound wave decorative elements -->
        <svg class="absolute -bottom-10 left-0 w-full opacity-10" viewBox="0 0 1440 320" fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
                d="M0,128L48,138.7C96,149,192,171,288,165.3C384,160,480,128,576,133.3C672,139,768,181,864,181.3C960,181,1056,139,1152,133.3C1248,128,1344,160,1392,176L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"
                fill="currentColor"></path>
        </svg>
        <svg class="absolute -right-16 -top-16 w-64 h-64 text-white opacity-10" fill="currentColor"
            viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <circle cx="50" cy="50" r="50" />
        </svg>
        <svg class="absolute left-1/4 bottom-0 w-32 h-32 text-white opacity-10" fill="currentColor"
            viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="20" />
        </svg>
    </div>

    <!-- Content Container -->
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center z-10">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Join the MixPitch Movement</h2>
        <p class="text-white/90 text-lg max-w-3xl mx-auto mb-8">
            Elevate your music or audio career through the power of collaboration. Connect with talented creators
            worldwide and transform your sound.
        </p>

        <div class="flex flex-wrap justify-center gap-4 mt-8">
            @auth
            <a href="{{ route('projects.create') }}"
                class="btn btn-lg bg-white hover:bg-white/90 text-primary border-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                Create New Project
            </a>
            @else
            <a href="{{ route('register') }}" class="btn btn-lg bg-white hover:bg-white/90 text-primary border-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                Sign Up Free
            </a>
            @endauth

            <a href="{{ route('projects.index') }}"
                class="btn btn-lg btn-outline border-white text-white hover:bg-white hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                    <path fill-rule="evenodd"
                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                        clip-rule="evenodd" />
                </svg>
                Explore Projects
            </a>
        </div>
    </div>
</div>