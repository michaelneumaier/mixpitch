// Fix for Filament sidebar Alpine.js store initialization
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Alpine.js to be available
    if (typeof Alpine !== 'undefined') {
        initializeSidebarStore();
    } else {
        // If Alpine.js isn't loaded yet, wait for it
        document.addEventListener('alpine:init', initializeSidebarStore);
    }
});

function initializeSidebarStore() {
    // Initialize the sidebar store if it doesn't exist
    if (typeof Alpine !== 'undefined' && Alpine.store) {
        // Check if sidebar store exists and fix collapsedGroups if null
        const sidebarStore = Alpine.store('sidebar');
        if (sidebarStore && sidebarStore.collapsedGroups === null) {
            sidebarStore.collapsedGroups = [];
        }
        
        // Also ensure localStorage has a valid value
        let collapsedGroups = localStorage.getItem('collapsedGroups');
        if (collapsedGroups === null || collapsedGroups === 'null') {
            localStorage.setItem('collapsedGroups', JSON.stringify([]));
        }
        
        // Force update the store
        if (sidebarStore) {
            try {
                sidebarStore.collapsedGroups = JSON.parse(localStorage.getItem('collapsedGroups')) || [];
            } catch (e) {
                sidebarStore.collapsedGroups = [];
                localStorage.setItem('collapsedGroups', JSON.stringify([]));
            }
        }
    }
}

// Also fix it on Livewire navigation
document.addEventListener('livewire:navigated', initializeSidebarStore);