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

// Create a global Uppy factory function with dynamic settings support
window.createUppy = function(options = {}) {
    const defaultOptions = {
        debug: false,
        autoProceed: false,
        allowMultipleUploads: false,
        restrictions: {
            maxFileSize: 200 * 1024 * 1024, // 200MB fallback
            allowedFileTypes: ['audio/*', 'application/pdf', 'image/*', 'application/zip'],
        },
    };

    // Merge restrictions separately to handle nested object properly
    const mergedOptions = {
        ...defaultOptions,
        ...options,
        restrictions: {
            ...defaultOptions.restrictions,
            ...(options.restrictions || {})
        }
    };

    return new Uppy(mergedOptions);
};

// Fetch upload settings from API
window.fetchUploadSettings = async function(context = 'global') {
    try {
        const response = await fetch(`/api/upload-settings/${context}`, {
            headers: {
                'X-CSRF-TOKEN': window.getCSRFToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin' // Include session cookies for authentication
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Upload settings fetched:', data);
        return data;
    } catch (error) {
        console.error('Failed to fetch upload settings:', error);
        // Return fallback settings
        return {
            settings: {
                max_file_size_mb: 200,
                chunk_size_mb: 5,
                max_concurrent_uploads: 3,
                max_retry_attempts: 3,
                enable_chunking: true,
                session_timeout_hours: 24
            },
            computed: {
                uppy_restrictions: {
                    maxFileSize: 200 * 1024 * 1024,
                    allowedFileTypes: ['audio/*', 'application/pdf', 'image/*', 'application/zip']
                },
                upload_config: {
                    chunkSize: 5 * 1024 * 1024,
                    limit: 3,
                    retryDelays: [1000, 1000, 1000]
                }
            }
        };
    }
};

// Create Uppy with dynamic settings
window.createUppyWithSettings = async function(context = 'global', additionalOptions = {}) {
    const settingsData = await window.fetchUploadSettings(context);
    
    const options = {
        debug: false,
        autoProceed: false,
        allowMultipleUploads: false,
        restrictions: settingsData.computed.uppy_restrictions,
        ...additionalOptions
    };
    
    const uppy = window.createUppy(options);
    
    // Store settings on the uppy instance for reference
    uppy.uploadSettings = settingsData;
    
    return uppy;
};

// CSRF token helper
window.getCSRFToken = function() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

// API token helper (for authenticated requests)
window.getAPIToken = function() {
    // For web routes, we'll use session authentication instead of Bearer token
    // This function exists for compatibility but returns empty string
    return '';
};

console.log('Uppy configuration loaded');