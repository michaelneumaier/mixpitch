import * as FilePond from 'filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import UploadProgressTracker from './upload-progress-tracker.js';
import AdaptiveChunkManager from './adaptive-chunk-manager.js';
import UploadSessionManager from './upload-session-manager.js';
import UploadPauseResumeManager from './upload-pause-resume-manager.js';

// Register FilePond plugins
FilePond.registerPlugin(
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize,
    FilePondPluginImagePreview
);

// Make FilePond plugins available globally for Livewire components
window.FilePondPluginFileValidateType = FilePondPluginFileValidateType;
window.FilePondPluginFileValidateSize = FilePondPluginFileValidateSize;
window.FilePondPluginImagePreview = FilePondPluginImagePreview;

/**
 * FilePond Configuration Manager
 * Handles dynamic configuration based on upload context and provides fallback mechanisms
 */
export class FilePondConfigManager {
    constructor() {
        this.defaultSettings = this.getDefaultSettings();
        this.contextSettings = new Map();
        this.fallbackEnabled = true;
    }

    /**
     * Get default FilePond settings
     */
    getDefaultSettings() {
        return {
            // Basic configuration
            allowMultiple: false,
            allowDrop: true,
            allowBrowse: true,
            allowPaste: false,
            allowReplace: true,
            allowRevert: true,
            allowRemove: true,

            // File validation defaults
            acceptedFileTypes: ['audio/*', 'application/pdf', 'image/*', 'application/zip'],
            maxFileSize: '500MB',
            maxFiles: 1,

            // Chunked upload defaults
            enableChunking: true,
            chunkSize: 5 * 1024 * 1024, // 5MB
            maxConcurrentUploads: 3,
            maxRetryAttempts: 3,

            // UI configuration
            enableDragDrop: true,
            showProgressIndicator: true,
            showSpeedIndicator: true,
            showTimeRemaining: true,

            // Context-specific settings
            context: 'global',
            modelId: null,

            // Error handling
            enableFallback: true,
            fallbackToSimpleUpload: true,

            // Labels and messages
            labels: {
                idle: 'Drag & Drop your files or <span class="filepond--label-action">Browse</span>',
                processing: 'Uploading',
                complete: 'Upload complete',
                error: 'Error during upload',
                cancelled: 'Upload cancelled',
                tapToCancel: 'tap to cancel',
                tapToRetry: 'tap to retry',
                tapToUndo: 'tap to undo'
            }
        };
    }

    /**
     * Set context-specific configuration
     */
    setContextConfig(context, config) {
        this.contextSettings.set(context, config);
    }

    /**
     * Get configuration for specific context
     */
    getContextConfig(context) {
        const baseConfig = { ...this.defaultSettings };
        const contextConfig = this.contextSettings.get(context) || {};

        return this.mergeConfigs(baseConfig, contextConfig);
    }

    /**
     * Merge configuration objects with proper handling of nested objects
     */
    mergeConfigs(base, override) {
        const merged = { ...base };

        for (const [key, value] of Object.entries(override)) {
            if (key === 'labels' && typeof value === 'object') {
                merged.labels = { ...merged.labels, ...value };
            } else {
                merged[key] = value;
            }
        }

        return merged;
    }

    /**
     * Load configuration from server/API
     */
    async loadServerConfig(context = 'global') {
        try {
            // For now, skip server config loading since we don't have this endpoint
            // Could be implemented later as /api/upload/config if needed
            console.log('Using default configuration for context:', context);
        } catch (error) {
            console.warn('Failed to load server configuration, using defaults:', error);
        }

        return this.getContextConfig(context);
    }

    /**
     * Get CSRF token from meta tag
     */
    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    /**
     * Validate configuration values
     */
    validateConfig(config) {
        const validated = { ...config };

        // Validate chunk size (1MB to 50MB)
        if (validated.chunkSize < 1024 * 1024) {
            console.warn('Chunk size too small, using minimum 1MB');
            validated.chunkSize = 1024 * 1024;
        } else if (validated.chunkSize > 50 * 1024 * 1024) {
            console.warn('Chunk size too large, using maximum 50MB');
            validated.chunkSize = 50 * 1024 * 1024;
        }

        // Validate max concurrent uploads (1-10)
        if (validated.maxConcurrentUploads < 1) {
            validated.maxConcurrentUploads = 1;
        } else if (validated.maxConcurrentUploads > 10) {
            validated.maxConcurrentUploads = 10;
        }

        // Validate retry attempts (1-5)
        if (validated.maxRetryAttempts < 1) {
            validated.maxRetryAttempts = 1;
        } else if (validated.maxRetryAttempts > 5) {
            validated.maxRetryAttempts = 5;
        }

        return validated;
    }
}

/**
 * Enhanced FilePond configuration for chunked uploads
 */
export class EnhancedFilePond {
    constructor(element, config = {}) {
        this.element = element;
        this.config = config;
        this.pond = null;
        this.uploadSessions = new Map();
        this.retryAttempts = new Map();
        this.uploadSpeeds = new Map();
        this.configManager = new FilePondConfigManager();
        this.progressTracker = new UploadProgressTracker();
        this.adaptiveChunkManager = new AdaptiveChunkManager(config.adaptiveChunking || {});
        this.sessionManager = new UploadSessionManager(config.sessionManagement || {});
        this.pauseResumeManager = new UploadPauseResumeManager(config.pauseResume || {});
        this.fallbackMode = false;
        this.networkConditions = {
            isOnline: navigator.onLine,
            connectionType: this.getConnectionType(),
            lastSpeedTest: null
        };

        this.init();
    }

    async init() {
        try {
            // Load server configuration for the specified context
            const context = this.config.context || 'global';
            await this.configManager.loadServerConfig(context);

            // Get and validate configuration
            const contextConfig = this.configManager.getContextConfig(context);
            const validatedConfig = this.configManager.validateConfig({ ...contextConfig, ...this.config });

            // Create FilePond configuration
            const filePondConfig = this.createFilePondConfig(validatedConfig);

            // Create FilePond instance with error handling
            this.pond = await this.createFilePondInstance(filePondConfig);

            // Set up event listeners and network monitoring
            this.setupEventListeners();
            this.setupNetworkMonitoring();

            // Initialize session management
            this.initializeSessionManagement();

            return this.pond;

        } catch (error) {
            console.error('Failed to initialize EnhancedFilePond:', error);

            // Fallback to basic configuration
            if (this.config.enableFallback !== false) {
                return this.initializeFallback();
            }

            throw error;
        }
    }

    /**
     * Initialize fallback mode with basic FilePond configuration
     */
    async initializeFallback() {
        console.warn('Initializing FilePond in fallback mode');
        this.fallbackMode = true;

        const basicConfig = {
            allowMultiple: this.config.allowMultiple || false,
            allowDrop: this.config.enableDragDrop !== false,
            allowBrowse: true,
            acceptedFileTypes: this.config.acceptedFileTypes || ['audio/*', 'application/pdf', 'image/*'],
            maxFileSize: this.config.maxFileSize || '500MB',
            maxFiles: this.config.maxFiles || 1,

            // Disable chunking in fallback mode
            chunkUploads: false,

            server: {
                url: '/api/upload/simple',
                headers: {
                    'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
                }
            },

            labelIdle: 'Drag & Drop your files or <span class="filepond--label-action">Browse</span> (Basic Mode)',

            onaddfile: (error, file) => this.handleFileAdd(error, file),
            onprocessfile: (error, file) => this.handleFileProcessed(error, file),
            onprocessfileprogress: (file, progress) => this.handleFileProgress(file, progress),
            onprocessfileerror: (error, file) => this.handleFileError(error, file),
            onremovefile: (error, file) => this.handleFileRemove(error, file),
        };

        this.pond = FilePond.create(this.element, basicConfig);
        this.setupEventListeners();

        return this.pond;
    }

    /**
     * Create FilePond instance with error handling
     */
    async createFilePondInstance(config) {
        try {
            return FilePond.create(this.element, config);
        } catch (error) {
            console.error('Failed to create FilePond instance:', error);

            // Check if FilePond is available
            if (typeof FilePond === 'undefined') {
                throw new Error('FilePond library is not available');
            }

            // Check if element exists
            if (!this.element) {
                throw new Error('Target element not found');
            }

            throw error;
        }
    }

    /**
     * Create FilePond configuration object
     */
    createFilePondConfig(config) {
        return {
            // Basic configuration
            allowMultiple: config.allowMultiple || false,
            allowDrop: config.enableDragDrop !== false,
            allowBrowse: true,
            allowPaste: false,
            allowReplace: !config.allowMultiple,
            allowRevert: true,
            allowRemove: true,

            // File validation
            acceptedFileTypes: config.acceptedFileTypes || ['audio/*', 'application/pdf', 'image/*', 'application/zip'],
            maxFileSize: config.maxFileSize || '500MB',
            maxFiles: config.maxFiles || (config.allowMultiple ? 10 : 1),

            // Chunked upload configuration
            chunkUploads: config.enableChunking !== false && !this.fallbackMode,
            chunkSize: config.chunkSize || (5 * 1024 * 1024), // 5MB default
            chunkRetryDelays: this.getRetryDelays(config.maxRetryAttempts || 3),

            // Server configuration - Fixed URLs to match Laravel routes
            server: this.getServerConfig(config),

            // UI Labels with context awareness
            labelIdle: config.labels?.idle || this.getContextualLabel(config.context),
            labelFileProcessing: config.labels?.processing || 'Uploading',
            labelFileProcessingComplete: config.labels?.complete || 'Upload complete',
            labelFileProcessingAborted: config.labels?.cancelled || 'Upload cancelled',
            labelFileProcessingError: config.labels?.error || 'Error during upload',
            labelTapToCancel: config.labels?.tapToCancel || 'tap to cancel',
            labelTapToRetry: config.labels?.tapToRetry || 'tap to retry',
            labelTapToUndo: config.labels?.tapToUndo || 'tap to undo',

            // Event handlers
            onaddfile: (error, file) => this.handleFileAdd(error, file),
            onprocessfile: (error, file) => this.handleFileProcessed(error, file),
            onprocessfileprogress: (file, progress) => this.handleFileProgress(file, progress),
            onprocessfileerror: (error, file) => this.handleFileError(error, file),
            onremovefile: (error, file) => this.handleFileRemove(error, file),
            onactivatefile: (file) => this.handleFileActivate(file),
            onpreparefile: (file, output) => this.handleFilePrepare(file, output),
        };
    }

    /**
     * Get contextual label based on upload context
     */
    getContextualLabel(context) {
        const labels = {
            'projects': 'Drag & Drop your project files or <span class="filepond--label-action">Browse</span>',
            'pitches': 'Drag & Drop your pitch files or <span class="filepond--label-action">Browse</span>',
            'client_portal': 'Drag & Drop your files or <span class="filepond--label-action">Browse</span>',
            'global': 'Drag & Drop your files or <span class="filepond--label-action">Browse</span>'
        };

        return labels[context] || labels['global'];
    }

    /**
     * Get retry delays with exponential backoff
     */
    getRetryDelays(maxRetries) {
        const delays = [];
        for (let i = 0; i < maxRetries; i++) {
            delays.push(Math.min(500 * Math.pow(2, i), 10000));
        }
        return delays;
    }

    /**
     * Get server configuration based on chunking enabled/disabled
     */
    getServerConfig(config) {
        if (!config.enableChunking || this.fallbackMode) {
            // Simple upload configuration - use Laravel API endpoints
            return {
                url: '/api/upload/simple',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
                },
                process: (fieldName, file, metadata, load, error, progress, abort) => {
                    return this.processSimpleUpload(fieldName, file, metadata, load, error, progress, abort);
                }
            };
        }

        // Chunked upload configuration - use custom processing for Laravel API
        return {
            // FilePond will handle the server communication through our custom process function
            process: (fieldName, file, metadata, load, error, progress, abort) => {
                return this.processChunkedUpload(fieldName, file, metadata, load, error, progress, abort);
            },
            revert: (uniqueFileId, load, error) => {
                return this.revertUpload(uniqueFileId, load, error);
            }
        };
    }
    /**
         * Process simple upload (fallback)
         */
    async processSimpleUpload(fieldName, file, metadata, load, error, progress, abort) {
        const formData = new FormData();
        formData.append(fieldName, file);
        formData.append('model_type', this.config.context || 'global');
        formData.append('model_id', this.config.modelId || '');

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                progress(true, e.loaded, e.total);
            }
        });

        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    load(response.data?.file_id || xhr.responseText);
                } catch (e) {
                    load(xhr.responseText);
                }
            } else {
                error('Upload failed');
            }
        });

        xhr.addEventListener('error', () => {
            error('Upload failed');
        });

        xhr.open('POST', '/api/upload/simple');
        xhr.setRequestHeader('X-CSRF-TOKEN', this.configManager.getCSRFToken());
        xhr.send(formData);

        return {
            abort: () => {
                xhr.abort();
            }
        };
    }

    /**
     * Process chunked upload with retry logic and resumption
     */
    async processChunkedUpload(fieldName, file, metadata, load, error, progress, abort) {
        try {
            const fileId = this.generateFileId();

            // Get optimal chunk size using adaptive chunk manager
            const uploadContext = {
                concurrentUploads: this.config.maxConcurrentUploads || 3,
                connectionType: this.networkConditions.connectionType,
                isOnline: this.networkConditions.isOnline
            };

            let chunkSize = this.adaptiveChunkManager.getOptimalChunkSize(fileId, file.size, uploadContext);
            const totalChunks = Math.ceil(file.size / chunkSize);

            // Create upload session
            const uploadSession = await this.createUploadSession(file, totalChunks);
            this.uploadSessions.set(fileId, uploadSession);

            // Create session in session manager
            const sessionData = {
                id: uploadSession.id,
                fileName: file.name,
                fileSize: file.size,
                totalChunks: totalChunks,
                chunkSize: chunkSize,
                status: 'uploading',
                modelType: this.config.context || 'global',
                modelId: this.config.modelId || '',
                metadata: {
                    originalName: file.name,
                    mimeType: file.type,
                    lastModified: file.lastModified
                }
            };

            const managedSession = this.sessionManager.createSession(fileId, sessionData);

            // Track upload start time for speed calculation
            this.uploadSpeeds.set(fileId, {
                startTime: Date.now(),
                uploadedBytes: 0,
                currentSpeed: 0,
                averageSpeed: 0,
                totalBytes: file.size
            });

            // Upload chunks with retry logic
            let uploadedChunks = 0;
            const abortController = new AbortController();

            // Set up abort handler
            const abortHandler = () => {
                abortController.abort();
                this.cancelUpload(uploadSession.id);
            };

            // Upload chunks with concurrency control
            const maxConcurrentChunks = this.config.maxConcurrentUploads || 3;
            const chunkPromises = [];

            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                const chunkPromise = this.uploadChunkWithRetry(
                    file,
                    uploadSession.id,
                    chunkIndex,
                    chunkSize,
                    abortController.signal
                ).then((uploadTime) => {
                    uploadedChunks++;
                    const progressValue = uploadedChunks / totalChunks;
                    progress(true, progressValue, 1);

                    // Update session manager with chunk progress
                    this.sessionManager.updateChunkProgress(managedSession.id, chunkIndex, {
                        status: 'uploaded',
                        size: chunkSize,
                        uploadTime: uploadTime
                    });

                    // Update upload speed and adapt chunk size
                    this.updateUploadSpeed(fileId, uploadedChunks * chunkSize);

                    // Update adaptive chunk manager with performance data
                    const newChunkSize = this.adaptiveChunkManager.updateChunkSize(
                        fileId,
                        chunkIndex,
                        chunkSize,
                        uploadTime,
                        true
                    );

                    // Update chunk size for remaining chunks if it changed significantly
                    if (Math.abs(newChunkSize - chunkSize) > chunkSize * 0.2) {
                        chunkSize = newChunkSize;
                        console.log(`Adapted chunk size to ${this.formatBytes(chunkSize)} for better performance`);
                    }
                }).catch((err) => {
                    if (!abortController.signal.aborted) {
                        // Update adaptive chunk manager with failure data
                        this.adaptiveChunkManager.updateChunkSize(
                            fileId,
                            chunkIndex,
                            chunkSize,
                            0,
                            false
                        );
                        console.error(`Chunk ${chunkIndex} upload failed:`, err);
                        throw err;
                    }
                });

                chunkPromises.push(chunkPromise);

                // Control concurrency
                if (chunkPromises.length >= maxConcurrentChunks) {
                    await Promise.race(chunkPromises);
                    // Remove completed promises
                    for (let i = chunkPromises.length - 1; i >= 0; i--) {
                        if (await this.isPromiseResolved(chunkPromises[i])) {
                            chunkPromises.splice(i, 1);
                        }
                    }
                }
            }

            // Wait for all chunks to complete
            await Promise.all(chunkPromises);

            // Assemble file
            const result = await this.assembleFile(uploadSession.id);

            // Complete session in session manager
            this.sessionManager.completeSession(managedSession.id, {
                fileId: result.data.file_id,
                filePath: result.data.file_path
            });

            // Clean up tracking
            this.uploadSessions.delete(fileId);
            this.uploadSpeeds.delete(fileId);
            this.retryAttempts.delete(fileId);

            // Call success callback
            load(result.data.file_id);

        } catch (err) {
            console.error('Chunked upload failed:', err);

            // Mark session as failed in session manager
            if (managedSession) {
                this.sessionManager.failSession(managedSession.id, err);
            }

            // Check if we should fallback to simple upload
            if (this.config.fallbackToSimpleUpload && !this.fallbackMode) {
                console.warn('Falling back to simple upload due to chunked upload failure');
                return this.processSimpleUpload(fieldName, file, metadata, load, error, progress, abort);
            }

            error(err.message || 'Upload failed');
        }

        return { abort: abortHandler };
    }

    /**
     * Revert upload (remove uploaded file)
     */
    async revertUpload(uniqueFileId, load, error) {
        try {
            // Implementation depends on how file IDs are tracked
            // For now, just call the load callback
            load();
        } catch (err) {
            error(err.message);
        }
    }

    /**
     * Get connection type for adaptive behavior
     */
    getConnectionType() {
        if ('connection' in navigator) {
            return navigator.connection.effectiveType || 'unknown';
        }
        return 'unknown';
    }

    /**
     * Setup network monitoring for adaptive behavior
     */
    setupNetworkMonitoring() {
        // Monitor online/offline status
        window.addEventListener('online', () => {
            this.networkConditions.isOnline = true;
            this.handleNetworkReconnection();
        });

        window.addEventListener('offline', () => {
            this.networkConditions.isOnline = false;
            this.handleNetworkDisconnection();
        });

        // Monitor connection changes
        if ('connection' in navigator) {
            navigator.connection.addEventListener('change', () => {
                this.networkConditions.connectionType = this.getConnectionType();
                this.adaptToNetworkConditions();
            });
        }
    }

    /**
     * Handle network reconnection
     */
    handleNetworkReconnection() {
        console.log('Network reconnected, resuming uploads');
        this.dispatchEvent('networkReconnected', {
            connectionType: this.networkConditions.connectionType
        });

        // Resume any paused uploads
        this.resumePausedUploads();
    }

    /**
     * Handle network disconnection
     */
    handleNetworkDisconnection() {
        console.log('Network disconnected, pausing uploads');
        this.dispatchEvent('networkDisconnected', {});

        // Pause ongoing uploads
        this.pauseOngoingUploads();
    }

    /**
     * Adapt configuration to network conditions
     */
    adaptToNetworkConditions() {
        const connectionType = this.networkConditions.connectionType;
        const fallbackMechanisms = this.adaptiveChunkManager.getFallbackMechanisms();

        // Apply adaptive chunk manager recommendations
        this.config.chunkSize = fallbackMechanisms.chunkSize;
        this.config.maxConcurrentUploads = fallbackMechanisms.maxConcurrentUploads;

        // Apply additional network-specific optimizations
        if (connectionType === 'slow-2g' || connectionType === '2g') {
            // Enable aggressive fallback mechanisms for very slow connections
            this.config.enableCompression = true;
            this.config.pauseOnSlowConnection = true;
            this.config.adaptiveQuality = true;

            // Use minimum chunk size for reliability
            this.config.chunkSize = Math.max(this.adaptiveChunkManager.config.minChunkSize, 512 * 1024);
            this.config.maxConcurrentUploads = 1;

            console.log('Applied slow connection optimizations');
        } else if (connectionType === '3g') {
            // Moderate optimizations for 3G
            this.config.enableCompression = fallbackMechanisms.enableCompression;
            this.config.chunkSize = Math.min(this.config.chunkSize || 5242880, 2097152); // Max 2MB
            this.config.maxConcurrentUploads = 2;

            console.log('Applied 3G connection optimizations');
        } else if (connectionType === '4g' || connectionType === 'unknown') {
            // Standard optimizations for good connections
            this.config.enableCompression = false;
            this.config.pauseOnSlowConnection = false;
            this.config.adaptiveQuality = false;

            console.log('Applied standard connection optimizations');
        }

        // Apply save-data mode optimizations
        if (this.networkConditions.saveData || fallbackMechanisms.enableCompression) {
            this.config.enableCompression = true;
            this.config.chunkSize = Math.min(this.config.chunkSize, 2 * 1024 * 1024); // Max 2MB in save-data mode

            console.log('Applied save-data mode optimizations');
        }

        this.dispatchEvent('networkAdapted', {
            connectionType,
            chunkSize: this.config.chunkSize,
            maxConcurrentUploads: this.config.maxConcurrentUploads,
            fallbackMechanisms: fallbackMechanisms,
            optimizations: {
                enableCompression: this.config.enableCompression,
                pauseOnSlowConnection: this.config.pauseOnSlowConnection,
                adaptiveQuality: this.config.adaptiveQuality
            }
        });
    }

    /**
     * Resume paused uploads
     */
    resumePausedUploads() {
        console.log('Resuming paused uploads...');

        // Check for any upload sessions that were interrupted
        this.uploadSessions.forEach(async (session, fileId) => {
            if (session.status === 'paused' || session.status === 'uploading') {
                try {
                    // Check upload status on server
                    const statusResponse = await fetch(`/api/upload/status/${session.id}`, {
                        headers: {
                            'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
                        }
                    });

                    if (statusResponse.ok) {
                        const statusData = await statusResponse.json();

                        // Resume from where we left off
                        if (statusData.data.uploaded_chunks < statusData.data.total_chunks) {
                            console.log(`Resuming upload for session ${session.id} from chunk ${statusData.data.uploaded_chunks}`);
                            // The actual resumption logic would be implemented here
                        }
                    }
                } catch (error) {
                    console.error(`Failed to resume upload session ${session.id}:`, error);
                }
            }
        });

        this.dispatchEvent('uploadsResumed', {
            sessionCount: this.uploadSessions.size
        });
    }

    /**
     * Pause ongoing uploads
     */
    pauseOngoingUploads() {
        console.log('Pausing ongoing uploads...');

        // Mark all active sessions as paused
        this.uploadSessions.forEach((session, fileId) => {
            if (session.status === 'uploading') {
                session.status = 'paused';
                session.pausedAt = Date.now();
            }
        });

        this.dispatchEvent('uploadsPaused', {
            sessionCount: this.uploadSessions.size
        });
    }
    /**
      * Event handlers
      */
    handleFileAdd(error, file) {
        if (error) {
            console.error('Error adding file:', error);
            return;
        }

        // Initialize progress tracking for the file
        const fileId = file.id;
        this.progressTracker.initializeFileProgress(fileId, file);

        // Track file upload for speed calculation
        this.trackFileUpload(file);

        // Dispatch custom event with progress information
        this.dispatchEvent('fileAdded', {
            file,
            progressInfo: this.progressTracker.getFormattedProgress(fileId)
        });
    }

    handleFileProcessed(error, file) {
        if (error) {
            this.handleFileError(error, file);
            return;
        }

        // Complete file upload tracking with progress tracker
        const fileId = file.id;
        this.progressTracker.completeFile(fileId);
        this.completeFileUpload(file);

        // Dispatch custom event with detailed progress information
        this.dispatchEvent('fileProcessed', {
            file,
            progressInfo: this.progressTracker.getFormattedProgress(fileId),
            batchProgress: this.progressTracker.getFormattedBatchProgress()
        });
    }

    handleFileProgress(file, progress) {
        const fileId = file.id;
        const bytesUploaded = file.fileSize * progress;

        // Update progress tracker with detailed progress information
        this.progressTracker.updateFileProgress(fileId, bytesUploaded, file.fileSize);

        // Update legacy speed tracking for backward compatibility
        const speedInfo = this.uploadSpeeds.get(fileId);
        if (speedInfo) {
            const now = Date.now();
            const elapsed = (now - speedInfo.startTime) / 1000;

            if (elapsed > 0) {
                const speed = bytesUploaded / elapsed;
                speedInfo.currentSpeed = speed;
                speedInfo.uploadedBytes = bytesUploaded;
                speedInfo.averageSpeed = (speedInfo.averageSpeed + speed) / 2;
            }
        }

        // Get formatted progress information for the event
        const progressInfo = this.progressTracker.getFormattedProgress(fileId);
        const batchProgress = this.progressTracker.getFormattedBatchProgress();

        // Dispatch enhanced custom event with detailed progress information
        this.dispatchEvent('fileProgress', {
            file,
            progress,
            progressInfo,
            batchProgress,
            uploadStats: {
                speed: progressInfo?.speed,
                timeRemaining: progressInfo?.timeRemaining,
                uploadedSize: progressInfo?.uploadedSize,
                totalSize: progressInfo?.totalSize
            }
        });
    }

    handleFileError(error, file) {
        console.error('File upload error:', error, file);

        const fileId = file.id;

        // Add error to progress tracker with user-friendly error handling
        const isRetryable = this.isErrorRetryable(error);
        this.progressTracker.addFileError(fileId, error, isRetryable);

        // Get retry options and formatted progress info
        const retryOptions = this.progressTracker.getRetryOptions(fileId);
        const progressInfo = this.progressTracker.getFormattedProgress(fileId);

        // Dispatch enhanced custom event with detailed error information
        this.dispatchEvent('fileError', {
            error,
            file,
            progressInfo,
            retryOptions,
            userFriendlyMessage: this.progressTracker.getUserFriendlyErrorMessage(error.message || error),
            suggestedActions: this.progressTracker.getSuggestedActions(error.message || error)
        });
    }

    handleFileRemove(error, file) {
        if (error) {
            console.error('Error removing file:', error);
            return;
        }

        // Clean up tracking
        this.cleanupFileTracking(file);

        // Dispatch custom event
        this.dispatchEvent('fileRemoved', { file });
    }

    handleFileActivate(file) {
        // Dispatch custom event for file activation
        this.dispatchEvent('fileActivated', { file });
    }

    handleFilePrepare(file, output) {
        // Dispatch custom event for file preparation
        this.dispatchEvent('filePrepared', { file, output });
    }

    /**
     * Setup additional event listeners
     */
    setupEventListeners() {
        if (!this.pond) return;

        // Listen for FilePond events and enhance them
        this.pond.on('addfile', (error, file) => {
            if (!error) {
                this.trackFileUpload(file);
            }
        });

        this.pond.on('processfile', (error, file) => {
            if (!error) {
                this.completeFileUpload(file);
            }
        });

        this.pond.on('removefile', (error, file) => {
            if (!error) {
                this.cleanupFileTracking(file);
            }
        });
    }

    /**
     * Track file upload for progress and speed calculation
     */
    trackFileUpload(file) {
        const fileId = file.id;
        this.uploadSpeeds.set(fileId, {
            startTime: Date.now(),
            uploadedBytes: 0,
            currentSpeed: 0,
            averageSpeed: 0,
            totalBytes: file.fileSize
        });
    }

    /**
     * Complete file upload tracking
     */
    completeFileUpload(file) {
        const fileId = file.id;
        const speedInfo = this.uploadSpeeds.get(fileId);

        if (speedInfo) {
            const totalTime = (Date.now() - speedInfo.startTime) / 1000;
            const averageSpeed = file.fileSize / totalTime;

            this.dispatchEvent('uploadCompleted', {
                file,
                totalTime,
                averageSpeed,
                fileSize: file.fileSize
            });
        }
    }

    /**
     * Clean up file tracking
     */
    cleanupFileTracking(file) {
        const fileId = file.id;
        this.uploadSessions.delete(fileId);
        this.uploadSpeeds.delete(fileId);
        this.retryAttempts.delete(fileId);
        this.progressTracker.removeFile(fileId);
    }

    /**
     * Generate unique file ID
     */
    generateFileId() {
        return 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Check if an error is retryable
     */
    isErrorRetryable(error) {
        const errorMessage = (error.message || error).toLowerCase();

        // Network-related errors are usually retryable
        if (errorMessage.includes('network') ||
            errorMessage.includes('timeout') ||
            errorMessage.includes('connection') ||
            errorMessage.includes('fetch')) {
            return true;
        }

        // Server errors (5xx) are usually retryable
        if (errorMessage.includes('server error') ||
            errorMessage.includes('internal server error') ||
            errorMessage.includes('service unavailable')) {
            return true;
        }

        // Chunk-specific errors are retryable
        if (errorMessage.includes('chunk') &&
            !errorMessage.includes('invalid')) {
            return true;
        }

        // Client errors (4xx) are usually not retryable
        if (errorMessage.includes('unauthorized') ||
            errorMessage.includes('forbidden') ||
            errorMessage.includes('not found') ||
            errorMessage.includes('bad request') ||
            errorMessage.includes('file too large') ||
            errorMessage.includes('invalid file type') ||
            errorMessage.includes('storage limit')) {
            return false;
        }

        // Default to retryable for unknown errors
        return true;
    }

    dispatchEvent(eventName, detail) {
        const event = new CustomEvent(`filepond:${eventName}`, { detail });
        this.element.dispatchEvent(event);
    }    /**

     * Create upload session
     */
    async createUploadSession(file, totalChunks) {
        const response = await fetch('/api/upload/session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
            },
            body: JSON.stringify({
                original_filename: file.name,
                total_size: file.size,
                total_chunks: totalChunks,
                chunk_size: this.config.chunkSize || (5 * 1024 * 1024),
                model_type: this.config.context || 'global',
                model_id: this.config.modelId || null,
            })
        });

        if (!response.ok) {
            throw new Error('Failed to create upload session');
        }

        const result = await response.json();
        return result.data;
    }

    /**
     * Upload a single chunk with retry logic
     */
    async uploadChunkWithRetry(file, uploadSessionId, chunkIndex, chunkSize, signal) {
        const maxRetries = this.config.maxRetryAttempts || 3;
        let retryCount = 0;

        // Set total chunks in progress tracker
        const fileId = file.id;
        const totalChunks = Math.ceil(file.size / chunkSize);
        this.progressTracker.setTotalChunks(fileId, totalChunks);

        while (retryCount <= maxRetries) {
            try {
                await this.uploadChunk(file, uploadSessionId, chunkIndex, chunkSize, signal);

                // Update chunk progress
                this.progressTracker.updateChunkProgress(fileId, chunkIndex, chunkSize, 'uploaded');
                return; // Success
            } catch (err) {
                if (signal.aborted) {
                    throw new Error('Upload aborted');
                }

                retryCount++;

                // Update chunk progress for failed chunk
                this.progressTracker.updateChunkProgress(fileId, chunkIndex, chunkSize, 'failed');

                if (retryCount > maxRetries) {
                    throw err;
                }

                // Exponential backoff with network condition awareness
                const baseDelay = Math.min(1000 * Math.pow(2, retryCount - 1), 10000);
                const networkMultiplier = this.getNetworkDelayMultiplier();
                const delay = baseDelay * networkMultiplier;

                console.warn(`Chunk ${chunkIndex} upload failed, retrying in ${delay}ms (attempt ${retryCount}/${maxRetries})`);
                await this.sleep(delay);
            }
        }
    }

    /**
     * Upload a single chunk
     */
    async uploadChunk(file, uploadSessionId, chunkIndex, chunkSize, signal) {
        const start = chunkIndex * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const chunk = file.slice(start, end);

        // Calculate chunk hash for integrity verification
        const chunkHash = await this.calculateHash(chunk);

        const formData = new FormData();
        formData.append('upload_session_id', uploadSessionId);
        formData.append('chunk_index', chunkIndex.toString());
        formData.append('chunk', chunk);
        formData.append('chunk_hash', chunkHash);

        const response = await fetch('/api/upload/chunk', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
            },
            body: formData,
            signal: signal
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Chunk upload failed');
        }

        return await response.json();
    }

    /**
     * Assemble uploaded chunks into final file
     */
    async assembleFile(uploadSessionId) {
        const response = await fetch('/api/upload/assemble', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
            },
            body: JSON.stringify({
                upload_session_id: uploadSessionId
            })
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'File assembly failed');
        }

        return await response.json();
    }

    /**
     * Cancel upload
     */
    async cancelUpload(uploadSessionId) {
        try {
            await fetch('/api/upload/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
                },
                body: JSON.stringify({
                    upload_session_id: uploadSessionId,
                    reason: 'User cancelled'
                })
            });
        } catch (err) {
            console.error('Failed to cancel upload:', err);
        }
    }

    /**
     * Calculate hash for chunk integrity verification
     */
    async calculateHash(blob) {
        try {
            const buffer = await blob.arrayBuffer();
            const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        } catch (error) {
            console.warn('Failed to calculate hash, using fallback:', error);
            // Fallback to simple checksum
            return `fallback_${blob.size}_${Date.now()}`;
        }
    }

    /**
     * Get network delay multiplier based on connection conditions
     */
    getNetworkDelayMultiplier() {
        const connectionType = this.networkConditions.connectionType;

        switch (connectionType) {
            case 'slow-2g':
                return 4;
            case '2g':
                return 3;
            case '3g':
                return 2;
            case '4g':
            case '5g':
                return 1;
            default:
                return 1.5; // Unknown connection, be conservative
        }
    }

    /**
     * Check if a promise is resolved (completed or rejected)
     */
    async isPromiseResolved(promise) {
        try {
            await Promise.race([promise, Promise.resolve()]);
            return true;
        } catch {
            return true; // Also consider rejected promises as "resolved"
        }
    }

    /**
     * Update upload speed tracking
     */
    updateUploadSpeed(fileId, uploadedBytes) {
        const speedInfo = this.uploadSpeeds.get(fileId);
        if (speedInfo) {
            const now = Date.now();
            const elapsed = (now - speedInfo.startTime) / 1000;
            if (elapsed > 0) {
                speedInfo.currentSpeed = uploadedBytes / elapsed;
                speedInfo.uploadedBytes = uploadedBytes;

                // Calculate average speed over time with smoothing
                if (speedInfo.averageSpeed === 0) {
                    speedInfo.averageSpeed = speedInfo.currentSpeed;
                } else {
                    speedInfo.averageSpeed = (speedInfo.averageSpeed * 0.7) + (speedInfo.currentSpeed * 0.3);
                }
            }
        }
    }

    /**
     * Sleep utility function
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Public API methods
     */
    getFiles() {
        return this.pond ? this.pond.getFiles() : [];
    }

    addFile(file) {
        if (this.pond) {
            return this.pond.addFile(file);
        }
    }

    removeFile(file) {
        if (this.pond) {
            this.pond.removeFile(file);
        }
    }

    removeFiles() {
        if (this.pond) {
            this.pond.removeFiles();
        }
    }

    processFiles() {
        if (this.pond) {
            this.pond.processFiles();
        }
    }

    /**
     * Get upload statistics with enhanced progress information
     */
    getUploadStats(fileId) {
        // Get enhanced progress information from progress tracker
        const progressInfo = this.progressTracker.getFormattedProgress(fileId);
        if (progressInfo) {
            return progressInfo;
        }

        // Fallback to legacy speed tracking
        const speedInfo = this.uploadSpeeds.get(fileId);
        if (!speedInfo) return null;

        const elapsed = (Date.now() - speedInfo.startTime) / 1000;
        return {
            uploadedBytes: speedInfo.uploadedBytes,
            currentSpeed: speedInfo.currentSpeed,
            averageSpeed: speedInfo.averageSpeed,
            elapsedTime: elapsed,
            estimatedTimeRemaining: speedInfo.currentSpeed > 0 ?
                (speedInfo.totalBytes - speedInfo.uploadedBytes) / speedInfo.currentSpeed : null
        };
    }

    /**
     * Get all upload progress information
     */
    getAllUploadProgress() {
        return this.progressTracker.getAllProgress();
    }

    /**
     * Get batch progress information
     */
    getBatchProgress() {
        return this.progressTracker.getFormattedBatchProgress();
    }

    /**
     * Get network conditions
     */
    getNetworkConditions() {
        return { ...this.networkConditions };
    }

    /**
     * Check if in fallback mode
     */
    isFallbackMode() {
        return this.fallbackMode;
    }

    /**
     * Start progress tracking updates
     */
    startProgressTracking(interval = 1000) {
        this.progressTracker.startProgressUpdates(interval);
    }

    /**
     * Stop progress tracking updates
     */
    stopProgressTracking() {
        this.progressTracker.stopProgressUpdates();
    }

    /**
     * Destroy the FilePond instance and clean up
     */
    destroy() {
        if (this.pond) {
            this.pond.destroy();
        }

        // Clean up tracking
        this.uploadSessions.clear();
        this.uploadSpeeds.clear();
        this.retryAttempts.clear();

        // Clean up progress tracker
        this.progressTracker.clearAll();
        this.progressTracker.stopProgressUpdates();

        // Remove event listeners
        window.removeEventListener('online', this.handleNetworkReconnection);
        window.removeEventListener('offline', this.handleNetworkDisconnection);

        if ('connection' in navigator) {
            navigator.connection.removeEventListener('change', this.adaptToNetworkConditions);
        }
    }

    /**
       * Generate unique file ID
       */
    generateFileId() {
        return 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Create upload session
     */
    async createUploadSession(file, totalChunks) {
        const response = await fetch('/api/upload/session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
            },
            body: JSON.stringify({
                original_filename: file.name,
                total_size: file.size,
                total_chunks: totalChunks,
                chunk_size: this.config.chunkSize || (5 * 1024 * 1024),
                model_type: this.config.context || 'global',
                model_id: this.config.modelId || null,
            })
        });

        if (!response.ok) {
            throw new Error('Failed to create upload session');
        }

        const result = await response.json();
        return {
            id: result.data.id,
            status: 'uploading',
            totalChunks: totalChunks,
            uploadedChunks: 0
        };
    }

    /**
     * Upload chunk with retry logic
     */
    async uploadChunkWithRetry(file, sessionId, chunkIndex, chunkSize, signal) {
        const startTime = Date.now();
        const start = chunkIndex * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const chunk = file.slice(start, end);

        let retryCount = 0;
        const maxRetries = this.config.maxRetryAttempts || 3;

        while (retryCount <= maxRetries) {
            if (signal.aborted) {
                throw new Error('Upload aborted');
            }

            try {
                const formData = new FormData();
                formData.append('upload_session_id', sessionId);
                formData.append('chunk_index', chunkIndex);
                formData.append('chunk', chunk);
                formData.append('chunk_hash', await this.calculateChunkHash(chunk));

                const response = await fetch('/api/upload/chunk', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
                    },
                    body: formData,
                    signal: signal
                });

                if (response.ok) {
                    const uploadTime = Date.now() - startTime;
                    return uploadTime;
                }

                throw new Error(`Chunk upload failed: ${response.status}`);

            } catch (error) {
                retryCount++;

                if (retryCount > maxRetries || signal.aborted) {
                    throw error;
                }

                // Exponential backoff
                const delay = Math.min(500 * Math.pow(2, retryCount - 1), 10000);
                await this.sleep(delay);
            }
        }
    }

    /**
     * Calculate chunk hash for integrity verification
     */
    async calculateChunkHash(chunk) {
        const buffer = await chunk.arrayBuffer();
        const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }

    /**
     * Sleep utility for retry delays
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Assemble file from chunks
     */
    async assembleFile(sessionId) {
        const response = await fetch('/api/upload/assemble', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                upload_session_id: sessionId
            })
        });

        if (!response.ok) {
            throw new Error('File assembly failed');
        }

        return await response.json();
    }

    /**
     * Cancel upload session
     */
    async cancelUpload(sessionId) {
        try {
            await fetch('/api/upload/cancel', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    upload_session_id: sessionId,
                    reason: 'User cancelled'
                })
            });
        } catch (error) {
            console.error('Failed to cancel upload:', error);
        }
    }

    /**
     * Check if promise is resolved
     */
    async isPromiseResolved(promise) {
        try {
            await Promise.race([
                promise,
                new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 0))
            ]);
            return true;
        } catch {
            return false;
        }
    }

    /**
     * Update upload speed tracking
     */
    updateUploadSpeed(fileId, uploadedBytes) {
        const speedInfo = this.uploadSpeeds.get(fileId);
        if (speedInfo) {
            const now = Date.now();
            const elapsed = (now - speedInfo.startTime) / 1000;

            if (elapsed > 0) {
                speedInfo.uploadedBytes = uploadedBytes;
                speedInfo.currentSpeed = uploadedBytes / elapsed;
                speedInfo.averageSpeed = (speedInfo.averageSpeed + speedInfo.currentSpeed) / 2;
            }
        }
    }

    /**
     * Track file upload initialization
     */
    trackFileUpload(file) {
        const fileId = file.id;
        this.uploadSpeeds.set(fileId, {
            startTime: Date.now(),
            uploadedBytes: 0,
            currentSpeed: 0,
            averageSpeed: 0,
            totalBytes: file.size
        });
    }

    /**
     * Complete file upload tracking
     */
    completeFileUpload(file) {
        const fileId = file.id;
        const speedInfo = this.uploadSpeeds.get(fileId);

        if (speedInfo) {
            const totalTime = (Date.now() - speedInfo.startTime) / 1000;
            const finalSpeed = speedInfo.totalBytes / totalTime;

            console.log(`Upload completed for ${file.name}: ${this.formatBytes(finalSpeed)}/s average speed`);

            // Clean up adaptive chunk manager
            this.adaptiveChunkManager.cleanupUploadStats(fileId);
        }
    }

    /**
     * Clean up file tracking
     */
    cleanupFileTracking(file) {
        const fileId = file.id;
        this.uploadSpeeds.delete(fileId);
        this.retryAttempts.delete(fileId);
        this.progressTracker.removeFile(fileId);
        this.adaptiveChunkManager.cleanupUploadStats(fileId);
    }

    /**
     * Check if error is retryable
     */
    isErrorRetryable(error) {
        const retryableErrors = [
            'network request failed',
            'chunk upload failed',
            'connection lost',
            'timeout',
            'server error'
        ];

        const errorMessage = (error.message || error).toLowerCase();
        return retryableErrors.some(retryableError => errorMessage.includes(retryableError));
    }

    /**
     * Format bytes for display
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';

        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Dispatch custom events
     */
    dispatchEvent(eventName, data) {
        const event = new CustomEvent(`enhancedFilePond:${eventName}`, {
            detail: data
        });
        document.dispatchEvent(event);
    }

    /**
     * Get network condition detection and optimization
     */
    getNetworkOptimizations() {
        const fallbackMechanisms = this.adaptiveChunkManager.getFallbackMechanisms();

        return {
            ...fallbackMechanisms,
            adaptiveChunkSize: true,
            networkAwareRetries: true,
            connectionTypeOptimization: true
        };
    }

    /**
     * Apply network-based optimizations
     */
    applyNetworkOptimizations() {
        const optimizations = this.getNetworkOptimizations();

        // Apply optimizations to current configuration
        if (optimizations.chunkSize) {
            this.config.chunkSize = optimizations.chunkSize;
        }

        if (optimizations.maxConcurrentUploads) {
            this.config.maxConcurrentUploads = optimizations.maxConcurrentUploads;
        }

        if (optimizations.retryDelays) {
            this.config.retryDelays = optimizations.retryDelays;
        }

        // Dispatch optimization event
        this.dispatchEvent('networkOptimizationsApplied', optimizations);
    }

    /**
     * Get adaptive chunk recommendations
     */
    getAdaptiveRecommendations(fileId) {
        return this.adaptiveChunkManager.getAdaptationRecommendations(fileId);
    }

    /**
     * Enable/disable adaptive chunking
     */
    setAdaptiveChunkingEnabled(enabled) {
        this.adaptiveChunkManager.setAdaptationEnabled(enabled);
        this.dispatchEvent('adaptiveChunkingToggled', { enabled });
    }

    /**
     * Get current network conditions
     */
    getCurrentNetworkConditions() {
        return {
            ...this.networkConditions,
            adaptiveManagerConditions: this.adaptiveChunkManager.getNetworkConditions()
        };
    }

    /**
     * Perform network speed test
     */
    async performNetworkSpeedTest() {
        try {
            const speedTestResult = await this.adaptiveChunkManager.performSpeedTest();

            if (speedTestResult) {
                this.networkConditions.lastSpeedTest = speedTestResult;
                this.dispatchEvent('speedTestCompleted', speedTestResult);

                // Apply optimizations based on speed test
                this.applyNetworkOptimizations();
            }

            return speedTestResult;
        } catch (error) {
            console.error('Network speed test failed:', error);
            return null;
        }
    }
    /**

     * Pause ongoing uploads
     */
    pauseOngoingUploads() {
        console.log('Pausing ongoing uploads...');

        // Mark all active sessions as paused in session manager
        this.sessionManager.getActiveSessions().forEach(session => {
            if (session.status === 'uploading') {
                this.sessionManager.pauseSession(session.id, 'network_disconnected');
            }
        });

        this.dispatchEvent('uploadsPaused', {
            sessionCount: this.sessionManager.getActiveSessions().length
        });
    }

    /**
     * Resume uploads using session manager
     */
    async resumeUploadsFromSessions() {
        const recoverableSessions = this.sessionManager.getRecoverableSessions();

        for (const session of recoverableSessions) {
            try {
                const recoveredSession = await this.sessionManager.recoverSession(session.id);
                if (recoveredSession) {
                    console.log(`Recovered session ${session.id} for file ${session.fileName}`);
                    // Trigger UI update to show recovered upload
                    this.dispatchEvent('sessionRecovered', recoveredSession);
                }
            } catch (error) {
                console.error(`Failed to recover session ${session.id}:`, error);
            }
        }
    }

    /**
     * Get session recovery status
     */
    getSessionRecoveryStatus() {
        const stats = this.sessionManager.getSessionStats();
        return {
            hasRecoverableSessions: stats.recoverable > 0,
            recoverableCount: stats.recoverable,
            activeCount: stats.active,
            totalPersistedCount: stats.persisted,
            sessionsByStatus: stats.byStatus
        };
    }

    /**
     * Manually trigger session recovery
     */
    async triggerSessionRecovery() {
        return await this.sessionManager.autoRecoverSessions();
    }

    /**
     * Clean up expired sessions
     */
    cleanupExpiredSessions() {
        return this.sessionManager.cleanupExpiredSessions();
    }

    /**
     * Setup session management event listeners
     */
    setupSessionManagementListeners() {
        // Listen for session manager events
        document.addEventListener('uploadSession:sessionRecovered', (event) => {
            const session = event.detail;
            console.log(`Session recovered: ${session.fileName}`);
            this.dispatchEvent('sessionRecoveredForUpload', session);
        });

        document.addEventListener('uploadSession:sessionFailed', (event) => {
            const { session, error } = event.detail;
            console.error(`Session failed: ${session.fileName}`, error);
            this.dispatchEvent('sessionFailedForUpload', { session, error });
        });

        document.addEventListener('uploadSession:sessionsAutoRecovered', (event) => {
            const recoveredSessions = event.detail;
            console.log(`Auto-recovered ${recoveredSessions.length} sessions`);
            this.dispatchEvent('sessionsAutoRecovered', recoveredSessions);
        });

        document.addEventListener('uploadSession:sessionsCleanedUp', (event) => {
            const cleanedSessions = event.detail;
            console.log(`Cleaned up ${cleanedSessions.length} expired sessions`);
        });
    }

    /**
     * Initialize session management
     */
    initializeSessionManagement() {
        this.setupSessionManagementListeners();

        // Auto-recover sessions on initialization
        setTimeout(() => {
            this.triggerSessionRecovery();
        }, 1000);
    }
    /**
      * Initialize pause/resume functionality
      */
    initializePauseResumeManagement() {
        // Setup pause/resume manager event listeners
        document.addEventListener('uploadPauseResume:uploadPaused', (event) => {
            const { uploadId, reason, uploadState } = event.detail;
            console.log(`Upload ${uploadId} paused: ${reason}`);

            // Update session manager
            if (this.sessionManager.getSession(uploadId)) {
                this.sessionManager.pauseSession(uploadId, reason);
            }
        });

        document.addEventListener('uploadPauseResume:uploadResumed', (event) => {
            const { uploadId, uploadState } = event.detail;
            console.log(`Upload ${uploadId} resumed`);

            // Update session manager
            if (this.sessionManager.getSession(uploadId)) {
                this.sessionManager.resumeSession(uploadId);
            }
        });

        document.addEventListener('uploadPauseResume:resumeUploadProcess', (event) => {
            const { uploadId, pausedData } = event.detail;
            this.handleResumeUploadProcess(uploadId, pausedData);
        });

        // Override pause/resume manager methods to integrate with our upload process
        this.pauseResumeManager.resumeUploadProcess = async (uploadId, pausedData) => {
            return await this.resumeChunkedUpload(uploadId, pausedData);
        };

        this.pauseResumeManager.getCurrentChunkState = (uploadId) => {
            const session = this.sessionManager.getSession(uploadId);
            return session ? Object.fromEntries(session.chunks) : {};
        };

        this.pauseResumeManager.getCurrentProgress = (uploadId) => {
            const progressInfo = this.progressTracker.getFormattedProgress(uploadId);
            return progressInfo ? progressInfo.progress : 0;
        };
    }

    /**
     * Handle resume upload process
     */
    async handleResumeUploadProcess(uploadId, pausedData) {
        try {
            // Get the session data
            const session = this.sessionManager.getSession(uploadId);
            if (!session) {
                throw new Error(`Session ${uploadId} not found`);
            }

            // Resume the chunked upload from where it left off
            await this.resumeChunkedUpload(uploadId, pausedData);

        } catch (error) {
            console.error(`Failed to resume upload process for ${uploadId}:`, error);
            throw error;
        }
    }

    /**
     * Resume chunked upload from paused state
     */
    async resumeChunkedUpload(uploadId, pausedData) {
        const session = this.sessionManager.getSession(uploadId);
        if (!session) {
            throw new Error(`Session ${uploadId} not found for resume`);
        }

        // Check server status to see which chunks are already uploaded
        const serverStatus = await this.checkServerSessionStatus(session.id);
        if (!serverStatus || !serverStatus.exists) {
            throw new Error('Server session not found');
        }

        const uploadedChunks = serverStatus.data.uploaded_chunks || 0;
        const totalChunks = session.totalChunks;

        if (uploadedChunks >= totalChunks) {
            // All chunks already uploaded, just assemble
            const result = await this.assembleFile(session.id);
            this.sessionManager.completeSession(session.id, {
                fileId: result.data.file_id,
                filePath: result.data.file_path
            });
            return result;
        }

        // Resume uploading remaining chunks
        const remainingChunks = totalChunks - uploadedChunks;
        console.log(`Resuming upload: ${remainingChunks} chunks remaining`);

        // Continue with remaining chunks...
        // This would integrate with the existing chunk upload logic

        return { resumed: true, remainingChunks };
    }

    /**
     * Check server session status
     */
    async checkServerSessionStatus(sessionId) {
        try {
            const response = await fetch(`/api/upload/status/${sessionId}`, {
                headers: {
                    'X-CSRF-TOKEN': this.configManager.getCSRFToken(),
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                return {
                    exists: data.success,
                    data: data.data
                };
            }

            return null;
        } catch (error) {
            console.error('Failed to check server session status:', error);
            return null;
        }
    }

    /**
     * Pause upload by ID
     */
    pauseUpload(uploadId, reason = 'user_requested') {
        return this.pauseResumeManager.pauseUpload(uploadId, reason);
    }

    /**
     * Resume upload by ID
     */
    async resumeUpload(uploadId) {
        return await this.pauseResumeManager.resumeUpload(uploadId);
    }

    /**
     * Cancel upload by ID
     */
    cancelUpload(uploadId) {
        return this.pauseResumeManager.cancelUpload(uploadId);
    }

    /**
     * Pause all uploads
     */
    pauseAllUploads(reason = 'user_requested') {
        return this.pauseResumeManager.pauseAllUploads(reason);
    }

    /**
     * Resume all uploads
     */
    async resumeAllUploads() {
        return await this.pauseResumeManager.resumeAllUploads();
    }

    /**
     * Register upload with pause/resume manager
     */
    registerUploadForPauseResume(fileId, file, abortController) {
        const uploadData = {
            fileName: file.name,
            fileSize: file.size,
            abortController: abortController,
            metadata: {
                mimeType: file.type,
                lastModified: file.lastModified
            }
        };

        return this.pauseResumeManager.registerUpload(fileId, uploadData);
    }

    /**
     * Update pause/resume manager with upload progress
     */
    updatePauseResumeProgress(uploadId, progress, status = 'uploading') {
        const uploadState = this.pauseResumeManager.uploadStates.get(uploadId);
        if (uploadState) {
            uploadState.progress = progress;
            uploadState.status = status;
            uploadState.lastActivity = Date.now();
        }
    }

    /**
     * Enhanced pause ongoing uploads with pause/resume manager
     */
    pauseOngoingUploads() {
        console.log('Pausing ongoing uploads...');

        // Use pause/resume manager to pause all uploads
        const pausedCount = this.pauseResumeManager.pauseAllUploads('network_disconnected');

        // Also mark sessions as paused in session manager
        this.sessionManager.getActiveSessions().forEach(session => {
            if (session.status === 'uploading') {
                this.sessionManager.pauseSession(session.id, 'network_disconnected');
            }
        });

        this.dispatchEvent('uploadsPaused', {
            sessionCount: pausedCount
        });
    }

    /**
     * Enhanced resume uploads with pause/resume manager
     */
    async resumeUploadsFromSessions() {
        console.log('Resuming uploads...');

        // Use pause/resume manager to resume all uploads
        const resumedCount = await this.pauseResumeManager.resumeAllUploads();

        // Also handle session recovery
        const recoverableSessions = this.sessionManager.getRecoverableSessions();

        for (const session of recoverableSessions) {
            try {
                const recoveredSession = await this.sessionManager.recoverSession(session.id);
                if (recoveredSession) {
                    console.log(`Recovered session ${session.id} for file ${session.fileName}`);
                    this.dispatchEvent('sessionRecovered', recoveredSession);
                }
            } catch (error) {
                console.error(`Failed to recover session ${session.id}:`, error);
            }
        }

        this.dispatchEvent('uploadsResumed', {
            resumedCount: resumedCount,
            recoveredSessions: recoverableSessions.length
        });

        return resumedCount;
    }

    /**
     * Get pause/resume statistics
     */
    getPauseResumeStats() {
        return {
            pauseResumeManager: this.pauseResumeManager.getUploadStats(),
            sessionManager: this.sessionManager.getSessionStats(),
            networkConditions: this.getCurrentNetworkConditions()
        };
    }

    /**
     * Initialize all management systems
     */
    initializeSessionManagement() {
        this.setupSessionManagementListeners();
        this.initializePauseResumeManagement();

        // Auto-recover sessions on initialization
        setTimeout(() => {
            this.triggerSessionRecovery();
        }, 1000);
    }
}

// Make classes available globally
window.FilePondConfigManager = FilePondConfigManager;
window.EnhancedFilePond = EnhancedFilePond;

export default EnhancedFilePond;