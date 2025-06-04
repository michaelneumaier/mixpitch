<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="main">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Mix Pitch') }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <!-- <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet"> -->
    <!-- <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@400;700&display=swap" rel="stylesheet"> -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous"> -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

    <!-- Add Bootstrap JS and its dependencies -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/homepage.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/star-rating.css') }}">
    <script src="https://unpkg.com/wavesurfer.js"></script>

    @livewireStyles

    <style>
        [x-cloak] {
            display: none !important;
        }

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
    </style>

</head>

<body class="font-sans antialiased bg-base-100">
    <div class="min-h-screen flex flex-col">
        @include('components.layouts.navigation')
        <!-- Page Heading -->
        @if (isset($header))
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endif
        <!-- Page Content -->
        <main class="flex-grow">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        @include('components.layouts.footer')
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    {{-- Choices.js for Tag Inputs --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    @yield('scripts')
    @livewireScripts
    @stack('scripts')

    <x-toaster-hub />
    
    {{-- Alpine component to handle URL opening/download --}}
    <div x-data 
         x-on:open-url.window="() => { 
             let url = $event.detail.url;
             let filename = $event.detail.filename;
             console.log('Alpine caught open-url event with URL:', url, 'Filename:', filename); 
             
             if (url) { 
                 const link = document.createElement('a');
                 link.href = url;
                 
                 // Add the download attribute if filename is provided
                 if (filename) {
                    link.setAttribute('download', filename);
                 } else {
                    // Optional: try to extract filename from URL as a fallback
                    try {
                        const urlParts = new URL(url);
                        const pathParts = urlParts.pathname.split('/');
                        link.setAttribute('download', pathParts[pathParts.length - 1]);
                    } catch (e) {
                        console.warn('Could not parse URL to extract filename.');
                    }
                 }
                 
                 // Append to body, click, and remove (modern approach)
                 document.body.appendChild(link);
                 link.click();
                 document.body.removeChild(link);
                 
             } else { 
                 console.error('Received open-url event but URL was missing.'); 
             } 
         }">
        {{-- This div doesn't render anything visible, it just listens for the event --}}
    </div>

    <!-- Global Modals - Positioned at root level for proper z-index behavior -->
    <x-pitch-action-modals />
</body>

</html>