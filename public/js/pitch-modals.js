// Pitch Modal Functionality
document.addEventListener('DOMContentLoaded', function () {
    // Form submission handlers
    window.pitchForms = {
        approveForm: null,
        denyForm: null,
        revisionsForm: null
    };

    // Open modal functions
    window.openApproveModal = function (snapshotId, url) {
        document.getElementById('approveModal').classList.remove('hidden');

        // Create form for submission
        window.pitchForms.approveForm = document.createElement('form');
        window.pitchForms.approveForm.method = 'POST';
        window.pitchForms.approveForm.action = url;

        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        window.pitchForms.approveForm.appendChild(csrfToken);

        // Set up submit button
        document.getElementById('approveSubmitBtn').onclick = function () {
            document.body.appendChild(window.pitchForms.approveForm);
            window.pitchForms.approveForm.submit();
        };
    };

    window.openDenyModal = function (snapshotId, url) {
        document.getElementById('denyModal').classList.remove('hidden');

        // Create form for submission
        window.pitchForms.denyForm = document.createElement('form');
        window.pitchForms.denyForm.method = 'POST';
        window.pitchForms.denyForm.action = url;

        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        window.pitchForms.denyForm.appendChild(csrfToken);

        // Set up submit button
        document.getElementById('denySubmitBtn').onclick = function () {
            const reason = document.getElementById('denyReason').value;
            if (!reason.trim()) {
                alert('Please provide a reason for denying this pitch.');
                return;
            }

            // Add reason to form
            const reasonField = document.createElement('input');
            reasonField.type = 'hidden';
            reasonField.name = 'reason';
            reasonField.value = reason;
            window.pitchForms.denyForm.appendChild(reasonField);

            document.body.appendChild(window.pitchForms.denyForm);
            window.pitchForms.denyForm.submit();
        };
    };

    window.openRevisionsModal = function (snapshotId, url) {
        document.getElementById('revisionsModal').classList.remove('hidden');

        // Create form for submission
        window.pitchForms.revisionsForm = document.createElement('form');
        window.pitchForms.revisionsForm.method = 'POST';
        window.pitchForms.revisionsForm.action = url;

        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        window.pitchForms.revisionsForm.appendChild(csrfToken);

        // Set up submit button
        document.getElementById('revisionsSubmitBtn').onclick = function () {
            const reason = document.getElementById('revisionsRequested').value;
            if (!reason.trim()) {
                alert('Please specify what revisions you would like to request.');
                return;
            }

            // Add reason to form
            const reasonField = document.createElement('input');
            reasonField.type = 'hidden';
            reasonField.name = 'reason';
            reasonField.value = reason;
            window.pitchForms.revisionsForm.appendChild(reasonField);

            document.body.appendChild(window.pitchForms.revisionsForm);
            window.pitchForms.revisionsForm.submit();
        };
    };

    // Close modal function
    window.closeModal = function (modalId) {
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