/**
 * Adaptive Chunk Manager
 * Handles automatic chunk size adjustment based on upload speed and network conditions
 */
export class AdaptiveChunkManager {
    constructor(config = {}) {
        this.config = {
            minChunkSize: 1024 * 1024, // 1MB minimum
            maxChunkSize: 50 * 1024 * 1024, // 50MB maximum
            defaultChunkSize: 5 * 1024 * 1024, // 5MB default
            speedTestDuration: 5000, // 5 seconds for speed test
            adaptationThreshold: 3, // Number of chunks before adaptation
            slowConnectionThreshold: 100 * 1024, // 100KB/s considered slow
            fastConnectionThreshold: 5 * 1024 * 1024, // 5MB/s considered fast
            ...config
        };
        
        this.networkConditions = {
            connectionType: this.getConnectionType(),
            effectiveType: this.getEffectiveType(),
            downlink: this.getDownlink(),
            rtt: this.getRTT(),
            saveData: this.getSaveData()
        };
        
        this.uploadStats = new Map(); // fileId -> stats
        this.chunkSizeHistory = [];
        this.adaptationEnabled = true;
        
        this.setupNetworkMonitoring();
    }
    
    /**
     * Get optimal chunk size for a file upload
     */
    getOptimalChunkSize(fileId, fileSize, uploadContext = {}) {
        // Start with base chunk size based on network conditions
        let chunkSize = this.getBaseChunkSize();
        
        // Adjust based on file size
        chunkSize = this.adjustForFileSize(chunkSize, fileSize);
        
        // Adjust based on connection type
        chunkSize = this.adjustForConnectionType(chunkSize);
        
        // Adjust based on historical performance
        chunkSize = this.adjustForHistoricalPerformance(chunkSize, fileId);
        
        // Apply context-specific adjustments
        chunkSize = this.adjustForContext(chunkSize, uploadContext);
        
        // Ensure chunk size is within bounds
        chunkSize = Math.max(this.config.minChunkSize, Math.min(this.config.maxChunkSize, chunkSize));
        
        // Store initial chunk size for this upload
        this.initializeUploadStats(fileId, chunkSize, fileSize);
        
        return chunkSize;
    }
    
    /**
     * Get base chunk size based on current network conditions
     */
    getBaseChunkSize() {
        const connectionType = this.networkConditions.effectiveType;
        
        switch (connectionType) {
            case 'slow-2g':
                return 512 * 1024; // 512KB
            case '2g':
                return 1024 * 1024; // 1MB
            case '3g':
                return 2 * 1024 * 1024; // 2MB
            case '4g':
                return 5 * 1024 * 1024; // 5MB
            default:
                return this.config.defaultChunkSize;
        }
    }
    
    /**
     * Adjust chunk size based on file size
     */
    adjustForFileSize(chunkSize, fileSize) {
        // For very large files, use larger chunks to reduce overhead
        if (fileSize > 1024 * 1024 * 1024) { // > 1GB
            return Math.min(chunkSize * 2, this.config.maxChunkSize);
        }
        
        // For small files, use smaller chunks for better progress granularity
        if (fileSize < 50 * 1024 * 1024) { // < 50MB
            return Math.max(chunkSize / 2, this.config.minChunkSize);
        }
        
        return chunkSize;
    }
    
    /**
     * Adjust chunk size based on connection type
     */
    adjustForConnectionType(chunkSize) {
        const downlink = this.networkConditions.downlink;
        const rtt = this.networkConditions.rtt;
        
        // Adjust for slow connections
        if (downlink && downlink < 1) { // < 1 Mbps
            return Math.max(chunkSize / 2, this.config.minChunkSize);
        }
        
        // Adjust for high latency connections
        if (rtt && rtt > 300) { // > 300ms RTT
            return Math.max(chunkSize / 1.5, this.config.minChunkSize);
        }
        
        // Adjust for fast connections
        if (downlink && downlink > 10) { // > 10 Mbps
            return Math.min(chunkSize * 1.5, this.config.maxChunkSize);
        }
        
        return chunkSize;
    }
    
    /**
     * Adjust chunk size based on historical performance
     */
    adjustForHistoricalPerformance(chunkSize, fileId) {
        if (this.chunkSizeHistory.length < 3) {
            return chunkSize;
        }
        
        // Calculate average performance for different chunk sizes
        const performanceBySize = new Map();
        
        this.chunkSizeHistory.forEach(entry => {
            if (!performanceBySize.has(entry.chunkSize)) {
                performanceBySize.set(entry.chunkSize, []);
            }
            performanceBySize.get(entry.chunkSize).push(entry.throughput);
        });
        
        // Find the chunk size with best average throughput
        let bestChunkSize = chunkSize;
        let bestThroughput = 0;
        
        performanceBySize.forEach((throughputs, size) => {
            const avgThroughput = throughputs.reduce((a, b) => a + b, 0) / throughputs.length;
            if (avgThroughput > bestThroughput) {
                bestThroughput = avgThroughput;
                bestChunkSize = size;
            }
        });
        
        return bestChunkSize;
    }
    
    /**
     * Adjust chunk size for specific upload context
     */
    adjustForContext(chunkSize, context) {
        // Adjust for mobile devices
        if (this.isMobileDevice()) {
            return Math.max(chunkSize / 2, this.config.minChunkSize);
        }
        
        // Adjust for save-data mode
        if (this.networkConditions.saveData) {
            return Math.max(chunkSize / 2, this.config.minChunkSize);
        }
        
        // Adjust for concurrent uploads
        if (context.concurrentUploads && context.concurrentUploads > 2) {
            return Math.max(chunkSize / context.concurrentUploads, this.config.minChunkSize);
        }
        
        return chunkSize;
    }
    
    /**
     * Update chunk size based on upload performance
     */
    updateChunkSize(fileId, chunkIndex, chunkSize, uploadTime, success = true) {
        const stats = this.uploadStats.get(fileId);
        if (!stats) return chunkSize;
        
        const throughput = success ? chunkSize / (uploadTime / 1000) : 0;
        
        // Update stats
        stats.chunks.push({
            index: chunkIndex,
            size: chunkSize,
            uploadTime,
            throughput,
            success
        });
        
        // Don't adapt until we have enough data
        if (stats.chunks.length < this.config.adaptationThreshold) {
            return chunkSize;
        }
        
        // Calculate recent performance
        const recentChunks = stats.chunks.slice(-this.config.adaptationThreshold);
        const avgThroughput = recentChunks.reduce((sum, chunk) => sum + chunk.throughput, 0) / recentChunks.length;
        const successRate = recentChunks.filter(chunk => chunk.success).length / recentChunks.length;
        
        let newChunkSize = chunkSize;
        
        // Adapt based on performance
        if (successRate < 0.8) {
            // High failure rate - reduce chunk size
            newChunkSize = Math.max(chunkSize * 0.7, this.config.minChunkSize);
        } else if (avgThroughput < this.config.slowConnectionThreshold) {
            // Slow upload - reduce chunk size
            newChunkSize = Math.max(chunkSize * 0.8, this.config.minChunkSize);
        } else if (avgThroughput > this.config.fastConnectionThreshold && successRate > 0.95) {
            // Fast and reliable - increase chunk size
            newChunkSize = Math.min(chunkSize * 1.2, this.config.maxChunkSize);
        }
        
        // Update stats with new chunk size
        stats.currentChunkSize = newChunkSize;
        
        // Add to history for future uploads
        this.chunkSizeHistory.push({
            chunkSize: newChunkSize,
            throughput: avgThroughput,
            successRate,
            timestamp: Date.now()
        });
        
        // Limit history size
        if (this.chunkSizeHistory.length > 50) {
            this.chunkSizeHistory = this.chunkSizeHistory.slice(-25);
        }
        
        return newChunkSize;
    }
    
    /**
     * Initialize upload statistics for a file
     */
    initializeUploadStats(fileId, initialChunkSize, fileSize) {
        this.uploadStats.set(fileId, {
            fileId,
            fileSize,
            initialChunkSize,
            currentChunkSize: initialChunkSize,
            startTime: Date.now(),
            chunks: [],
            totalChunks: Math.ceil(fileSize / initialChunkSize),
            adaptations: 0
        });
    }
    
    /**
     * Get upload statistics for a file
     */
    getUploadStats(fileId) {
        return this.uploadStats.get(fileId);
    }
    
    /**
     * Clean up upload statistics
     */
    cleanupUploadStats(fileId) {
        this.uploadStats.delete(fileId);
    }
    
    /**
     * Setup network monitoring
     */
    setupNetworkMonitoring() {
        // Monitor connection changes
        if ('connection' in navigator) {
            navigator.connection.addEventListener('change', () => {
                this.updateNetworkConditions();
            });
        }
        
        // Monitor online/offline status
        window.addEventListener('online', () => {
            this.updateNetworkConditions();
        });
        
        window.addEventListener('offline', () => {
            this.updateNetworkConditions();
        });
        
        // Periodic network condition updates
        setInterval(() => {
            this.updateNetworkConditions();
        }, 30000); // Every 30 seconds
    }
    
    /**
     * Update network conditions
     */
    updateNetworkConditions() {
        this.networkConditions = {
            connectionType: this.getConnectionType(),
            effectiveType: this.getEffectiveType(),
            downlink: this.getDownlink(),
            rtt: this.getRTT(),
            saveData: this.getSaveData(),
            isOnline: navigator.onLine
        };
        
        // Dispatch event for other components
        this.dispatchEvent('networkConditionsUpdated', this.networkConditions);
    }
    
    /**
     * Get connection type
     */
    getConnectionType() {
        if ('connection' in navigator) {
            return navigator.connection.type || 'unknown';
        }
        return 'unknown';
    }
    
    /**
     * Get effective connection type
     */
    getEffectiveType() {
        if ('connection' in navigator) {
            return navigator.connection.effectiveType || '4g';
        }
        return '4g';
    }
    
    /**
     * Get downlink speed estimate
     */
    getDownlink() {
        if ('connection' in navigator) {
            return navigator.connection.downlink;
        }
        return null;
    }
    
    /**
     * Get round-trip time estimate
     */
    getRTT() {
        if ('connection' in navigator) {
            return navigator.connection.rtt;
        }
        return null;
    }
    
    /**
     * Get save-data preference
     */
    getSaveData() {
        if ('connection' in navigator) {
            return navigator.connection.saveData || false;
        }
        return false;
    }
    
    /**
     * Check if device is mobile
     */
    isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    /**
     * Perform network speed test
     */
    async performSpeedTest() {
        const testSize = 1024 * 1024; // 1MB test
        const testUrl = '/api/upload/speed-test';
        
        try {
            const startTime = Date.now();
            
            const response = await fetch(testUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/octet-stream',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: new ArrayBuffer(testSize)
            });
            
            const endTime = Date.now();
            const duration = (endTime - startTime) / 1000; // seconds
            const speed = testSize / duration; // bytes per second
            
            return {
                speed,
                duration,
                timestamp: Date.now()
            };
        } catch (error) {
            console.warn('Speed test failed:', error);
            return null;
        }
    }
    
    /**
     * Get fallback mechanisms for slow connections
     */
    getFallbackMechanisms() {
        const connectionType = this.networkConditions.effectiveType;
        const isSlowConnection = ['slow-2g', '2g'].includes(connectionType);
        const isSaveDataEnabled = this.networkConditions.saveData;
        
        return {
            enableChunking: true, // Always enable chunking for reliability
            chunkSize: isSlowConnection ? this.config.minChunkSize : this.config.defaultChunkSize,
            maxConcurrentUploads: isSlowConnection ? 1 : (isSaveDataEnabled ? 2 : 3),
            retryDelays: isSlowConnection ? [1000, 3000, 5000, 10000] : [500, 1000, 2000],
            enableCompression: isSlowConnection || isSaveDataEnabled,
            pauseOnSlowConnection: isSlowConnection,
            adaptiveQuality: isSlowConnection || isSaveDataEnabled
        };
    }
    
    /**
     * Enable or disable adaptation
     */
    setAdaptationEnabled(enabled) {
        this.adaptationEnabled = enabled;
    }
    
    /**
     * Get current network conditions
     */
    getNetworkConditions() {
        return { ...this.networkConditions };
    }
    
    /**
     * Get adaptation recommendations
     */
    getAdaptationRecommendations(fileId) {
        const stats = this.uploadStats.get(fileId);
        if (!stats || stats.chunks.length < 2) {
            return null;
        }
        
        const recentChunks = stats.chunks.slice(-3);
        const avgThroughput = recentChunks.reduce((sum, chunk) => sum + chunk.throughput, 0) / recentChunks.length;
        const successRate = recentChunks.filter(chunk => chunk.success).length / recentChunks.length;
        
        const recommendations = [];
        
        if (successRate < 0.7) {
            recommendations.push({
                type: 'reduce_chunk_size',
                reason: 'High failure rate detected',
                suggestion: 'Reduce chunk size to improve reliability'
            });
        }
        
        if (avgThroughput < this.config.slowConnectionThreshold) {
            recommendations.push({
                type: 'optimize_for_slow_connection',
                reason: 'Slow upload speed detected',
                suggestion: 'Switch to single concurrent upload and smaller chunks'
            });
        }
        
        if (avgThroughput > this.config.fastConnectionThreshold && successRate > 0.95) {
            recommendations.push({
                type: 'increase_chunk_size',
                reason: 'Fast and reliable connection detected',
                suggestion: 'Increase chunk size to improve efficiency'
            });
        }
        
        return recommendations;
    }
    
    /**
     * Dispatch custom events
     */
    dispatchEvent(eventName, data) {
        const event = new CustomEvent(`adaptiveChunk:${eventName}`, {
            detail: data
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Reset all statistics and history
     */
    reset() {
        this.uploadStats.clear();
        this.chunkSizeHistory = [];
        this.updateNetworkConditions();
    }
}

// Make available globally
window.AdaptiveChunkManager = AdaptiveChunkManager;

export default AdaptiveChunkManager;