/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Add error handling for WebSocket connections
try {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY || 'MixPitchApp',
        wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
        wsPort: import.meta.env.VITE_PUSHER_PORT || 8080,
        forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'http') === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
        // Add connection timeout and error handling
        connectionTimeout: 10000,
    });
    
    // Add connection error handling
    window.Echo.connector.pusher.connection.bind('error', (error) => {
        console.log('WebSocket connection error:', error);
        // The app will continue to function without real-time updates
    });
    
    // Log successful connection
    window.Echo.connector.pusher.connection.bind('connected', () => {
        console.log('WebSocket connected successfully');
    });
} catch (error) {
    console.error('Failed to initialize Echo:', error);
    // Create a dummy Echo object to prevent errors when Echo is referenced
    window.Echo = {
        private: () => ({
            listen: () => ({}),
            notification: () => ({})
        }),
        channel: () => ({
            listen: () => ({})
        })
    };
}
