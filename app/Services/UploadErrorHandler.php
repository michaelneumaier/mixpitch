<?php

namespace App\Services;

use App\Models\UploadSession;
use App\Models\UploadChunk;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Handles various error scenarios during file uploads
 * Provides recovery mechanisms and cleanup operations
 */
class UploadErrorHandler
{
    protected ChunkProcessingService $chunkProcessor;

    public function __construct(ChunkProcessingService $chunkProcessor)
    {
        $this->chunkProcessor = $chunkProcessor;
    }

    /**
     * Handle chunk upload errors with retry logic
     */
    public function handleChunkError(UploadSession $session, int $chunkIndex, Exception $error): array
    {
        Log::warning('Chunk upload error', [
            'session_id' => $session->id,
            'chunk_index' => $chunkIndex,
            'error' => $error->getMessage(),
            'user_id' => $session->user_id
        ]);

        // Find the specific chunk
        $chunk = $session->chunks()->where('chunk_index', $chunkIndex)->first();
        
        if (!$chunk) {
            return [
                'success' => false,
                'error' => 'Chunk not found',
                'retry' => false,
                'action' => 'restart_upload'
            ];
        }

        // Increment retry count
        $retryCount = $chunk->metadata['retry_count'] ?? 0;
        $maxRetries = 3; // From requirements

        if ($retryCount >= $maxRetries) {
            // Mark chunk as failed
            $chunk->update([
                'status' => UploadChunk::STATUS_FAILED,
                'metadata' => array_merge($chunk->metadata ?? [], [
                    'retry_count' => $retryCount,
                    'last_error' => $error->getMessage(),
                    'failed_at' => now()
                ])
            ]);

            // Check if this failure should fail the entire session
            $failedChunks = $session->chunks()->where('status', UploadChunk::STATUS_FAILED)->count();
            if ($failedChunks > ($session->total_chunks * 0.1)) { // More than 10% failed
                $this->failSession($session, 'Too many chunk failures');
                return [
                    'success' => false,
                    'error' => 'Upload failed due to multiple chunk errors',
                    'retry' => false,
                    'action' => 'restart_upload'
                ];
            }

            return [
                'success' => false,
                'error' => 'Chunk failed after maximum retries',
                'retry' => false,
                'action' => 'retry_chunk',
                'chunk_index' => $chunkIndex
            ];
        }

        // Update chunk for retry
        $chunk->update([
            'status' => UploadChunk::STATUS_PENDING,
            'metadata' => array_merge($chunk->metadata ?? [], [
                'retry_count' => $retryCount + 1,
                'last_error' => $error->getMessage(),
                'retry_at' => now()
            ])
        ]);

        // Calculate exponential backoff delay
        $delay = min(pow(2, $retryCount) * 1000, 30000); // Max 30 seconds

        return [
            'success' => false,
            'error' => $error->getMessage(),
            'retry' => true,
            'action' => 'retry_chunk',
            'chunk_index' => $chunkIndex,
            'delay' => $delay,
            'retry_count' => $retryCount + 1
        ];
    }

    /**
     * Handle file assembly errors with cleanup
     */
    public function handleAssemblyError(UploadSession $session, Exception $error): array
    {
        Log::error('File assembly error', [
            'session_id' => $session->id,
            'error' => $error->getMessage(),
            'user_id' => $session->user_id,
            'uploaded_chunks' => $session->uploaded_chunks,
            'total_chunks' => $session->total_chunks
        ]);

        // Check if we can retry assembly
        $assemblyRetries = $session->metadata['assembly_retries'] ?? 0;
        $maxAssemblyRetries = 2;

        if ($assemblyRetries >= $maxAssemblyRetries) {
            $this->failSession($session, 'Assembly failed after retries: ' . $error->getMessage());
            
            return [
                'success' => false,
                'error' => 'File assembly failed permanently',
                'retry' => false,
                'action' => 'restart_upload'
            ];
        }

        // Update session for assembly retry
        $session->update([
            'status' => UploadSession::STATUS_UPLOADING,
            'metadata' => array_merge($session->metadata ?? [], [
                'assembly_retries' => $assemblyRetries + 1,
                'last_assembly_error' => $error->getMessage(),
                'assembly_retry_at' => now()
            ])
        ]);

        return [
            'success' => false,
            'error' => $error->getMessage(),
            'retry' => true,
            'action' => 'retry_assembly',
            'delay' => 5000, // 5 second delay
            'retry_count' => $assemblyRetries + 1
        ];
    }

    /**
     * Handle validation errors with detailed feedback
     */
    public function handleValidationError(array $files, ValidationException $error): array
    {
        $errors = $error->errors();
        $fileErrors = [];

        foreach ($files as $index => $file) {
            $fileName = $file['name'] ?? "File " . ($index + 1);
            $fileErrors[$fileName] = [];

            // Check for specific validation errors
            foreach ($errors as $field => $messages) {
                if (strpos($field, "files.{$index}") === 0) {
                    $fileErrors[$fileName] = array_merge($fileErrors[$fileName], $messages);
                }
            }

            // Add generic errors if no specific ones found
            if (empty($fileErrors[$fileName]) && !empty($errors)) {
                $fileErrors[$fileName] = ['File validation failed'];
            }
        }

        Log::info('File validation errors', [
            'file_count' => count($files),
            'errors' => $fileErrors
        ]);

        return [
            'success' => false,
            'error' => 'File validation failed',
            'file_errors' => $fileErrors,
            'retry' => false,
            'action' => 'fix_validation'
        ];
    }

    /**
     * Clean up failed upload session
     */
    public function cleanupFailedUpload(UploadSession $session): void
    {
        Log::info('Cleaning up failed upload session', [
            'session_id' => $session->id,
            'user_id' => $session->user_id
        ]);

        try {
            // Clean up chunk files
            $this->chunkProcessor->cleanupChunks($session->id);

            // Update session status
            $session->update([
                'status' => UploadSession::STATUS_FAILED,
                'metadata' => array_merge($session->metadata ?? [], [
                    'cleaned_up_at' => now(),
                    'cleanup_reason' => 'Failed upload cleanup'
                ])
            ]);

            // Mark all chunks as failed
            $session->chunks()->update([
                'status' => UploadChunk::STATUS_FAILED
            ]);

        } catch (Exception $e) {
            Log::error('Error during upload cleanup', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle network timeout errors
     */
    public function handleNetworkTimeout(UploadSession $session, int $chunkIndex = null): array
    {
        Log::warning('Network timeout detected', [
            'session_id' => $session->id,
            'chunk_index' => $chunkIndex,
            'user_id' => $session->user_id
        ]);

        if ($chunkIndex !== null) {
            // Handle specific chunk timeout
            return $this->handleChunkError($session, $chunkIndex, new Exception('Network timeout'));
        }

        // Handle session-level timeout
        $timeoutCount = $session->metadata['timeout_count'] ?? 0;
        
        if ($timeoutCount >= 5) {
            $this->failSession($session, 'Multiple network timeouts');
            return [
                'success' => false,
                'error' => 'Upload failed due to network issues',
                'retry' => false,
                'action' => 'restart_upload'
            ];
        }

        $session->update([
            'metadata' => array_merge($session->metadata ?? [], [
                'timeout_count' => $timeoutCount + 1,
                'last_timeout_at' => now()
            ])
        ]);

        return [
            'success' => false,
            'error' => 'Network timeout',
            'retry' => true,
            'action' => 'resume_upload',
            'delay' => 3000 // 3 second delay
        ];
    }

    /**
     * Handle storage limit exceeded errors
     */
    public function handleStorageLimitError(UploadSession $session, string $limitType = 'user'): array
    {
        Log::warning('Storage limit exceeded', [
            'session_id' => $session->id,
            'user_id' => $session->user_id,
            'limit_type' => $limitType
        ]);

        $this->failSession($session, "Storage limit exceeded: {$limitType}");

        $errorMessages = [
            'user' => 'Your storage limit has been exceeded. Please upgrade your plan or delete some files.',
            'project' => 'This project has reached its storage limit.',
            'system' => 'System storage limit reached. Please contact support.'
        ];

        return [
            'success' => false,
            'error' => $errorMessages[$limitType] ?? 'Storage limit exceeded',
            'retry' => false,
            'action' => 'upgrade_storage',
            'limit_type' => $limitType
        ];
    }

    /**
     * Handle browser compatibility issues
     */
    public function handleBrowserCompatibilityError(string $feature): array
    {
        Log::info('Browser compatibility issue', [
            'feature' => $feature,
            'user_agent' => request()->header('User-Agent')
        ]);

        $fallbackMessages = [
            'filepond' => 'Your browser doesn\'t support advanced upload features. Using basic uploader.',
            'chunking' => 'Chunked uploads not supported. Files will be uploaded normally.',
            'drag_drop' => 'Drag and drop not supported. Please use the browse button.',
            'progress' => 'Upload progress indication not available in your browser.'
        ];

        return [
            'success' => false,
            'error' => $fallbackMessages[$feature] ?? 'Browser compatibility issue',
            'retry' => false,
            'action' => 'use_fallback',
            'feature' => $feature
        ];
    }

    /**
     * Mark session as failed with reason
     */
    protected function failSession(UploadSession $session, string $reason): void
    {
        $session->update([
            'status' => UploadSession::STATUS_FAILED,
            'metadata' => array_merge($session->metadata ?? [], [
                'failure_reason' => $reason,
                'failed_at' => now()
            ])
        ]);

        Log::error('Upload session failed', [
            'session_id' => $session->id,
            'user_id' => $session->user_id,
            'reason' => $reason
        ]);
    }

    /**
     * Get user-friendly error message
     */
    public function getUserFriendlyError(string $errorType, array $context = []): string
    {
        $messages = [
            'chunk_failed' => 'Part of your file failed to upload. Retrying...',
            'assembly_failed' => 'There was an issue processing your file. Retrying...',
            'validation_failed' => 'Your file doesn\'t meet the requirements. Please check the file type and size.',
            'network_timeout' => 'Upload timed out due to network issues. Retrying...',
            'storage_limit' => 'You\'ve reached your storage limit. Please upgrade or free up space.',
            'browser_unsupported' => 'Your browser doesn\'t support this feature. Using basic upload instead.',
            'file_too_large' => 'Your file is too large. Maximum size is ' . ($context['max_size'] ?? 'unknown'),
            'invalid_file_type' => 'This file type is not allowed. Accepted types: ' . ($context['accepted_types'] ?? 'unknown'),
            'upload_failed' => 'Upload failed. Please try again or contact support if the problem persists.'
        ];

        return $messages[$errorType] ?? $messages['upload_failed'];
    }
}