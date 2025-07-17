<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UploadSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'model_type',
        'model_id',
        'original_filename',
        'total_size',
        'chunk_size',
        'total_chunks',
        'uploaded_chunks',
        'status',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'total_size' => 'integer',
        'chunk_size' => 'integer',
        'total_chunks' => 'integer',
        'uploaded_chunks' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_UPLOADING = 'uploading';

    const STATUS_ASSEMBLING = 'assembling';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    // Valid status transitions
    const VALID_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_UPLOADING, self::STATUS_FAILED],
        self::STATUS_UPLOADING => [self::STATUS_ASSEMBLING, self::STATUS_FAILED],
        self::STATUS_ASSEMBLING => [self::STATUS_COMPLETED, self::STATUS_FAILED],
        self::STATUS_COMPLETED => [],
        self::STATUS_FAILED => [self::STATUS_PENDING, self::STATUS_UPLOADING],
    ];

    /**
     * Relationship to the user who owns this upload session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relationship to the model this upload is for (Project or Pitch)
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relationship to the upload chunks
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(UploadChunk::class);
    }

    /**
     * Relationship to Project if this upload is for a project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'model_id')->where('model_type', Project::class);
    }

    /**
     * Relationship to Pitch if this upload is for a pitch
     */
    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class, 'model_id')->where('model_type', Pitch::class);
    }

    /**
     * Transition the upload session to a new status
     */
    public function transitionTo(string $newStatus): bool
    {
        if (! $this->canTransitionTo($newStatus)) {
            return false;
        }

        $this->status = $newStatus;

        return $this->save();
    }

    /**
     * Check if the session can transition to the given status
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $currentStatus = $this->status;

        if (! isset(self::VALID_TRANSITIONS[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, self::VALID_TRANSITIONS[$currentStatus]);
    }

    /**
     * Check if the upload session has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if all chunks have been uploaded
     */
    public function isComplete(): bool
    {
        return $this->uploaded_chunks >= $this->total_chunks;
    }

    /**
     * Get the progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_chunks === 0) {
            return 0;
        }

        return round(($this->uploaded_chunks / $this->total_chunks) * 100, 2);
    }

    /**
     * Increment the uploaded chunks count
     */
    public function incrementUploadedChunks(): bool
    {
        $this->uploaded_chunks = $this->uploaded_chunks + 1;

        return $this->save();
    }

    /**
     * Mark the session as failed with optional error message
     */
    public function markAsFailed(?string $errorMessage = null): bool
    {
        $metadata = $this->metadata ?? [];
        if ($errorMessage) {
            $metadata['error'] = $errorMessage;
            $metadata['failed_at'] = now()->toISOString();
        }

        $this->metadata = $metadata;

        return $this->transitionTo(self::STATUS_FAILED);
    }

    /**
     * Scope for expired sessions
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope for active sessions (not completed or failed)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_FAILED]);
    }

    /**
     * Scope for sessions by model type
     */
    public function scopeForModelType($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Get all valid statuses
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_UPLOADING,
            self::STATUS_ASSEMBLING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
        ];
    }
}
