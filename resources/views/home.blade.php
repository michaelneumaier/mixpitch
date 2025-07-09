@extends('components.layouts.app')

@section('title', 'MixPitch - Where Artists & Audio Professionals Connect')
@section('description', 'Connect with skilled audio professionals to transform your music or find exciting projects to
showcase your mixing and mastering talents.')

@section('content')
<x-home.hero />
<x-home.your-journey />
{{-- <x-home.statistics /> --}}
<x-home.how-it-works />
{{-- <x-home.testimonials /> --}}
<x-home.faq />
<x-home.cta />
@endsection

@push('scripts')
<script src="{{ asset('js/hero.js') }}"></script>
<script>
    document.addEventListener('alpine:init', () => {
        // Role toggle functionality
        Alpine.data('roleToggle', () => ({
            selectedRole: 'artist',
            selectRole(role) {
                this.selectedRole = role;
            }
        }));

        // Animated counter for statistics
        Alpine.data('counter', (target, duration = 2000) => ({
            current: 0,
            init() {
                const startTime = performance.now();
                const updateCount = (timestamp) => {
                    const elapsed = timestamp - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    this.current = Math.floor(progress * target);
                    if (progress < 1) {
                        requestAnimationFrame(updateCount);
                    }
                };
                requestAnimationFrame(updateCount);
            }
        }));
    });
</script>
@endpush

@push('styles')
<style>
    /* Hero section wave animation */
    .wave-container {
        position: absolute;
        width: 100%;
        transform-origin: bottom;
        animation: wave 15s ease-in-out infinite alternate;
    }

    @keyframes wave {
        0% {
            transform: scale(1.05) translateY(2px);
        }

        100% {
            transform: scale(1) translateY(-2px);
        }
    }

    /* Audio visualization animation */
    .audio-bar {
        height: 15px;
        border-radius: 2px;
        background-color: currentColor;
        animation: equalizer 1.5s ease-in-out infinite alternate;
    }

    .audio-bar:nth-child(1) {
        animation-delay: -1.2s;
    }

    .audio-bar:nth-child(2) {
        animation-delay: -0.9s;
    }

    .audio-bar:nth-child(3) {
        animation-delay: -0.6s;
    }

    .audio-bar:nth-child(4) {
        animation-delay: -0.3s;
    }

    .audio-bar:nth-child(5) {
        animation-delay: 0s;
    }

    @keyframes equalizer {
        0% {
            height: 5px;
        }

        100% {
            height: 25px;
        }
    }

    /* Transition arrow animation */
    .transform-arrow {
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 0.7;
            transform: scale(1);
        }

        50% {
            opacity: 1;
            transform: scale(1.05);
        }
    }

    /* Role toggle styling */
    .role-toggle-btn {
        color: rgba(0, 0, 0, 0.6);
        font-weight: 500;
    }

    .role-toggle-btn.active {
        color: white !important;
    }

    /* Clean fade-in animation without background color transitions */
    @keyframes fadeIn {
        0% {
            opacity: 0;
            transform: translateY(10px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fadeIn 0.6s ease-out;
    }

    /* Fix mobile responsiveness issues */
    @media (max-width: 768px) {
        .role-toggle {
            flex-direction: column;
            width: 100%;
        }

        .audio-comparison {
            flex-direction: column;
            align-items: center;
        }

        .audio-comparison>div {
            width: 100%;
            max-width: 300px;
            margin-bottom: 1rem;
        }

        .transform-arrow-container {
            transform: rotate(90deg);
            margin: 1rem 0;
        }

        .how-it-works-section {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }
</style>
@endpush