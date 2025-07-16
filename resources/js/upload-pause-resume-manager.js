/**
 * Upload Pause Resume Manager
 * Handles upload pause/resume controls and automatic pause/resume on network disconnection
 */
export class UploadPauseResumeManager {
    constructor(config = {}) {
        this.config = {
            enableManualPauseResume: true,
            enableAutoPauseOnDisconnect: true,
            enableAutoPauseOnSlowConnection: false,
            slowConnectionThreshold: 50 * 1024, // 50KB/s
            pauseResumeButtonSelector: '.upload-pause-resume-btn',
            uploadItemSelector: '.upload-item',
            enableKeyboardShortcuts: true,
            pauseKeyCode: 'Space',
            resumeKeyCode: 'Enter',
            ...config
        };

        this.pausedUploads = new Map();
        this.uploadStates = new Map();
        this.networkMonitor = null;
        this.keyboardListeners = new Map();
        this.uiElements = new Map();

        this.init();
    }

    /**
     * Initialize pause/resume manager
     */
    init() {
        this.setupNetworkMonitoring();

        if (this.config.enableKeyboardShortcuts) {
            this.setupKeyboardShortcuts();
        }

        this.setupUIEventListeners();
    }

    /**
     * Register an upload for pause/resume management
     */
    registerUpload(uploadId, uploadData) {
        const uploadState = {
            id: uploadId,
            fileName: uploadData.fileName,
            fileSize: uploadData.fileSize,
            status: 'uploading',
            pausedAt: null,
            resumedAt: null,
            pauseReason: null,
            abortController: uploadData.abortController || new AbortController(),
            progressCallback: uploadData.progressCallback,
            errorCallback: uploadData.errorCallback,
            completeCallback: uploadData.completeCallback,
            metadata: uploadData.metadata || {}
        };

        this.uploadStates.set(uploadId, uploadState);
        this.createUploadUI(uploadId, uploadState);

        this.dispatchEvent('uploadRegistered', uploadState);
        return uploadState;
    }

    /**
     * Pause an upload
     */
    pauseUpload(uploadId, reason = 'user_requested') {
        const uploadState = this.uploadStates.get(uploadId);
        if (!uploadState) {
            console.warn(`Upload ${uploadId} not found for pause`);
            return false;
        }

        if (uploadState.status === 'paused') {
            console.log(`Upload ${uploadId} is already paused`);
            return true;
        }

        if (uploadState.status !== 'uploading') {
            console.warn(`Cannot pause upload ${uploadId} with status ${uploadState.status}`);
            return false;
        }

        // Abort the current upload process
        if (uploadState.abortController) {
            uploadState.abortController.abort();
        }

        // Update state
        uploadState.status = 'paused';
        uploadState.pausedAt = Date.now();
        uploadState.pauseReason = reason;

        // Store paused upload data
        this.pausedUploads.set(uploadId, {
            ...uploadState,
            pausedChunks: this.getCurrentChunkState(uploadId),
            pausedProgress: this.getCurrentProgress(uploadId)
        });

        // Update UI
        this.updateUploadUI(uploadId, 'paused');

        this.dispatchEvent('uploadPaused', {
            uploadId,
            reason,
            uploadState
        });

        console.log(`Upload ${uploadId} paused: ${reason}`);
        return true;
    }

    /**
     * Resume an upload
     */
    async resumeUpload(uploadId) {
        const uploadState = this.uploadStates.get(uploadId);
        if (!uploadState) {
            console.warn(`Upload ${uploadId} not found for resume`);
            return false;
        }

        if (uploadState.status !== 'paused') {
            console.warn(`Cannot resume upload ${uploadId} with status ${uploadState.status}`);
            return false;
        }

        const pausedData = this.pausedUploads.get(uploadId);
        if (!pausedData) {
            console.error(`No paused data found for upload ${uploadId}`);
            return false;
        }

        try {
            // Create new abort controller for resumed upload
            uploadState.abortController = new AbortController();

            // Update state
            uploadState.status = 'uploading';
            uploadState.resumedAt = Date.now();
            uploadState.pauseReason = null;

            // Update UI
            this.updateUploadUI(uploadId, 'uploading');

            // Resume the upload process
            await this.resumeUploadProcess(uploadId, pausedData);

            // Clean up paused data
            this.pausedUploads.delete(uploadId);

            this.dispatchEvent('uploadResumed', {
                uploadId,
                uploadState,
                resumedFrom: pausedData.pausedProgress
            });

            console.log(`Upload ${uploadId} resumed`);
            return true;

        } catch (error) {
            console.error(`Failed to resume upload ${uploadId}:`, error);

            // Revert state on failure
            uploadState.status = 'paused';
            this.updateUploadUI(uploadId, 'paused');

            this.dispatchEvent('uploadResumeError', {
                uploadId,
                error,
                uploadState
            });

            return false;
        }
    }

    /**
     * Cancel an upload
     */
    cancelUpload(uploadId) {
        const uploadState = this.uploadStates.get(uploadId);
        if (!uploadState) {
            console.warn(`Upload ${uploadId} not found for cancel`);
            return false;
        }

        // Abort the upload process
        if (uploadState.abortController) {
            uploadState.abortController.abort();
        }

        // Update state
        uploadState.status = 'cancelled';
        uploadState.cancelledAt = Date.now();

        // Clean up
        this.pausedUploads.delete(uploadId);
        this.uploadStates.delete(uploadId);

        // Update UI
        this.updateUploadUI(uploadId, 'cancelled');

        this.dispatchEvent('uploadCancelled', {
            uploadId,
            uploadState
        });

        console.log(`Upload ${uploadId} cancelled`);
        return true;
    }

    /**
     * Pause all active uploads
     */
    pauseAllUploads(reason = 'batch_pause') {
        let pausedCount = 0;

        this.uploadStates.forEach((uploadState, uploadId) => {
            if (uploadState.status === 'uploading') {
                if (this.pauseUpload(uploadId, reason)) {
                    pausedCount++;
                }
            }
        });

        this.dispatchEvent('allUploadsPaused', {
            reason,
            pausedCount
        });

        return pausedCount;
    }

    /**
     * Resume all paused uploads
     */
    async resumeAllUploads() {
        const resumePromises = [];

        this.uploadStates.forEach((uploadState, uploadId) => {
            if (uploadState.status === 'paused') {
                resumePromises.push(this.resumeUpload(uploadId));
            }
        });

        const results = await Promise.allSettled(resumePromises);
        const resumedCount = results.filter(result => result.status === 'fulfilled' && result.value).length;

        this.dispatchEvent('allUploadsResumed', {
            resumedCount,
            totalAttempted: resumePromises.length
        });

        return resumedCount;
    }

    /**
     * Setup network monitoring for automatic pause/resume
     */
    setupNetworkMonitoring() {
        if (!this.config.enableAutoPauseOnDisconnect) return;

        // Monitor online/offline status
        window.addEventListener('online', () => {
            this.handleNetworkReconnection();
        });

        window.addEventListener('offline', () => {
            this.handleNetworkDisconnection();
        });

        // Monitor connection quality if available
        if ('connection' in navigator && this.config.enableAutoPauseOnSlowConnection) {
            navigator.connection.addEventListener('change', () => {
                this.handleConnectionChange();
            });
        }
    }

    /**
     * Handle network disconnection
     */
    handleNetworkDisconnection() {
        console.log('Network disconnected - pausing all uploads');

        const pausedCount = this.pauseAllUploads('network_disconnected');

        this.dispatchEvent('networkDisconnected', {
            pausedCount
        });

        // Show user notification
        this.showNetworkNotification('disconnected');
    }

    /**
     * Handle network reconnection
     */
    async handleNetworkReconnection() {
        console.log('Network reconnected - resuming paused uploads');

        // Wait a moment for connection to stabilize
        await this.sleep(2000);

        const resumedCount = await this.resumeAllUploads();

        this.dispatchEvent('networkReconnected', {
            resumedCount
        });

        // Show user notification
        this.showNetworkNotification('reconnected', resumedCount);
    }

    /**
     * Handle connection quality changes
     */
    handleConnectionChange() {
        if (!navigator.connection) return;

        const connection = navigator.connection;
        const effectiveType = connection.effectiveType;
        const downlink = connection.downlink;

        // Pause uploads on very slow connections
        if (this.config.enableAutoPauseOnSlowConnection) {
            const isSlowConnection = effectiveType === 'slow-2g' ||
                effectiveType === '2g' ||
                (downlink && downlink < 0.5);

            if (isSlowConnection) {
                console.log('Slow connection detected - pausing uploads');
                this.pauseAllUploads('slow_connection');
                this.showNetworkNotification('slow_connection');
            }
        }

        this.dispatchEvent('connectionChanged', {
            effectiveType,
            downlink,
            rtt: connection.rtt
        });
    }

    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardShortcuts() {
        const keydownHandler = (event) => {
            // Only handle shortcuts when focused on upload area
            if (!this.isUploadAreaFocused(event.target)) return;

            if (event.code === this.config.pauseKeyCode && !event.ctrlKey && !event.altKey) {
                event.preventDefault();
                this.handleKeyboardPause();
            } else if (event.code === this.config.resumeKeyCode && !event.ctrlKey && !event.altKey) {
                event.preventDefault();
                this.handleKeyboardResume();
            }
        };

        document.addEventListener('keydown', keydownHandler);
        this.keyboardListeners.set('keydown', keydownHandler);
    }

    /**
     * Handle keyboard pause shortcut
     */
    handleKeyboardPause() {
        const activeUploads = this.getActiveUploads();
        if (activeUploads.length > 0) {
            this.pauseAllUploads('keyboard_shortcut');
            this.showUserNotification('Uploads paused', 'info');
        }
    }

    /**
     * Handle keyboard resume shortcut
     */
    async handleKeyboardResume() {
        const pausedUploads = this.getPausedUploads();
        if (pausedUploads.length > 0) {
            const resumedCount = await this.resumeAllUploads();
            this.showUserNotification(`${resumedCount} uploads resumed`, 'success');
        }
    }

    /**
     * Setup UI event listeners
     */
    setupUIEventListeners() {
        // Delegate event handling for pause/resume buttons
        document.addEventListener('click', (event) => {
            if (event.target.matches(this.config.pauseResumeButtonSelector)) {
                this.handlePauseResumeButtonClick(event);
            }
        });
    }

    /**
     * Handle pause/resume button clicks
     */
    handlePauseResumeButtonClick(event) {
        const button = event.target;
        const uploadId = button.dataset.uploadId;
        const action = button.dataset.action;

        if (!uploadId) {
            console.warn('No upload ID found on pause/resume button');
            return;
        }

        if (action === 'pause') {
            this.pauseUpload(uploadId, 'user_requested');
        } else if (action === 'resume') {
            this.resumeUpload(uploadId);
        } else if (action === 'cancel') {
            this.cancelUpload(uploadId);
        }
    }

    /**
     * Create UI elements for upload control
     */
    createUploadUI(uploadId, uploadState) {
        const uploadItem = document.querySelector(`${this.config.uploadItemSelector}[data-upload-id="${uploadId}"]`);
        if (!uploadItem) return;

        // Create control buttons container
        const controlsContainer = document.createElement('div');
        controlsContainer.className = 'upload-controls';

        // Create pause/resume button
        const pauseResumeBtn = document.createElement('button');
        pauseResumeBtn.className = 'upload-pause-resume-btn btn btn-sm';
        pauseResumeBtn.dataset.uploadId = uploadId;
        pauseResumeBtn.dataset.action = 'pause';
        pauseResumeBtn.innerHTML = '<i class="fas fa-pause"></i> Pause';
        pauseResumeBtn.title = 'Pause upload (Space)';

        // Create cancel button
        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'upload-cancel-btn btn btn-sm btn-outline-danger';
        cancelBtn.dataset.uploadId = uploadId;
        cancelBtn.dataset.action = 'cancel';
        cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
        cancelBtn.title = 'Cancel upload';

        controlsContainer.appendChild(pauseResumeBtn);
        controlsContainer.appendChild(cancelBtn);

        // Add to upload item
        uploadItem.appendChild(controlsContainer);

        // Store UI references
        this.uiElements.set(uploadId, {
            uploadItem,
            controlsContainer,
            pauseResumeBtn,
            cancelBtn
        });
    }

    /**
     * Update upload UI based on status
     */
    updateUploadUI(uploadId, status) {
        const uiElements = this.uiElements.get(uploadId);
        if (!uiElements) return;

        const { uploadItem, pauseResumeBtn, cancelBtn } = uiElements;

        // Update upload item class
        uploadItem.className = uploadItem.className.replace(/upload-status-\w+/g, '');
        uploadItem.classList.add(`upload-status-${status}`);

        // Update pause/resume button
        if (status === 'paused') {
            pauseResumeBtn.dataset.action = 'resume';
            pauseResumeBtn.innerHTML = '<i class="fas fa-play"></i> Resume';
            pauseResumeBtn.title = 'Resume upload (Enter)';
            pauseResumeBtn.classList.remove('btn-warning');
            pauseResumeBtn.classList.add('btn-success');
        } else if (status === 'uploading') {
            pauseResumeBtn.dataset.action = 'pause';
            pauseResumeBtn.innerHTML = '<i class="fas fa-pause"></i> Pause';
            pauseResumeBtn.title = 'Pause upload (Space)';
            pauseResumeBtn.classList.remove('btn-success');
            pauseResumeBtn.classList.add('btn-warning');
        } else if (status === 'completed') {
            pauseResumeBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
        } else if (status === 'cancelled' || status === 'failed') {
            pauseResumeBtn.style.display = 'none';
            cancelBtn.innerHTML = '<i class="fas fa-trash"></i> Remove';
            cancelBtn.title = 'Remove from list';
        }
    }

    /**
     * Resume upload process (to be implemented by integrating component)
     */
    async resumeUploadProcess(uploadId, pausedData) {
        // This method should be overridden by the integrating component
        // to handle the actual resumption of the upload process

        this.dispatchEvent('resumeUploadProcess', {
            uploadId,
            pausedData
        });

        // Simulate resumption for now
        return new Promise((resolve) => {
            setTimeout(resolve, 100);
        });
    }

    /**
     * Get current chunk state for an upload
     */
    getCurrentChunkState(uploadId) {
        // This should be implemented by the integrating component
        // to return the current state of uploaded chunks
        return {};
    }

    /**
     * Get current progress for an upload
     */
    getCurrentProgress(uploadId) {
        // This should be implemented by the integrating component
        // to return the current upload progress
        return 0;
    }

    /**
     * Get active uploads
     */
    getActiveUploads() {
        return Array.from(this.uploadStates.values()).filter(state => state.status === 'uploading');
    }

    /**
     * Get paused uploads
     */
    getPausedUploads() {
        return Array.from(this.uploadStates.values()).filter(state => state.status === 'paused');
    }

    /**
     * Check if upload area is focused
     */
    isUploadAreaFocused(element) {
        return element.closest('.filepond--root') !== null ||
            element.closest('.upload-area') !== null ||
            element.matches(this.config.uploadItemSelector);
    }

    /**
     * Show network notification to user
     */
    showNetworkNotification(type, count = 0) {
        const messages = {
            disconnected: 'Network disconnected. Uploads have been paused.',
            reconnected: `Network reconnected. ${count} uploads resumed.`,
            slow_connection: 'Slow connection detected. Uploads paused to save data.'
        };

        const message = messages[type] || 'Network status changed.';
        this.showUserNotification(message, type === 'reconnected' ? 'success' : 'warning');
    }

    /**
     * Show user notification
     */
    showUserNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `upload-notification alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info'}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
            ${message}
            <button type="button" class="btn-close" aria-label="Close"></button>
        `;

        // Add to page
        const container = document.querySelector('.upload-notifications') || document.body;
        container.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);

        // Handle close button
        notification.querySelector('.btn-close').addEventListener('click', () => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        });
    }

    /**
     * Sleep utility
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Get upload statistics
     */
    getUploadStats() {
        const stats = {
            total: this.uploadStates.size,
            uploading: 0,
            paused: 0,
            completed: 0,
            failed: 0,
            cancelled: 0
        };

        this.uploadStates.forEach(state => {
            stats[state.status]++;
        });

        return stats;
    }

    /**
     * Dispatch custom events
     */
    dispatchEvent(eventName, data) {
        const event = new CustomEvent(`uploadPauseResume:${eventName}`, {
            detail: data
        });
        document.dispatchEvent(event);
    }

    /**
     * Cleanup and destroy manager
     */
    destroy() {
        // Remove event listeners
        this.keyboardListeners.forEach((handler, eventType) => {
            document.removeEventListener(eventType, handler);
        });

        // Clear data
        this.uploadStates.clear();
        this.pausedUploads.clear();
        this.uiElements.clear();
        this.keyboardListeners.clear();
    }
}

// Make available globally
window.UploadPauseResumeManager = UploadPauseResumeManager;

export default UploadPauseResumeManager;