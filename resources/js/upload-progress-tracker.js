/**
 * Upload Progress Tracker
 * Handles detailed progress indication, speed calculation, and user feedback
 */
export class UploadProgressTracker {
    constructor() {
        this.progressData = new Map();
        this.batchProgress = {
            totalFiles: 0,
            completedFiles: 0,
            totalBytes: 0,
            uploadedBytes: 0,
            startTime: null,
            currentSpeed: 0,
            averageSpeed: 0,
            estimatedTimeRemaining: null
        };
        this.progressUpdateInterval = null;
        this.speedCalculationWindow = 5000; // 5 seconds for speed calculation
        this.speedHistory = [];
    }
    
    /**
     * Initialize progress tracking for a file
     */
    initializeFileProgress(fileId, file) {
        const progressInfo = {
            fileId: fileId,
            fileName: file.name,
            fileSize: file.size,
            uploadedBytes: 0,
            progress: 0,
            speed: 0,
            averageSpeed: 0,
            startTime: Date.now(),
            lastUpdateTime: Date.now(),
            estimatedTimeRemaining: null,
            status: 'initializing',
            chunks: {
                total: 0,
                uploaded: 0,
                failed: 0
            },
            errors: [],
            retryCount: 0
        };
        
        this.progressData.set(fileId, progressInfo);
        this.updateBatchProgress();
        
        return progressInfo;
    }
    
    /**
     * Update file progress
     */
    updateFileProgress(fileId, uploadedBytes, totalBytes = null) {
        const progressInfo = this.progressData.get(fileId);
        if (!progressInfo) return;
        
        const now = Date.now();
        const previousBytes = progressInfo.uploadedBytes;
        
        // Update basic progress info
        progressInfo.uploadedBytes = uploadedBytes;
        if (totalBytes) {
            progressInfo.fileSize = totalBytes;
        }
        progressInfo.progress = progressInfo.fileSize > 0 ? uploadedBytes / progressInfo.fileSize : 0;
        progressInfo.lastUpdateTime = now;
        
        // Calculate speed
        const timeDiff = (now - progressInfo.startTime) / 1000; // seconds
        const bytesDiff = uploadedBytes - previousBytes;
        
        if (timeDiff > 0) {
            // Current speed (bytes per second)
            progressInfo.speed = bytesDiff / ((now - progressInfo.lastUpdateTime) / 1000);
            
            // Average speed since start
            progressInfo.averageSpeed = uploadedBytes / timeDiff;
            
            // Estimated time remaining
            const remainingBytes = progressInfo.fileSize - uploadedBytes;
            if (progressInfo.averageSpeed > 0 && remainingBytes > 0) {
                progressInfo.estimatedTimeRemaining = remainingBytes / progressInfo.averageSpeed;
            }
        }
        
        // Update status
        if (progressInfo.progress >= 1) {
            progressInfo.status = 'completed';
        } else if (progressInfo.progress > 0) {
            progressInfo.status = 'uploading';
        }
        
        this.updateBatchProgress();
        this.dispatchProgressEvent('fileProgress', progressInfo);
    }
    
    /**
     * Update chunk progress for a file
     */
    updateChunkProgress(fileId, chunkIndex, chunkSize, status = 'uploaded') {
        const progressInfo = this.progressData.get(fileId);
        if (!progressInfo) return;
        
        if (status === 'uploaded') {
            progressInfo.chunks.uploaded++;
            this.updateFileProgress(fileId, progressInfo.chunks.uploaded * chunkSize);
        } else if (status === 'failed') {
            progressInfo.chunks.failed++;
            progressInfo.retryCount++;
        }
        
        this.dispatchProgressEvent('chunkProgress', {
            fileId,
            chunkIndex,
            status,
            totalChunks: progressInfo.chunks.total,
            uploadedChunks: progressInfo.chunks.uploaded,
            failedChunks: progressInfo.chunks.failed
        });
    }
    
    /**
     * Set total chunks for a file
     */
    setTotalChunks(fileId, totalChunks) {
        const progressInfo = this.progressData.get(fileId);
        if (progressInfo) {
            progressInfo.chunks.total = totalChunks;
        }
    }
    
    /**
     * Add error to file progress
     */
    addFileError(fileId, error, isRetryable = true) {
        const progressInfo = this.progressData.get(fileId);
        if (!progressInfo) return;
        
        const errorInfo = {
            message: error.message || error,
            timestamp: Date.now(),
            isRetryable: isRetryable,
            retryCount: progressInfo.retryCount
        };
        
        progressInfo.errors.push(errorInfo);
        progressInfo.status = isRetryable ? 'retrying' : 'failed';
        
        this.dispatchProgressEvent('fileError', {
            fileId,
            error: errorInfo,
            progressInfo
        });
    }
    
    /**
     * Mark file as completed
     */
    completeFile(fileId) {
        const progressInfo = this.progressData.get(fileId);
        if (!progressInfo) return;
        
        progressInfo.status = 'completed';
        progressInfo.progress = 1;
        progressInfo.uploadedBytes = progressInfo.fileSize;
        
        const totalTime = (Date.now() - progressInfo.startTime) / 1000;
        progressInfo.totalTime = totalTime;
        progressInfo.finalAverageSpeed = progressInfo.fileSize / totalTime;
        
        this.updateBatchProgress();
        this.dispatchProgressEvent('fileCompleted', progressInfo);
    }
    
    /**
     * Update overall batch progress
     */
    updateBatchProgress() {
        let totalFiles = 0;
        let completedFiles = 0;
        let totalBytes = 0;
        let uploadedBytes = 0;
        let earliestStartTime = null;
        
        this.progressData.forEach((progressInfo) => {
            totalFiles++;
            totalBytes += progressInfo.fileSize;
            uploadedBytes += progressInfo.uploadedBytes;
            
            if (progressInfo.status === 'completed') {
                completedFiles++;
            }
            
            if (!earliestStartTime || progressInfo.startTime < earliestStartTime) {
                earliestStartTime = progressInfo.startTime;
            }
        });
        
        this.batchProgress.totalFiles = totalFiles;
        this.batchProgress.completedFiles = completedFiles;
        this.batchProgress.totalBytes = totalBytes;
        this.batchProgress.uploadedBytes = uploadedBytes;
        
        if (earliestStartTime) {
            this.batchProgress.startTime = earliestStartTime;
            
            const elapsed = (Date.now() - earliestStartTime) / 1000;
            if (elapsed > 0) {
                this.batchProgress.averageSpeed = uploadedBytes / elapsed;
                
                const remainingBytes = totalBytes - uploadedBytes;
                if (this.batchProgress.averageSpeed > 0 && remainingBytes > 0) {
                    this.batchProgress.estimatedTimeRemaining = remainingBytes / this.batchProgress.averageSpeed;
                }
            }
        }
        
        this.dispatchProgressEvent('batchProgress', this.batchProgress);
    }
    
    /**
     * Get formatted progress information for display
     */
    getFormattedProgress(fileId) {
        const progressInfo = this.progressData.get(fileId);
        if (!progressInfo) return null;
        
        return {
            fileName: progressInfo.fileName,
            progress: Math.round(progressInfo.progress * 100),
            uploadedSize: this.formatBytes(progressInfo.uploadedBytes),
            totalSize: this.formatBytes(progressInfo.fileSize),
            speed: this.formatSpeed(progressInfo.averageSpeed),
            timeRemaining: this.formatTime(progressInfo.estimatedTimeRemaining),
            status: this.getStatusText(progressInfo.status),
            chunks: progressInfo.chunks.total > 0 ? {
                uploaded: progressInfo.chunks.uploaded,
                total: progressInfo.chunks.total,
                failed: progressInfo.chunks.failed
            } : null,
            errors: progressInfo.errors.map(error => ({
                message: this.getUserFriendlyErrorMessage(error.message),
                isRetryable: error.isRetryable,
                timestamp: new Date(error.timestamp).toLocaleTimeString()
            }))
        };
    }
    
    /**
     * Get formatted batch progress information
     */
    getFormattedBatchProgress() {
        return {
            totalFiles: this.batchProgress.totalFiles,
            completedFiles: this.batchProgress.completedFiles,
            progress: this.batchProgress.totalBytes > 0 ? 
                Math.round((this.batchProgress.uploadedBytes / this.batchProgress.totalBytes) * 100) : 0,
            uploadedSize: this.formatBytes(this.batchProgress.uploadedBytes),
            totalSize: this.formatBytes(this.batchProgress.totalBytes),
            averageSpeed: this.formatSpeed(this.batchProgress.averageSpeed),
            timeRemaining: this.formatTime(this.batchProgress.estimatedTimeRemaining),
            isComplete: this.batchProgress.completedFiles === this.batchProgress.totalFiles
        };
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
     * Format speed for display
     */
    formatSpeed(bytesPerSecond) {
        if (!bytesPerSecond || bytesPerSecond === 0) return '0 B/s';
        
        return this.formatBytes(bytesPerSecond) + '/s';
    }
    
    /**
     * Format time for display
     */
    formatTime(seconds) {
        if (!seconds || seconds === 0 || !isFinite(seconds)) return 'Unknown';
        
        if (seconds < 60) {
            return Math.round(seconds) + 's';
        } else if (seconds < 3600) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.round(seconds % 60);
            return `${minutes}m ${remainingSeconds}s`;
        } else {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            return `${hours}h ${minutes}m`;
        }
    }
    
    /**
     * Get user-friendly status text
     */
    getStatusText(status) {
        const statusTexts = {
            'initializing': 'Preparing...',
            'uploading': 'Uploading...',
            'completed': 'Complete',
            'failed': 'Failed',
            'retrying': 'Retrying...',
            'paused': 'Paused',
            'cancelled': 'Cancelled'
        };
        
        return statusTexts[status] || status;
    }
    
    /**
     * Get user-friendly error messages
     */
    getUserFriendlyErrorMessage(errorMessage) {
        const errorMappings = {
            'Network request failed': 'Connection lost. Please check your internet connection.',
            'Chunk upload failed': 'Upload interrupted. Retrying automatically...',
            'File assembly failed': 'Error processing your file. Please try again.',
            'Upload session expired': 'Upload session timed out. Please restart the upload.',
            'File too large': 'File size exceeds the maximum allowed limit.',
            'Invalid file type': 'This file type is not supported.',
            'Storage limit exceeded': 'You have reached your storage limit. Please upgrade your plan.',
            'Server error': 'Server temporarily unavailable. Please try again later.'
        };
        
        // Check for partial matches
        for (const [key, friendlyMessage] of Object.entries(errorMappings)) {
            if (errorMessage.toLowerCase().includes(key.toLowerCase())) {
                return friendlyMessage;
            }
        }
        
        // Default fallback
        return 'An error occurred during upload. Please try again.';
    }
    
    /**
     * Get retry options for failed uploads
     */
    getRetryOptions(fileId) {
        const progressInfo = this.progressData.get(fileId);
        if (!progressInfo || progressInfo.status !== 'failed') return null;
        
        const lastError = progressInfo.errors[progressInfo.errors.length - 1];
        
        return {
            canRetry: lastError?.isRetryable !== false,
            retryCount: progressInfo.retryCount,
            maxRetries: 3,
            suggestedActions: this.getSuggestedActions(lastError?.message),
            retryDelay: Math.min(1000 * Math.pow(2, progressInfo.retryCount), 10000)
        };
    }
    
    /**
     * Get suggested actions for errors
     */
    getSuggestedActions(errorMessage) {
        if (!errorMessage) return [];
        
        const suggestions = [];
        const lowerError = errorMessage.toLowerCase();
        
        if (lowerError.includes('network') || lowerError.includes('connection')) {
            suggestions.push('Check your internet connection');
            suggestions.push('Try switching to a more stable network');
        }
        
        if (lowerError.includes('file too large') || lowerError.includes('size')) {
            suggestions.push('Try compressing your file');
            suggestions.push('Split large files into smaller parts');
        }
        
        if (lowerError.includes('storage') || lowerError.includes('limit')) {
            suggestions.push('Free up storage space');
            suggestions.push('Consider upgrading your plan');
        }
        
        if (lowerError.includes('server') || lowerError.includes('timeout')) {
            suggestions.push('Wait a moment and try again');
            suggestions.push('Contact support if the problem persists');
        }
        
        return suggestions;
    }
    
    /**
     * Remove file from progress tracking
     */
    removeFile(fileId) {
        this.progressData.delete(fileId);
        this.updateBatchProgress();
        this.dispatchProgressEvent('fileRemoved', { fileId });
    }
    
    /**
     * Clear all progress data
     */
    clearAll() {
        this.progressData.clear();
        this.batchProgress = {
            totalFiles: 0,
            completedFiles: 0,
            totalBytes: 0,
            uploadedBytes: 0,
            startTime: null,
            currentSpeed: 0,
            averageSpeed: 0,
            estimatedTimeRemaining: null
        };
        this.dispatchProgressEvent('progressCleared', {});
    }
    
    /**
     * Dispatch progress events
     */
    dispatchProgressEvent(eventName, data) {
        const event = new CustomEvent(`uploadProgress:${eventName}`, { 
            detail: data 
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Start periodic progress updates
     */
    startProgressUpdates(interval = 1000) {
        if (this.progressUpdateInterval) {
            clearInterval(this.progressUpdateInterval);
        }
        
        this.progressUpdateInterval = setInterval(() => {
            this.updateBatchProgress();
        }, interval);
    }
    
    /**
     * Stop periodic progress updates
     */
    stopProgressUpdates() {
        if (this.progressUpdateInterval) {
            clearInterval(this.progressUpdateInterval);
            this.progressUpdateInterval = null;
        }
    }
    
    /**
     * Get all progress data
     */
    getAllProgress() {
        const fileProgress = [];
        this.progressData.forEach((progressInfo, fileId) => {
            fileProgress.push(this.getFormattedProgress(fileId));
        });
        
        return {
            files: fileProgress,
            batch: this.getFormattedBatchProgress()
        };
    }
}

// Make available globally
window.UploadProgressTracker = UploadProgressTracker;

export default UploadProgressTracker;