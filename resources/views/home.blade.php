@extends('components.layouts.app')

@section('content')
<div class="pt-4">
    <!-- Hero Section with Empower, Elevate, Unleash -->
    <div class="py-2 md:px-20">
        <h1 class="text-3xl md:text-7xl text-secondary text-center grid grid-cols-2 gap-x-2">
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

        <!-- Introduction Section -->
        <p class="md:mt-4 p-4 text-l md:text-3xl text-secondary text-center">
            MixPitch is the ultimate platform for musicians to collaborate with mixers, producers, and audio engineers
            from around the world. Whether you're looking to refine your tracks or showcase your mixing skills, MixPitch
            connects you with a community dedicated to elevating your sound.
        </p>

        <!-- Call to Action Buttons -->
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

    <!-- Features Highlights -->
    <div class="bg-base-200 mt-4">
        <div class="py-8 md:py-6 px-4 md:px-20">
            <h2 class="font-bold text-3xl md:text-4xl text-center mb-6">Why MixPitch?</h2>
            <div class="grid md:grid-cols-3 grid-cols-1 md:gap-6">
                <!-- For Artists -->
                <div class="p-4">
                    <h3 class="font-semibold text-2xl mb-2">For Artists</h3>
                    <ul class="list-disc list-inside text-lg">
                        <li><strong>Unlock Global Talent:</strong> Upload your tracks and receive contributions from
                            multiple audio engineers and producers.</li>
                        <li><strong>Pay for Perfection:</strong> Only invest in the work that resonates with your
                            vision.</li>
                        <li><strong>Diverse Perspectives:</strong> Experience fresh takes on your music to elevate your
                            sound.</li>
                    </ul>
                </div>
                <!-- For Audio Professionals -->
                <div class="p-4">
                    <h3 class="font-semibold text-2xl mb-2">For Audio Professionals</h3>
                    <ul class="list-disc list-inside text-lg">
                        <li><strong>Expand Your Portfolio:</strong> Work on a variety of projects to showcase your
                            skills.</li>
                        <li><strong>Gain Real Experience:</strong> Collaborate with artists and receive valuable
                            feedback.</li>
                        <li><strong>Earn Opportunities:</strong> Get compensated when your work aligns with the artist's
                            needs.</li>
                    </ul>
                </div>
                <!-- Why Choose MixPitch -->
                <div class="p-4">
                    <h3 class="font-semibold text-2xl mb-2">Why Choose MixPitch?</h3>
                    <ul class="list-disc list-inside text-lg">
                        <li><strong>Collaborative Environment:</strong> Multiple creatives can work on the same project
                            simultaneously.</li>
                        <li><strong>Risk-Free Exploration:</strong> Artists can explore different styles without upfront
                            costs.</li>
                        <li><strong>Community Growth:</strong> Join a supportive network aimed at mutual growth and
                            success.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <div class="flex items-center p-4 md:p-5">
        <div class="md:w-5/6 mx-auto flex flex-wrap">
            <!-- For Artists -->
            <div class="w-full p-8 mb-10 md:mb-0 md:w-1/2 md:px-4">
                <h2 class="font-bold text-2xl mb-4">How MixPitch Works for Artists</h2>
                <p class="text-lg">
                    Elevate your music by leveraging the expertise of our vast community. Upload your tracks, critique
                    diverse mixes, and handpick the one that resonates with your vision.
                </p>
            </div>
            <!-- For Audio Professionals -->
            <div class="w-full p-8 md:w-1/2 md:px-4">
                <h2 class="font-bold text-2xl mb-4">How MixPitch Works for Mixing/Mastering Engineers</h2>
                <p class="text-lg">
                    Dive deep into real-world mixing challenges with tracks from aspiring artists. Showcase your
                    expertise, build a compelling portfolio, and solidify your place among audio professionals.
                </p>
            </div>
        </div>
    </div>

    <!-- Join the Movement -->
    <div class="bg-base-200 mt-4">
        <div class="p-8 md:p-6 text-center">
            <h2 class="font-bold text-3xl md:text-4xl mb-4">Join the MixPitch Movement</h2>
            <p class="text-lg md:text-xl mb-6">
                Ready to take your music or audio career to the next level? Dive into a world where collaboration fuels
                creativity.
            </p>
            @auth
            <a href="{{ route('projects.create') }}"
                class="transition-all hover:scale-[1.02] inline-block bg-accent hover:bg-accent-focus text-xl text-center font-bold py-2 px-6 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded whitespace-nowrap">Get
                Started Today</a>
            @else
            <a href="{{ route('register') }}"
                class="transition-all hover:scale-[1.02] inline-block bg-accent hover:bg-accent-focus text-xl text-center font-bold py-2 px-6 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded whitespace-nowrap">Get
                Started Today</a>
            @endauth
        </div>
    </div>

    <!-- Testimonials (Optional) -->
    <div class="py-8 md:py-6 px-4 md:px-20">
        <h2 class="font-bold text-3xl md:text-4xl text-center mb-6">What Our Users Say</h2>
        <div class="grid md:grid-cols-3 grid-cols-1 md:gap-6">
            <!-- Testimonial 1 -->
            <div class="p-4">
                <div class="bg-white p-6 rounded shadow">
                    <p class="text-lg mb-4">"MixPitch has transformed the way I collaborate with audio engineers. The
                        platform is intuitive and the community is incredibly supportive."</p>
                    <p class="font-semibold">- Alex R., Musician</p>
                </div>
            </div>
            <!-- Testimonial 2 -->
            <div class="p-4">
                <div class="bg-white p-6 rounded shadow">
                    <p class="text-lg mb-4">"As an audio engineer, MixPitch has provided me with endless opportunities
                        to showcase my skills and grow my portfolio."</p>
                    <p class="font-semibold">- Jamie L., Audio Engineer</p>
                </div>
            </div>
            <!-- Testimonial 3 -->
            <div class="p-4">
                <div class="bg-white p-6 rounded shadow">
                    <p class="text-lg mb-4">"The collaborative environment on MixPitch is unparalleled. I've connected
                        with talented artists and professionals worldwide."</p>
                    <p class="font-semibold">- Taylor M., Producer</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection