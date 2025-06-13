import { EnhancedAudioProcessor } from './enhanced-audio-processor.js';

/**
 * Main Cloudflare Worker for audio waveform generation
 */
export default {
    async fetch(request, env, ctx) {
        // Handle CORS preflight requests
        if (request.method === 'OPTIONS') {
            return new Response(null, {
                status: 200,
                headers: {
                    'Access-Control-Allow-Origin': '*',
                    'Access-Control-Allow-Methods': 'POST, OPTIONS',
                    'Access-Control-Allow-Headers': 'Content-Type, Authorization',
                    'Access-Control-Max-Age': '86400',
                },
            });
        }

        // Only allow POST requests
        if (request.method !== 'POST') {
            return new Response(JSON.stringify({
                error: 'Method not allowed. Use POST.',
                status: 405
            }), {
                status: 405,
                headers: {
                    'Content-Type': 'application/json',
                    'Access-Control-Allow-Origin': '*',
                },
            });
        }

        try {
            const startTime = Date.now();
            const requestData = await request.json();

            // Extract parameters
            const fileUrl = requestData.file_url;
            const peaksCount = parseInt(requestData.peaks_count) || parseInt(env.DEFAULT_PEAKS_COUNT) || 200;
            const debug = requestData.debug || false; // Add debug flag

            // Validate input
            if (!fileUrl) {
                return new Response(JSON.stringify({
                    error: 'Missing required parameter: file_url',
                    status: 400
                }), {
                    status: 400,
                    headers: {
                        'Content-Type': 'application/json',
                        'Access-Control-Allow-Origin': '*',
                    },
                });
            }

            // Validate peaks count
            if (peaksCount < 1 || peaksCount > 1000) {
                return new Response(JSON.stringify({
                    error: 'peaks_count must be between 1 and 1000',
                    status: 400
                }), {
                    status: 400,
                    headers: {
                        'Content-Type': 'application/json',
                        'Access-Control-Allow-Origin': '*',
                    },
                });
            }

            console.log('Processing audio file:', {
                url: fileUrl,
                peaks_count: peaksCount,
                debug: debug,
                environment: env.ENVIRONMENT
            });

            // Download the audio file
            console.log('Downloading audio file from URL...');
            const audioResponse = await fetch(fileUrl);

            if (!audioResponse.ok) {
                throw new Error(`Failed to download audio file: ${audioResponse.status} ${audioResponse.statusText}`);
            }

            const audioBuffer = await audioResponse.arrayBuffer();

            // Check file size
            const maxSizeMB = parseInt(env.MAX_FILE_SIZE_MB) || 50;
            const fileSizeMB = audioBuffer.byteLength / (1024 * 1024);

            if (fileSizeMB > maxSizeMB) {
                return new Response(JSON.stringify({
                    error: `File size exceeds ${maxSizeMB}MB limit`,
                    status: 413
                }), {
                    status: 413,
                    headers: {
                        'Content-Type': 'application/json',
                        'Access-Control-Allow-Origin': '*',
                    },
                });
            }

            console.log(`Downloaded ${fileSizeMB.toFixed(2)}MB audio file`);

            // Process the audio
            const audioProcessor = new EnhancedAudioProcessor();
            const result = await audioProcessor.processAudio(audioBuffer, peaksCount);

            const processingTime = Date.now() - startTime;

            // Build response
            const response = {
                status: 'success',
                duration: result.duration,
                waveform_peaks: result.peaks,
                peaks: result.peaks, // AWS Lambda compatibility
                metadata: {
                    processing_time_ms: processingTime,
                    file_size_mb: fileSizeMB,
                    peaks_count: result.peaks.length,
                    worker_version: '1.0.0',
                    environment: env.ENVIRONMENT
                }
            };

            // Add debug information if requested
            if (debug) {
                response.debug_info = {
                    buffer_size: audioBuffer.byteLength,
                    sample_statistics: {
                        // These will be filled by the processor if debug is enabled
                        note: 'Enable detailed logging in AudioProcessor for more debug info'
                    }
                };
            }

            return new Response(JSON.stringify(response), {
                status: 200,
                headers: {
                    'Content-Type': 'application/json',
                    'Access-Control-Allow-Origin': '*',
                    'Cache-Control': 'public, max-age=3600', // Cache for 1 hour
                },
            });

        } catch (error) {
            console.error('Error processing audio:', error);

            return new Response(JSON.stringify({
                error: error.message || 'Internal server error',
                status: 500,
                timestamp: new Date().toISOString()
            }), {
                status: 500,
                headers: {
                    'Content-Type': 'application/json',
                    'Access-Control-Allow-Origin': '*',
                },
            });
        }
    },
};

/**
 * Handle CORS preflight requests
 * @returns {Response} CORS response
 */
function handleCORS() {
    return new Response(null, {
        status: 204,
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'POST, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type, Authorization',
            'Access-Control-Max-Age': '86400'
        }
    });
}

/**
 * Create a successful JSON response
 * @param {Object} data - Response data
 * @returns {Response} Success response
 */
function createSuccessResponse(data) {
    return new Response(JSON.stringify(data), {
        status: 200,
        headers: {
            'Content-Type': 'application/json',
            'Access-Control-Allow-Origin': '*',
            'Cache-Control': 'public, max-age=3600' // Cache successful responses for 1 hour
        }
    });
}

/**
 * Create an error JSON response
 * @param {string} message - Error message
 * @param {number} status - HTTP status code
 * @param {Object} metadata - Additional metadata
 * @returns {Response} Error response
 */
function createErrorResponse(message, status = 500, metadata = {}) {
    const errorResponse = {
        error: message,
        status: status,
        timestamp: new Date().toISOString(),
        ...metadata
    };

    return new Response(JSON.stringify(errorResponse), {
        status: status,
        headers: {
            'Content-Type': 'application/json',
            'Access-Control-Allow-Origin': '*'
        }
    });
}

/**
 * Handle uncaught exceptions
 */
addEventListener('unhandledrejection', event => {
    console.error('Unhandled promise rejection:', event.reason);
});

addEventListener('error', event => {
    console.error('Uncaught error:', event.error);
}); 