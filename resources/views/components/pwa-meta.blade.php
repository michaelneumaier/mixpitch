{{-- 
  PWA Meta Tags Component
  Centralized PWA meta tags for consistent implementation across all layouts
--}}

{{-- Web App Manifest --}}
<link rel="manifest" href="/site.webmanifest">

{{-- Theme Colors --}}
<meta name="theme-color" content="#1f2937">
<meta name="msapplication-TileColor" content="#1f2937">
<meta name="msapplication-navbutton-color" content="#1f2937">
<meta name="apple-mobile-web-app-status-bar-style" content="default">

{{-- PWA Meta Tags --}}
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="MixPitch">
<meta name="application-name" content="MixPitch">

{{-- Icons --}}
<link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
<link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
<link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
<link rel="mask-icon" href="/logo.svg" color="#1f2937">

{{-- Microsoft Tile --}}
<meta name="msapplication-TileImage" content="/icons/icon-144x144.png">
<meta name="msapplication-config" content="/browserconfig.xml">

{{-- PWA App Information --}}
<meta name="format-detection" content="telephone=no">
<meta name="format-detection" content="address=no">

{{-- Service Worker Registration --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if service workers are supported
    if ('serviceWorker' in navigator) {
        console.log('Service Worker: Browser support detected');

        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js', {
                scope: '/'
            })
            .then(function(registration) {
                console.log('Service Worker: Registration successful', registration);

                // Check for updates
                registration.addEventListener('updatefound', function() {
                    console.log('Service Worker: Update found');
                    const newWorker = registration.installing;

                    newWorker.addEventListener('statechange', function() {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // Show update available notification and activate immediately
                            showUpdateAvailableNotification(newWorker);
                        }
                    });
                });

                // Force update check on page load
                registration.update();
            })
            .catch(function(error) {
                console.error('Service Worker: Registration failed', error);
            });
        });
        
        // Listen for messages from service worker
        navigator.serviceWorker.addEventListener('message', function(event) {
            console.log('Service Worker: Message received', event.data);
        });
    } else {
        console.log('Service Worker: Not supported in this browser');
    }
    
    // PWA Install Prompt
    let deferredInstallPrompt = null;
    const installButton = document.getElementById('pwa-install-button');
    
    window.addEventListener('beforeinstallprompt', function(event) {
        console.log('PWA: Install prompt event triggered');
        
        // Prevent the default install prompt
        event.preventDefault();
        
        // Store the event for later use
        deferredInstallPrompt = event;
        
        // Show custom install button if it exists
        if (installButton) {
            installButton.style.display = 'block';
            installButton.addEventListener('click', installPWA);
        }
        
        // Dispatch custom event for app to handle
        window.dispatchEvent(new CustomEvent('pwaInstallAvailable', {
            detail: { prompt: event }
        }));
    });
    
    // Handle successful installation
    window.addEventListener('appinstalled', function(event) {
        console.log('PWA: Installation successful');
        
        // Hide install button
        if (installButton) {
            installButton.style.display = 'none';
        }
        
        // Clear stored prompt
        deferredInstallPrompt = null;
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('pwaInstalled'));
    });
    
    // Install PWA function
    function installPWA() {
        if (deferredInstallPrompt) {
            deferredInstallPrompt.prompt();
            
            deferredInstallPrompt.userChoice.then(function(choiceResult) {
                console.log('PWA: User choice:', choiceResult.outcome);
                deferredInstallPrompt = null;
            });
        }
    }
    
    // Show update notification and activate new service worker
    function showUpdateAvailableNotification(newWorker) {
        // Tell the new service worker to skip waiting and activate immediately
        if (newWorker) {
            newWorker.postMessage({ type: 'SKIP_WAITING' });
        }

        // Notify user and reload to use new service worker
        if (window.Livewire) {
            // Use Livewire notification if available
            window.Livewire.dispatch('notify', {
                type: 'info',
                title: 'App Update Available',
                message: 'A new version of MixPitch is available. Refreshing now...',
            });
            // Auto-refresh after brief delay
            setTimeout(() => window.location.reload(), 1500);
        } else {
            // Auto-refresh for non-Livewire pages
            window.location.reload();
        }
    }

    // Listen for service worker controller change (new SW activated)
    navigator.serviceWorker.addEventListener('controllerchange', function() {
        console.log('Service Worker: Controller changed, reloading page for fresh content');
        window.location.reload();
    });

    // Authentication state change detection
    // Notify service worker when user logs in or out to clear caches
    function notifyAuthStateChange() {
        if (navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'AUTH_STATE_CHANGED'
            });
            console.log('Service Worker: Auth state change notification sent');
        }
    }

    // Listen for Livewire navigation events that might indicate auth changes
    document.addEventListener('livewire:navigated', function() {
        // Check if we navigated to/from login/logout pages
        const currentPath = window.location.pathname;
        if (currentPath.includes('/login') || currentPath.includes('/logout') ||
            currentPath.includes('/register') || currentPath === '/') {
            notifyAuthStateChange();
        }
    });

    // Also listen for storage events (in case of multi-tab scenarios)
    window.addEventListener('storage', function(e) {
        if (e.key && (e.key.includes('auth') || e.key.includes('token') || e.key.includes('session'))) {
            notifyAuthStateChange();
        }
    });

    // Network status detection
    function updateNetworkStatus() {
        if (navigator.onLine) {
            document.body.classList.remove('offline');
            document.body.classList.add('online');
            
            // Dispatch online event
            window.dispatchEvent(new CustomEvent('networkOnline'));
        } else {
            document.body.classList.remove('online');
            document.body.classList.add('offline');
            
            // Dispatch offline event
            window.dispatchEvent(new CustomEvent('networkOffline'));
        }
    }
    
    // Listen for network changes
    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);
    
    // Initial network status check
    updateNetworkStatus();
});
</script>

{{-- PWA Styles --}}
<style>
/* PWA-specific styles */
.offline .online-only {
    display: none !important;
}

.online .offline-only {
    display: none !important;
}

/* Install button styles */
#pwa-install-button {
    display: none;
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    padding: 12px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

#pwa-install-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

/* Network status indicator */
.network-status {
    position: fixed;
    top: 10px;
    right: 10px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    z-index: 1000;
    transition: all 0.3s ease;
}

.online .network-status {
    background: #10b981;
    color: white;
    opacity: 0;
}

.offline .network-status {
    background: #ef4444;
    color: white;
    opacity: 1;
}

/* PWA loading states */
.pwa-loading {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.pwa-loading::after {
    content: '';
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #333;
    border-radius: 50%;
    animation: pwa-spin 1s linear infinite;
}

@keyframes pwa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>