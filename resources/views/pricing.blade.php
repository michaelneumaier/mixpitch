@extends('components.layouts.app')

@section('content')
<div class="pt-4">
    <!-- Hero Section -->
    <div class="py-2 md:px-20">
        <h1 class="text-3xl md:text-7xl text-secondary text-center mb-6">
            <b>Pricing & Plans</b>
        </h1>
        <p class="md:mt-4 p-4 text-l md:text-3xl text-secondary text-center">
            Choose the perfect plan for your music collaboration needs
        </p>
    </div>

    <!-- Pricing Plans -->
    <div class="py-8 md:py-12 px-4 md:px-20">
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Basic Plan -->
            <div class="bg-white rounded-lg shadow-lg p-6 flex flex-col">
                <h2 class="text-2xl font-bold mb-4 text-center">Basic</h2>
                <p class="text-4xl font-bold text-center mb-6">$0<span class="text-lg font-normal">/month</span></p>
                <ul class="list-disc list-inside mb-6 flex-grow">
                    <li>Upload 1 project</li>
                    <li>Work on up to 3 projects</li>
                    <li>Basic collaboration tools</li>
                    <li>Community support</li>
                </ul>
                <a href="#" class="btn btn-primary w-full">Get Started</a>
            </div>

            <!-- Pro Artist Plan -->
            <div class="bg-primary text-white rounded-lg shadow-lg p-6 flex flex-col transform scale-105">
                <h2 class="text-2xl font-bold mb-4 text-center">Pro Artist</h2>
                <p class="text-4xl font-bold text-center mb-6">$9<span class="text-lg font-normal">/month</span></p>
                <ul class="list-disc list-inside mb-6 flex-grow">
                    <li>Upload unlimited projects</li>
                    <li>2 projects prioritized at a time</li>
                    <li>Advanced collaboration tools</li>
                    <li>Priority support</li>
                    <li>Custom portfolio page</li>
                </ul>
                <a href="{{ route('register') }}"
                    class="transition-all hover:scale-[1.02] inline-block bg-accent hover:bg-accent-focus text-xl text-black text-center font-bold py-2 px-6 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded whitespace-nowrap">
                    Go Pro Artist
                </a>            </div>

            <!-- Pro Engineer Plan -->
            <div class="bg-base-300 text-secondary rounded-lg shadow-lg p-6 flex flex-col transform scale-105">
                <h2 class="text-2xl font-bold mb-4 text-center">Pro Engineer</h2>
                <p class="text-4xl font-bold text-center mb-6">$9<span class="text-lg font-normal">/month</span></p>
                <ul class="list-disc list-inside mb-6 flex-grow">
                    <li>Work on unlimited projects</li>
                    <li>5 prioritized pitches per month</li>
                    <li>Advanced collaboration tools</li>
                    <li>Priority support</li>
                    <li>Custom portfolio page</li>
                </ul>
                <a href="{{ route('register') }}"
                    class="transition-all hover:scale-[1.02] inline-block bg-accent hover:bg-accent-focus text-xl text-center font-bold py-2 px-6 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded whitespace-nowrap">
                    Go Pro Engineer
                </a>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="bg-base-200 mt-4">
        <div class="py-8 md:py-12 px-4 md:px-20">
            <h2 class="font-bold text-3xl md:text-4xl text-center mb-8">Frequently Asked Questions</h2>
            <div class="space-y-4">
                <div class="collapse collapse-plus bg-base-100">
                    <input type="radio" name="my-accordion-3" checked="checked" /> 
                    <div class="collapse-title text-xl font-medium">
                        Can I upgrade or downgrade my plan at any time?
                    </div>
                    <div class="collapse-content"> 
                        <p>Yes, you can upgrade or downgrade your plan at any time. Changes will be reflected in your next billing cycle.</p>
                    </div>
                </div>
                <div class="collapse collapse-plus bg-base-100">
                    <input type="radio" name="my-accordion-3" /> 
                    <div class="collapse-title text-xl font-medium">
                        Is there a free trial for the Pro plan?
                    </div>
                    <div class="collapse-content"> 
                        <p>We offer a 14-day free trial for our Pro plan. You can cancel anytime during the trial period without being charged.</p>
                    </div>
                </div>
                <div class="collapse collapse-plus bg-base-100">
                    <input type="radio" name="my-accordion-3" /> 
                    <div class="collapse-title text-xl font-medium">
                        What payment methods do you accept?
                    </div>
                    <div class="collapse-content"> 
                        <p>We accept all major credit cards, PayPal, and bank transfers for Enterprise plans.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="py-8 md:py-12 px-4 md:px-20 text-center">
        <h2 class="font-bold text-3xl md:text-4xl mb-6">Ready to elevate your music collaboration?</h2>
        <p class="text-lg md:text-xl mb-6">
            Join Mix Pitch today and start connecting with talented musicians and audio professionals worldwide.
        </p>
        <a href="{{ route('register') }}"
            class="transition-all hover:scale-[1.02] inline-block bg-accent hover:bg-accent-focus text-xl text-center font-bold py-2 px-6 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded whitespace-nowrap">
            Get Started Now
        </a>
    </div>
</div>
@endsection
