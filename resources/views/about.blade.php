@extends('components.layouts.app')

@section('content')
<div class="pt-4">
    <!-- Hero Section -->
    <div class="py-2 md:px-20">
        <h1 class="text-3xl md:text-7xl text-secondary text-center mb-6">
            <b>About Mix Pitch</b>
        </h1>
        <p class="md:mt-4 p-4 text-l md:text-3xl text-secondary text-center">
            Empowering musicians and audio professionals to create, collaborate, and innovate in the world of music.
        </p>
    </div>

    <!-- Our Mission -->
    <div class="bg-base-200 mt-4">
        <div class="py-8 md:py-6 px-4 md:px-20">
            <h2 class="font-bold text-3xl md:text-4xl text-center mb-6">Our Mission</h2>
            <p class="text-lg md:text-xl text-center">
                Mix Pitch is dedicated to revolutionizing the music industry by connecting talented musicians with skilled audio professionals. We aim to foster creativity, facilitate collaboration, and provide a platform where musical visions come to life.
            </p>
        </div>
    </div>

    <!-- Our Story -->
    <div class="py-8 md:py-6 px-4 md:px-20">
        <h2 class="font-bold text-3xl md:text-4xl text-center mb-6">Our Story</h2>
        <div class="flex flex-col md:flex-row items-center justify-center">
            <div class="md:w-1/2 mb-6 md:mb-0 md:pr-8">
                <p class="text-lg mb-4">
                    Mix Pitch was born from the frustration of countless audio engineers struggling to find real-world projects to work on. We saw talented professionals spending more time searching for opportunities than actually creating music.
                </p>
                <p class="text-lg mb-4">
                    At the same time, we noticed artists hesitating to work with unknown engineers, often sticking to their existing networks or big names in the industry. This created a barrier for new talent to break into the field and for artists to discover fresh perspectives.
                </p>
                <p class="text-lg">
                    Mix Pitch bridges this gap. We've created a platform where audio engineers can showcase their skills on real projects, building their portfolios and gaining valuable experience. For artists, it's an opportunity to take a chance on new talent, potentially discovering their next go-to engineer at an affordable price point.
                </p>
            </div>
            <div class="md:w-1/2">
                <div class="bg-gray-300 rounded-lg shadow-lg h-64 flex items-center justify-center">
                    <span class="text-gray-600 text-lg">Mix Pitch Team at Work</span>
                </div>
            </div>
        </div>
    </div>

    <!-- What We Offer -->
    <div class="bg-base-200 mt-4">
        <div class="py-8 md:py-6 px-4 md:px-20">
            <h2 class="font-bold text-3xl md:text-4xl text-center mb-6">What We Offer</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="font-semibold text-2xl mb-4">For Musicians</h3>
                    <ul class="list-disc list-inside text-lg">
                        <li>Access to a global network of audio professionals</li>
                        <li>Multiple perspectives on your music</li>
                        <li>Affordable mixing and mastering services</li>
                        <li>Secure platform for file sharing and communication</li>
                    </ul>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="font-semibold text-2xl mb-4">For Audio Professionals</h3>
                    <ul class="list-disc list-inside text-lg">
                        <li>Diverse projects to work on and expand your portfolio</li>
                        <li>Opportunity to showcase your skills to a wide audience</li>
                        <li>Fair compensation for your expertise</li>
                        <li>Networking with artists and other professionals</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Our Values -->
    <div class="py-8 md:py-6 px-4 md:px-20">
        <h2 class="font-bold text-3xl md:text-4xl text-center mb-6">Our Values</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="text-center">
                <i class="fas fa-handshake text-5xl text-primary mb-4"></i>
                <h3 class="font-semibold text-2xl mb-2">Collaboration</h3>
                <p class="text-lg">We believe in the power of working together to create something extraordinary.</p>
            </div>
            <div class="text-center">
                <i class="fas fa-lightbulb text-5xl text-primary mb-4"></i>
                <h3 class="font-semibold text-2xl mb-2">Innovation</h3>
                <p class="text-lg">We constantly strive to push the boundaries of what's possible in music collaboration.</p>
            </div>
            <div class="text-center">
                <i class="fas fa-users text-5xl text-primary mb-4"></i>
                <h3 class="font-semibold text-2xl mb-2">Community</h3>
                <p class="text-lg">We foster a supportive environment where creativity thrives and relationships flourish.</p>
            </div>
        </div>
    </div>

    <!-- Join Us -->
    <div class="bg-base-200 mt-4">
        <div class="py-8 md:py-6 px-4 md:px-20 text-center">
            <h2 class="font-bold text-3xl md:text-4xl mb-6">Join the Mix Pitch Community</h2>
            <p class="text-lg md:text-xl mb-6">
                Whether you're a seasoned professional or just starting your musical journey, Mix Pitch is here to help you connect, create, and succeed. Join our community today and be part of the future of music collaboration!
            </p>
            @auth
            <a href="{{ route('projects.create') }}"
                class="transition-all hover:scale-[1.02] inline-block bg-accent hover:bg-accent-focus text-xl text-center font-bold py-2 px-6 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded whitespace-nowrap">
                Start Collaborating
            </a>
            @else
            <a href="{{ route('register') }}"
                class="transition-all hover:scale-[1.02] inline-block bg-accent hover:bg-accent-focus text-xl text-center font-bold py-2 px-6 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded whitespace-nowrap">
                Join Mix Pitch
            </a>
            @endauth
        </div>
    </div>
</div>
@endsection