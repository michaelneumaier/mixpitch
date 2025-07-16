<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'upload_session_id',
        'chunk_index',
        'chunk_hash',
        'storage_path',
        'size',
        'status'
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'size' => 'integer'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_UPLOADED = 'uploaded';
    const STATUS_VERIFIED = 'verified';
    const STATUS_FAILED = 'failed';

    /**
     * Relationship to the upload session
     */
    public function uploadSession(): BelongsTo
    {
        return $this->belongsTo(UploadSession::class);
    }

    /**
     * Validate chunk integrity by comparing stored hash with expected hash
     */
    public function validateIntegrity(?string $expectedHash = null): bool
    {
        try {
            // Use provided hash or the stored hash
            $hashToValidate = $expectedHash ?? $this->chunk_hash;
            
            if (!$hashToValidate) {
                Log::warning("No hash available for chunk validation", [
                    'chunk_id' => $this->id,
                    'upload_session_id' => $this->upload_session_id
                ]);
                return false;
            }

            // Check if file exists
            if (!$this->fileExists()) {
                Log::error("Chunk file not found during validation", [
                    'chunk_id' => $this->id,
                    'storage_path' => $this->storage_path
                ]);
                return false;
            }

            // Calculate hash of stored file
            $actualHash = $this->calculateFileHash();
            
            if (!$actualHash) {
                Log::error("Failed to calculate hash for chunk file", [
                    'chunk_id' => $this->id,
                    'storage_path' => $this->storage_path
                ]);
                return false;
            }

            $isValid = hash_equals($hashToValidate, $actualHash);
            
            if ($isValid) {
                $this->markAsVerified();
            } else {
                Log::warning("Chunk integrity validation failed", [
                    'chunk_id' => $this->id,
                    'expected_hash' => $hashToValidate,
                    'actual_hash' => $actualHash
                ]);
                $this->markAsFailed();
            }

            return $isValid;
            
        } catch (\Exception $e) {
            Log::error("Exception during chunk integrity validation", [
                'chunk_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            $this->markAsFailed();
            return false;
        }
    }

    /**
     * Calculate the hash of the stored chunk file
     */
    public function calculateFileHash(): ?string
    {
        try {
            if (!$this->fileExists()) {
                return null;
            }

            $filePath = $this->getFullStoragePath();
            return hash_file('sha256', $filePath);
            
        } catch (\Exception $e) {
            Log::error("Failed to calculate file hash", [
                'chunk_id' => $this->id,
                'storage_path' => $this->storage_path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if the chunk file exists in storage
     */
    public function fileExists(): bool
    {
        if (!$this->storage_path) {
            return false;
        }

        try {
            return Storage::disk('local')->exists($this->storage_path);
        } catch (\Exception $e) {
            Log::error("Error checking chunk file existence", [
                'chunk_id' => $this->id,
                'storage_path' => $this->storage_path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get the full storage path for the chunk file
     */
    public function getFullStoragePath(): ?string
    {
        if (!$this->storage_path) {
            return null;
        }

        try {
            return Storage::disk('local')->path($this->storage_path);
        } catch (\Exception $e) {
            Log::error("Error getting full storage path", [
                'chunk_id' => $this->id,
                'storage_path' => $this->storage_path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get the chunk file contents
     */
    public function getFileContents(): ?string
    {
        try {
            if (!$this->fileExists()) {
                return null;
            }

            return Storage::disk('local')->get($this->storage_path);
            
        } catch (\Exception $e) {
            Log::error("Failed to get chunk file contents", [
                'chunk_id' => $this->id,
                'storage_path' => $this->storage_path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete the chunk file from storage
     */
    public function deleteFile(): bool
    {
        try {
            if (!$this->storage_path) {
                return true; // Nothing to delete
            }

            if (Storage::disk('local')->exists($this->storage_path)) {
                return Storage::disk('local')->delete($this->storage_path);
            }

            return true; // File doesn't exist, consider it deleted
            
        } catch (\Exception $e) {
            Log::error("Failed to delete chunk file", [
                'chunk_id' => $this->id,
                'storage_path' => $this->storage_path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mark chunk as uploaded
     */
    public function markAsUploaded(): bool
    {
        $this->status = self::STATUS_UPLOADED;
        return $this->save();
    }

    /**
     * Mark chunk as verified
     */
    public function markAsVerified(): bool
    {
        $this->status = self::STATUS_VERIFIED;
        return $this->save();
    }

    /**
     * Mark chunk as failed
     */
    public function markAsFailed(): bool
    {
        $this->status = self::STATUS_FAILED;
        return $this->save();
    }

    /**
     * Check if chunk is ready for assembly (uploaded and optionally verified)
     */
    public function isReadyForAssembly(): bool
    {
        return in_array($this->status, [self::STATUS_UPLOADED, self::STATUS_VERIFIED]);
    }

    /**
     * Scope for chunks by upload session
     */
    public function scopeForSession($query, int $uploadSessionId)
    {
        return $query->where('upload_session_id', $uploadSessionId);
    }

    /**
     * Scope for chunks by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for verified chunks
     */
    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /**
     * Scope for uploaded chunks (uploaded or verified)
     */
    public function scopeUploaded($query)
    {
        return $query->whereIn('status', [self::STATUS_UPLOADED, self::STATUS_VERIFIED]);
    }

    /**
     * Scope for failed chunks
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Order chunks by index
     */
    public function scopeOrderedByIndex($query)
    {
        return $query->orderBy('chunk_index');
    }

    /**
     * Get all valid statuses
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_UPLOADED,
            self::STATUS_VERIFIED,
            self::STATUS_FAILED
        ];
    }

    /**
     * Clean up chunk file when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($chunk) {
            $chunk->deleteFile();
        });
    }
}