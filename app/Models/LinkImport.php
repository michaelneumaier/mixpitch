<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LinkImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'source_url',
        'source_domain',
        'detected_files',
        'imported_files',
        'status',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'detected_files' => 'array',
        'imported_files' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_ANALYZING = 'analyzing';

    public const STATUS_IMPORTING = 'importing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * Get the project that owns the link import.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that created the link import.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the imported files for this link import.
     */
    public function importedFiles(): HasMany
    {
        return $this->hasMany(ImportedFile::class);
    }

    /**
     * Check if the import is in progress.
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ANALYZING,
            self::STATUS_IMPORTING,
        ]);
    }

    /**
     * Check if the import is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the import has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get progress percentage (0-100).
     */
    public function getProgressPercentage(): int
    {
        $metadata = $this->metadata ?? [];
        $progress = $metadata['progress'] ?? [];

        $total = $progress['total'] ?? 0;
        $completed = $progress['completed'] ?? 0;

        if ($total <= 0) {
            return 0;
        }

        return (int) round(($completed / $total) * 100);
    }

    /**
     * Get the current file being processed.
     */
    public function getCurrentFile(): ?string
    {
        $metadata = $this->metadata ?? [];
        $progress = $metadata['progress'] ?? [];

        return $progress['current_file'] ?? null;
    }
}
