<div
    class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-base-300 to-base-100">
    <div class="mb-6">
        {{ $logo }}
    </div>

    <div class="w-full sm:max-w-md mt-4 px-8 py-8 bg-white shadow-lg overflow-hidden rounded-xl border border-gray-100">
        {{ $slot }}
    </div>

    <!-- Decorative sound waves (similar to the hero section) -->
    <div class="absolute inset-0 flex items-center justify-center opacity-10 overflow-hidden -z-10">
        <div class="wave-container">
            <div class="wave wave1"></div>
            <div class="wave wave2"></div>
            <div class="wave wave3"></div>
            <div class="wave wave4"></div>
        </div>
    </div>
</div>