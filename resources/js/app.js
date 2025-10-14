import './bootstrap';
import '../../vendor/masmerise/livewire-toaster/resources/js';
import './uppy-config';
import './global-audio-manager';
import './global-upload-manager';
import './hero';

// Load Stripe handler if available
try {
    import('./filament/billing/stripe-handler.js');
} catch (e) {
    console.error('Could not load Stripe handler:', e);
}