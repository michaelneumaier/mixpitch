@once
    <script>
        // Custom JavaScript for the Filament admin panel
        document.addEventListener('DOMContentLoaded', function () {
            // Any custom initialization for the admin panel
            console.log('Filament admin panel loaded');
            
            // Example of a custom function that could be used across the admin panel
            window.refreshData = function() {
                Livewire.dispatch('refresh-data');
            };
        });
    </script>
@endonce 