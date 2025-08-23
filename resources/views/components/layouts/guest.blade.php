<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'MixPitch') }} - Authentication</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    <link href="{{ asset('css/auth.css') }}" rel="stylesheet">

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    
    <!-- Anti-flash: Apply theme before anything else loads -->
    <script>
        // Immediately apply saved theme to prevent flash
        (function() {
            const saved = sessionStorage.getItem('mixpitch_theme');
            if (saved === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (saved === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                // Check system preference if no saved theme
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (systemDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>
    
    <!-- reCAPTCHA scripts -->
    @stack('scripts')
</head>

<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
    {{ $slot }}
</body>

</html>