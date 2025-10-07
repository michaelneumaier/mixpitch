/**
 * MixPitch Service Worker
 * Implements PWA caching strategies for music collaboration platform
 *
 * IMPORTANT: This service worker does NOT cache HTML pages to prevent stale content.
 * It only caches: audio files, static assets, and the offline fallback page.
 * Page navigation is handled by Livewire's wire:navigate for fresh content on every load.
 */

const CACHE_NAME = 'mixpitch-v2.0.0'; // Bumped to force cache clear
const OFFLINE_PAGE = '/offline';

// Assets to cache immediately - only offline page and manifest
// DO NOT cache home page or other HTML pages here
const STATIC_CACHE_FILES = [
  '/offline',
  '/site.webmanifest'
];

// These routes should NEVER be cached - always fetch fresh from network
// This includes all HTML pages to prevent stale content issues
const NEVER_CACHE_ROUTES = [
  '/api/',
  '/livewire/',
  '/dashboard',
  '/projects',
  '/pitches',
  '/login',
  '/logout',
  '/register',
  '/billing',
  '/profile'
];

// Audio-related URLs that should be cached for PWA playback
const AUDIO_CACHE_ROUTES = [
  '/pitch-files/',
  '/project-files/',
  '/audio/',
  '.mp3',
  '.wav',
  '.m4a',
  '.ogg'
];

// Cache-first routes (static assets)
const CACHE_FIRST_ROUTES = [
  '/css/',
  '/js/',
  '/images/',
  '/icons/',
  '/webfonts/',
  '/vendor/',
  '/favicon'
];

/**
 * Install Event - Cache essential files
 */
self.addEventListener('install', event => {
  console.log('Service Worker: Installing');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Service Worker: Caching essential files');
        return cache.addAll(STATIC_CACHE_FILES.map(url => new Request(url, {cache: 'reload'})));
      })
      .catch(error => {
        console.error('Service Worker: Cache installation failed:', error);
      })
  );
  
  // Activate immediately
  self.skipWaiting();
});

/**
 * Activate Event - Clean up old caches
 */
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating');
  
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME) {
              console.log('Service Worker: Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        // Take control of all pages
        return self.clients.claim();
      })
  );
});

/**
 * Fetch Event - Implement caching strategies
 */
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip chrome-extension and other non-http(s) schemes
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // CRITICAL: Never cache HTML navigation requests to prevent stale pages
  if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(networkOnlyStrategy(request));
    return;
  }

  // Handle different route types with appropriate strategies
  if (isNeverCacheRoute(url.pathname)) {
    // Always fetch fresh - no caching for dynamic content
    event.respondWith(networkOnlyStrategy(request));
  } else if (isAudioRoute(url.pathname)) {
    // Cache audio files for offline playback
    event.respondWith(audioCacheStrategy(request));
  } else if (isCacheFirstRoute(url.pathname)) {
    // Cache static assets (CSS, JS, images, fonts)
    event.respondWith(cacheFirstStrategy(request));
  } else {
    // Default: try network first, but don't cache HTML
    event.respondWith(networkOnlyStrategy(request));
  }
});

/**
 * Network Only Strategy - Always fetch from network, never cache
 * Used for HTML pages and dynamic content to ensure fresh data
 */
async function networkOnlyStrategy(request) {
  try {
    const networkResponse = await fetch(request);
    return networkResponse;
  } catch (error) {
    console.log('Network request failed:', error);

    // Only return offline page for navigation requests
    if (request.mode === 'navigate') {
      const offlinePage = await caches.match(OFFLINE_PAGE);
      if (offlinePage) {
        return offlinePage;
      }
    }

    // For all other requests, just fail
    throw error;
  }
}

/**
 * Cache First Strategy - For static assets
 * Good for CSS, JS, images, fonts
 */
async function cacheFirstStrategy(request) {
  const cachedResponse = await caches.match(request);
  
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.error('Cache first strategy failed:', error);
    throw error;
  }
}

// Stale While Revalidate strategy removed - was causing stale page issues
// All HTML pages now use networkOnlyStrategy for guaranteed fresh content

/**
 * Audio Caching Strategy - For audio files with range request support
 * Optimized for PWA audio playback with partial content support
 */
async function audioCacheStrategy(request) {
  const cache = await caches.open(CACHE_NAME + '-audio');
  
  // Check if it's a range request
  const rangeHeader = request.headers.get('range');
  
  if (rangeHeader) {
    // For range requests, try network first to get partial content
    try {
      const networkResponse = await fetch(request);
      
      if (networkResponse.ok && networkResponse.status === 206) {
        // Cache the full file if it's not already cached
        const fullRequest = new Request(request.url, {
          headers: new Headers(request.headers)
        });
        delete fullRequest.headers.range;
        
        const cachedResponse = await cache.match(fullRequest);
        if (!cachedResponse) {
          try {
            const fullResponse = await fetch(fullRequest);
            if (fullResponse.ok) {
              cache.put(fullRequest, fullResponse.clone());
            }
          } catch (error) {
            // Ignore caching errors for full file
            console.log('Could not cache full audio file:', error);
          }
        }
        
        return networkResponse;
      }
    } catch (error) {
      console.log('Range request failed, trying cache:', error);
    }
  }
  
  // For non-range requests or when range request fails
  const cachedResponse = await cache.match(request);
  
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      // Cache audio files for offline playback
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.error('Audio cache strategy failed:', error);
    throw error;
  }
}

/**
 * Route checking helpers
 */
function isAudioRoute(pathname) {
  return AUDIO_CACHE_ROUTES.some(route =>
    pathname.startsWith(route) || pathname.includes(route)
  );
}

function isNeverCacheRoute(pathname) {
  return NEVER_CACHE_ROUTES.some(route => pathname.startsWith(route));
}

function isCacheFirstRoute(pathname) {
  return CACHE_FIRST_ROUTES.some(route => pathname.startsWith(route));
}

/**
 * Background Sync Event - For offline form submissions
 */
self.addEventListener('sync', event => {
  console.log('Service Worker: Background sync triggered', event.tag);
  
  if (event.tag === 'background-sync-pitch-submission') {
    event.waitUntil(handleOfflinePitchSubmissions());
  }
  
  if (event.tag === 'background-sync-project-updates') {
    event.waitUntil(handleOfflineProjectUpdates());
  }
});

/**
 * Handle offline pitch submissions
 */
async function handleOfflinePitchSubmissions() {
  // Implementation would depend on IndexedDB storage
  // This is a placeholder for offline functionality
  console.log('Service Worker: Processing offline pitch submissions');
}

/**
 * Handle offline project updates
 */
async function handleOfflineProjectUpdates() {
  // Implementation would depend on IndexedDB storage
  // This is a placeholder for offline functionality
  console.log('Service Worker: Processing offline project updates');
}

/**
 * Push Event - For notifications
 */
self.addEventListener('push', event => {
  console.log('Service Worker: Push message received', event);
  
  let notificationData = {
    title: 'MixPitch',
    body: 'You have new updates!',
    icon: '/icons/icon-192x192.png',
    badge: '/icons/icon-96x96.png',
    tag: 'mixpitch-notification',
    requireInteraction: false,
    actions: [
      {
        action: 'open',
        title: 'Open MixPitch'
      },
      {
        action: 'dismiss',
        title: 'Dismiss'
      }
    ]
  };
  
  if (event.data) {
    try {
      const data = event.data.json();
      notificationData = { ...notificationData, ...data };
    } catch (error) {
      console.error('Service Worker: Error parsing push data:', error);
    }
  }
  
  event.waitUntil(
    self.registration.showNotification(notificationData.title, notificationData)
  );
});

/**
 * Notification Click Event
 */
self.addEventListener('notificationclick', event => {
  console.log('Service Worker: Notification clicked', event);
  
  event.notification.close();
  
  if (event.action === 'open' || !event.action) {
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});

/**
 * Message Event - Communication with main thread
 */
self.addEventListener('message', event => {
  console.log('Service Worker: Message received', event.data);

  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'CACHE_URLS') {
    const urls = event.data.payload;
    caches.open(CACHE_NAME).then(cache => {
      cache.addAll(urls);
    });
  }

  if (event.data && event.data.type === 'CLEAR_CACHE') {
    caches.delete(CACHE_NAME).then(() => {
      console.log('Service Worker: Cache cleared');
    });
  }

  // Authentication-aware cache invalidation
  if (event.data && event.data.type === 'AUTH_STATE_CHANGED') {
    // Clear all caches when user logs in or out to prevent stale data
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          console.log('Service Worker: Clearing cache due to auth change:', cacheName);
          return caches.delete(cacheName);
        })
      );
    }).then(() => {
      console.log('Service Worker: All caches cleared after auth change');
      // Re-cache essential offline assets
      return caches.open(CACHE_NAME).then(cache => {
        return cache.addAll(STATIC_CACHE_FILES);
      });
    });
  }
  
  // Handle audio player state persistence
  if (event.data && event.data.type === 'SAVE_AUDIO_STATE') {
    handleSaveAudioState(event.data.payload);
  }
  
  if (event.data && event.data.type === 'RESTORE_AUDIO_STATE') {
    handleRestoreAudioState(event);
  }
  
  // Preload audio files for better PWA performance
  if (event.data && event.data.type === 'PRELOAD_AUDIO') {
    preloadAudioFile(event.data.payload);
  }
});

/**
 * Save audio player state for PWA persistence
 */
async function handleSaveAudioState(state) {
  try {
    // Use IndexedDB for more robust storage
    const db = await openAudioDB();
    const transaction = db.transaction(['audioState'], 'readwrite');
    const store = transaction.objectStore('audioState');
    
    await store.put({
      id: 'current',
      ...state,
      timestamp: Date.now()
    });
    
    console.log('Service Worker: Audio state saved');
  } catch (error) {
    console.error('Service Worker: Failed to save audio state:', error);
    
    // Fallback to cache storage
    try {
      const cache = await caches.open(CACHE_NAME + '-state');
      const response = new Response(JSON.stringify(state));
      await cache.put(new Request('/audio-state'), response);
    } catch (cacheError) {
      console.error('Service Worker: Cache fallback also failed:', cacheError);
    }
  }
}

/**
 * Restore audio player state from storage
 */
async function handleRestoreAudioState(event) {
  try {
    // Try IndexedDB first
    const db = await openAudioDB();
    const transaction = db.transaction(['audioState'], 'readonly');
    const store = transaction.objectStore('audioState');
    const state = await store.get('current');
    
    if (state && Date.now() - state.timestamp < 3600000) { // 1 hour limit
      event.ports[0].postMessage({
        type: 'AUDIO_STATE_RESTORED',
        payload: state
      });
      return;
    }
  } catch (error) {
    console.log('Service Worker: IndexedDB restore failed, trying cache:', error);
  }
  
  try {
    // Fallback to cache storage
    const cache = await caches.open(CACHE_NAME + '-state');
    const response = await cache.match('/audio-state');
    
    if (response) {
      const state = await response.json();
      event.ports[0].postMessage({
        type: 'AUDIO_STATE_RESTORED',
        payload: state
      });
    } else {
      event.ports[0].postMessage({
        type: 'AUDIO_STATE_NOT_FOUND'
      });
    }
  } catch (error) {
    console.error('Service Worker: Failed to restore audio state:', error);
    event.ports[0].postMessage({
      type: 'AUDIO_STATE_ERROR',
      error: error.message
    });
  }
}

/**
 * Preload audio files for better PWA performance
 */
async function preloadAudioFile(audioUrl) {
  try {
    const cache = await caches.open(CACHE_NAME + '-audio');
    const response = await fetch(audioUrl);
    
    if (response.ok) {
      await cache.put(audioUrl, response);
      console.log('Service Worker: Audio file preloaded:', audioUrl);
    }
  } catch (error) {
    console.error('Service Worker: Failed to preload audio file:', error);
  }
}

/**
 * Open IndexedDB for audio state storage
 */
function openAudioDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('MixPitchAudio', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('audioState')) {
        db.createObjectStore('audioState', { keyPath: 'id' });
      }
    };
  });
}