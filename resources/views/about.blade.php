<x-layouts.app-sidebar>
<x-public-nav-header />
<div class="bg-gray-50 min-h-screen">
    <!-- Background Effects -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-purple-400/20 to-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-tr from-indigo-400/20 to-blue-600/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 left-1/4 w-64 h-64 bg-gradient-to-r from-blue-300/10 to-purple-300/10 rounded-full blur-2xl"></div>
        <div class="absolute bottom-1/3 right-1/4 w-48 h-48 bg-gradient-to-l from-indigo-300/15 to-purple-300/15 rounded-full blur-xl"></div>
    </div>

    <div class="relative min-h-screen bg-gradient-to-br from-blue-50/30 via-white to-purple-50/30 py-4 md:py-12">
        <div class="max-w-7xl mx-auto px-2 lg:px-8">

            <!-- Enhanced Hero Section -->
            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-3xl shadow-2xl overflow-hidden mb-16">
                <!-- Hero Background Effects -->
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/30 via-purple-50/20 to-blue-50/30"></div>
                <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-purple-400/10 rounded-full blur-2xl animate-pulse"></div>
                    <div class="absolute -bottom-20 -left-20 w-32 h-32 bg-indigo-400/10 rounded-full blur-xl animate-pulse" style="animation-delay: 1s;"></div>
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-blue-300/5 to-purple-300/5 rounded-full blur-3xl"></div>
                </div>
                
                <!-- Animated Sound Waves -->
                <div class="absolute inset-0 flex items-center justify-center opacity-5 overflow-hidden">
                    <div class="wave-container">
                        <div class="wave wave1"></div>
                        <div class="wave wave2"></div>
                        <div class="wave wave3"></div>
                    </div>
                </div>

                <div class="relative z-10 px-2 md:px-8 py-8 md:py-16 text-center">
                    <div class="max-w-4xl mx-auto">
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold mb-6 bg-gradient-to-r from-gray-900 via-indigo-800 to-purple-800 bg-clip-text text-transparent">
                            About MixPitch
                        </h1>
                        <p class="text-xl sm:text-2xl text-gray-600 font-medium leading-relaxed max-w-3xl mx-auto">
                            Empowering musicians and audio professionals to create, collaborate, and innovate in the world of music.
                        </p>
                        
                        <!-- Hero Stats -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-12 max-w-2xl mx-auto">
                            <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm hover:shadow-lg transition-all duration-300 hover:scale-105">
                                <div class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">1000+</div>
                                <div class="text-sm text-gray-600 font-medium">Projects Created</div>
                            </div>
                            <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm hover:shadow-lg transition-all duration-300 hover:scale-105">
                                <div class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">500+</div>
                                <div class="text-sm text-gray-600 font-medium">Audio Professionals</div>
                            </div>
                            <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm hover:shadow-lg transition-all duration-300 hover:scale-105">
                                <div class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">50+</div>
                                <div class="text-sm text-gray-600 font-medium">Countries</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Our Mission Section -->
            <div class="mb-16">
                <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50/20 to-indigo-50/20"></div>
                    <div class="absolute top-4 right-4 w-20 h-20 bg-blue-400/10 rounded-full blur-xl"></div>
                    
                    <div class="relative p-4 md:p-8">
                        <div class="flex items-center mb-8">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-3 w-14 h-14 flex items-center justify-center mr-4 shadow-lg">
                                <i class="fas fa-bolt text-white text-xl"></i>
                            </div>
                            <h2 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent">Our Mission</h2>
                        </div>
                        <p class="text-lg sm:text-xl leading-relaxed text-gray-700 max-w-4xl">
                            MixPitch is dedicated to revolutionizing the music industry by connecting talented musicians with
                            skilled audio professionals. We aim to foster creativity, facilitate collaboration, and provide a
                            platform where musical visions come to life through innovative technology and community-driven experiences.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Our Story Section -->
            <div class="mb-16">
                <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-50/20 to-pink-50/20"></div>
                    <div class="absolute bottom-4 left-4 w-16 h-16 bg-purple-400/10 rounded-full blur-lg"></div>
                    
                    <div class="relative p-4 md:p-8">
                        <div class="flex items-center mb-8">
                            <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl p-3 w-14 h-14 flex items-center justify-center mr-4 shadow-lg">
                                <i class="fas fa-book-open text-white text-xl"></i>
                            </div>
                            <h2 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-gray-900 to-purple-800 bg-clip-text text-transparent">Our Story</h2>
                        </div>

                        <div class="grid lg:grid-cols-2 gap-12 items-center">
                            <div class="space-y-6 text-lg text-gray-700 leading-relaxed">
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
                            
                            <div class="relative">
                                <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                    <div class="text-center">
                                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full p-6 w-20 h-20 mx-auto mb-6 shadow-lg">
                                            <i class="fas fa-users text-white text-2xl"></i>
                                        </div>
                                        <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent mb-4">
                                            MixPitch Community
                                        </h3>
                                        <p class="text-gray-600 leading-relaxed">
                                            A global network of musicians and audio professionals working together to create extraordinary music experiences.
                                        </p>
                                        
                                        <!-- Community Stats -->
                                        <div class="grid grid-cols-2 gap-4 mt-6">
                                            <div class="text-center">
                                                <div class="text-xl font-bold text-indigo-600">24/7</div>
                                                <div class="text-xs text-gray-500">Support</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-xl font-bold text-purple-600">Global</div>
                                                <div class="text-xs text-gray-500">Reach</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- What We Offer Section -->
            <div class="mb-16">
                <div class="text-center mb-12">
                    <h2 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent mb-4">
                        What We <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Offer</span>
                    </h2>
                    <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                        Solutions designed for both sides of the musical collaboration
                    </p>
                </div>

                <div class="grid lg:grid-cols-2 gap-8">
                    <!-- For Musicians -->
                    <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-[1.02]">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 to-indigo-50/20"></div>
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 to-blue-600"></div>
                        <div class="absolute top-4 right-4 w-16 h-16 bg-blue-400/10 rounded-full blur-lg"></div>
                        
                        <div class="relative p-8">
                            <div class="bg-gradient-to-r from-indigo-500 to-blue-600 rounded-xl p-3 w-14 h-14 flex items-center justify-center mb-6 shadow-lg">
                                <i class="fas fa-music text-white text-xl"></i>
                            </div>

                            <h3 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent mb-6">
                                For Musicians
                            </h3>

                            <ul class="space-y-4">
                                <li class="flex items-start group">
                                    <div class="bg-gradient-to-r from-indigo-500 to-blue-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <span class="text-gray-700"><strong class="text-gray-900">Access to a global network</strong> of audio professionals</span>
                                </li>
                                <li class="flex items-start group">
                                    <div class="bg-gradient-to-r from-indigo-500 to-blue-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <span class="text-gray-700"><strong class="text-gray-900">Multiple perspectives</strong> on your music</span>
                                </li>
                                <li class="flex items-start group">
                                    <div class="bg-gradient-to-r from-indigo-500 to-blue-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <span class="text-gray-700"><strong class="text-gray-900">Affordable mixing and mastering</strong> services</span>
                                </li>
                                <li class="flex items-start group">
                                    <div class="bg-gradient-to-r from-indigo-500 to-blue-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <span class="text-gray-700"><strong class="text-gray-900">Secure platform</strong> for file sharing and communication</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- For Audio Professionals -->
                    <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-[1.02]">
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-50/30 to-pink-50/20"></div>
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-500 to-pink-600"></div>
                        <div class="absolute bottom-4 left-4 w-16 h-16 bg-purple-400/10 rounded-full blur-lg"></div>
                        
                        <div class="relative p-8">
                            <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl p-3 w-14 h-14 flex items-center justify-center mb-6 shadow-lg">
                                <i class="fas fa-microphone text-white text-xl"></i>
                            </div>

                            <h3 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-6">
                                For Audio Professionals
                            </h3>

                            <ul class="space-y-4">
                                <li class="flex items-start group">
                                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <span class="text-gray-700"><strong class="text-gray-900">Diverse projects</strong> to work on and expand your portfolio</span>
                                </li>
                                <li class="flex items-start group">
                                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <span class="text-gray-700"><strong class="text-gray-900">Opportunity to showcase</strong> your skills to a wide audience</span>
                                </li>
                                <li class="flex items-start group">
                                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <span class="text-gray-700"><strong class="text-gray-900">Fair compensation</strong> for your expertise</span>
                                </li>
                                <li class="flex items-start group">
                                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <span class="text-gray-700"><strong class="text-gray-900">Networking</strong> with artists and other professionals</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Our Values Section -->
            <div class="mb-16">
                <div class="text-center mb-12">
                    <h2 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent mb-4">
                        Our <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Values</span>
                    </h2>
                    <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                        The principles that guide everything we do at MixPitch
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Collaboration -->
                    <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-105 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 to-indigo-50/20 group-hover:from-blue-50/50 group-hover:to-indigo-50/30 transition-all duration-300"></div>
                        <div class="absolute top-4 right-4 w-12 h-12 bg-blue-400/10 rounded-full blur-lg group-hover:bg-blue-400/20 transition-all duration-300"></div>
                        
                        <div class="relative p-8 text-center">
                            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full p-4 w-16 h-16 flex items-center justify-center mx-auto mb-6 shadow-lg group-hover:scale-110 transition-transform">
                                <i class="fas fa-handshake text-white text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Collaboration</h3>
                            <p class="text-gray-600 leading-relaxed">
                                We believe in the power of working together to create something extraordinary that transcends individual capabilities.
                            </p>
                        </div>
                    </div>

                    <!-- Innovation -->
                    <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-105 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-50/30 to-pink-50/20 group-hover:from-purple-50/50 group-hover:to-pink-50/30 transition-all duration-300"></div>
                        <div class="absolute bottom-4 left-4 w-12 h-12 bg-purple-400/10 rounded-full blur-lg group-hover:bg-purple-400/20 transition-all duration-300"></div>
                        
                        <div class="relative p-8 text-center">
                            <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-full p-4 w-16 h-16 flex items-center justify-center mx-auto mb-6 shadow-lg group-hover:scale-110 transition-transform">
                                <i class="fas fa-lightbulb text-white text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Innovation</h3>
                            <p class="text-gray-600 leading-relaxed">
                                We constantly strive to push the boundaries of what's possible in music collaboration through cutting-edge technology.
                            </p>
                        </div>
                    </div>

                    <!-- Community -->
                    <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-105 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-green-50/30 to-emerald-50/20 group-hover:from-green-50/50 group-hover:to-emerald-50/30 transition-all duration-300"></div>
                        <div class="absolute top-4 left-4 w-12 h-12 bg-green-400/10 rounded-full blur-lg group-hover:bg-green-400/20 transition-all duration-300"></div>
                        
                        <div class="relative p-8 text-center">
                            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-full p-4 w-16 h-16 flex items-center justify-center mx-auto mb-6 shadow-lg group-hover:scale-110 transition-transform">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Community</h3>
                            <p class="text-gray-600 leading-relaxed">
                                We foster a supportive environment where creativity thrives and meaningful relationships flourish across the globe.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Join Us CTA Section -->
            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-3xl shadow-2xl overflow-hidden">
                <!-- CTA Background Effects -->
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-black/20 via-transparent to-black/20"></div>
                <div class="absolute -top-20 -right-20 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-20 -left-20 w-32 h-32 bg-white/10 rounded-full blur-xl"></div>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                
                <!-- Pattern Overlay -->
                <div class="absolute inset-0 opacity-10">
                    <div class="bg-pattern w-full h-full"></div>
                </div>

                <div class="relative z-10 px-8 sm:px-12 py-16 text-center">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-6 text-white">
                        Join the MixPitch Community
                    </h2>
                    <p class="text-xl sm:text-2xl max-w-3xl mx-auto mb-12 text-white/90 leading-relaxed">
                        Whether you're a seasoned professional or just starting your musical journey, MixPitch is here to
                        help you connect, create, and succeed.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                        @auth
                        <a href="{{ route('projects.create') }}"
                            class="group inline-flex items-center px-8 py-4 bg-white text-indigo-600 text-lg font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 hover:bg-gray-50">
                            <i class="fas fa-plus mr-3 group-hover:scale-110 transition-transform"></i>
                            Start Collaborating
                        </a>
                        @else
                        <a href="{{ route('register') }}"
                            class="group inline-flex items-center px-8 py-4 bg-white text-indigo-600 text-lg font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 hover:bg-gray-50">
                            <i class="fas fa-user-plus mr-3 group-hover:scale-110 transition-transform"></i>
                            Join MixPitch
                        </a>
                        @endauth

                        <a href="{{ route('projects.index') }}"
                            class="group inline-flex items-center px-8 py-4 bg-transparent text-white text-lg font-bold border-2 border-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 hover:bg-white/10">
                            <i class="fas fa-search mr-3 group-hover:scale-110 transition-transform"></i>
                            Explore Projects
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced CSS for animations and effects -->
<style>
    /* Enhanced Audio visualization waves */
    .wave-container {
        position: relative;
        width: 100%;
        height: 400px;
    }

    .wave {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 120px;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23667eea" fill-opacity="0.3" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
        background-size: 1440px 120px;
        background-repeat: repeat-x;
        animation: wave-animation 25s linear infinite;
    }

    .wave1 {
        opacity: 0.4;
        animation-duration: 25s;
        animation-delay: 0s;
    }

    .wave2 {
        opacity: 0.3;
        animation-duration: 20s;
        animation-delay: -3s;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23764ba2" fill-opacity="0.3" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,149.3C960,160,1056,160,1152,138.7C1248,117,1344,75,1392,53.3L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
        background-size: 1440px 120px;
    }

    .wave3 {
        opacity: 0.2;
        animation-duration: 30s;
        animation-delay: -5s;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23f093fb" fill-opacity="0.3" d="M0,288L48,272C96,256,192,224,288,197.3C384,171,480,149,576,165.3C672,181,768,235,864,250.7C960,267,1056,245,1152,224C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
        background-size: 1440px 120px;
    }

    @keyframes wave-animation {
        0% {
            background-position-x: 0;
        }
        100% {
            background-position-x: 1440px;
        }
    }

    /* Enhanced background patterns */
    .bg-pattern {
        background-image: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%23ffffff" fill-opacity="0.4" fill-rule="evenodd"%3E%3C/path%3E%3C/svg%3E');
    }

    /* Smooth scroll behavior */
    html {
        scroll-behavior: smooth;
    }

    /* Enhanced hover effects */
    .group:hover .group-hover\:scale-110 {
        transform: scale(1.1);
    }

    .group:hover .group-hover\:scale-105 {
        transform: scale(1.05);
    }
</style>
</x-layouts.app-sidebar>