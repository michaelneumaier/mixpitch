<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Client Portal - {{ $project->name }}</title>

    <x-pwa-meta />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

    @fluxAppearance

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- WaveSurfer.js for audio -->
    <script src="https://unpkg.com/wavesurfer.js"></script>

    @livewireStyles

    @php
        $allowedTypes = config('file-types.allowed_types', [
            'audio/*',
            'video/*',
            'application/pdf',
            'image/*',
            'application/zip',
        ]);
    @endphp
    <script>
        // Set default allowed file types from configuration
        window.defaultAllowedFileTypes = @json($allowedTypes);
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50 font-sans antialiased dark:bg-gray-900">

    @include('client_portal.components.preview-banner', ['isPreview' => $isPreview ?? false])

    <x-draggable-upload-page :model="$project" title="Client Portal">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            {{-- Dark Mode Toggle --}}
            <div class="fixed top-4 right-4 z-50">
                <div x-data="{ 
                    isDark: document.documentElement.classList.contains('dark'),
                    toggle() {
                        if (typeof window.toggleDarkMode === 'function') {
                            window.toggleDarkMode();
                            this.isDark = document.documentElement.classList.contains('dark');
                        }
                    }
                }"
                x-on:theme-changed.window="isDark = $event.detail.isDark">
                    <flux:button @click="toggle()" 
                                variant="ghost" 
                                size="sm"
                                class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border border-gray-200 dark:border-gray-700 shadow-sm hover:bg-white dark:hover:bg-gray-800">
                        <flux:icon.sun x-show="isDark" class="text-gray-600 dark:text-gray-300" />
                        <flux:icon.moon x-show="!isDark" class="text-gray-600 dark:text-gray-300" />
                        <span class="hidden sm:inline ml-2 text-sm text-gray-700 dark:text-gray-300" x-text="isDark ? 'Light Mode' : 'Dark Mode'"></span>
                        <span class="sm:hidden ml-2 text-sm text-gray-700 dark:text-gray-300" x-text="isDark ? 'Light' : 'Dark'"></span>
                    </flux:button>
                </div>
            </div>

            <div class="mx-auto p-2">
                <div class="mx-auto max-w-5xl">

                    @include('client_portal.components.project-progress-card', [
                        'project' => $project,
                        'pitch' => $pitch,
                        'branding' => $branding,
                    ])

                    {{-- Flash Messages --}}
                    @include('client_portal.components.flash-messages', [
                        'snapshotHistory' => $snapshotHistory,
                    ])

                    {{-- Project Description --}}
                    @include('client_portal.components.project-brief-card', ['project' => $project])

                    {{-- Account Upgrade Section --}}
                    @include('client_portal.components.guest-upgrade-card', [
                        'project' => $project,
                    ])

                    @include('client_portal.components.project-files-card', [
                        'project' => $project,
                        'pitch' => $pitch,
                        'currentSnapshot' => $currentSnapshot,
                        'snapshotHistory' => $snapshotHistory,
                        'branding' => $branding,
                        'milestones' => $milestones ?? collect(),
                        'isPreview' => $isPreview ?? false,
                    ])

                        @include('client_portal.components.review-approval-card', [
                        'project' => $project,
                        'pitch' => $pitch,
                        'currentSnapshot' => $currentSnapshot,
                    ])

                    @include('client_portal.components.post-approval-success-card', [
                        'project' => $project,
                        'pitch' => $pitch,
                        'milestones' => $milestones ?? collect(),
                    ])

                    @include('client_portal.components.client-communication-hub', [
                        'project' => $project,
                        'pitch' => $pitch,
                    ])

                    {{-- Version Comparison Component --}}
                    @include('client_portal.components.version-comparison', [
                        'snapshotHistory' => $snapshotHistory,
                    ])

                </div>

                {{-- Utility Functions --}}
                <script>
                    // File highlighting and navigation utility functions
                    function highlightTargetFile() {
                        const targetFile = window.location.hash.replace('#file-', '');
                        if (targetFile && document.getElementById('file-' + targetFile)) {
                            document.getElementById('file-' + targetFile).scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            document.getElementById('file-' + targetFile).classList.add('ring-2', 'ring-blue-500', 'ring-opacity-50');
                            
                            // Show helpful notification
                            showNotification('File highlighted below', 'Found and highlighted the requested file for you!');
                        }
                    }

                    function showNotification(title, suggestion) {
                        const notification = document.createElement('div');
                        notification.className = 'fixed top-4 right-4 bg-gradient-to-br from-blue-500 to-indigo-600 text-white px-6 py-4 rounded-xl shadow-2xl z-50 max-w-sm transform transition-all duration-500 ease-out translate-x-full opacity-0';
                        notification.innerHTML = `
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-lightbulb text-yellow-300"></i>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-semibold">${title}</div>
                                    <div class="text-xs opacity-90 mt-1">${suggestion}</div>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(notification);

                        setTimeout(() => {
                            notification.style.opacity = '1';
                            notification.style.transform = 'translateX(0)';
                        }, 100);

                        setTimeout(() => {
                            notification.style.opacity = '0';
                            notification.style.transform = 'translateX(100%)';
                            setTimeout(() => notification.remove(), 300);
                        }, 4000);
                    }

                    // Auto-scroll to Producer Deliverables if hash is present
                    function autoScrollToDeliverables() {
                        if (window.location.hash === '#producer-deliverables') {
                            setTimeout(() => {
                                const deliverables = document.getElementById('producer-deliverables');
                                if (deliverables) {
                                    deliverables.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'start'
                                    });
                                }
                            }, 100);
                        }
                    }

                    // Run functions when page loads
                    document.addEventListener('DOMContentLoaded', function() {
                        highlightTargetFile();
                        autoScrollToDeliverables();
                    });
                </script>

            </div>
        </div>
    </x-draggable-upload-page>

    {{-- Global Components --}}
    @livewire('global-file-uploader')
    
    {{-- Global Audio Player with Persistence --}}
    @persist('global-audio-player')
        @livewire('global-audio-player')
    @endpersist

    {{-- Scripts --}}
    @livewireScripts
    @fluxScripts
    @yield('scripts')
    @stack('scripts')

    {{-- Dark Mode Implementation --}}
    <script>
        function applyDarkMode(isDark) {
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            
            // Store preference
            const themeValue = isDark ? 'dark' : 'light';
            try {
                sessionStorage.setItem('mixpitch_theme', themeValue);
                localStorage.setItem('theme', themeValue);
                window.mixpitchTheme = themeValue;
            } catch (e) {
                console.error('Storage failed:', e);
            }
        }
        
        function getCurrentTheme() {
            // Check sessionStorage first (most reliable)
            let saved = sessionStorage.getItem('mixpitch_theme');
            if (saved) {
                return saved;
            }
            
            // Fallback to system preference
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            return systemDark ? 'dark' : 'light';
        }
        
        function toggleTheme() {
            const currentIsDark = document.documentElement.classList.contains('dark');
            const newIsDark = !currentIsDark;
            applyDarkMode(newIsDark);
            
            // Update Alpine component
            window.dispatchEvent(new CustomEvent('theme-changed', { 
                detail: { isDark: newIsDark } 
            }));
        }
        
        // Override Flux's theme application with our stored preference
        function applyStoredTheme() {
            const savedTheme = getCurrentTheme();
            applyDarkMode(savedTheme === 'dark');
        }
        
        // Apply immediately and after a short delay to override Flux
        applyStoredTheme();
        setTimeout(applyStoredTheme, 100);
        setTimeout(applyStoredTheme, 500);
        
        // Make toggle function global
        window.toggleDarkMode = toggleTheme;
    </script>

    {{-- Alpine.js Data Functions for Client Portal --}}
    <script>
        document.addEventListener('alpine:init', () => {
            // File approval functionality
            Alpine.data('approveFile', (config) => ({
                loading: false,
                async submit() {
                    if (this.loading) return;
                    
                    this.loading = true;
                    try {
                        const response = await fetch(config.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        
                        if (response.ok) {
                            // Reload the page to show updated status
                            window.location.reload();
                        } else {
                            console.error('Failed to approve file');
                            alert('Failed to approve file. Please try again.');
                        }
                    } catch (error) {
                        console.error('Error approving file:', error);
                        alert('An error occurred. Please try again.');
                    }
                    this.loading = false;
                }
            }));

            // Approve all files functionality
            Alpine.data('approveAll', (config) => ({
                loading: false,
                async submit() {
                    if (this.loading) return;
                    
                    this.loading = true;
                    try {
                        const response = await fetch(config.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        
                        if (response.ok) {
                            // Reload the page to show updated status
                            window.location.reload();
                        } else {
                            console.error('Failed to approve all files');
                            alert('Failed to approve files. Please try again.');
                        }
                    } catch (error) {
                        console.error('Error approving files:', error);
                        alert('An error occurred. Please try again.');
                    }
                    this.loading = false;
                }
            }));

            // Version comparison functionality
            Alpine.data('versionComparison', () => ({
                showComparison: false,
                selectedVersions: [],
                
                init() {
                    // Handle version comparison toggle
                    const toggleButton = document.querySelector('.js-toggle-comparison');
                    if (toggleButton) {
                        toggleButton.addEventListener('click', () => {
                            this.toggleComparison();
                        });
                    }
                    
                    // Handle hide comparison
                    const hideButton = document.querySelector('#js-hide-comparison');
                    if (hideButton) {
                        hideButton.addEventListener('click', () => {
                            this.hideComparison();
                        });
                    }
                },
                
                toggleComparison() {
                    this.showComparison = !this.showComparison;
                    const checkboxes = document.querySelectorAll('.comparison-checkbox');
                    const comparisonDiv = document.querySelector('#version-comparison');
                    
                    if (this.showComparison) {
                        checkboxes.forEach(cb => cb.classList.remove('hidden'));
                        if (comparisonDiv) comparisonDiv.classList.remove('hidden');
                    } else {
                        checkboxes.forEach(cb => cb.classList.add('hidden'));
                        if (comparisonDiv) comparisonDiv.classList.add('hidden');
                        this.selectedVersions = [];
                    }
                },
                
                hideComparison() {
                    this.showComparison = false;
                    const checkboxes = document.querySelectorAll('.comparison-checkbox');
                    const comparisonDiv = document.querySelector('#version-comparison');
                    
                    checkboxes.forEach(cb => {
                        cb.classList.add('hidden');
                        cb.checked = false;
                    });
                    if (comparisonDiv) comparisonDiv.classList.add('hidden');
                    this.selectedVersions = [];
                }
            }));
        });

        // Global function for comparison checkbox updates
        function updateComparison() {
            const checkboxes = document.querySelectorAll('.comparison-checkbox:checked');
            // Handle comparison logic here if needed
            console.log('Selected versions for comparison:', Array.from(checkboxes).map(cb => cb.dataset.snapshotId));
        }
    </script>
</body>

</html>
