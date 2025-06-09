import { FFmpeg } from '@ffmpeg/ffmpeg';
import { fetchFile } from '@ffmpeg/util';

/**
 * AudioProcessor class for generating waveforms using FFmpeg WASM
 */
export class AudioProcessor {
    constructor() {
        this.ffmpeg = null;
        this.isLoaded = false;
    }

    /**
     * Initialize FFmpeg WASM
     */
    async initialize() {
        if (this.isLoaded) return;

        try {
            this.ffmpeg = new FFmpeg();

            // Load FFmpeg WASM binaries directly from CDN (required for Cloudflare Workers)
            const baseURL = 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/esm';
            await this.ffmpeg.load({
                coreURL: `${baseURL}/ffmpeg-core.js`,
                wasmURL: `${baseURL}/ffmpeg-core.wasm`,
            });

            this.isLoaded = true;
            console.log('FFmpeg WASM initialized successfully');
        } catch (error) {
            console.error('Failed to initialize FFmpeg WASM:', error);
            throw new Error(`FFmpeg initialization failed: ${error.message}`);
        }
    }

    /**
     * Process audio buffer and generate waveform data
     * @param {ArrayBuffer} audioBuffer - The audio file buffer
     * @param {number} peaksCount - Number of peaks to generate (default: 200)
     * @returns {Promise<{duration: number, peaks: Array}>}
     */
    async processAudio(audioBuffer, peaksCount = 200) {
        if (!this.isLoaded) {
            await this.initialize();
        }

        try {
            const inputFileName = 'input_audio';
            const outputFileName = 'output.raw';

            // Store audioBuffer for fallback duration estimation
            this.audioBuffer = audioBuffer;

            // Write audio buffer to FFmpeg's virtual filesystem
            await this.ffmpeg.writeFile(inputFileName, new Uint8Array(audioBuffer));

            // Convert to raw PCM data for waveform analysis
            await this.convertToRawPCM(inputFileName, outputFileName);

            // Read the raw PCM data
            const rawData = await this.ffmpeg.readFile(outputFileName);

            // Calculate duration from raw PCM data (more efficient)
            const duration = this.calculateDurationFromPCM(rawData);

            // Generate waveform peaks from raw PCM data
            const peaks = this.generateWaveformPeaks(rawData, peaksCount);

            // Clean up temporary files
            await this.cleanup([inputFileName, outputFileName]);

            return {
                duration: parseFloat(duration.toFixed(2)),
                peaks: peaks,                    // Match AWS Lambda format exactly
                waveform_peaks: peaks           // Keep Laravel compatibility
            };

        } catch (error) {
            console.error('Audio processing error:', error);
            throw new Error(`Audio processing failed: ${error.message}`);
        }
    }

    /**
     * Calculate duration from raw PCM data
     * @param {Uint8Array} rawData - Raw PCM data
     * @returns {number} Duration in seconds
     */
    calculateDurationFromPCM(rawData) {
        // Raw PCM is 16-bit (2 bytes per sample), mono, 44.1kHz
        const totalSamples = rawData.length / 2;
        const sampleRate = 44100;
        const duration = totalSamples / sampleRate;

        console.log(`Calculated duration from PCM: ${duration} seconds`);
        return duration;
    }

    /**
     * Convert audio to raw PCM format for waveform analysis
     * @param {string} inputFileName - Input file name
     * @param {string} outputFileName - Output file name
     */
    async convertToRawPCM(inputFileName, outputFileName) {
        // Use EXACT same FFmpeg command as AWS Lambda (no -acodec parameter)
        await this.ffmpeg.exec([
            '-i', inputFileName,
            '-f', 's16le',        // 16-bit signed little-endian PCM
            '-ac', '1',           // Mono channel
            '-ar', '44100',       // 44.1kHz sample rate
            outputFileName
        ]);
    }

    /**
     * Generate waveform peaks from raw PCM data
     * @param {Uint8Array} rawData - Raw PCM data
     * @param {number} peaksCount - Number of peaks to generate
     * @returns {Array<Array<number>>} Array of [min, max] peak pairs
     */
    generateWaveformPeaks(rawData, peaksCount) {
        // Convert raw bytes to 16-bit signed integers (matching AWS Lambda)
        const samples = new Int16Array(rawData.buffer);
        console.log(`Converted to ${samples.length} samples`);
        console.log(`Raw PCM data size: ${rawData.length} bytes`);

        // Debug: Log some sample statistics
        let minSample = samples[0], maxSample = samples[0];
        let sampleSum = 0;
        for (let i = 0; i < Math.min(samples.length, 1000); i++) {
            minSample = Math.min(minSample, samples[i]);
            maxSample = Math.max(maxSample, samples[i]);
            sampleSum += Math.abs(samples[i]);
        }
        console.log(`Sample range: ${minSample} to ${maxSample}, avg abs of first 1000: ${sampleSum / Math.min(samples.length, 1000)}`);

        // EXACT AWS Lambda replication: Take absolute values for waveform first
        // Note: This modifies the array in-place like numpy does
        for (let i = 0; i < samples.length; i++) {
            samples[i] = Math.abs(samples[i]);
        }

        // Debug: Log absolute sample statistics
        let maxAbsSample = 0;
        let absSampleSum = 0;
        for (let i = 0; i < Math.min(samples.length, 1000); i++) {
            maxAbsSample = Math.max(maxAbsSample, samples[i]);
            absSampleSum += samples[i];
        }
        console.log(`Max absolute sample in first 1000: ${maxAbsSample}, avg: ${absSampleSum / Math.min(samples.length, 1000)}`);

        // Create segments and take max in each segment (EXACT AWS Lambda logic)
        const samplesPerPeak = Math.max(1, Math.floor(samples.length / peaksCount)); // Use floor like Python //
        const peaks = [];

        console.log(`Calculating ${peaksCount} peaks with ${samplesPerPeak} samples per peak`);

        // Debug: Track peak statistics
        let maxPeakValue = 0;
        let totalPeakValue = 0;
        let nonZeroPeaks = 0;

        for (let i = 0; i < peaksCount; i++) {
            const start = i * samplesPerPeak;
            const end = Math.min(start + samplesPerPeak, samples.length);

            if (start >= samples.length) {
                // Fill remaining peaks with zeros if we've run out of samples
                peaks.push([0, 0]);
                continue;
            }

            // Extract segment exactly like AWS Lambda: samples[start:end]
            let maxValue = 0;
            for (let j = start; j < end; j++) {
                maxValue = Math.max(maxValue, samples[j]);
            }

            // Normalize to 0-1 range and create symmetrical peaks (EXACT AWS Lambda)
            const normalizedMax = maxValue / 32768.0;
            peaks.push([-normalizedMax, normalizedMax]);

            // Debug tracking
            if (normalizedMax > 0) {
                nonZeroPeaks++;
                totalPeakValue += normalizedMax;
                maxPeakValue = Math.max(maxPeakValue, normalizedMax);
            }
        }

        console.log(`Generated ${peaks.length} peaks`);
        console.log(`Peak stats: Max=${maxPeakValue}, Avg=${totalPeakValue / nonZeroPeaks}, NonZero=${nonZeroPeaks}/${peaksCount}`);
        console.log(`First few peaks: ${JSON.stringify(peaks.slice(0, 5))}`);

        return peaks;
    }

    /**
     * Estimate duration from audio buffer size (fallback method)
     * @param {ArrayBuffer} audioBuffer - Audio buffer
     * @returns {number} Estimated duration in seconds
     */
    estimateDurationFromBuffer(audioBuffer) {
        // Very rough estimation based on typical audio bitrates
        // This is a fallback when proper duration extraction fails
        const sizeInMB = audioBuffer.byteLength / (1024 * 1024);

        // Assume average bitrate of 128kbps for estimation
        const estimatedSeconds = (sizeInMB * 8 * 1024) / 128;

        return Math.max(1, estimatedSeconds); // At least 1 second
    }

    /**
     * Clean up temporary files from FFmpeg filesystem
     * @param {Array<string>} fileNames - Array of file names to delete
     */
    async cleanup(fileNames) {
        for (const fileName of fileNames) {
            try {
                await this.ffmpeg.deleteFile(fileName);
            } catch (error) {
                // Ignore cleanup errors
                console.warn(`Failed to delete temporary file ${fileName}:`, error);
            }
        }
    }

    /**
     * Get supported audio formats
     * @returns {Array<string>} Array of supported file extensions
     */
    static getSupportedFormats() {
        return ['mp3', 'wav', 'flac', 'aac', 'm4a', 'ogg', 'opus', 'wma'];
    }

    /**
     * Validate if file format is supported
     * @param {string} fileName - File name to check
     * @returns {boolean} True if format is supported
     */
    static isFormatSupported(fileName) {
        const extension = fileName.split('.').pop()?.toLowerCase();
        return this.getSupportedFormats().includes(extension);
    }
} 