// Pitch Modal Functionality
document.addEventListener('DOMContentLoaded', function () {
    // Open modal functions
    window.openApproveModal = function (snapshotId, url) {
        console.log('Opening approve modal for snapshot:', snapshotId, 'URL:', url);

        // Get the pre-existing form
        const form = document.getElementById('approveForm');
        form.action = url;

        // Clear any old inputs first (except CSRF token)
        Array.from(form.querySelectorAll('input:not([name="_token"])')).forEach(input => input.remove());

        // Show the modal
        document.getElementById('approveModal').classList.remove('hidden');

        // Set up submit button
        document.getElementById('approveSubmitBtn').onclick = function () {
            console.log('Submitting approve form to URL:', url);
            form.submit();
        };
    };

    window.openDenyModal = function (snapshotId, url) {
        console.log('Opening deny modal for snapshot:', snapshotId, 'URL:', url);

        // Get the pre-existing form
        const form = document.getElementById('denyForm');
        form.action = url;

        // Clear any old inputs first (except CSRF token)
        Array.from(form.querySelectorAll('input:not([name="_token"])')).forEach(input => input.remove());

        // Reset textarea value
        document.getElementById('denyReason').value = '';

        // Show the modal
        document.getElementById('denyModal').classList.remove('hidden');

        // Set up submit button
        document.getElementById('denySubmitBtn').onclick = function () {
            const reasonInput = document.getElementById('denyReason');
            const reason = reasonInput.value;

            if (!reason.trim()) {
                alert('Please provide a reason for denying this pitch.');
                reasonInput.focus(); // Focus the input for better UX
                return;
            }
            if (reason.trim().length < 10) {
                alert('The reason must be at least 10 characters long.');
                reasonInput.focus(); // Focus the input
                return;
            }

            // Add reason to form
            const reasonField = document.createElement('input');
            reasonField.type = 'hidden';
            reasonField.name = 'reason';
            reasonField.value = reason;
            form.appendChild(reasonField);

            console.log('Submitting deny form to URL:', url, 'with reason:', reason);
            form.submit();
        };
    };

    window.openRevisionsModal = function (snapshotId, url) {
        console.log('Opening revisions modal for snapshot:', snapshotId, 'URL:', url);

        // Get the pre-existing form
        const form = document.getElementById('revisionsForm');
        form.action = url;

        // Clear any old inputs first (except CSRF token)
        Array.from(form.querySelectorAll('input:not([name="_token"])')).forEach(input => input.remove());

        // Reset textarea value
        document.getElementById('revisionsRequested').value = '';

        // Show the modal
        document.getElementById('revisionsModal').classList.remove('hidden');

        // Set up submit button
        document.getElementById('revisionsSubmitBtn').onclick = function () {
            const reasonInput = document.getElementById('revisionsRequested');
            const reason = reasonInput.value;

            if (!reason.trim()) {
                alert('Please specify what revisions you would like to request.');
                reasonInput.focus(); // Focus the input
                return;
            }
            if (reason.trim().length < 10) {
                alert('The requested revisions must be at least 10 characters long.');
                reasonInput.focus(); // Focus the input
                return;
            }

            // Add reason to form
            const reasonField = document.createElement('input');
            reasonField.type = 'hidden';
            reasonField.name = 'reason';
            reasonField.value = reason;
            form.appendChild(reasonField);

            console.log('Submitting revisions form to URL:', url, 'with reason:', reason);
            form.submit();
        };
    };

    // Close modal function
    window.closeModal = function (modalId) {
        console.log('Closing modal:', modalId);
        document.getElementById(modalId).classList.add('hidden');
    };

    // Close modals when clicking outside or pressing escape
    window.addEventListener('click', function (event) {
        const modals = ['approveModal', 'denyModal', 'revisionsModal'];
        modals.forEach(function (modalId) {
            const modal = document.getElementById(modalId);
            if (modal && event.target === modal) {
                closeModal(modalId);
            }
        });
    });

    window.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            const modals = ['approveModal', 'denyModal', 'revisionsModal'];
            modals.forEach(function (modalId) {
                closeModal(modalId);
            });
        }
    });
}); 