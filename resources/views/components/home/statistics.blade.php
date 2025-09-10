<!-- Statistics Component with Animated Counters -->
<div class="py-16 bg-base-100">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Growing Community of <span class="text-primary">Music
                    Creators</span></h2>
            <p class="text-base-content/70 max-w-3xl mx-auto">Join thousands of artists and audio professionals already
                collaborating on MixPitch</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Active Users -->
            <div
                class="bg-white p-6 rounded-xl shadow-sm text-center relative overflow-hidden group hover:shadow-md transition-all duration-300">
                <div
                    class="absolute inset-0 bg-gradient-to-r from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                </div>
                <div class="relative z-10">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 mb-4 text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-5xl font-bold mb-2" x-data="{ count: 0 }" x-init="() => {
                        const target = 3500;
                        const duration = 2000;
                        const startTime = performance.now();
                        const updateCount = (timestamp) => {
                            const elapsed = timestamp - startTime;
                            const progress = Math.min(elapsed / duration, 1);
                            $el.textContent = Math.floor(progress * target).toLocaleString();
                            if (progress < 1) {
                                requestAnimationFrame(updateCount);
                            }
                        };
                        requestAnimationFrame(updateCount);
                    }">0</h3>
                    <p class="text-lg text-base-content/80 font-medium">Active Users</p>
                    <p class="text-sm text-base-content/60 mt-2">Musicians and audio pros collaborating daily</p>
                </div>
            </div>

            <!-- Projects Completed -->
            <div
                class="bg-white p-6 rounded-xl shadow-sm text-center relative overflow-hidden group hover:shadow-md transition-all duration-300">
                <div
                    class="absolute inset-0 bg-gradient-to-r from-accent/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                </div>
                <div class="relative z-10">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-accent/10 mb-4 text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-5xl font-bold mb-2" x-data="{ count: 0 }" x-init="() => {
                        const target = 12000;
                        const duration = 2000;
                        const startTime = performance.now();
                        const updateCount = (timestamp) => {
                            const elapsed = timestamp - startTime;
                            const progress = Math.min(elapsed / duration, 1);
                            $el.textContent = Math.floor(progress * target).toLocaleString();
                            if (progress < 1) {
                                requestAnimationFrame(updateCount);
                            }
                        };
                        requestAnimationFrame(updateCount);
                    }">0</h3>
                    <p class="text-lg text-base-content/80 font-medium">Projects Completed</p>
                    <p class="text-sm text-base-content/60 mt-2">Successful collaborations and finished tracks</p>
                </div>
            </div>

            <!-- Total Earnings -->
            <div
                class="bg-white p-6 rounded-xl shadow-sm text-center relative overflow-hidden group hover:shadow-md transition-all duration-300">
                <div
                    class="absolute inset-0 bg-gradient-to-r from-secondary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                </div>
                <div class="relative z-10">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-secondary/10 mb-4 text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-5xl font-bold mb-2" x-data="{ count: 0 }" x-init="() => {
                        const target = 750000;
                        const duration = 2000;
                        const startTime = performance.now();
                        const updateCount = (timestamp) => {
                            const elapsed = timestamp - startTime;
                            const progress = Math.min(elapsed / duration, 1);
                            const value = Math.floor(progress * target);
                            $el.textContent = '$' + value.toLocaleString();
                            if (progress < 1) {
                                requestAnimationFrame(updateCount);
                            }
                        };
                        requestAnimationFrame(updateCount);
                    }">$0</h3>
                    <p class="text-lg text-base-content/80 font-medium">Paid to Audio Pros</p>
                    <p class="text-sm text-base-content/60 mt-2">Income earned by our audio professional community</p>
                </div>
            </div>
        </div>
    </div>
</div>