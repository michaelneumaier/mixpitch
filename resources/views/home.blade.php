@extends('layouts.app')

@section('content')
<div class="container-home">
    <div class="py-2 md:px-20">
        <h1 class="text-5xl md:text-7xl text-white text-center text-clip">Empower&nbsp;Musicians. Elevate&nbsp;Mixers.
            Unleash&nbsp;Creativity.</h1>
        <p class="md:mt-4 p-4 text-2xl md:text-3xl text-white text-center">
            MixPitch is the ultimate platform for musicians to have their music mixed by anyone. With real-world
            recordings and a level playing field, it's never been easier to get paid for mixing.
        </p>
        <div class="flex flex-col md:flex-row justify-center mt-8 md:space-x-4 px-20 md:px-4">
            <div class="w-full md:w-1/2 lg:w-1/3 mb-4 md:mb-0">
                @auth
                <a href="{{ route('projects.upload') }}"
                    class="block bg-blue-500 hover:bg-blue-400 text-2xl text-white text-center font-bold w-full py-2 px-4 border-b-4 border-blue-700 hover:border-blue-500 rounded whitespace-nowrap">Submit
                    Your Music</a>
                @else
                <a href="{{ route('login') }}"
                    class="block bg-blue-500 hover:bg-blue-400 text-2xl text-white text-center font-bold w-full py-2 px-4 border-b-4 border-blue-700 hover:border-blue-500 rounded whitespace-nowrap">Submit
                    Your Music</a>
                @endauth
            </div>
            <div class="w-full md:w-1/2 lg:w-1/3">
                <a href="{{ route('projects.index') }}"
                    class="block bg-gray-500 hover:bg-gray-400 text-2xl text-white text-center font-bold w-full py-2 px-4 border-b-4 border-gray-700 hover:border-gray-500 rounded">Browse
                    Projects</a>
            </div>
        </div>

    </div>

    <div class="bg-light mt-16">
        <div class="grid md:grid-cols-3 grid-cols-1 md:gap-4">
            <!-- Left Column -->
            <div class="col-span-2 md:col-span-2">
                <div class="p-5 h-full">
                    <h2 class="text-6xl mb-4">Share Your Hits or Pitch Your Mix</h2>
                    <p class="text-lg mb-4">Discover the power of MixPitch, a web app that revolutionizes music
                        mixing.
                        Whether you're an artist or a mixer/engineer, MixPitch provides the platform to enhance your
                        skills and get paid for your work.</p>

                    <div class="flex flex-wrap mt-10">
                        <!-- First Block -->
                        <div class="w-1/2">
                            <h3 class="text-4xl mb-2">Unlock Potential</h3>
                            <p>MixPitch levels the playing field, giving artists and mixers/engineers the
                                opportunity to
                                shine.</p>
                        </div>
                        <!-- Second Block -->
                        <div class="w-1/2 px-5">
                            <h3 class="text-4xl mb-2">Boost Success</h3>
                            <p>With MixPitch, artists and mixers/engineers can take their music to new heights.</p>
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


    <div class="flex items-center py-20">
        <div class="w-5/6 mx-auto flex flex-wrap">
            <div class="w-full mb-20 md:mb-0 md:w-1/2 text-white px-4">
                <h2 class="text-4xl mb-4">How MixPitch Works for Artists</h2>
                <p class="text-lg">
                    Elevate your music by leveraging the expertise of our vast community. Upload your tracks,
                    critique diverse mixes, and handpick the one that resonates with your vision.
                </p>
            </div>
            <div class="w-full md:w-1/2 text-white px-4">
                <h2 class="text-4xl mb-4">How MixPitch Works for Mixing/Mastering Engineers</h2>
                <p class="text-lg">
                    Dive deep into real-world mixing challenges with tracks from aspiring artists. Showcase your
                    expertise, build a compelling portfolio, and solidify your place among audio professionals.
                </p>
            </div>
        </div>
    </div>
</div>


{{-- <div class="container-home py-5">--}}
    {{-- <div class="py-5">--}}
        {{-- <h1 class="display-2 text-white align-items-center text-center">Empower&nbsp;Musicians.
            Elevate&nbsp;Mixers.--}}
            {{-- Unleash&nbsp;Creativity.</h1>--}}
        {{-- <p class="lead welcome-text">--}}
            {{-- MixPitch is the ultimate platform for musicians to have their music mixed by anyone. With real
            world--}}
            {{-- recordings and a level playing field, it's never been easier to get paid for mixing.--}}
            {{-- </p>--}}
        {{-- <div class="row col-lg-6 col-md-8 col-sm-10 col-12 mx-auto pb-4">--}}

            {{-- <div class="col-6 d-grid gap-2"> <!-- Adjust the column size if needed -->--}}
                {{-- @auth--}}
                {{-- <a href="{{ route('projects.upload') }}" --}} {{-- class="upload-btn">Submit Your--}}
                    {{-- Music</a>--}}
                {{-- <!-- Adjust the route as needed -->--}}
                {{-- @else--}}
                {{-- <a href="{{ route('login') }}" --}} {{-- class="upload-btn">Submit Your--}}
                    {{-- Music</a>--}}
                {{-- @endauth--}}
                {{-- </div>--}}
            {{-- <div class="col-6 d-grid gap-2">--}}
                {{-- <!-- Adjust the column size if needed -->--}}
                {{-- @auth--}}
                {{-- <a href="{{ route('login') }}" --}} {{-- class="browse-btn">Browse Projects</a>--}}
                {{-- <!-- Adjust the route as needed -->--}}
                {{-- @else--}}
                {{-- <a href="{{ route('login') }}" --}} {{-- class="browse-btn">Browse Projects</a>--}}
                {{-- @endauth--}}
                {{-- </div>--}}
            {{-- </div>--}}
        {{-- </div>--}}

    {{-- <div class="d-flex justify-content-center align-items-center bg-light">--}}
        {{-- <div class="row d-flex flex-row align-items-stretch">--}}
            {{-- <div class="col-md-7 align-items-center py-5 px-5">--}}
                {{-- <div class="py-5 pl-5">--}}
                    {{-- <!-- Main Header -->--}}
                    {{-- <h2 class="display-5 display-md-4">Share Your Hits or Pitch Your Mix</h2>--}}
                    {{-- <!-- Main Paragraph -->--}}
                    {{-- <p class="lead h5-md">Discover the power of MixPitch, a web app that revolutionizes music--}}
                        {{-- mixing. Whether you're an artist or a mixer/engineer, MixPitch provides the platform to--}}
                        {{-- enhance--}}
                        {{-- your skills and get paid for your work.</p>--}}
                    {{-- <div class="row mt-5">--}}
                        {{-- <!-- First Subheader and Paragraph -->--}}
                        {{-- <div class="col-md-6">--}}
                            {{-- <h4 class="display-6 h5-md">Unlock Potential</h4>--}}
                            {{-- <p>MixPitch levels the playing field, giving artists and mixers/engineers--}}
                                {{-- the--}}
                                {{-- opportunity to shine.</p>--}}
                            {{-- </div>--}}
                        {{-- <!-- Second Subheader and Paragraph -->--}}
                        {{-- <div class="col-md-6">--}}
                            {{-- <h4 class="display-6 h5-md">Boost Success</h4>--}}
                            {{-- <p>With MixPitch, artists and mixers/engineers can take their music to new--}}
                                {{-- heights.</p>--}}
                            {{-- </div>--}}
                        {{-- </div>--}}
                    {{-- </div>--}}
                {{-- </div>--}}
            {{-- <div class="col-md-5 d-flex flex-column" --}} {{--
                style="background-image: url('https://placehold.co/500'); background-size: cover; background-position: center; min-height: 300px;">
                --}}
                {{-- </div>--}}


            {{-- </div>--}}
        {{-- </div>--}}

    {{-- <div class="d-flex justify-content-center align-items-center py-5">--}}
        {{-- <div class="row col-10 align-items-center py-5">--}}
            {{-- <!-- First Column for Artists -->--}}
            {{-- <div class="col-md-6 text-white align-items-center px-5">--}}
                {{-- <h2 class="display-5 pb-4">How MixPitch Works for Artists</h2>--}}
                {{-- <p class="lead">--}}
                    {{-- Elevate your music by leveraging the expertise of our vast community. Upload your tracks,--}}
                    {{-- critique diverse mixes, and handpick the one that resonates with your vision.--}}
                    {{-- </p>--}}
                {{-- </div>--}}

            {{-- <!-- Second Column for Mixing/Mastering Engineers -->--}}
            {{-- <div class="col-md-6 text-white align-items-center px-5">--}}
                {{-- <h2 class="display-5 pb-4">How MixPitch Works for Mixing/Mastering Engineers</h2>--}}
                {{-- <p class="lead">--}}
                    {{-- Dive deep into real-world mixing challenges with tracks from aspiring artists. Showcase
                    your--}}
                    {{-- expertise, build a compelling portfolio, and solidify your place among audio professionals.--}}
                    {{-- </p>--}}
                {{-- </div>--}}
            {{-- </div>--}}
        {{-- </div>--}}


    {{-- </div>--}}

@endsection