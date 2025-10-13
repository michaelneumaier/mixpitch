<!-- Next-Level FAQ Section -->
<div class="py-10 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden">
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
                Everything you need to know about MixPitch and how our platform works for musicians, studios, and audio professionals.
            </p>
        </div>

        <!-- FAQ Grid -->
        <div class="grid gap-6 md:gap-8" x-data="{ openItem: 'item-1' }">
            <!-- FAQ Item 1: Revision Policy -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.2s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-1' ? null : 'item-1'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-1' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            How does the revision policy work?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-purple-400 transition-transform duration-300"
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
                            When creating a client management project, you define how many revision rounds are included and the price for additional rounds. If your client exceeds the included revisions, the system automatically generates a new payment milestone with your configured pricing—no awkward conversations about extra fees. Clients see the revision policy in their portal from day one, and payment is required before submitting the next round beyond what's included.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 2: Client Portal Access -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.3s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-2' ? null : 'item-2'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-2' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            Can clients access their files without creating an account?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-blue-400 transition-transform duration-300"
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
                            Yes! When using the client management workflow, your clients receive a secure, branded portal link that expires after 7 days—no account signup required. They can listen to files, leave time-stamped feedback, approve deliverables, upload their own files, and make payments all from a mobile-friendly interface. This makes the client experience seamless while you maintain full professional control over the project workflow.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 3: Existing Clients -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.4s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-3' ? null : 'item-3'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-3' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            Can I use MixPitch for my existing clients?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-purple-400 transition-transform duration-300"
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
                            Absolutely! The client management workflow is perfect for your existing clients. Create a secure portal for each project, send them a link, and they access everything without creating an account. You control the entire workflow—revision policies, milestones, file delivery—while your clients enjoy a simple, professional experience. Many studios use MixPitch exclusively for client project management rather than the marketplace features.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 4: Payment Methods -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.5s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-4' ? null : 'item-4'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-4' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                            What payment methods are supported?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-blue-400 transition-transform duration-300"
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
                            MixPitch supports flexible payment options to fit your business: Stripe Connect for credit/debit cards, PayPal for direct PayPal payments, and manual payment marking for cash, check, wire transfers, or any other offline payment method you prefer. When clients pay through the platform, funds are processed securely and watermarks are removed automatically. For manual payments, you mark milestones as paid once you receive payment outside the platform.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 5: Watermarking -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.6s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-5' ? null : 'item-5'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-5' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            How does watermarking protect my files?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-purple-400 transition-transform duration-300"
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
                            You have full control over file watermarking. When enabled, preview files are watermarked to prevent clients from downloading final deliverables before paying. Once a milestone is marked as paid (either through platform payment processing or manual confirmation), watermarks are removed instantly and clients gain access to the clean, professional files. This optional protection safeguards your work while maintaining a seamless approval workflow.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 6: Unlimited Revisions -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.7s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-6' ? null : 'item-6'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-6' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                                </svg>
                            </div>
                            What happens if my client wants unlimited revisions?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-blue-400 transition-transform duration-300"
                            :class="openItem === 'item-6' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-6'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            You have flexibility with revision policies. If you want to offer unlimited revisions, simply set the price for additional rounds to $0 in your project settings—the system will track revision rounds without generating additional charges. Alternatively, you can work outside the revision policy for specific clients while still using the platform for file management, version control, and payments. The system adapts to your business model.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 7: Bulk Version Uploads -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.8s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-7' ? null : 'item-7'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-7' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            How do bulk version uploads work?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-purple-400 transition-transform duration-300"
                            :class="openItem === 'item-7' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-7'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            When working on albums or multi-track projects, bulk version upload is a huge time-saver. Upload multiple files at once, and our intelligent name-matching system automatically matches them to existing files in your project. For example, if you have "Track_01.wav" already uploaded and you bulk upload "Track_01.wav" again, the system creates a new version of that file with complete history. No manual matching required—just drag, drop, and the system handles the rest.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 8: How MixPitch Works -->
            <div class="group animate-fade-in-up" style="animation-delay: 0.9s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-8' ? null : 'item-8'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-8' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            How does MixPitch work?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-blue-400 transition-transform duration-300"
                            :class="openItem === 'item-8' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-8'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            MixPitch operates as both a marketplace and a client management platform. In our <strong>marketplace</strong> (Standard Projects), artists post their tracks publicly and multiple audio professionals compete by submitting their own mix interpretations. Artists review all submissions and choose the version that best captures their vision—paying only for the mix they select. Alternatively, studios and engineers can use our <strong>client management workflow</strong> to manage their existing clients with secure portals, revision policies, and milestone billing—without any marketplace involvement.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 9: Cost -->
            <div class="group animate-fade-in-up" style="animation-delay: 1.0s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-9' ? null : 'item-9'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-9' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                            How much does it cost to use MixPitch?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-purple-400 transition-transform duration-300"
                            :class="openItem === 'item-9' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-9'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            Creating an account is completely free for everyone. For <strong>marketplace projects</strong>, artists set their budget when posting a project, and audio professionals submit mixes within that budget—prices typically range from $50-$500 per project. For <strong>client management</strong>, studios and engineers set their own project prices and revision rates. Platform subscriptions unlock unlimited projects and advanced features, with pricing tiers designed to scale with your business needs.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 10: Rights -->
            <div class="group animate-fade-in-up" style="animation-delay: 1.1s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-10' ? null : 'item-10'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-10' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            Who owns the rights to my music?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-blue-400 transition-transform duration-300"
                            :class="openItem === 'item-10' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-10'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            You retain 100% ownership of your music and all associated rights by default. When an audio professional creates a mix of your track, they're providing a technical service, not claiming ownership. However, MixPitch includes flexible license management tools that allow you and the professional to establish mutual agreements about licensing, credits, and ownership—whether that's work-for-hire, shared credits, or custom arrangements. The platform supports whatever licensing structure works for your collaboration.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 11: Professional Payment -->
            <div class="group animate-fade-in-up" style="animation-delay: 1.2s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-11' ? null : 'item-11'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-11' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            How do audio professionals get paid?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-purple-400 transition-transform duration-300"
                            :class="openItem === 'item-11' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-11'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            For <strong>marketplace projects</strong>, the artist sets a budget when creating the project, and audio professionals submit mixes within that budget. When an artist selects their work, payment is processed securely through the platform and the professional receives their payment. For <strong>client management projects</strong>, studios receive payments directly from their clients as milestone payments are completed. Payment processing times and withdrawal methods depend on your chosen payment platform (Stripe, PayPal, or manual payment tracking).
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 12: Not Satisfied -->
            <div class="group animate-fade-in-up" style="animation-delay: 1.3s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl overflow-hidden hover:bg-white/15 transition-all duration-300 hover:shadow-2xl">
                    <button @click="openItem = openItem === 'item-12' ? null : 'item-12'"
                        class="flex justify-between items-center w-full p-6 text-left group-hover:bg-white/5 transition-colors duration-300"
                        :class="openItem === 'item-12' ? 'border-b border-white/20' : ''">
                        <h3 class="text-lg md:text-xl font-semibold text-white flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            What if I'm not satisfied with any of the mixes?
                        </h3>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-blue-400 transition-transform duration-300"
                            :class="openItem === 'item-12' ? 'transform rotate-180' : ''" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="openItem === 'item-12'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="p-6 pt-0">
                        <p class="text-white/80 leading-relaxed">
                            For <strong>marketplace projects</strong>, you're under no obligation to select any mix if you're not completely satisfied. You can provide detailed feedback to audio professionals and request revisions before making your final decision. If you still don't find what you're looking for, you can extend the submission deadline or close the project without making a selection. For <strong>client management projects</strong>, revision policies are established upfront—included revisions allow for feedback and refinement, while additional rounds can be billed automatically based on your configured pricing.
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
                <a href="mailto:support@mixpitch.com" class="inline-flex items-center bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 px-8 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>
