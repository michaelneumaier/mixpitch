<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Offline - {{ config('app.name', 'MixPitch') }}</title>
    
    {{-- PWA Meta Tags --}}
    <x-pwa-meta />
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css'])
    
    <style>
        .offline-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .pulse-dot {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
            }
            
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
            }
        }
        
        .bounce {
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto px-6 text-center">
        <!-- Logo -->
        <div class="mb-8">
            <img src="/logo.svg" alt="MixPitch" class="h-16 w-auto mx-auto">
        </div>
        
        <!-- Offline Icon -->
        <div class="mb-8 flex justify-center">
            <div class="offline-gradient rounded-full p-6 pulse-dot">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 1 0 0 19.5 9.75 9.75 0 1 0 0-19.5Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 12h7.5" />
                </svg>
            </div>
        </div>
        
        <!-- Main Message -->
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
            You're Offline
        </h1>
        
        <p class="text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
            It looks like you've lost your internet connection. Don't worry - MixPitch works offline too! 
            Some features may be limited, but you can still browse your cached content.
        </p>
        
        <!-- Connection Status -->
        <div class="mb-8 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Connection Status:</span>
                <div id="connection-status" class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                    <span class="text-sm font-medium text-red-600 dark:text-red-400">Offline</span>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="space-y-4">
            <button onclick="location.reload()" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                <span class="flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Try Again</span>
                </span>
            </button>
            
            <button onclick="goBack()" 
                    class="w-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                <span class="flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Go Back</span>
                </span>
            </button>
        </div>
        
        <!-- Offline Features -->
        <div class="mt-12 text-left">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 text-center">What You Can Do Offline:</h2>
            <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                    <span>Browse previously visited pages and cached content</span>
                </li>
                <li class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                    <span>View downloaded audio files and project details</span>
                </li>
                <li class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                    <span>Continue working on drafts (will sync when online)</span>
                </li>
                <li class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full mt-2 flex-shrink-0"></div>
                    <span>Limited: New uploads and real-time features</span>
                </li>
            </ul>
        </div>
        
        <!-- Network Detection Message -->
        <div id="online-message" class="hidden mt-6 p-4 bg-green-100 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg">
            <div class="flex items-center justify-center space-x-2 text-green-700 dark:text-green-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">Connection restored! Refreshing...</span>
            </div>
        </div>
    </div>

    <script>
        // Network status monitoring
        function updateConnectionStatus() {
            const statusElement = document.getElementById('connection-status');
            const onlineMessage = document.getElementById('online-message');
            
            if (navigator.onLine) {
                statusElement.innerHTML = `
                    <div class="w-2 h-2 bg-green-500 rounded-full bounce"></div>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">Online</span>
                `;
                
                // Show online message and refresh after delay
                onlineMessage.classList.remove('hidden');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                statusElement.innerHTML = `
                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                    <span class="text-sm font-medium text-red-600 dark:text-red-400">Offline</span>
                `;
                onlineMessage.classList.add('hidden');
            }
        }
        
        // Go back function
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/';
            }
        }
        
        // Listen for connection changes
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);
        
        // Check connection status periodically
        setInterval(updateConnectionStatus, 5000);
        
        // Initial status check
        updateConnectionStatus();
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            if (event.key === 'r' && (event.metaKey || event.ctrlKey)) {
                event.preventDefault();
                location.reload();
            }
            
            if (event.key === 'Escape') {
                goBack();
            }
        });
        
        // Auto-retry when online
        let retryTimeout;
        window.addEventListener('online', function() {
            clearTimeout(retryTimeout);
            retryTimeout = setTimeout(() => {
                console.log('Network restored, attempting to reload...');
                location.reload();
            }, 1000);
        });
        
        // PWA-specific offline handling
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(function(registration) {
                console.log('Service Worker ready for offline functionality');
                
                // Listen for service worker messages
                navigator.serviceWorker.addEventListener('message', function(event) {
                    if (event.data && event.data.type === 'CACHE_UPDATED') {
                        console.log('Cache updated, content available offline');
                    }
                });
            });
        }
    </script>
</body>
</html>