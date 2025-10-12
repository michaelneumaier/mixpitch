<!-- Next-Level FAQ Section -->
<div class="py-20 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden">
    <!-- Background Effects -->
    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/10 via-purple-600/10 to-pink-600/10 animate-gradient-x"></div>
    
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Section Header -->
        <div class="text-center mb-16 animate-fade-in-up">
            <div class="inline-flex items-center px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white/90 text-sm font-medium mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Got Questions?
            </div>
            <h2 class="text-4xl lg:text-5xl font-bold text-white mb-6">
                Frequently Asked 
                <span class="bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                    Questions
                </span>
            </h2>
            <p class="text-xl text-white/80 max-w-3xl mx-auto leading-relaxed">
                Everything you need to know about MixPitch and how our collaborative platform works for artists and audio professionals.
            </p>
        </div>

        <!-- FAQ Grid -->
        <div class="grid gap-6 md:gap-8" x-data="{ openItem: 'item-1' }">
            <!-- FAQ Item 1 -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.2s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
                    <button @click="openItem = openItem === 'item-1' ? null : 'item-1'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-1' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center mr-4 text-sm font-bold">
                                1
                            </div>
                            How does MixPitch work?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-blue-400 transition-transform duration-300"
                            :class="openItem === 'item-1' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-1'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            MixPitch connects artists with audio professionals for music mixing and mastering. Artists submit their tracks with project details and budget, audio professionals create their own interpretations, and artists choose the version that best captures their vision. Once selected, secure payment is processed and the artist receives their professionally enhanced track.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.3s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
                    <button @click="openItem = openItem === 'item-2' ? null : 'item-2'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-2' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center mr-4 text-sm font-bold">
                                2
                            </div>
                            How much does it cost to use MixPitch?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-purple-400 transition-transform duration-300"
                            :class="openItem === 'item-2' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-2'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            Creating an account is completely free for both artists and audio professionals. Artists pay only when they select a professional mix they want to finalize. Prices vary based on project complexity and the audio professional's rate, typically ranging from $50-$500 per project. There are no hidden fees, subscription costs, or upfront charges.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.4s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
                    <button @click="openItem = openItem === 'item-3' ? null : 'item-3'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-3' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-full flex items-center justify-center mr-4 text-sm font-bold">
                                3
                            </div>
                            Who owns the rights to my music?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-indigo-400 transition-transform duration-300"
                            :class="openItem === 'item-3' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-3'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            You retain 100% ownership of your music and all associated rights. When an audio professional creates a mix of your track, they're providing a technical service, not claiming ownership. The final mixed track belongs entirely to you, and you're free to release, sell, or distribute it however you choose. Our terms of service clearly outline these rights protections for your peace of mind.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.5s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
                    <button @click="openItem = openItem === 'item-4' ? null : 'item-4'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-4' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-teal-500 rounded-full flex items-center justify-center mr-4 text-sm font-bold">
                                4
                            </div>
                            How do audio professionals get paid?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-green-400 transition-transform duration-300"
                            :class="openItem === 'item-4' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-4'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            Audio professionals set their own rates when submitting a mix proposal. When an artist selects their work, payment is processed securely through our platform. Once the project is completed and finalized, the audio professional receives their payment minus a small platform fee. Payments are typically processed within 3-5 business days and can be withdrawn via PayPal, Stripe, or direct bank transfer.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.6s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
                    <button @click="openItem = openItem === 'item-5' ? null : 'item-5'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-5' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-orange-500 to-red-500 rounded-full flex items-center justify-center mr-4 text-sm font-bold">
                                5
                            </div>
                            What if I'm not satisfied with any of the mixes?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-orange-400 transition-transform duration-300"
                            :class="openItem === 'item-5' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-5'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            You're under no obligation to select any mix if you're not completely satisfied. You can provide detailed feedback to audio professionals and request revisions before making your final decision. If you still don't find what you're looking for, you can extend the submission deadline or close the project without making a selection. Our goal is to ensure you're fully satisfied with the final product.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- More Questions Contact -->
        <div class="mt-16 text-center animate-fade-in-up" style="animation-delay: 0.8s;">
            <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-8 max-w-2xl mx-auto">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-white mb-4">Still have questions?</h3>
                <p class="text-white/80 mb-6 leading-relaxed">
                    Our support team is here to help you every step of the way. Get personalized assistance with your projects and platform questions.
                </p>
                <a href="mailto:support@mixpitch.com" class="inline-flex items-center bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 px-8 rounded-xl transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>