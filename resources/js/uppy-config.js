import Uppy from '@uppy/core';
import DragDrop from '@uppy/drag-drop';
import StatusBar from '@uppy/status-bar';
import ProgressBar from '@uppy/progress-bar';
import FileInput from '@uppy/file-input';
import AwsS3 from '@uppy/aws-s3';

// Import CSS
import '@uppy/core/dist/style.min.css';
import '@uppy/drag-drop/dist/style.min.css';
import '@uppy/status-bar/dist/style.min.css';
import '@uppy/progress-bar/dist/style.min.css';
import '@uppy/file-input/dist/style.min.css';

// Make Uppy components available globally
window.Uppy = Uppy;
window.UppyDragDrop = DragDrop;
window.UppyStatusBar = StatusBar;
window.UppyProgressBar = ProgressBar;
window.UppyFileInput = FileInput;
window.UppyAwsS3 = AwsS3;

// Create a global Uppy factory function
window.createUppy = function(options = {}) {
    const defaultOptions = {
        debug: false,
        autoProceed: false,
        allowMultipleUploads: false,
        restrictions: {
            maxFileSize: 200 * 1024 * 1024, // 200MB
            allowedFileTypes: ['audio/*', 'application/pdf', 'image/*', 'application/zip'],
        },
    };

    return new Uppy({
        ...defaultOptions,
        ...options
    });
};

// CSRF token helper
window.getCSRFToken = function() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

console.log('Uppy configuration loaded');