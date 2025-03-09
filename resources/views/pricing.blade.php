@extends('components.layouts.app')

@section('content')
<div class="pt-4">
    <!-- Minimal Hero Section -->
    <div class="bg-gradient-to-r from-base-300 to-base-100 rounded-xl shadow-lg mx-4 md:mx-12 mb-8">
        <div class="relative overflow-hidden">
            <!-- Decorative sound waves -->
            <div class="absolute inset-0 flex items-center justify-center opacity-10 overflow-hidden">
                <div class="wave-container">
                    <div class="wave wave1"></div>
                    <div class="wave wave2"></div>
                </div>
            </div>

            <div class="relative z-10 py-12 px-6 md:px-20 text-center">
                <h1 class="text-3xl md:text-5xl text-primary font-bold mb-4">
                    Pricing & Plans
                </h1>
                <p class="md:mt-4 text-lg md:text-2xl text-secondary max-w-3xl mx-auto">
                    Choose the perfect plan for your music collaboration needs
                </p>
            </div>
        </div>
    </div>

    <!-- Modernized Pricing Plans -->
    <div class="mx-4 md:mx-12 mb-12">
        <div class="relative">
            <!-- Subtle decorative element -->
            <div
                class="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-accent/20 h-12 w-12 rounded-full blur-xl">
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Basic Plan -->
                <div
                    class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-4">
                            <h2 class="text-2xl font-bold">Basic</h2>
                            <span class="px-3 py-1 bg-base-200 rounded-full text-sm font-medium">Free</span>
                        </div>
                        <p class="text-4xl font-bold mb-6">$0<span
                                class="text-lg font-normal text-base-content/70">/month</span></p>
                        <div class="border-t border-base-200 my-6"></div>
                        <ul class="space-y-4 mb-8 flex-grow">
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Upload 1 project</span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Work on up to 3 projects</span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Basic collaboration tools</span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Community support</span>
                            </li>
                        </ul>
                        <a href="#"
                            class="block bg-base-200 hover:bg-base-300 text-center py-3 px-4 rounded-lg font-semibold transition-colors">
                            Get Started for Free
                        </a>
                    </div>
                </div>

                <!-- Pro Artist Plan - Featured -->
                <div
                    class="bg-gradient-to-b from-primary/5 to-primary/0 rounded-xl shadow-lg overflow-hidden flex flex-col relative transform transition-all duration-300 hover:-translate-y-2 hover:shadow-xl">
                    <!-- Featured badge -->
                    <div class="absolute top-4 right-4">
                        <span
                            class="bg-accent text-white text-xs font-bold px-2.5 py-1.5 rounded-full uppercase tracking-wide shadow-sm">Popular</span>
                    </div>

                    <div class="h-2 bg-primary"></div>
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-4">
                            <h2 class="text-2xl font-bold text-primary">Pro Artist</h2>
                            <span class="px-3 py-1 bg-primary/10 text-primary rounded-full text-sm font-medium">Best
                                Value</span>
                        </div>
                        <p class="text-4xl font-bold mb-6">$9<span
                                class="text-lg font-normal text-base-content/70">/month</span></p>
                        <div class="border-t border-base-200 my-6"></div>
                        <ul class="space-y-4 mb-8 flex-grow">
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span><strong>Unlimited</strong> project uploads</span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span><strong>2 prioritized</strong> projects at a time</span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Advanced collaboration tools</span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Priority support</span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-primary mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Custom portfolio page</span>
                            </li>
                        </ul>
                        <a href="{{ route('register') }}"
                            class="transition-all transform hover:scale-105 block bg-primary hover:bg-primary-focus text-white text-center py-3 px-4 rounded-lg font-semibold shadow-md">
                            Go Pro Artist
                        </a>
                    </div>
                </div>

                <!-- Pro Engineer Plan -->
                <div
                    class="bg-gradient-to-b from-accent/5 to-accent/0 rounded-xl shadow-md overflow-hidden flex flex-col transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="h-2 bg-accent"></div>
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-4">
                            <h2 class="text-2xl font-bold text-accent">Pro Engineer</h2>
                            <span
                                class="px-3 py-1 bg-accent/10 text-accent rounded-full text-sm font-medium">Expert</span>
                        </div>
                        <p class="text-4xl font-bold mb-6">$9<span
                                class="text-lg font-normal text-base-content/70">/month</span></p>
                        <div class="border-t border-base-200 my-6"></div>
                        <ul class="space-y-4 mb-8 flex-grow">
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-accent mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Work on <strong>unlimited</strong> projects</span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-accent mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span><strong>5 prioritized</strong> pitches per month</span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-accent mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Advanced collaboration tools</span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-accent mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Priority support</span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-accent mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Custom portfolio page</span>
                            </li>
                        </ul>
                        <a href="{{ route('register') }}"
                            class="transition-all transform hover:scale-105 block bg-accent hover:bg-accent-focus text-white text-center py-3 px-4 rounded-lg font-semibold shadow-md">
                            Go Pro Engineer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modernized FAQ Section -->
    <div class="mx-4 md:mx-12 mb-12">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-8 md:p-10">
                <div class="flex items-center mb-8">
                    <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-secondary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="font-bold text-2xl md:text-3xl">Frequently Asked Questions</h2>
                </div>

                <div class="space-y-4">
                    <div class="border border-base-200 rounded-lg overflow-hidden">
                        <details class="group">
                            <summary class="flex items-center justify-between p-4 cursor-pointer">
                                <h3 class="text-lg font-medium">Can I upgrade or downgrade my plan at any time?</h3>
                                <svg class="h-5 w-5 text-primary transition-transform group-open:rotate-180"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <div class="p-4 pt-0 border-t border-base-200">
                                <p class="text-base-content/80">Yes, you can upgrade or downgrade your plan at any time.
                                    Changes will be reflected in your next billing cycle.</p>
                            </div>
                        </details>
                    </div>

                    <div class="border border-base-200 rounded-lg overflow-hidden">
                        <details class="group">
                            <summary class="flex items-center justify-between p-4 cursor-pointer">
                                <h3 class="text-lg font-medium">Is there a free trial for the Pro plan?</h3>
                                <svg class="h-5 w-5 text-primary transition-transform group-open:rotate-180"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <div class="p-4 pt-0 border-t border-base-200">
                                <p class="text-base-content/80">We offer a 14-day free trial for our Pro plan. You can
                                    cancel anytime during the trial period without being charged.</p>
                            </div>
                        </details>
                    </div>

                    <div class="border border-base-200 rounded-lg overflow-hidden">
                        <details class="group">
                            <summary class="flex items-center justify-between p-4 cursor-pointer">
                                <h3 class="text-lg font-medium">What payment methods do you accept?</h3>
                                <svg class="h-5 w-5 text-primary transition-transform group-open:rotate-180"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <div class="p-4 pt-0 border-t border-base-200">
                                <p class="text-base-content/80">We accept all major credit cards, PayPal, and bank
                                    transfers for Enterprise plans.</p>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Call to Action -->
    <div class="mx-4 md:mx-12 mb-12 relative overflow-hidden">
        <div class="bg-gradient-to-r from-accent via-primary to-secondary rounded-xl shadow-lg overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute inset-0 bg-pattern opacity-10"></div>
            <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white opacity-10 blur-xl"></div>
            <div class="absolute -left-16 -bottom-16 h-64 w-64 rounded-full bg-white opacity-10 blur-xl"></div>

            <div class="relative z-10 p-8 md:p-14 text-center">
                <h2 class="font-bold text-3xl md:text-4xl mb-6 text-white">Ready to Elevate Your Music Collaboration?
                </h2>
                <p class="text-xl max-w-3xl mx-auto mb-8 text-white/90">
                    Join MixPitch today and start connecting with talented musicians and audio professionals worldwide.
                </p>

                <div class="flex flex-col md:flex-row items-center justify-center gap-6">
                    <a href="{{ route('register') }}"
                        class="transition-all transform hover:scale-105 inline-block bg-white text-primary text-xl text-center font-bold py-4 px-10 border-b-4 border-white/80 shadow-lg shadow-primary/30 rounded-lg">
                        <span class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Get Started Now
                        </span>
                    </a>

                    <a href="{{ route('about') }}"
                        class="transition-all transform hover:scale-105 inline-block bg-transparent text-white text-xl text-center font-bold py-4 px-10 border-2 border-white shadow-lg shadow-primary/10 rounded-lg">
                        <span class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Learn More
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Audio visualization waves */
    .wave-container {
        position: relative;
        width: 100%;
        height: 300px;
    }

    .wave {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 100px;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.5" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
        background-size: 1440px 100px;
        background-repeat: repeat-x;
        animation: wave-animation 20s linear infinite;
    }

    .wave1 {
        opacity: 0.3;
        animation-duration: 20s;
        animation-delay: 0s;
    }

    .wave2 {
        opacity: 0.2;
        animation-duration: 17s;
        animation-delay: -2s;
    }

    @keyframes wave-animation {
        0% {
            background-position-x: 0;
        }

        100% {
            background-position-x: 1440px;
        }
    }

    /* Background patterns */
    .bg-pattern {
        background-image: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%23ffffff" fill-opacity="1" fill-rule="evenodd"%3E%3C/path%3E%3C/svg%3E');
    }
</style>
@endsection