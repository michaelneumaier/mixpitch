<!-- FAQ Section Component -->
<div class="py-16 bg-base-200">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Frequently Asked <span class="text-primary">Questions</span>
            </h2>
            <p class="text-base-content/70 max-w-3xl mx-auto">Everything you need to know about MixPitch and how it
                works</p>
        </div>

        <div class="grid gap-6 md:gap-8" x-data="{ openItem: 'item-1' }">
            <!-- FAQ Item 1 -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <button @click="openItem = openItem === 'item-1' ? null : 'item-1'"
                    class="flex justify-between items-center w-full p-6 text-left"
                    :class="openItem === 'item-1' ? 'border-b border-base-200' : ''">
                    <h3 class="text-lg md:text-xl font-semibold">How does MixPitch work?</h3>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-primary transition-transform duration-300"
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
                    <p class="text-base-content/80">MixPitch connects artists with audio professionals for music mixing
                        and mastering. Artists submit their tracks, audio pros create their own versions, and artists
                        choose the one they like best. Once selected, the audio pro gets paid and the artist receives
                        the professionally mixed track.</p>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <button @click="openItem = openItem === 'item-2' ? null : 'item-2'"
                    class="flex justify-between items-center w-full p-6 text-left"
                    :class="openItem === 'item-2' ? 'border-b border-base-200' : ''">
                    <h3 class="text-lg md:text-xl font-semibold">How much does it cost to use MixPitch?</h3>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-primary transition-transform duration-300"
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
                    <p class="text-base-content/80">Creating an account is completely free for both artists and audio
                        professionals. Artists pay only when they select a professional mix they want to finalize.
                        Prices vary based on the project complexity and the audio professional's rate, typically ranging
                        from $50-$500 per project. There are no hidden fees or subscription costs.</p>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <button @click="openItem = openItem === 'item-3' ? null : 'item-3'"
                    class="flex justify-between items-center w-full p-6 text-left"
                    :class="openItem === 'item-3' ? 'border-b border-base-200' : ''">
                    <h3 class="text-lg md:text-xl font-semibold">Who owns the rights to my music?</h3>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-primary transition-transform duration-300"
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
                    <p class="text-base-content/80">You retain 100% ownership of your music. When an audio professional
                        creates a mix of your track, they're providing a service, not claiming ownership. The final
                        mixed track belongs entirely to you, and you're free to release, sell, or distribute it however
                        you choose. Our terms of service clearly outline these rights protections.</p>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <button @click="openItem = openItem === 'item-4' ? null : 'item-4'"
                    class="flex justify-between items-center w-full p-6 text-left"
                    :class="openItem === 'item-4' ? 'border-b border-base-200' : ''">
                    <h3 class="text-lg md:text-xl font-semibold">How do audio professionals get paid?</h3>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-primary transition-transform duration-300"
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
                    <p class="text-base-content/80">Audio professionals set their own rates when submitting a mix. When
                        an artist selects their mix, the payment is processed through our secure platform. Once the
                        project is completed, the audio professional receives their payment minus a small platform fee.
                        Payments are typically processed within 3-5 business days and can be withdrawn via PayPal,
                        Stripe, or direct bank transfer.</p>
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <button @click="openItem = openItem === 'item-5' ? null : 'item-5'"
                    class="flex justify-between items-center w-full p-6 text-left"
                    :class="openItem === 'item-5' ? 'border-b border-base-200' : ''">
                    <h3 class="text-lg md:text-xl font-semibold">What if I'm not satisfied with any of the mixes?</h3>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-primary transition-transform duration-300"
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
                    <p class="text-base-content/80">You're under no obligation to select any mix if you're not
                        completely satisfied. You can provide feedback to audio professionals and request revisions
                        before making your final decision. If you still don't find what you're looking for, you can
                        extend the submission deadline or close the project without making a selection. Our goal is to
                        ensure you're fully satisfied with the final product.</p>
                </div>
            </div>
        </div>

        <!-- More Questions Contact -->
        <div class="mt-12 text-center">
            <p class="mb-4 text-base-content/80">Still have questions? We're here to help!</p>
            <a href="#" class="btn btn-primary">Contact Support</a>
        </div>
    </div>
</div>