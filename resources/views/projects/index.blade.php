@auth
<x-layouts.app-sidebar>
    @livewire('projects-component')
</x-layouts.app-sidebar>
@else
<x-layouts.marketing title="Browse Projects - MixPitch" description="Discover amazing music projects and connect with talented artists and audio professionals.">
    @guest
    <!-- Guest Onboarding Banner -->
    <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-b border-blue-200 dark:border-blue-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-blue-600 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-blue-900 dark:text-blue-100">Welcome to MixPitch</h2>
                    </div>
                    <p class="text-blue-800 dark:text-blue-200 mb-4 max-w-2xl">
                        Discover amazing music projects and connect with talented artists and audio professionals. Join our community to collaborate, create, and bring your musical vision to life.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            Join MixPitch
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200 font-medium transition-colors">
                            Already have an account? Sign in
                        </a>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="flex space-x-1">
                        <div class="w-2 h-8 bg-blue-300 dark:bg-blue-600 rounded animate-pulse"></div>
                        <div class="w-2 h-6 bg-purple-300 dark:bg-purple-600 rounded animate-pulse" style="animation-delay: 0.2s;"></div>
                        <div class="w-2 h-10 bg-blue-400 dark:bg-blue-500 rounded animate-pulse" style="animation-delay: 0.4s;"></div>
                        <div class="w-2 h-7 bg-purple-400 dark:bg-purple-500 rounded animate-pulse" style="animation-delay: 0.6s;"></div>
                        <div class="w-2 h-9 bg-blue-300 dark:bg-blue-600 rounded animate-pulse" style="animation-delay: 0.8s;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endguest

    @livewire('projects-component')
</x-layouts.marketing>
@endauth