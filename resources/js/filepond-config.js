import * as FilePond from 'filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';

// Register FilePond plugins
FilePond.registerPlugin(
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize,
    FilePondPluginImagePreview
);

// Default FilePond configuration
const defaultConfig = {
    // Server configuration for chunked uploads
    server: {
        url: '/filepond',
        timeout: 7000,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        process: {
            url: '/filepond',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        },
        revert: '/filepond',
        restore: null,
        load: null,
        fetch: null
    },

    // File validation
    acceptedFileTypes: ['audio/*', 'image/*'],
    maxFileSize: '100MB',
    maxFiles: 10,

    // Chunked upload configuration
    chunkUploads: true,
    chunkSize: 5000000, // 5MB chunks
    chunkRetryDelays: [500, 1000, 3000],

    // UI configuration
    allowMultiple: true,
    allowReorder: true,
    allowRemove: true,
    allowRevert: true,
    allowProcess: true,

    // Labels
    labelIdle: 'Drag & Drop your files or <span class="filepond--label-action">Browse</span>',
    labelInvalidField: 'Invalid files',
    labelFileWaitingForSize: 'Waiting for size',
    labelFileSizeNotAvailable: 'Size not available',
    labelFileLoading: 'Loading',
    labelFileLoadError: 'Error during load',
    labelFileProcessing: 'Uploading',
    labelFileProcessingComplete: 'Upload complete',
    labelFileProcessingAborted: 'Upload cancelled',
    labelFileProcessingError: 'Error during upload',
    labelFileProcessingRevertError: 'Error during revert',
    labelFileRemoveError: 'Error during remove',
    labelTapToCancel: 'tap to cancel',
    labelTapToRetry: 'tap to retry',
    labelTapToUndo: 'tap to undo',
    labelButtonRemoveItem: 'Remove',
    labelButtonAbortItemLoad: 'Abort',
    labelButtonRetryItemLoad: 'Retry',
    labelButtonAbortItemProcessing: 'Cancel',
    labelButtonUndoItemProcessing: 'Undo',
    labelButtonRetryItemProcessing: 'Retry',
    labelButtonProcessItem: 'Upload',
};

// FilePond configuration factory
export class FilePondConfig {
    static create(element, customConfig = {}) {
        const config = { ...defaultConfig, ...customConfig };

        // Ensure CSRF token is always set
        if (!config.server.headers['X-CSRF-TOKEN']) {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (token) {
                config.server.headers['X-CSRF-TOKEN'] = token;
            }
        }

        return FilePond.create(element, config);
    }

    static getDefaultConfig() {
        return { ...defaultConfig };
    }

    static updateCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            defaultConfig.server.headers['X-CSRF-TOKEN'] = token;
        }
    }
}

// Make FilePond available globally for Livewire components
window.FilePond = FilePond;
window.FilePondConfig = FilePondConfig;

export default FilePond;