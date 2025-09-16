import Uppy from '@uppy/core';
import AwsS3 from '@uppy/aws-s3';
import StatusBar from '@uppy/status-bar';
import ProgressBar from '@uppy/progress-bar';

// Optional persistence plugins
// import GoldenRetriever from '@uppy/golden-retriever';
// import LocalStorage from '@uppy/store-defaults'; // For Uppy v4, local state persistence is built-in via @uppy/store-localstorage (not currently installed)

const GlobalUploadManager = (() => {
    let instance;

    function create() {
        const state = {
            uppy: null,
            isPaused: false,
            queue: [],
            activeMeta: null,
            livewire: null,
            wasOffline: false,
        };

        function broadcast() {
            const aggregate = computeAggregate(state.queue);
            // Send a cloned array so Alpine reactivity reliably detects changes
            const clonedQueue = state.queue.map(item => ({ ...item }));
            
            // Debug: Check for duplicate IDs
            const ids = clonedQueue.map(item => item.id);
            const uniqueIds = [...new Set(ids)];
            if (ids.length !== uniqueIds.length) {
                console.warn('Duplicate IDs detected in upload queue:', ids);
                // Remove duplicates by keeping the last occurrence of each ID
                const deduplicatedQueue = clonedQueue.filter((item, index, arr) => 
                    arr.findIndex(i => i.id === item.id) === index
                );
                clonedQueue.splice(0, clonedQueue.length, ...deduplicatedQueue);
            }
            
            window.dispatchEvent(new CustomEvent('global-uploader:update', {
                detail: {
                    queue: clonedQueue,
                    isPaused: state.isPaused,
                    aggregateProgress: aggregate,
                }
            }));
        }

        function computeAggregate(items) {
            if (!items.length) return 0;
            const sum = items.reduce((acc, i) => acc + (i.progress || 0), 0);
            return Math.min(100, Math.round(sum / items.length));
        }

        function initUppy() {
            if (state.uppy) return state.uppy;

            state.uppy = new Uppy({
                autoProceed: true,
                allowMultipleUploads: true,
                restrictions: {
                    allowedFileTypes: ['audio/*', 'application/pdf', 'image/*', 'application/zip'],
                },
                onBeforeFileAdded: (currentFile, files) => {
                    // Inject current active meta when adding files
                    currentFile.meta = {
                        ...(currentFile.meta || {}),
                        ...(state.activeMeta || {}),
                    };
                    return true;
                },
            })
                .use(ProgressBar, { target: '#global-uppy-progress', hideAfterFinish: false })
                .use(StatusBar, { target: '#global-uppy-status', hideRetryButton: false, hidePauseResumeButton: false })
                .use(AwsS3, {
                    shouldUseMultipart: true,
                    // createMultipartUpload
                    createMultipartUpload(file) {
                        return fetch('/s3/multipart', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({
                                filename: file.name,
                                type: file.type,
                                size: file.size,
                                metadata: {
                                    modelId: file.meta?.modelId,
                                    modelType: file.meta?.modelType,
                                    context: file.meta?.context,
                                }
                            }),
                            credentials: 'same-origin',
                        })
                            .then(r => {
                                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                                return r.json();
                            })
                            .then(data => ({
                                uploadId: data.uploadId,
                                key: data.key,
                            }));
                    },
                    signPart(file, partData) {
                        const url = `/s3/multipart/${partData.uploadId}/${partData.partNumber}?key=${encodeURIComponent(partData.key)}`;
                        return fetch(url, { method: 'GET', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                            .then(r => {
                                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                                return r.json();
                            })
                            .then(data => ({
                                url: data.url,
                                headers: data.headers || {},
                            }));
                    },
                    completeMultipartUpload(file, { uploadId, parts, key }) {
                        return fetch(`/s3/multipart/${uploadId}/complete`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ parts, key }),
                            credentials: 'same-origin',
                        }).then(r => {
                            if (!r.ok) throw new Error(`HTTP ${r.status}`);
                            return r.json();
                        }).then(data => ({
                            location: data.location,
                        }));
                    },
                    abortMultipartUpload(file, { uploadId, key }) {
                        return fetch(`/s3/multipart/${uploadId}?key=${encodeURIComponent(key)}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                    },
                });

            state.uppy.on('file-added', (file) => {
                // Ensure file has a unique ID
                if (!file.id) {
                    file.id = `uppy-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
                    console.warn('File missing ID, generated:', file.id);
                }
                
                // Check if file already exists in queue
                const existingIndex = state.queue.findIndex(item => item.id === file.id);
                if (existingIndex >= 0) {
                    console.warn('File already in queue, updating:', file.id);
                    state.queue[existingIndex] = { 
                        id: file.id, 
                        name: file.name, 
                        size: file.size, 
                        meta: file.meta, 
                        progress: 0, 
                        status: 'queued' 
                    };
                } else {
                    state.queue.push({ 
                        id: file.id, 
                        name: file.name, 
                        size: file.size, 
                        meta: file.meta, 
                        progress: 0, 
                        status: 'queued' 
                    });
                }
                broadcast();
            });

            state.uppy.on('upload-progress', (file, progress) => {
                const idx = state.queue.findIndex(i => i.id === file.id);
                if (idx >= 0) {
                    const pct = Math.min(100, Math.round((file.progress?.percentage ?? 0)));
                    state.queue[idx].status = (file.progress?.uploadComplete ? 'complete' : 'uploading');
                    state.queue[idx].progress = pct;
                    broadcast();
                }
            });

            // Sync statuses on upload lifecycle events
            const syncStatuses = () => {
                const files = state.uppy.getFiles();
                files.forEach((f) => {
                    const idx = state.queue.findIndex(i => i.id === f.id);
                    if (idx >= 0) {
                        const pct = Math.min(100, Math.round((f.progress?.percentage ?? 0)));
                        state.queue[idx].progress = pct;
                        if (f.progress?.uploadComplete) {
                            state.queue[idx].status = 'complete';
                        } else if (f.progress?.uploadStarted) {
                            state.queue[idx].status = 'uploading';
                        }
                    }
                });
                broadcast();
            };

            state.uppy.on('upload', syncStatuses);
            state.uppy.on('upload-resumed', () => syncStatuses());
            state.uppy.on('file-removed', () => syncStatuses());

            state.uppy.on('upload-success', (file, response) => {
                const idx = state.queue.findIndex(i => i.id === file.id);
                if (idx >= 0) {
                    state.queue[idx].status = 'complete';
                    state.queue[idx].progress = 100;
                    state.queue[idx].response = response;
                    broadcast();
                }
                // Immediately process this file on the server rather than waiting for the whole batch
                if (state.livewire) {
                    const single = [{
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        key: file.response?.body?.Key || file.meta?.key || file.s3Multipart?.key,
                        meta: file.meta || {},
                    }];
                    try {
                        // Invoke via Livewire emit to avoid batching debounce issues
                        if (typeof state.livewire.call === 'function') {
                            state.livewire.call('handleGlobalUploadSuccess', single);
                        } else if (typeof state.livewire.handleGlobalUploadSuccess === 'function') {
                            state.livewire.handleGlobalUploadSuccess(single);
                        }
                    } catch (e) { /* no-op */ }
                }
            });

            state.uppy.on('upload-error', (file, error) => {
                const idx = state.queue.findIndex(i => i.id === file.id);
                if (idx >= 0) {
                    state.queue[idx].status = 'error';
                    state.queue[idx].error = error?.message || 'Upload failed';
                    broadcast();
                }
            });

            state.uppy.on('complete', () => {
                // Batch complete: we already processed per-file successes; just refresh UI
                broadcast();
            });

            return state.uppy;
        }

        function ensureUppy() {
            return state.uppy || initUppy();
        }

        return {
            attachLivewire(livewire) {
                state.livewire = livewire;
                ensureUppy();
                broadcast();
                // set up network handlers once attached
                this.initNetworkHandlers();
            },
            getActiveMeta() {
                return state.activeMeta;
            },
            setActiveTarget(meta) {
                state.activeMeta = { ...meta };
            },
            openFileDialog(meta) {
                state.activeMeta = { ...meta };
                ensureUppy().getPlugin('Dashboard')?.openModal?.();
                // Fallback: programmatically create a hidden input via FileInput if needed
                const input = document.createElement('input');
                input.type = 'file';
                input.multiple = true;
                input.onchange = () => {
                    const files = Array.from(input.files || []);
                    this.addFiles(files, meta);
                };
                input.click();
            },
            addFiles(files, meta) {
                state.activeMeta = { ...meta };
                const uppy = ensureUppy();
                files.forEach((file) => {
                    uppy.addFile({
                        name: file.name,
                        type: file.type,
                        data: file,
                        meta: { ...(meta || {}) },
                    });
                });
            },
            pauseAll() {
                state.isPaused = true;
                ensureUppy().pauseAll();
                broadcast();
            },
            resumeAll() {
                state.isPaused = false;
                const uppy = ensureUppy();
                uppy.resumeAll();
                // If nothing resumes (e.g., all queued), trigger upload to kickstart
                const files = uppy.getFiles();
                const hasUploading = files.some(f => f.progress?.uploadStarted && !f.progress?.uploadComplete);
                const hasQueued = files.some(f => !f.progress?.uploadStarted && !f.progress?.uploadComplete);
                if (!hasUploading && hasQueued) {
                    uppy.upload().catch(() => { });
                }
                broadcast();
            },
            cancelAll() {
                ensureUppy().cancelAll();
                state.queue = state.queue.filter(i => i.status !== 'uploading');
                broadcast();
            },
            togglePause(id) {
                const item = state.queue.find(i => i.id === id);
                if (!item) return;
                if (item.paused) {
                    item.paused = false;
                    const uppy = ensureUppy();
                    uppy.pauseResume(id); // toggle to resume
                    // Kick if needed
                    const files = uppy.getFiles();
                    const hasUploading = files.some(f => f.progress?.uploadStarted && !f.progress?.uploadComplete);
                    const hasQueued = files.some(f => !f.progress?.uploadStarted && !f.progress?.uploadComplete);
                    if (!hasUploading && hasQueued) {
                        uppy.upload().catch(() => { });
                    }
                } else {
                    item.paused = true;
                    ensureUppy().pauseResume(id);
                }
                broadcast();
            },
            retry(id) {
                ensureUppy().retryUpload(id);
            },
            retryFailed() {
                ensureUppy().retryAll();
            },
            clearCompleted() {
                state.queue = state.queue.filter(i => i.status !== 'complete');
                broadcast();
            },

            initNetworkHandlers() {
                const onOnline = () => {
                    if (state.wasOffline) {
                        state.wasOffline = false;
                        this.resumeAll();
                        try { window.dispatchEvent(new CustomEvent('toaster:info', { detail: { message: 'Back online. Resuming uploads.' } })); } catch (e) { }
                    }
                };
                const onOffline = () => {
                    state.wasOffline = true;
                    this.pauseAll();
                    try { window.dispatchEvent(new CustomEvent('toaster:warning', { detail: { message: 'You are offline. Uploads paused.' } })); } catch (e) { }
                };
                window.removeEventListener('online', onOnline);
                window.removeEventListener('offline', onOffline);
                window.addEventListener('online', onOnline);
                window.addEventListener('offline', onOffline);
            },
            
            // Enhanced methods for drag & drop integration
            enableGlobalDragDrop(defaultMeta) {
                if (window.GlobalDragDrop) {
                    window.GlobalDragDrop.enablePageDragDrop(defaultMeta);
                }
            },
            
            disableGlobalDragDrop() {
                if (window.GlobalDragDrop) {
                    window.GlobalDragDrop.disablePageDragDrop();
                }
            },
            
            registerDropZone(element, meta) {
                if (window.GlobalDragDrop) {
                    window.GlobalDragDrop.registerDropZone(element, meta);
                }
            },
            
            unregisterDropZone(element) {
                if (window.GlobalDragDrop) {
                    window.GlobalDragDrop.unregisterDropZone(element);
                }
            },
            
            // Enhanced file validation
            validateFiles(files, meta) {
                const allowedTypes = ['audio/*', 'application/pdf', 'image/*', 'application/zip'];
                const validFiles = [];
                const invalidFiles = [];
                
                files.forEach(file => {
                    const isValid = allowedTypes.some(type => {
                        if (type.endsWith('/*')) {
                            return file.type.startsWith(type.slice(0, -1));
                        }
                        return file.type === type;
                    });
                    
                    if (isValid) {
                        validFiles.push(file);
                    } else {
                        invalidFiles.push(file);
                    }
                });
                
                if (invalidFiles.length > 0) {
                    const fileNames = invalidFiles.map(f => f.name).join(', ');
                    try { 
                        window.dispatchEvent(new CustomEvent('toaster:error', { 
                            detail: { 
                                message: `Invalid file types: ${fileNames}. Only audio, PDF, image, and ZIP files are allowed.` 
                            } 
                        })); 
                    } catch (e) { }
                }
                
                return validFiles;
            },
            
            // Enhanced addFiles with validation
            addValidatedFiles(files, meta) {
                const validFiles = this.validateFiles(files, meta);
                if (validFiles.length > 0) {
                    this.addFiles(validFiles, meta);
                    
                    try { 
                        window.dispatchEvent(new CustomEvent('toaster:success', { 
                            detail: { 
                                message: `${validFiles.length} file${validFiles.length > 1 ? 's' : ''} added to upload queue` 
                            } 
                        })); 
                    } catch (e) { }
                }
                
                return validFiles.length;
            },
            
            // Get upload statistics
            getUploadStats() {
                const totalFiles = state.queue.length;
                const completedFiles = state.queue.filter(item => item.status === 'complete').length;
                const uploadingFiles = state.queue.filter(item => item.status === 'uploading').length;
                const queuedFiles = state.queue.filter(item => item.status === 'queued').length;
                const errorFiles = state.queue.filter(item => item.status === 'error').length;
                
                return {
                    total: totalFiles,
                    completed: completedFiles,
                    uploading: uploadingFiles,
                    queued: queuedFiles,
                    errors: errorFiles,
                    isPaused: state.isPaused
                };
            },
        };
    }

    return {
        getInstance() {
            if (!instance) instance = create();
            return instance;
        }
    };
})();

window.GlobalUploader = GlobalUploadManager.getInstance();



