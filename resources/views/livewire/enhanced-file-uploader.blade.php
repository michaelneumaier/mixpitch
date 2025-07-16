<div x-data="filePondUploader(@js($uploadConfig))" 
     class="enhanced-file-uploader"
     x-init="init()">
    
    {{-- Enhanced File Upload Interface --}}
    <div class="mb-4">
        <div class="flex flex-col">
            <label class="mb-1.5 sm:mb-2 text-sm sm:text-base text-gray-700">
                Upload files
                @if($uploadConfig['allowMultiple'])
                    <span class="text-xs text-gray-500">(Multiple files supported)</span>
                @endif
            </label>
            
            {{-- FilePond Container --}}
            <div class="filepond-container mb-4" wire:ignore>
                <input type="file" 
                       class="filepond-input"
                       id="filepond-input-{{ md5($uploadConfig['modelType'] . '-' . $uploadConfig['modelId']) }}"
                       @if($uploadConfig['allowMultiple']) multiple @endif
                       accept="{{ implode(',', $uploadConfig['acceptedFileTypes']) }}">
            </div>
            
            {{-- Upload Status Messages --}}
            <div x-show="uploadStatus" class="mt-2">
                <div x-show="uploadStatus === 'uploading'" class="text-blue-600 text-sm">
                    <i class="fas fa-spinner fa-spin mr-1"></i>
                    Uploading files...
                </div>
                <div x-show="uploadStatus === 'completed'" class="text-green-600 text-sm">
                    <i class="fas fa-check-circle mr-1"></i>
                    Upload completed successfully!
                </div>
                <div x-show="uploadStatus === 'error'" class="text-red-600 text-sm">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    <span x-text="errorMessage"></span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Alpine.js Component Script --}}
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('filePondUploader', (config) => ({
        // Configuration
        config: config,
        
        // FilePond instance
        pond: null,
        
        // Status tracking
        uploadStatus: '',
        errorMessage: '',
        
        init() {
            // More robust initialization with multiple safety checks
            setTimeout(() => {
                this.waitForFilePond(() => {
                    this.initializeFilePond();
                    this.setupEventListeners();
                });
            }, 100); // Small delay to ensure DOM is fully ready
        },
        
        waitForFilePond(callback, maxAttempts = 10, attempt = 1) {
            if (typeof FilePond !== 'undefined' && FilePond.create) {
                callback();
            } else if (attempt < maxAttempts) {
                console.log(`Waiting for FilePond library... (attempt ${attempt}/${maxAttempts})`);
                setTimeout(() => {
                    this.waitForFilePond(callback, maxAttempts, attempt + 1);
                }, 100);
            } else {
                console.error('FilePond library not loaded after maximum attempts');
                this.setStatus('error', 'File uploader library not available');
            }
        },
        
        initializeFilePond() {
            // Try multiple approaches to find the FilePond input element
            console.log('Looking for FilePond input element...');
            console.log('Alpine $el:', this.$el);
            
            // Generate the expected ID based on config
            const expectedId = 'filepond-input-' + this.generateElementId();
            console.log('Expected input ID:', expectedId);
            
            // Try multiple selection methods
            let filePondInput = this.$el.querySelector('.filepond-input');
            if (!filePondInput) {
                filePondInput = document.getElementById(expectedId);
            }
            if (!filePondInput) {
                filePondInput = this.$el.querySelector('input[type="file"]');
            }
            
            console.log('Found FilePond input:', filePondInput);
            
            if (!filePondInput) {
                console.error('FilePond input element not found with any method');
                console.log('Available elements in $el:', this.$el.querySelectorAll('*'));
                console.log('Available file inputs in document:', document.querySelectorAll('input[type="file"]'));
                return;
            }
            
            if (!document.contains(filePondInput)) {
                console.error('FilePond input element not attached to DOM');
                return;
            }
            
            try {
                // Register FilePond plugins if available
                if (window.FilePondPluginFileValidateType) {
                    FilePond.registerPlugin(window.FilePondPluginFileValidateType);
                }
                if (window.FilePondPluginFileValidateSize) {
                    FilePond.registerPlugin(window.FilePondPluginFileValidateSize);
                }
                if (window.FilePondPluginImagePreview) {
                    FilePond.registerPlugin(window.FilePondPluginImagePreview);
                }
                
                // Create FilePond instance with sopamo package integration
                this.pond = FilePond.create(filePondInput, {
                    // Basic configuration
                    allowMultiple: this.config.allowMultiple || false,
                    allowDrop: true,
                    allowBrowse: true,
                    allowReplace: !this.config.allowMultiple,
                    allowRevert: true,
                    allowRemove: true,
                    
                    // File validation
                    acceptedFileTypes: this.config.acceptedFileTypes || ['audio/*', 'application/pdf', 'image/*', 'application/zip'],
                    maxFileSize: this.config.maxFileSize || '200MB',
                    maxFiles: this.config.maxFiles || (this.config.allowMultiple ? 10 : 1),
                    
                    // Chunked upload configuration with 5MB chunks
                    chunkUploads: true,
                    chunkSize: 5000000, // 5MB chunks for better performance
                    chunkForce: true, // Force chunking for all files
                    chunkRetryDelays: [500, 1000, 3000],
                    
                    // Rahulhaque package server configuration
                    server: {
                        url: '/filepond',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    },
                    
                    // Labels
                    labelIdle: 'Drag & Drop your files or <span class="filepond--label-action">Browse</span>',
                    labelFileProcessing: 'Uploading',
                    labelFileProcessingComplete: 'Upload complete',
                    labelFileProcessingAborted: 'Upload cancelled',
                    labelFileProcessingError: 'Error during upload',
                    labelTapToCancel: 'tap to cancel',
                    labelTapToRetry: 'tap to retry',
                    labelTapToUndo: 'tap to undo',
                    
                    // Event handlers
                    onprocessfiles: () => this.handleProcessComplete(),
                    onprocessfileerror: (error) => this.handleProcessError(error),
                    onaddfilestart: () => this.setStatus('uploading'),
                    onaddfile: (error, file) => {
                        if (error) {
                            this.handleProcessError(error);
                        }
                    }
                });
                
                console.log('FilePond initialized successfully');
                
            } catch (error) {
                console.error('Failed to initialize FilePond:', error);
                this.setStatus('error', 'Failed to initialize file uploader');
            }
        },
        
        setupEventListeners() {
            // Listen for Livewire events
            this.$wire.on('filesUploaded', (data) => {
                this.setStatus('completed');
                console.log('Files uploaded successfully:', data);
                
                // Clear the pond after successful upload
                setTimeout(() => {
                    if (this.pond) {
                        this.pond.removeFiles();
                    }
                    this.setStatus('');
                }, 2000);
            });
            
            this.$wire.on('fileUploadError', (data) => {
                this.setStatus('error', data.message || 'Upload failed');
            });
        },
        
        handleProcessComplete() {
            console.log('All files processed, getting temp file IDs...');
            
            // Get temporary file IDs from FilePond
            const tempFileIds = this.pond.getFiles()
                .map(file => file.serverId)
                .filter(id => id !== null && id !== undefined);
            
            if (tempFileIds.length > 0) {
                console.log('Sending temp file IDs to Livewire:', tempFileIds);
                
                // Send temporary file IDs to Livewire for processing
                this.$wire.handleFilePondUpload(tempFileIds);
            } else {
                this.setStatus('error', 'No files were uploaded successfully');
            }
        },
        
        handleProcessError(error) {
            console.error('FilePond processing error:', error);
            let message = 'Upload failed';
            
            if (error && error.body) {
                message = error.body;
            } else if (typeof error === 'string') {
                message = error;
            }
            
            this.setStatus('error', message);
        },
        
        setStatus(status, message = '') {
            this.uploadStatus = status;
            this.errorMessage = message;
        },
        
        generateElementId() {
            // Mirror the PHP hash generation for the element ID
            const hashInput = this.config.modelType + '-' + this.config.modelId;
            // Simple hash function since we don't have PHP's md5 in JS
            let hash = 0;
            for (let i = 0; i < hashInput.length; i++) {
                const char = hashInput.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32-bit integer
            }
            return Math.abs(hash).toString();
        }
    }));
});
</script>
@endpush

{{-- Styles --}}
@push('styles')
<style>
.enhanced-file-uploader .filepond--root {
    margin-bottom: 0;
}

.enhanced-file-uploader .filepond--drop-label {
    color: #6b7280;
    font-size: 0.875rem;
}

.enhanced-file-uploader .filepond--panel-root {
    border-radius: 0.5rem;
    border: 2px dashed #d1d5db;
    background-color: #f9fafb;
    transition: all 0.2s ease;
}

.enhanced-file-uploader .filepond--panel-root:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.enhanced-file-uploader .filepond--item {
    width: calc(50% - 0.5em);
}

@media (max-width: 640px) {
    .enhanced-file-uploader .filepond--item {
        width: 100%;
    }
}

.enhanced-file-uploader .filepond--file-action-button {
    border-radius: 0.375rem;
}

.enhanced-file-uploader .filepond--file-status-main {
    font-size: 0.75rem;
}

.enhanced-file-uploader .filepond--progress-indicator {
    color: #3b82f6;
}

/* Chunk upload progress styling */
.enhanced-file-uploader .filepond--file-status-sub {
    font-size: 0.65rem;
    opacity: 0.8;
}
</style>
@endpush