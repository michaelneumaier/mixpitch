{{-- 
  PWA Install Prompt Component
  Provides a customizable install button and prompt for PWA installation
--}}

@props([
    'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left, floating
    'style' => 'default', // default, minimal, banner
    'text' => 'Install MixPitch',
    'subtitle' => 'Get the full app experience',
    'showIcon' => true,
    'autoHide' => true // Hide after successful install
])

@php
$positionClasses = match($position) {
    'bottom-right' => 'fixed bottom-6 right-6',
    'bottom-left' => 'fixed bottom-6 left-6',
    'top-right' => 'fixed top-20 right-6',
    'top-left' => 'fixed top-20 left-6',
    'floating' => 'fixed bottom-1/2 right-6 transform translate-y-1/2',
    default => 'fixed bottom-6 right-6'
};

$styleClasses = match($style) {
    'minimal' => 'p-3 bg-white dark:bg-gray-800 rounded-full shadow-lg border border-gray-200 dark:border-gray-700',
    'banner' => 'w-full max-w-sm bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4',
    default => 'bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full shadow-lg px-6 py-3'
};
@endphp

<div id="pwa-install-prompt-{{ uniqid() }}" 
     class="{{ $positionClasses }} {{ $styleClasses }} z-50 transform transition-all duration-300 scale-0 opacity-0"
     x-data="pwaInstallPrompt"
     x-show="showPrompt"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-75"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-75">

    @if($style === 'banner')
        {{-- Banner Style --}}
        <div class="flex items-start space-x-4">
            @if($showIcon)
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            @endif
            
            <div class="flex-grow">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $text }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $subtitle }}</p>
                
                <div class="flex space-x-2 mt-3">
                    <button @click="installApp" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Install
                    </button>
                    <button @click="dismissPrompt" 
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 px-4 py-2 text-sm font-medium transition-colors">
                        Maybe Later
                    </button>
                </div>
            </div>
            
            <button @click="dismissPrompt" 
                    class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
    @elseif($style === 'minimal')
        {{-- Minimal Style --}}
        <button @click="installApp" 
                class="flex items-center justify-center text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                title="{{ $text }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
        </button>
        
    @else
        {{-- Default Style --}}
        <button @click="installApp" 
                class="flex items-center space-x-2 font-semibold hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5">
            @if($showIcon)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            @endif
            <span>{{ $text }}</span>
        </button>
        
        {{-- Close button for default style --}}
        <button @click="dismissPrompt" 
                class="ml-3 text-white/70 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pwaInstallPrompt', () => ({
        showPrompt: false,
        deferredPrompt: null,
        isInstalled: false,
        dismissedAt: null,
        
        init() {
            // Check if already installed
            this.checkInstallStatus();
            
            // Listen for install prompt
            window.addEventListener('beforeinstallprompt', (e) => {
                console.log('PWA Install Prompt: beforeinstallprompt event fired');
                e.preventDefault();
                this.deferredPrompt = e;
                
                // Check if user dismissed recently
                const dismissed = localStorage.getItem('pwa-install-dismissed');
                if (dismissed) {
                    const dismissedTime = new Date(dismissed);
                    const hoursSinceDismissed = (Date.now() - dismissedTime.getTime()) / (1000 * 60 * 60);
                    
                    // Don't show for 24 hours after dismissal
                    if (hoursSinceDismissed < 24) {
                        return;
                    }
                }
                
                // Show prompt after a short delay
                setTimeout(() => {
                    if (!this.isInstalled) {
                        this.showPrompt = true;
                    }
                }, 3000);
            });
            
            // Listen for successful installation
            window.addEventListener('appinstalled', () => {
                console.log('PWA Install Prompt: App installed successfully');
                this.showPrompt = false;
                this.isInstalled = true;
                localStorage.removeItem('pwa-install-dismissed');
                
                @if($autoHide)
                    // Hide the component completely after installation
                    this.$el.style.display = 'none';
                @endif
                
                // Dispatch custom event
                window.dispatchEvent(new CustomEvent('pwa-installed'));
                
                // Show success message
                this.showInstallSuccess();
            });
            
            // Listen for PWA install available event (from pwa-meta component)
            window.addEventListener('pwaInstallAvailable', (e) => {
                this.deferredPrompt = e.detail.prompt;
                this.showPrompt = true;
            });
        },
        
        async installApp() {
            if (!this.deferredPrompt) {
                console.log('PWA Install Prompt: No deferred prompt available');
                return;
            }
            
            try {
                console.log('PWA Install Prompt: Showing install prompt');
                this.deferredPrompt.prompt();
                
                const { outcome } = await this.deferredPrompt.userChoice;
                console.log('PWA Install Prompt: User choice:', outcome);
                
                if (outcome === 'accepted') {
                    console.log('PWA Install Prompt: User accepted installation');
                } else {
                    console.log('PWA Install Prompt: User dismissed installation');
                    this.dismissPrompt();
                }
                
                this.deferredPrompt = null;
            } catch (error) {
                console.error('PWA Install Prompt: Installation failed:', error);
            }
        },
        
        dismissPrompt() {
            this.showPrompt = false;
            localStorage.setItem('pwa-install-dismissed', new Date().toISOString());
            
            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('pwa-install-dismissed'));
        },
        
        checkInstallStatus() {
            // Check if app is already installed
            if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
                this.isInstalled = true;
                return;
            }
            
            // Check for iOS standalone mode
            if (window.navigator.standalone === true) {
                this.isInstalled = true;
                return;
            }
            
            // Check for Android installed PWA
            if (document.referrer.includes('android-app://')) {
                this.isInstalled = true;
                return;
            }
        },
        
        showInstallSuccess() {
            // Create temporary success notification
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-full';
            notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>MixPitch installed successfully!</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Slide in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Slide out and remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 4000);
        }
    }));
});
</script>