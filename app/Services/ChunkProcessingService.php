<?php

namespace App\Services;

use App\Models\UploadSession;
use App\Models\UploadChunk;
use App\Models\ProjectFile;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Exceptions\FileUploadException;
use App\Services\FileSecurityService;

class ChunkProcessingService
{
    protected FileSecurityService $securityService;

    public function __construct(FileSecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Store an individual chunk to secure temporary storage with security validation
     *
     * @param UploadedFile $chunk The uploaded chunk file
     * @param string $uploadId The upload session ID
     * @param int $chunkIndex The index of this chunk (0-based)
     * @param string|null $expectedHash Optional hash for integrity verification
     * @return string The storage path of the stored chunk
     * @throws FileUploadException
     */
    public function storeChunk(UploadedFile $chunk, string $uploadId, int $chunkIndex, ?string $expectedHash = null): string
    {
        try {
            // Find the upload session
            $uploadSession = UploadSession::find($uploadId);
            if (!$uploadSession) {
                throw new FileUploadException("Upload session not found: {$uploadId}");
            }

            // Validate chunk index
            if ($chunkIndex < 0 || $chunkIndex >= $uploadSession->total_chunks) {
                throw new FileUploadException("Invalid chunk index: {$chunkIndex}");
            }

            // Check if chunk already exists
            $existingChunk = UploadChunk::where('upload_session_id', $uploadId)
                ->where('chunk_index', $chunkIndex)
                ->first();

            if ($existingChunk && $existingChunk->status === UploadChunk::STATUS_VERIFIED) {
                Log::info("Chunk already exists and is verified", [
                    'upload_session_id' => $uploadId,
                    'chunk_index' => $chunkIndex
                ]);
                return $existingChunk->storage_path;
            }

            // Use secure storage for chunk with security validation
            $storagePath = $this->securityService->storeSecureChunk($chunk, $uploadId, $chunkIndex, $expectedHash);

            // Calculate hash of stored chunk for database record
            $fullPath = Storage::disk('local')->path($storagePath);
            $actualHash = $this->securityService->generateSecureHash($fullPath);

            if (!$actualHash) {
                throw new FileUploadException("Failed to generate hash for stored chunk");
            }

            // Create or update chunk record
            $chunkRecord = UploadChunk::updateOrCreate(
                [
                    'upload_session_id' => $uploadId,
                    'chunk_index' => $chunkIndex
                ],
                [
                    'chunk_hash' => $actualHash,
                    'storage_path' => $storagePath,
                    'size' => $chunk->getSize(),
                    'status' => $expectedHash ? UploadChunk::STATUS_VERIFIED : UploadChunk::STATUS_UPLOADED
                ]
            );

            // Update upload session progress
            $uploadSession->incrementUploadedChunks();

            Log::info("Secure chunk stored successfully", [
                'upload_session_id' => $uploadId,
                'chunk_index' => $chunkIndex,
                'size' => $chunk->getSize(),
                'hash' => $actualHash
            ]);

            return $storagePath;

        } catch (\Exception $e) {
            Log::error("Failed to store secure chunk", [
                'upload_session_id' => $uploadId,
                'chunk_index' => $chunkIndex,
                'error' => $e->getMessage()
            ]);
            throw new FileUploadException("Failed to store chunk {$chunkIndex}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate chunk integrity using secure hash verification
     *
     * @param string $chunkPath The storage path of the chunk
     * @param string $expectedHash The expected hash of the chunk
     * @return bool True if integrity is valid, false otherwise
     */
    public function validateChunkIntegrity(string $chunkPath, string $expectedHash): bool
    {
        try {
            // Check if chunk file exists
            if (!Storage::disk('local')->exists($chunkPath)) {
                Log::warning("Chunk file not found for integrity validation", [
                    'chunk_path' => $chunkPath
                ]);
                return false;
            }

            // Use security service for secure hash validation
            $fullPath = Storage::disk('local')->path($chunkPath);
            $isValid = $this->securityService->validateHashIntegrity($fullPath, $expectedHash);

            if (!$isValid) {
                Log::warning("Chunk integrity validation failed", [
                    'chunk_path' => $chunkPath,
                    'expected_hash' => $expectedHash
                ]);
            }

            return $isValid;

        } catch (\Exception $e) {
            Log::error("Exception during chunk integrity validation", [
                'chunk_path' => $chunkPath,
                'expected_hash' => $expectedHash,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clean up chunks for a specific upload session using secure deletion
     *
     * @param string $uploadId The upload session ID
     * @return bool True if cleanup was successful, false otherwise
     */
    public function cleanupChunks(string $uploadId): bool
    {
        try {
            Log::info("Starting secure chunk cleanup", ['upload_session_id' => $uploadId]);

            // Get all chunks for this upload session
            $chunks = UploadChunk::where('upload_session_id', $uploadId)->get();
            $cleanupSuccess = true;

            // Delete chunk records from database first
            foreach ($chunks as $chunk) {
                try {
                    $chunk->delete();
                    Log::debug("Deleted chunk record", [
                        'chunk_id' => $chunk->id,
                        'storage_path' => $chunk->storage_path
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to delete chunk record", [
                        'chunk_id' => $chunk->id,
                        'upload_session_id' => $uploadId,
                        'error' => $e->getMessage()
                    ]);
                    $cleanupSuccess = false;
                }
            }

            // Use secure cleanup for chunk files
            $secureCleanupSuccess = $this->securityService->cleanupSecureChunks($uploadId);
            if (!$secureCleanupSuccess) {
                $cleanupSuccess = false;
            }

            // Fallback cleanup for old-style chunk directories (backward compatibility)
            try {
                $oldChunksDir = "chunks/{$uploadId}";
                if (Storage::disk('local')->exists($oldChunksDir)) {
                    Storage::disk('local')->deleteDirectory($oldChunksDir);
                    Log::debug("Deleted old-style chunks directory", ['directory' => $oldChunksDir]);
                }
            } catch (\Exception $e) {
                Log::warning("Failed to delete old-style chunks directory", [
                    'upload_session_id' => $uploadId,
                    'error' => $e->getMessage()
                ]);
                // Don't mark as failure for backward compatibility cleanup
            }

            if ($cleanupSuccess) {
                Log::info("Secure chunk cleanup completed successfully", [
                    'upload_session_id' => $uploadId,
                    'chunks_cleaned' => $chunks->count()
                ]);
            } else {
                Log::warning("Secure chunk cleanup completed with some errors", [
                    'upload_session_id' => $uploadId
                ]);
            }

            return $cleanupSuccess;

        } catch (\Exception $e) {
            Log::error("Exception during secure chunk cleanup", [
                'upload_session_id' => $uploadId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Assemble chunks into final file using streaming for memory efficiency
     *
     * @param string $uploadId The upload session ID
     * @param array $chunkHashes Optional array of expected chunk hashes for verification
     * @return UploadedFile The assembled file as an UploadedFile instance
     * @throws FileUploadException
     */
    public function assembleChunks(string $uploadId, array $chunkHashes = []): UploadedFile
    {
        try {
            // Find the upload session
            $uploadSession = UploadSession::find($uploadId);
            if (!$uploadSession) {
                throw new FileUploadException("Upload session not found: {$uploadId}");
            }

            // Transition session to assembling status
            if (!$uploadSession->transitionTo(UploadSession::STATUS_ASSEMBLING)) {
                throw new FileUploadException("Cannot transition upload session to assembling status");
            }

            Log::info("Starting file assembly", [
                'upload_session_id' => $uploadId,
                'total_chunks' => $uploadSession->total_chunks
            ]);

            // Get all chunks ordered by index
            $chunks = UploadChunk::where('upload_session_id', $uploadId)
                ->orderBy('chunk_index')
                ->get();

            // Validate we have all chunks
            if ($chunks->count() !== $uploadSession->total_chunks) {
                throw new FileUploadException(
                    "Missing chunks: expected {$uploadSession->total_chunks}, found {$chunks->count()}"
                );
            }

            // Validate chunk integrity if hashes provided
            if (!empty($chunkHashes)) {
                foreach ($chunks as $chunk) {
                    $expectedHash = $chunkHashes[$chunk->chunk_index] ?? null;
                    if ($expectedHash && !$this->validateChunkIntegrity($chunk->storage_path, $expectedHash)) {
                        throw new FileUploadException("Chunk integrity validation failed for chunk {$chunk->chunk_index}");
                    }
                }
            }

            // Create temporary file for assembly
            $tempFileName = 'assembled_' . $uploadId . '_' . time();
            $tempPath = storage_path("app/temp/{$tempFileName}");
            
            // Ensure temp directory exists
            $tempDir = dirname($tempPath);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Assemble file using streaming to handle large files efficiently
            $this->streamAssembleChunks($chunks, $tempPath);

            // Verify assembled file integrity
            $this->verifyAssembledFile($tempPath, $uploadSession);

            // Create UploadedFile instance from assembled file
            $assembledFile = new UploadedFile(
                $tempPath,
                $uploadSession->original_filename,
                null, // Let it detect MIME type
                null, // Let it detect file size
                true  // Test mode - don't validate upload
            );

            Log::info("File assembly completed successfully", [
                'upload_session_id' => $uploadId,
                'assembled_file_size' => filesize($tempPath),
                'original_filename' => $uploadSession->original_filename
            ]);

            return $assembledFile;

        } catch (\Exception $e) {
            Log::error("Failed to assemble chunks", [
                'upload_session_id' => $uploadId,
                'error' => $e->getMessage()
            ]);

            // Mark session as failed
            if (isset($uploadSession)) {
                $uploadSession->markAsFailed("Assembly failed: " . $e->getMessage());
            }

            throw new FileUploadException("Failed to assemble file: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Stream chunks together to create the final file efficiently
     *
     * @param \Illuminate\Database\Eloquent\Collection $chunks
     * @param string $outputPath
     * @throws FileUploadException
     */
    private function streamAssembleChunks($chunks, string $outputPath): void
    {
        $outputHandle = fopen($outputPath, 'wb');
        if (!$outputHandle) {
            throw new FileUploadException("Cannot create output file: {$outputPath}");
        }

        try {
            foreach ($chunks as $chunk) {
                if (!Storage::disk('local')->exists($chunk->storage_path)) {
                    throw new FileUploadException("Chunk file not found: {$chunk->storage_path}");
                }

                $chunkPath = Storage::disk('local')->path($chunk->storage_path);
                $chunkHandle = fopen($chunkPath, 'rb');
                
                if (!$chunkHandle) {
                    throw new FileUploadException("Cannot read chunk file: {$chunkPath}");
                }

                try {
                    // Stream chunk data to output file in 8KB blocks
                    while (!feof($chunkHandle)) {
                        $data = fread($chunkHandle, 8192);
                        if ($data === false) {
                            throw new FileUploadException("Error reading chunk data");
                        }
                        
                        if (fwrite($outputHandle, $data) === false) {
                            throw new FileUploadException("Error writing to assembled file");
                        }
                    }
                } finally {
                    fclose($chunkHandle);
                }

                Log::debug("Assembled chunk", [
                    'chunk_index' => $chunk->chunk_index,
                    'chunk_size' => $chunk->size
                ]);
            }
        } finally {
            fclose($outputHandle);
        }
    }

    /**
     * Verify the integrity of the assembled file
     *
     * @param string $filePath Path to the assembled file
     * @param UploadSession $uploadSession The upload session
     * @throws FileUploadException
     */
    private function verifyAssembledFile(string $filePath, UploadSession $uploadSession): void
    {
        // Check file exists
        if (!file_exists($filePath)) {
            throw new FileUploadException("Assembled file not found");
        }

        // Verify file size matches expected total
        $actualSize = filesize($filePath);
        if ($actualSize !== $uploadSession->total_size) {
            throw new FileUploadException(
                "Assembled file size mismatch: expected {$uploadSession->total_size}, got {$actualSize}"
            );
        }

        // Calculate hash of assembled file for integrity verification
        $assembledHash = hash_file('sha256', $filePath);
        if (!$assembledHash) {
            throw new FileUploadException("Failed to calculate hash of assembled file");
        }

        // Store hash in session metadata for future reference
        $metadata = $uploadSession->metadata ?? [];
        $metadata['assembled_hash'] = $assembledHash;
        $metadata['assembled_at'] = now()->toISOString();
        $uploadSession->metadata = $metadata;
        $uploadSession->save();

        Log::info("Assembled file integrity verified", [
            'upload_session_id' => $uploadSession->id,
            'file_size' => $actualSize,
            'file_hash' => $assembledHash
        ]);
    }

    /**
     * Finalize upload by creating ProjectFile or PitchFile record and integrating with FileManagementService
     *
     * @param UploadSession $session The upload session
     * @return ProjectFile|PitchFile The created file record
     * @throws FileUploadException
     */
    public function finalizeUpload(UploadSession $session): ProjectFile|PitchFile
    {
        try {
            Log::info("Starting upload finalization", [
                'upload_session_id' => $session->id,
                'model_type' => $session->model_type,
                'model_id' => $session->model_id
            ]);

            // Assemble the chunks into final file
            $assembledFile = $this->assembleChunks($session->id);

            // Use database transaction for atomicity
            return DB::transaction(function () use ($session, $assembledFile) {
                $fileRecord = null;

                // Get the FileManagementService
                $fileManagementService = app(FileManagementService::class);

                // Create appropriate file record based on model type
                if ($session->model_type === Project::class) {
                    $project = Project::find($session->model_id);
                    if (!$project) {
                        throw new FileUploadException("Project not found: {$session->model_id}");
                    }

                    $fileRecord = $fileManagementService->uploadProjectFile(
                        $project,
                        $assembledFile,
                        $session->user,
                        ['upload_session_id' => $session->id]
                    );

                } elseif ($session->model_type === Pitch::class) {
                    $pitch = Pitch::find($session->model_id);
                    if (!$pitch) {
                        throw new FileUploadException("Pitch not found: {$session->model_id}");
                    }

                    $fileRecord = $fileManagementService->uploadPitchFile(
                        $pitch,
                        $assembledFile,
                        $session->user
                    );

                } else {
                    throw new FileUploadException("Unsupported model type: {$session->model_type}");
                }

                // Mark session as completed
                if (!$session->transitionTo(UploadSession::STATUS_COMPLETED)) {
                    throw new FileUploadException("Failed to mark upload session as completed");
                }

                // Update session metadata with final file info
                $metadata = $session->metadata ?? [];
                $metadata['finalized_at'] = now()->toISOString();
                $metadata['file_record_id'] = $fileRecord->id;
                $metadata['file_record_type'] = get_class($fileRecord);
                $session->metadata = $metadata;
                $session->save();

                Log::info("Upload finalization completed successfully", [
                    'upload_session_id' => $session->id,
                    'file_record_id' => $fileRecord->id,
                    'file_record_type' => get_class($fileRecord)
                ]);

                return $fileRecord;
            });

        } catch (\Exception $e) {
            Log::error("Failed to finalize upload", [
                'upload_session_id' => $session->id,
                'error' => $e->getMessage()
            ]);

            // Mark session as failed and perform rollback
            $this->rollbackFailedUpload($session, $e->getMessage());

            throw new FileUploadException("Failed to finalize upload: " . $e->getMessage(), 0, $e);

        } finally {
            // Clean up temporary assembled file
            if (isset($assembledFile)) {
                $tempPath = $assembledFile->getRealPath();
                if ($tempPath && file_exists($tempPath)) {
                    try {
                        unlink($tempPath);
                        Log::debug("Cleaned up temporary assembled file", ['temp_path' => $tempPath]);
                    } catch (\Exception $e) {
                        Log::warning("Failed to clean up temporary assembled file", [
                            'temp_path' => $tempPath,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Clean up chunks after successful finalization or failure
            $this->cleanupChunks($session->id);
        }
    }

    /**
     * Rollback a failed upload by cleaning up resources and marking session as failed
     *
     * @param UploadSession $session The upload session
     * @param string $errorMessage The error message
     */
    private function rollbackFailedUpload(UploadSession $session, string $errorMessage): void
    {
        try {
            Log::info("Starting upload rollback", [
                'upload_session_id' => $session->id,
                'error' => $errorMessage
            ]);

            // Mark session as failed
            $session->markAsFailed($errorMessage);

            // Clean up any partially created file records would be handled by the database transaction rollback
            // The FileManagementService handles its own cleanup in case of exceptions

            Log::info("Upload rollback completed", [
                'upload_session_id' => $session->id
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to rollback upload", [
                'upload_session_id' => $session->id,
                'rollback_error' => $e->getMessage(),
                'original_error' => $errorMessage
            ]);
        }
    }

    /**
     * Get upload progress information
     *
     * @param string $uploadId The upload session ID
     * @return array Progress information
     */
    public function getUploadProgress(string $uploadId): array
    {
        $session = UploadSession::find($uploadId);
        if (!$session) {
            return ['error' => 'Upload session not found'];
        }

        $chunks = UploadChunk::where('upload_session_id', $uploadId)->get();
        $uploadedChunks = $chunks->where('status', '!=', UploadChunk::STATUS_PENDING)->count();
        $verifiedChunks = $chunks->where('status', UploadChunk::STATUS_VERIFIED)->count();

        return [
            'session_id' => $session->id,
            'status' => $session->status,
            'progress_percentage' => $session->getProgressPercentage(),
            'total_chunks' => $session->total_chunks,
            'uploaded_chunks' => $uploadedChunks,
            'verified_chunks' => $verifiedChunks,
            'total_size' => $session->total_size,
            'original_filename' => $session->original_filename,
            'is_complete' => $session->isComplete(),
            'is_expired' => $session->isExpired(),
            'metadata' => $session->metadata
        ];
    }
}