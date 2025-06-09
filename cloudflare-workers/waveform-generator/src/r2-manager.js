/**
 * R2FileManager class for handling audio file operations with Cloudflare R2
 */
export class R2FileManager {
    constructor(r2Bucket) {
        this.bucket = r2Bucket;
    }

    /**
     * Download audio file from R2 using file URL
     * @param {string} fileUrl - The R2 file URL or file path
     * @returns {Promise<ArrayBuffer>} Audio file buffer
     */
    async downloadFile(fileUrl) {
        try {
            // Extract file path from URL if it's a full URL
            const filePath = this.extractFilePathFromUrl(fileUrl);

            // Get the file from R2
            const object = await this.bucket.get(filePath);

            if (object === null) {
                throw new Error(`File not found in R2: ${filePath}`);
            }

            // Return the file as ArrayBuffer
            return await object.arrayBuffer();

        } catch (error) {
            console.error('R2 download error:', error);
            throw new Error(`Failed to download file from R2: ${error.message}`);
        }
    }

    /**
     * Extract file path from full URL or return path as-is
     * @param {string} fileUrl - File URL or path
     * @returns {string} Extracted file path
     */
    extractFilePathFromUrl(fileUrl) {
        try {
            // If it's a full URL, extract the path
            if (fileUrl.startsWith('http://') || fileUrl.startsWith('https://')) {
                const url = new URL(fileUrl);
                let path = decodeURIComponent(url.pathname.substring(1));

                // If the path starts with the bucket name, remove it
                // This handles R2 presigned URLs that include the bucket name in the path
                if (path.includes('/')) {
                    const pathParts = path.split('/');
                    // Remove bucket name if it's the first part of the path
                    // Common bucket names: mixpitch-dev, mixpitch-audio-files, etc.
                    if (pathParts[0].startsWith('mixpitch') || pathParts[0].match(/^[a-f0-9]{32}$/)) {
                        // Skip the first part (bucket name) and rejoin the rest
                        path = pathParts.slice(1).join('/');
                    }
                }

                console.log('Extracted file path:', {
                    original_url: fileUrl,
                    pathname: url.pathname,
                    extracted_path: path
                });

                return path;
            }

            // If it's already a path, return as-is
            return fileUrl;

        } catch (error) {
            console.warn('URL parsing failed, using as file path:', error);
            return fileUrl;
        }
    }

    /**
     * Check if file exists in R2
     * @param {string} filePath - File path in R2
     * @returns {Promise<boolean>} True if file exists
     */
    async fileExists(filePath) {
        try {
            const object = await this.bucket.head(filePath);
            return object !== null;
        } catch (error) {
            return false;
        }
    }

    /**
     * Get file metadata from R2
     * @param {string} filePath - File path in R2
     * @returns {Promise<Object>} File metadata
     */
    async getFileMetadata(filePath) {
        try {
            const object = await this.bucket.head(filePath);

            if (object === null) {
                throw new Error(`File not found: ${filePath}`);
            }

            return {
                size: object.size,
                lastModified: object.uploaded,
                etag: object.etag,
                contentType: object.httpMetadata?.contentType || 'application/octet-stream'
            };

        } catch (error) {
            console.error('Failed to get file metadata:', error);
            throw new Error(`Failed to get file metadata: ${error.message}`);
        }
    }

    /**
     * Validate file size against limits
     * @param {string} filePath - File path in R2
     * @param {number} maxSizeMB - Maximum allowed size in MB
     * @returns {Promise<boolean>} True if file size is within limits
     */
    async validateFileSize(filePath, maxSizeMB) {
        try {
            const metadata = await this.getFileMetadata(filePath);
            const fileSizeMB = metadata.size / (1024 * 1024);

            return fileSizeMB <= maxSizeMB;

        } catch (error) {
            console.error('File size validation error:', error);
            return false;
        }
    }

    /**
     * Download file with streaming for large files
     * @param {string} fileUrl - The R2 file URL or path
     * @param {number} chunkSize - Chunk size for streaming (default 1MB)
     * @returns {Promise<ArrayBuffer>} Complete file buffer
     */
    async downloadFileStreaming(fileUrl, chunkSize = 1024 * 1024) {
        try {
            const filePath = this.extractFilePathFromUrl(fileUrl);
            const metadata = await this.getFileMetadata(filePath);
            const totalSize = metadata.size;

            const chunks = [];
            let downloaded = 0;

            while (downloaded < totalSize) {
                const end = Math.min(downloaded + chunkSize - 1, totalSize - 1);

                const object = await this.bucket.get(filePath, {
                    range: { offset: downloaded, length: end - downloaded + 1 }
                });

                if (object === null) {
                    throw new Error(`Failed to download chunk at offset ${downloaded}`);
                }

                const chunk = await object.arrayBuffer();
                chunks.push(chunk);
                downloaded += chunk.byteLength;

                console.log(`Downloaded ${downloaded}/${totalSize} bytes (${Math.round(downloaded / totalSize * 100)}%)`);
            }

            // Combine all chunks into a single ArrayBuffer
            return this.combineArrayBuffers(chunks);

        } catch (error) {
            console.error('Streaming download error:', error);
            throw new Error(`Failed to download file with streaming: ${error.message}`);
        }
    }

    /**
     * Combine multiple ArrayBuffers into one
     * @param {Array<ArrayBuffer>} buffers - Array of buffers to combine
     * @returns {ArrayBuffer} Combined buffer
     */
    combineArrayBuffers(buffers) {
        const totalLength = buffers.reduce((sum, buffer) => sum + buffer.byteLength, 0);
        const result = new Uint8Array(totalLength);

        let offset = 0;
        for (const buffer of buffers) {
            result.set(new Uint8Array(buffer), offset);
            offset += buffer.byteLength;
        }

        return result.buffer;
    }

    /**
     * Get file extension from file path or URL
     * @param {string} fileUrl - File URL or path
     * @returns {string} File extension (lowercase)
     */
    getFileExtension(fileUrl) {
        const filePath = this.extractFilePathFromUrl(fileUrl);
        const parts = filePath.split('.');
        return parts.length > 1 ? parts.pop().toLowerCase() : '';
    }

    /**
     * Validate if the file is an audio file based on extension
     * @param {string} fileUrl - File URL or path
     * @returns {boolean} True if it's an audio file
     */
    isAudioFile(fileUrl) {
        const extension = this.getFileExtension(fileUrl);
        const audioExtensions = ['mp3', 'wav', 'flac', 'aac', 'm4a', 'ogg', 'opus', 'wma'];
        return audioExtensions.includes(extension);
    }
} 