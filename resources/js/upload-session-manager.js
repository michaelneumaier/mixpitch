/**
 * Upload Session Manager
 * Handles upload session persistence across browser sessions and automatic cleanup
 */
export class UploadSessionManager {
    constructor(config = {}) {
        this.config = {
            storageKey: 'enhanced_upload_sessions',
            maxSessionAge: 24 * 60 * 60 * 1000, // 24 hours
            cleanupInterval: 60 * 60 * 1000, // 1 hour
            maxStoredSessions: 50,
            enablePersistence: true,
            enableAutoRecovery: true,
            ...config
        };
        
        this.activeSessions = new Map();
        this.persistedSessions = new Map();
        this.cleanupTimer = null;
        this.recoveryInProgress = new Set();
        
        this.init();
    }
    
    /**
     * Initialize session manager
     */
    init() {
        if (this.config.enablePersistence) {
            this.loadPersistedSessions();
            this.startCleanupTimer();
            this.setupBeforeUnloadHandler();
        }
        
        if (this.config.enableAutoRecovery) {
            this.setupAutoRecovery();
        }
    }
    
    /**
     * Create new upload session
     */
    createSession(fileId, sessionData) {
        const session = {
            id: sessionData.id || this.generateSessionId(),
            fileId: fileId,
            fileName: sessionData.fileName,
            fileSize: sessionData.fileSize,
            totalChunks: sessionData.totalChunks,
            uploadedChunks: sessionData.uploadedChunks || 0,
            chunkSize: sessionData.chunkSize,
            status: sessionData.status || 'pending',
            createdAt: Date.now(),
            lastActivity: Date.now(),
            uploadedBytes: sessionData.uploadedBytes || 0,
            modelType: sessionData.modelType,
            modelId: sessionData.modelId,
            metadata: sessionData.metadata || {},
            chunks: sessionData.chunks || new Map(),
            retryCount: 0,
            errors: []
        };
        
        this.activeSessions.set(session.id, session);
        
        if (this.config.enablePersistence) {
            this.persistSession(session);
        }
        
        this.dispatchEvent('sessionCreated', session);
        return session;
    }
    
    /**
     * Update session data
     */
    updateSession(sessionId, updates) {
        const session = this.activeSessions.get(sessionId);
        if (!session) {
            console.warn(`Session ${sessionId} not found for update`);
            return null;
        }
        
        // Update session data
        Object.assign(session, updates);
        session.lastActivity = Date.now();
        
        // Update persisted session
        if (this.config.enablePersistence) {
            this.persistSession(session);
        }
        
        this.dispatchEvent('sessionUpdated', session);
        return session;
    }
    
    /**
     * Update chunk progress for session
     */
    updateChunkProgress(sessionId, chunkIndex, chunkData) {
        const session = this.activeSessions.get(sessionId);
        if (!session) return null;
        
        // Update chunk data
        session.chunks.set(chunkIndex, {
            index: chunkIndex,
            status: chunkData.status || 'uploaded',
            hash: chunkData.hash,
            size: chunkData.size,
            uploadedAt: Date.now(),
            retryCount: chunkData.retryCount || 0
        });
        
        // Update session progress
        if (chunkData.status === 'uploaded') {
            session.uploadedChunks = Math.max(session.uploadedChunks, chunkIndex + 1);
            session.uploadedBytes += chunkData.size || 0;
        }
        
        session.lastActivity = Date.now();
        
        // Update persisted session
        if (this.config.enablePersistence) {
            this.persistSession(session);
        }
        
        this.dispatchEvent('chunkProgressUpdated', {
            sessionId,
            chunkIndex,
            session
        });
        
        return session;
    }
    
    /**
     * Mark session as completed
     */
    completeSession(sessionId, finalData = {}) {
        const session = this.activeSessions.get(sessionId);
        if (!session) return null;
        
        session.status = 'completed';
        session.completedAt = Date.now();
        session.finalFileId = finalData.fileId;
        session.finalFilePath = finalData.filePath;
        
        // Clean up from active sessions after a delay
        setTimeout(() => {
            this.removeSession(sessionId);
        }, 5000); // Keep for 5 seconds for any final operations
        
        this.dispatchEvent('sessionCompleted', session);
        return session;
    }
    
    /**
     * Mark session as failed
     */
    failSession(sessionId, error) {
        const session = this.activeSessions.get(sessionId);
        if (!session) return null;
        
        session.status = 'failed';
        session.failedAt = Date.now();
        session.errors.push({
            message: error.message || error,
            timestamp: Date.now(),
            stack: error.stack
        });
        
        if (this.config.enablePersistence) {
            this.persistSession(session);
        }
        
        this.dispatchEvent('sessionFailed', { session, error });
        return session;
    }
    
    /**
     * Pause session
     */
    pauseSession(sessionId, reason = 'user_requested') {
        const session = this.activeSessions.get(sessionId);
        if (!session) return null;
        
        session.status = 'paused';
        session.pausedAt = Date.now();
        session.pauseReason = reason;
        
        if (this.config.enablePersistence) {
            this.persistSession(session);
        }
        
        this.dispatchEvent('sessionPaused', { session, reason });
        return session;
    }
    
    /**
     * Resume session
     */
    resumeSession(sessionId) {
        const session = this.activeSessions.get(sessionId);
        if (!session) return null;
        
        if (session.status !== 'paused') {
            console.warn(`Cannot resume session ${sessionId} with status ${session.status}`);
            return null;
        }
        
        session.status = 'uploading';
        session.resumedAt = Date.now();
        session.lastActivity = Date.now();
        delete session.pausedAt;
        delete session.pauseReason;
        
        if (this.config.enablePersistence) {
            this.persistSession(session);
        }
        
        this.dispatchEvent('sessionResumed', session);
        return session;
    }
    
    /**
     * Remove session
     */
    removeSession(sessionId) {
        const session = this.activeSessions.get(sessionId);
        
        this.activeSessions.delete(sessionId);
        this.persistedSessions.delete(sessionId);
        this.recoveryInProgress.delete(sessionId);
        
        if (this.config.enablePersistence) {
            this.removePersistedSession(sessionId);
        }
        
        if (session) {
            this.dispatchEvent('sessionRemoved', session);
        }
        
        return session;
    }
    
    /**
     * Get session by ID
     */
    getSession(sessionId) {
        return this.activeSessions.get(sessionId) || this.persistedSessions.get(sessionId);
    }
    
    /**
     * Get all active sessions
     */
    getActiveSessions() {
        return Array.from(this.activeSessions.values());
    }
    
    /**
     * Get recoverable sessions
     */
    getRecoverableSessions() {
        const recoverableSessions = [];
        
        this.persistedSessions.forEach(session => {
            if (this.isSessionRecoverable(session)) {
                recoverableSessions.push(session);
            }
        });
        
        return recoverableSessions;
    }
    
    /**
     * Check if session is recoverable
     */
    isSessionRecoverable(session) {
        if (!session) return false;
        
        // Check if session is not too old
        const age = Date.now() - session.createdAt;
        if (age > this.config.maxSessionAge) return false;
        
        // Check if session is in a recoverable state
        const recoverableStates = ['uploading', 'paused', 'pending'];
        if (!recoverableStates.includes(session.status)) return false;
        
        // Check if session has some progress
        return session.uploadedChunks > 0 || session.uploadedBytes > 0;
    }
    
    /**
     * Recover session
     */
    async recoverSession(sessionId) {
        if (this.recoveryInProgress.has(sessionId)) {
            console.log(`Recovery already in progress for session ${sessionId}`);
            return null;
        }
        
        const session = this.persistedSessions.get(sessionId);
        if (!session || !this.isSessionRecoverable(session)) {
            console.warn(`Session ${sessionId} is not recoverable`);
            return null;
        }
        
        this.recoveryInProgress.add(sessionId);
        
        try {
            // Check session status on server
            const serverStatus = await this.checkServerSessionStatus(sessionId);
            
            if (serverStatus && serverStatus.exists) {
                // Merge server state with local state
                const mergedSession = this.mergeSessionStates(session, serverStatus.data);
                
                // Move to active sessions
                this.activeSessions.set(sessionId, mergedSession);
                
                this.dispatchEvent('sessionRecovered', mergedSession);
                return mergedSession;
            } else {
                // Server session doesn't exist, clean up local session
                this.removeSession(sessionId);
                return null;
            }
        } catch (error) {
            console.error(`Failed to recover session ${sessionId}:`, error);
            this.dispatchEvent('sessionRecoveryFailed', { sessionId, error });
            return null;
        } finally {
            this.recoveryInProgress.delete(sessionId);
        }
    }
    
    /**
     * Auto-recover sessions on page load
     */
    async autoRecoverSessions() {
        const recoverableSessions = this.getRecoverableSessions();
        
        if (recoverableSessions.length === 0) {
            return [];
        }
        
        console.log(`Found ${recoverableSessions.length} recoverable sessions`);
        
        const recoveryPromises = recoverableSessions.map(session => 
            this.recoverSession(session.id).catch(error => {
                console.error(`Failed to auto-recover session ${session.id}:`, error);
                return null;
            })
        );
        
        const recoveredSessions = await Promise.all(recoveryPromises);
        const successfulRecoveries = recoveredSessions.filter(session => session !== null);
        
        if (successfulRecoveries.length > 0) {
            this.dispatchEvent('sessionsAutoRecovered', successfulRecoveries);
        }
        
        return successfulRecoveries;
    }
    
    /**
     * Persist session to localStorage
     */
    persistSession(session) {
        if (!this.config.enablePersistence) return;
        
        try {
            // Convert Map to Object for serialization
            const sessionData = {
                ...session,
                chunks: Object.fromEntries(session.chunks)
            };
            
            this.persistedSessions.set(session.id, sessionData);
            
            // Save to localStorage
            const allSessions = Object.fromEntries(this.persistedSessions);
            localStorage.setItem(this.config.storageKey, JSON.stringify(allSessions));
            
        } catch (error) {
            console.error('Failed to persist session:', error);
        }
    }
    
    /**
     * Load persisted sessions from localStorage
     */
    loadPersistedSessions() {
        try {
            const stored = localStorage.getItem(this.config.storageKey);
            if (!stored) return;
            
            const sessions = JSON.parse(stored);
            
            Object.entries(sessions).forEach(([sessionId, sessionData]) => {
                // Convert chunks back to Map
                sessionData.chunks = new Map(Object.entries(sessionData.chunks || {}));
                this.persistedSessions.set(sessionId, sessionData);
            });
            
            console.log(`Loaded ${this.persistedSessions.size} persisted sessions`);
            
        } catch (error) {
            console.error('Failed to load persisted sessions:', error);
            // Clear corrupted data
            localStorage.removeItem(this.config.storageKey);
        }
    }
    
    /**
     * Remove persisted session
     */
    removePersistedSession(sessionId) {
        this.persistedSessions.delete(sessionId);
        
        try {
            const allSessions = Object.fromEntries(this.persistedSessions);
            localStorage.setItem(this.config.storageKey, JSON.stringify(allSessions));
        } catch (error) {
            console.error('Failed to remove persisted session:', error);
        }
    }
    
    /**
     * Clean up expired sessions
     */
    cleanupExpiredSessions() {
        const now = Date.now();
        const expiredSessions = [];
        
        // Clean up active sessions
        this.activeSessions.forEach((session, sessionId) => {
            const age = now - session.lastActivity;
            if (age > this.config.maxSessionAge) {
                expiredSessions.push(session);
                this.activeSessions.delete(sessionId);
            }
        });
        
        // Clean up persisted sessions
        this.persistedSessions.forEach((session, sessionId) => {
            const age = now - session.createdAt;
            if (age > this.config.maxSessionAge) {
                expiredSessions.push(session);
                this.persistedSessions.delete(sessionId);
            }
        });
        
        // Update localStorage
        if (this.config.enablePersistence && expiredSessions.length > 0) {
            try {
                const allSessions = Object.fromEntries(this.persistedSessions);
                localStorage.setItem(this.config.storageKey, JSON.stringify(allSessions));
            } catch (error) {
                console.error('Failed to update persisted sessions after cleanup:', error);
            }
        }
        
        if (expiredSessions.length > 0) {
            console.log(`Cleaned up ${expiredSessions.length} expired sessions`);
            this.dispatchEvent('sessionsCleanedUp', expiredSessions);
        }
        
        return expiredSessions;
    }
    
    /**
     * Start automatic cleanup timer
     */
    startCleanupTimer() {
        if (this.cleanupTimer) {
            clearInterval(this.cleanupTimer);
        }
        
        this.cleanupTimer = setInterval(() => {
            this.cleanupExpiredSessions();
        }, this.config.cleanupInterval);
    }
    
    /**
     * Stop cleanup timer
     */
    stopCleanupTimer() {
        if (this.cleanupTimer) {
            clearInterval(this.cleanupTimer);
            this.cleanupTimer = null;
        }
    }
    
    /**
     * Setup beforeunload handler to persist active sessions
     */
    setupBeforeUnloadHandler() {
        window.addEventListener('beforeunload', () => {
            // Persist all active sessions before page unload
            this.activeSessions.forEach(session => {
                if (session.status === 'uploading') {
                    session.status = 'paused';
                    session.pausedAt = Date.now();
                    session.pauseReason = 'page_unload';
                }
                this.persistSession(session);
            });
        });
    }
    
    /**
     * Setup auto-recovery on page load
     */
    setupAutoRecovery() {
        // Auto-recover sessions when page loads
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                this.autoRecoverSessions();
            }, 1000); // Delay to ensure other components are initialized
        });
    }
    
    /**
     * Check session status on server
     */
    async checkServerSessionStatus(sessionId) {
        try {
            const response = await fetch(`/api/upload/session/${sessionId}/status`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                return await response.json();
            }
            
            return null;
        } catch (error) {
            console.error('Failed to check server session status:', error);
            return null;
        }
    }
    
    /**
     * Merge local and server session states
     */
    mergeSessionStates(localSession, serverSession) {
        return {
            ...localSession,
            // Prefer server data for critical fields
            uploadedChunks: Math.max(localSession.uploadedChunks, serverSession.uploaded_chunks || 0),
            status: serverSession.status || localSession.status,
            // Merge chunk data
            chunks: this.mergeChunkData(localSession.chunks, serverSession.chunks || {}),
            // Update timestamps
            lastActivity: Date.now(),
            recoveredAt: Date.now()
        };
    }
    
    /**
     * Merge chunk data from local and server
     */
    mergeChunkData(localChunks, serverChunks) {
        const mergedChunks = new Map(localChunks);
        
        Object.entries(serverChunks).forEach(([chunkIndex, chunkData]) => {
            const index = parseInt(chunkIndex);
            const existing = mergedChunks.get(index);
            
            if (!existing || chunkData.status === 'uploaded') {
                mergedChunks.set(index, {
                    index: index,
                    status: chunkData.status,
                    hash: chunkData.hash,
                    size: chunkData.size,
                    uploadedAt: chunkData.uploaded_at ? new Date(chunkData.uploaded_at).getTime() : Date.now()
                });
            }
        });
        
        return mergedChunks;
    }
    
    /**
     * Generate unique session ID
     */
    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    /**
     * Get session statistics
     */
    getSessionStats() {
        const activeSessions = this.getActiveSessions();
        const recoverableSessions = this.getRecoverableSessions();
        
        return {
            active: activeSessions.length,
            recoverable: recoverableSessions.length,
            persisted: this.persistedSessions.size,
            byStatus: this.getSessionsByStatus(),
            totalUploadedBytes: activeSessions.reduce((total, session) => total + session.uploadedBytes, 0)
        };
    }
    
    /**
     * Get sessions grouped by status
     */
    getSessionsByStatus() {
        const statusGroups = {};
        
        this.activeSessions.forEach(session => {
            if (!statusGroups[session.status]) {
                statusGroups[session.status] = [];
            }
            statusGroups[session.status].push(session);
        });
        
        return statusGroups;
    }
    
    /**
     * Dispatch custom events
     */
    dispatchEvent(eventName, data) {
        const event = new CustomEvent(`uploadSession:${eventName}`, {
            detail: data
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Destroy session manager
     */
    destroy() {
        this.stopCleanupTimer();
        this.activeSessions.clear();
        this.persistedSessions.clear();
        this.recoveryInProgress.clear();
    }
}

// Make available globally
window.UploadSessionManager = UploadSessionManager;

export default UploadSessionManager;