/**
 * Bulk Download Polling
 *
 * Listens for 'bulk-download-started' Livewire events and polls the API
 * for download status updates. Automatically downloads when ready.
 *
 * Features:
 * - Polls for ZIP creation status every 3 seconds
 * - Shows Flux toast notifications for success/error
 * - Prevents navigation while ZIP is being prepared
 */

document.addEventListener('livewire:init', () => {
    const activePolls = new Map();

    // Listen for bulk download started event
    Livewire.on('bulk-download-started', (event) => {
        const archiveId = event.archiveId;

        // Don't start duplicate polls
        if (activePolls.has(archiveId)) {
            console.log(`Poll already active for archive ${archiveId}`);
            return;
        }

        console.log(`Starting bulk download poll for archive ${archiveId}`);

        // Poll every 3 seconds
        const pollInterval = setInterval(async () => {
            try {
                const response = await fetch(`/bulk-download/${archiveId}/status`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    console.error(`Status check failed: ${response.status}`);
                    return;
                }

                const data = await response.json();

                if (data.status === 'completed') {
                    // Stop polling
                    clearInterval(pollInterval);
                    activePolls.delete(archiveId);

                    // Show success toast via Flux
                    if (window.Flux && window.Flux.toast) {
                        window.Flux.toast({
                            heading: 'Download Ready!',
                            text: 'Your ZIP file is now downloading.',
                            variant: 'success',
                            duration: 5000
                        });
                    }

                    // Dispatch download ready event
                    Livewire.dispatch('bulk-download-ready', {
                        url: data.download_url
                    });

                    console.log(`Archive ${archiveId} completed, initiating download`);

                } else if (data.status === 'failed') {
                    // Stop polling on failure
                    clearInterval(pollInterval);
                    activePolls.delete(archiveId);

                    // Show error toast via Flux
                    if (window.Flux && window.Flux.toast) {
                        window.Flux.toast({
                            heading: 'Download Failed',
                            text: data.error_message || 'An error occurred while preparing your ZIP.',
                            variant: 'danger',
                            duration: 10000
                        });
                    }

                    console.error(`Archive ${archiveId} failed: ${data.error_message}`);
                }

            } catch (error) {
                console.error('Error checking bulk download status:', error);
            }
        }, 3000);

        // Store the interval
        activePolls.set(archiveId, pollInterval);

        // Stop polling after 5 minutes (timeout)
        setTimeout(() => {
            if (activePolls.has(archiveId)) {
                console.log(`Polling timeout for archive ${archiveId}`);
                clearInterval(pollInterval);
                activePolls.delete(archiveId);

                // Show timeout toast
                if (window.Flux && window.Flux.toast) {
                    window.Flux.toast({
                        heading: 'Download Timeout',
                        text: 'The ZIP download is taking longer than expected. Please try again.',
                        variant: 'warning',
                        duration: 10000
                    });
                }
            }
        }, 300000); // 5 minutes
    });

    // Listen for download ready event and trigger download
    Livewire.on('bulk-download-ready', (event) => {
        const url = event.url;

        if (!url) {
            console.error('No download URL provided');
            return;
        }

        // Initiate download by redirecting to download URL
        window.location.href = url;

        console.log(`Downloading from: ${url}`);
    });

    // Listen for individual file downloads
    Livewire.on('download-file', (event) => {
        const { url, filename } = event;

        if (!url) {
            console.error('No download URL provided for individual file');
            return;
        }

        // Create invisible iframe to trigger download without navigation
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.style.position = 'fixed';
        iframe.style.width = '1px';
        iframe.style.height = '1px';
        iframe.style.top = '-10px';
        iframe.style.left = '-10px';
        iframe.src = url;

        document.body.appendChild(iframe);

        // Remove iframe after download starts (1 second delay)
        setTimeout(() => {
            if (iframe.parentNode) {
                document.body.removeChild(iframe);
            }
        }, 1000);

        console.log(`Downloading file: ${filename || 'unknown'} from ${url}`);
    });

    // Navigation prevention and cleanup on page unload
    window.addEventListener('beforeunload', (e) => {
        // If there are active polls, warn the user
        if (activePolls.size > 0) {
            // Modern browsers ignore custom messages but still show a confirmation
            e.preventDefault();
            e.returnValue = 'Your ZIP download is still being prepared. Are you sure you want to leave?';
            return e.returnValue;
        }

        // Clean up polls if leaving anyway
        activePolls.forEach((interval) => clearInterval(interval));
        activePolls.clear();
    });
});
