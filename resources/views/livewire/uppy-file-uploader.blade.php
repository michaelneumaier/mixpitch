<div class="uppy-file-uploader" x-data="uppyFileUploader(@entangle('uploadedFiles').live, $wire)" wire:ignore>
    <!-- Upload Area -->
    <div class="uppy-upload-container">
        <!-- Drag & Drop Area -->
        <div id="uppy-drag-drop-{{ $uploadConfig['modelId'] }}" class="uppy-drag-drop-area"></div>
        
        <!-- Progress Bar -->
        <div id="uppy-progress-{{ $uploadConfig['modelId'] }}" class="uppy-progress"></div>
        
        <!-- Status Bar -->
        <div id="uppy-status-{{ $uploadConfig['modelId'] }}" class="uppy-status"></div>
        
    </div>

    <!-- Upload Configuration -->
    <script>
        window.uppyConfig_{{ $uploadConfig['modelId'] }} = @json($uploadConfig);
        
        
        function uppyFileUploader(uploadedFiles, wire) {
            return {
                uppy: null,
                uploadedFiles: uploadedFiles,
                wire: wire,
                
                init() {
                    this.initializeUppy();
                    
                    // Listen for reset event from Livewire
                    this.$wire.on('resetUploader', () => {
                        this.clearCompletedFiles();
                    });
                },
                
                initializeUppy() {
                    const config = window.uppyConfig_{{ $uploadConfig['modelId'] }};
                    const modelId = config.modelId;
                    
                    // Create Uppy instance
                    this.uppy = window.createUppy({
                        debug: true,
                        autoProceed: false,
                        allowMultipleUploads: true, // Always allow multiple uploads
                        restrictions: {
                            maxFileSize: config.maxFileSize,
                            maxNumberOfFiles: config.maxFiles,
                            allowedFileTypes: config.acceptedFileTypes.length > 0 ? config.acceptedFileTypes : null,
                        },
                        meta: {
                            modelId: modelId,
                            modelType: config.modelType,
                            context: config.context,
                        },
                        onBeforeFileAdded: (currentFile, files) => {
                            // Allow adding files even after previous uploads complete
                            return true;
                        }
                    });

                    // Add plugins
                    this.uppy
                        .use(window.UppyDragDrop, {
                            target: `#uppy-drag-drop-${modelId}`,
                            note: 'Drop multiple files here or click to browse (max {{ $this->formatFileSize($uploadConfig["maxFileSize"]) }} each)',
                            width: '100%',
                            height: 200,
                            multiple: true,
                        })
                        .use(window.UppyProgressBar, {
                            target: `#uppy-progress-${modelId}`,
                            hideAfterFinish: false,
                        })
                        .use(window.UppyStatusBar, {
                            target: `#uppy-status-${modelId}`,
                            hideUploadButton: false,
                            hideRetryButton: false,
                            hidePauseResumeButton: false,
                            hideCancelButton: false,
                            showProgressDetails: true,
                            hideAfterFinish: false,
                            doneButtonHandler: () => {
                                // Clear completed files and reset for new uploads
                                this.uppy.reset();
                                this.refreshUploadArea();
                            }
                        })
                        .use(window.UppyAwsS3, {
                            shouldUseMultipart: true,
                            limit: 4, // Number of concurrent uploads
                            retryDelays: [0, 1000, 3000, 5000],
                            getChunkSize(file) {
                                // Use 5MB chunks for files larger than 100MB
                                return file.size > 100 * 1024 * 1024 ? 5 * 1024 * 1024 : null;
                            },
                            createMultipartUpload(file) {
                                return fetch('/s3/multipart', {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: JSON.stringify({
                                        filename: file.name,
                                        type: file.type,
                                        size: file.size,
                                        metadata: {
                                            modelId: config.modelId,
                                            modelType: config.modelType,
                                            context: config.context,
                                        }
                                    }),
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => ({
                                    uploadId: data.uploadId,
                                    key: data.key,
                                }));
                            },
                            signPart(file, partData) {
                                const url = `/s3/multipart/${partData.uploadId}/${partData.partNumber}?key=${encodeURIComponent(partData.key)}`;
                                return fetch(url, {
                                    method: 'GET',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => ({
                                    url: data.url,
                                    headers: data.headers || {}
                                }));
                            },
                            completeMultipartUpload(file, { uploadId, parts, key }) {
                                return fetch(`/s3/multipart/${uploadId}/complete`, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: JSON.stringify({
                                        parts: parts,
                                        key: key
                                    }),
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => ({
                                    location: data.location
                                }));
                            },
                            abortMultipartUpload(file, { uploadId, key }) {
                                const url = `/s3/multipart/${uploadId}?key=${encodeURIComponent(key)}`;
                                return fetch(url, {
                                    method: 'DELETE',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                });
                            }
                        });

                    // Event listeners
                    this.uppy.on('complete', (result) => {
                        console.log('Upload complete:', result);
                        
                        if (result.successful && result.successful.length > 0) {
                            const uploadData = result.successful.map(file => ({
                                name: file.name,
                                size: file.size,
                                type: file.type,
                                key: file.response?.body?.Key || file.meta?.key || file.s3Multipart?.key,
                                response: file.response
                            }));
                            
                            console.log('Processed upload data:', uploadData);
                            
                            // Call Livewire method to process uploaded files
                            this.wire.handleUploadSuccess(uploadData);
                        }
                        
                        if (result.failed && result.failed.length > 0) {
                            console.error('Some uploads failed:', result.failed);
                            result.failed.forEach(file => {
                                console.error(`Failed: ${file.name}`, file.error);
                            });
                        }
                    });

                    this.uppy.on('upload-error', (file, error, response) => {
                        console.error('Upload error:', { file, error, response });
                        // Use wire from component scope
                        if (this.wire) {
                            this.wire.dispatch('fileUploadError', {
                                message: `Upload failed for ${file.name}: ${error.message || 'Unknown error'}`
                            });
                        } else {
                            console.error('wire not available for error dispatch');
                        }
                    });

                    this.uppy.on('file-added', (file) => {
                        console.log('File added:', file);
                    });

                    this.uppy.on('upload-progress', (file, progress) => {
                        console.log('Upload progress:', file.name, progress);
                    });

                    this.uppy.on('upload-success', (file, response) => {
                        console.log('Upload success:', file.name, response);
                    });

                    console.log('Uppy initialized for model:', modelId);
                },
                
                clearCompletedFiles() {
                    if (this.uppy) {
                        // Remove all completed files but keep the uppy instance ready for new uploads
                        const files = this.uppy.getFiles();
                        files.forEach(file => {
                            if (file.progress?.uploadComplete) {
                                this.uppy.removeFile(file.id);
                            }
                        });
                        
                        // Reset the upload state
                        this.uppy.cancelAll();
                        
                        console.log('Cleared completed files, ready for new uploads');
                        this.refreshUploadArea();
                    }
                },
                
                refreshUploadArea() {
                    // Visual feedback that the area is ready for new uploads
                    const dragDropArea = document.querySelector(`#uppy-drag-drop-{{ $uploadConfig['modelId'] }}`);
                    if (dragDropArea) {
                        dragDropArea.classList.add('uppy-ready-for-new');
                        setTimeout(() => {
                            dragDropArea.classList.remove('uppy-ready-for-new');
                        }, 1000);
                    }
                },
                
                extractS3KeyFromUrl(url) {
                    try {
                        const urlObj = new URL(url);
                        // Remove leading slash
                        return urlObj.pathname.substring(1);
                    } catch (e) {
                        console.error('Failed to extract S3 key from URL:', url, e);
                        return null;
                    }
                },
                
                destroy() {
                    if (this.uppy) {
                        this.uppy.destroy();
                        this.uppy = null;
                    }
                }
            };
        }
    </script>

    <style>
        /* Use Inter font family to match website */
        .uppy-file-uploader,
        .uppy-file-uploader * {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        }

        .uppy-file-uploader {
            margin: 0;
        }

        .uppy-upload-container {
            border: none;
            border-radius: 0;
            padding: 0;
            background-color: transparent;
        }

        .uppy-drag-drop-area {
            margin-bottom: 1rem;
        }

        .uppy-progress {
            margin-bottom: 1rem;
        }

        .uppy-status {
            margin-top: 1rem;
        }

        /* Clean, simple Uppy styling to match site aesthetic */
        .uppy-DragDrop-container {
            border: none !important;
            background: transparent !important;
            font-family: inherit !important;
        }

        .uppy-DragDrop-inner {
            border: 2px dashed #e5e7eb !important;
            border-radius: 0.5rem !important;
            padding: 2rem !important;
            text-align: center !important;
            background: #f9fafb !important;
            transition: all 0.2s ease !important;
            font-family: inherit !important;
            min-height: 160px !important;
        }

        .uppy-DragDrop-label {
            font-family: inherit !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
            color: #374151 !important;
            line-height: 1.5 !important;
        }

        .uppy-DragDrop-note {
            font-family: inherit !important;
            font-size: 0.875rem !important;
            color: #6b7280 !important;
            margin-top: 0.5rem !important;
            line-height: 1.4 !important;
        }

        .uppy-DragDrop-inner:hover,
        .uppy-is-drag-over .uppy-DragDrop-inner {
            border-color: #3b82f6 !important;
            background: #eff6ff !important;
        }

        /* Simple StatusBar styling */
        .uppy-StatusBar {
            background: #f3f4f6 !important;
            border-radius: 0.5rem !important;
            border: 1px solid #e5e7eb !important;
            font-family: inherit !important;
            margin-top: 1rem !important;
        }

        .uppy-StatusBar-content {
            font-family: inherit !important;
        }

        .uppy-StatusBar-statusPrimary,
        .uppy-StatusBar-statusSecondary {
            font-family: inherit !important;
            font-size: 0.875rem !important;
        }

        .uppy-StatusBar-statusPrimary {
            color: #374151 !important;
            font-weight: 500 !important;
        }

        .uppy-StatusBar-statusSecondary {
            color: #6b7280 !important;
        }

        /* Simple ProgressBar styling */
        .uppy-ProgressBar {
            background: #e5e7eb !important;
            border-radius: 0.25rem !important;
            overflow: hidden !important;
            height: 6px !important;
        }

        .uppy-ProgressBar-fill {
            background: #3b82f6 !important;
        }

        /* Simple button styling */
        .uppy-StatusBar-actionBtn--upload,
        .uppy-StatusBar-actionBtn--done,
        .uppy-StatusBar-actionBtn--retry {
            background: #3b82f6 !important;
            color: white !important;
            border: none !important;
            border-radius: 0.375rem !important;
            font-family: inherit !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            padding: 0.5rem 1rem !important;
            transition: background-color 0.2s ease !important;
        }

        .uppy-StatusBar-actionBtn--upload:hover,
        .uppy-StatusBar-actionBtn--done:hover,
        .uppy-StatusBar-actionBtn--retry:hover {
            background: #2563eb !important;
        }

        /* File item styling */
        .uppy-StatusBar-file {
            font-family: inherit !important;
            font-size: 0.875rem !important;
        }

        /* Simple ready state */
        .uppy-ready-for-new {
            border-color: #10b981 !important;
            background: #ecfdf5 !important;
        }

        .uppy-ready-for-new::after {
            content: '✓ Ready for more files';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #10b981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            font-family: inherit;
            pointer-events: none;
            z-index: 10;
        }


        /* Additional styling to ensure consistency */
        .uppy-Dashboard,
        .uppy-Dashboard * {
            font-family: inherit !important;
        }

        /* Ensure all text elements use the correct font */
        .uppy-StatusBar-details,
        .uppy-StatusBar-spinner,
        .uppy-ProgressBar-percentage {
            font-family: inherit !important;
        }
    </style>
</div>