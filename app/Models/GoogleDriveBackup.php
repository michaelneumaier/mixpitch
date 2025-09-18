<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GoogleDriveBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_id',
        'file_type',
        'original_file_name',
        'file_size',
        'mime_type',
        'file_hash',
        'google_drive_file_id',
        'google_drive_folder_id',
        'google_drive_folder_name',
        'google_drive_file_path',
        'project_id',
        'project_name',
        'status',
        'backed_up_at',
        'error_message',
        'metadata',
        'version',
        'is_latest_version',
    ];

    protected $casts = [
        'backed_up_at' => 'datetime',
        'metadata' => 'array',
        'file_size' => 'integer',
        'version' => 'integer',
        'is_latest_version' => 'boolean',
    ];

    /**
     * Get the user who performed the backup
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source file (ProjectFile or PitchFile)
     */
    public function file(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the project associated with this backup
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope for completed backups
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed backups
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for latest versions only
     */
    public function scopeLatestVersions($query)
    {
        return $query->where('is_latest_version', true);
    }

    /**
     * Scope for a specific user
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope for a specific project
     */
    public function scopeForProject($query, Project $project)
    {
        return $query->where('project_id', $project->id);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return round($bytes / pow(1024, $power), 2).' '.$units[$power];
    }

    /**
     * Check if backup is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if backup failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if backup is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Mark backup as completed
     */
    public function markAsCompleted(string $googleDriveFileId): void
    {
        $this->update([
            'status' => 'completed',
            'google_drive_file_id' => $googleDriveFileId,
            'backed_up_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark backup as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Generate file hash from file content
     */
    public static function generateFileHash(string $content): string
    {
        return hash('sha256', $content);
    }
}
