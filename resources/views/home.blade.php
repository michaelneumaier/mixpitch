@extends('components.layouts.app')

@section('content')
<div class="pt-4">
    <div class="py-2 md:px-20">
        <h1 class="text-3xl md:text-7xl text-secondary text-center grid grid-cols-2 gap-x-4">
            <div class="text-right">
                <b>Empower</b><br>
                <b>Elevate</b><br>
                <b>Unleash</b>
            </div>
            <div class="text-left">
                Musicians.<br>
                Mixers.<br>
                Creativity.
            </div>
        </h1>



        <p class="md:mt-4 p-4 text-l md:text-3xl text-secondary text-center">
            MixPitch is the ultimate platform for musicians to have their music mixed by anyone. With real-world
            recordings and a level playing field, it's never been easier to get paid for mixing.
        </p>
        <div class="flex flex-col md:flex-row justify-center my-4 md:mt-8 md:space-x-4 px-10 md:px-4">
            <div class="w-full md:w-1/2 lg:w-1/3 mb-8 md:mb-4 md:mb-0">
                @auth
                <a href="{{ route('projects.create') }}"
                    class="transition-all hover:scale-[1.02] block bg-accent hover:bg-accent-focus text-xl text-center font-bold w-full py-2 px-4 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded whitespace-nowrap">Submit
                    Your Music</a>
                @else
                <a href="{{ route('login') }}"
                    class="transition-all hover:scale-[1.02] block bg-accent hover:bg-accent-focus text-xl text-center font-bold w-full py-2 px-4 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded whitespace-nowrap">Submit
                    Your Music</a>
                @endauth
            </div>
            <div class="w-full md:w-1/2 lg:w-1/3">
                <a href="{{ route('projects.index') }}"
                    class="transition-all hover:scale-[1.02] block bg-button hover:bg-buttonFocus text-xl font-bold text-center w-full py-2 px-4 shadow-lightGlow shadow-button hover:shadow-focus rounded">Browse
                    Projects</a>
            </div>
        </div>

    </div>

    <div class="bg-base-200 mt-4">
        <div class="grid md:grid-cols-3 grid-cols-1 md:gap-4">
            <!-- Left Column -->
            <div class="col-span-2 md:col-span-2">
                <div class="p-8 md:p-6 h-full">
                    <h2 class="font-bold text-2xl md:text-3xl mb-2 md:mb-4">Share Your Hits or Pitch Your Mix</h2>
                    <p class="text-lg mb-4 p-2">Discover the power of MixPitch, a web app that revolutionizes
                        music
                        mixing.
                        Whether you're an artist or a mixer/engineer, MixPitch provides the platform to enhance your
                        skills and get paid for your work.</p>

                    <div class="flex flex-wrap md:mt-10">
                        <!-- First Block -->
                        <div class="mb-4 md:w-1/2">
                            <h3 class="font-bold text-2xl md:text-3xl mb-2">Unlock Potential</h3>
                            <p class="p-2">MixPitch levels the playing field, giving artists and mixers/engineers the
                                opportunity to
                                shine.</p>
                        </div>
                        <!-- Second Block -->
                        <div class="md:w-1/2 md:px-5">
                            <h3 class="font-bold text-2xl md:text-3xl mb-2">Boost Success</h3>
                            <p class="p-2">With MixPitch, artists and mixers/engineers can take their music to new
                                heights.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Image -->
            <div class="col-span-1 md:col-span-1 bg-cover bg-center min-h-[300px]"
                style="background-image: url({{ asset('images/home_mixing.jpg') }})">

            </div>
        </div>
    </div>


    <div class="flex items-center p-4 md:p-5">
        <div class="md:w-5/6 mx-auto flex flex-wrap">
            <div class="w-full p-8 mb-10 md:mb-0 md:w-1/2 md:px-4">
                <h2 class="font-bold text-2xl mb-4">How MixPitch Works for Artists</h2>
                <p class="text-lg">
                    Elevate your music by leveraging the expertise of our vast community. Upload your tracks,
                    critique diverse mixes, and handpick the one that resonates with your vision.
                </p>
            </div>
            <div class="w-full p-8 md:w-1/2 md:px-4">
                <h2 class="font-bold text-2xl mb-4">How MixPitch Works for Mixing/Mastering Engineers</h2>
                <p class="text-lg">
                    Dive deep into real-world mixing challenges with tracks from aspiring artists. Showcase your
                    expertise, build a compelling portfolio, and solidify your place among audio professionals.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection