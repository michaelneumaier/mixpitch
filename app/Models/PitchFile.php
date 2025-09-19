<?php

// app/Models/PitchFile.php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PitchFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pitch_id',
        'file_path',
        'storage_path',
        'file_name',
        'original_file_name',
        'mime_type',
        'note',
        'user_id',
        'size',
        'uuid',
        'waveform_peaks',
        'waveform_processed',
        'waveform_processed_at',
        'duration',
        // Client approval fields
        'client_approval_status',
        'client_approved_at',
        'client_approval_note',
        // Audio processing fields
        'audio_processed',
        'audio_processed_at',
        'processed_file_path',
        'processed_file_name',
        'processed_file_size',
        'is_transcoded',
        'is_watermarked',
        'processed_format',
        'processed_bitrate',
        'processing_metadata',
        'processing_error',
    ];

    protected $casts = [
        'waveform_processed' => 'boolean',
        'waveform_processed_at' => 'datetime',
        'duration' => 'float',
        'audio_processed' => 'boolean',
        'audio_processed_at' => 'datetime',
        'is_transcoded' => 'boolean',
        'is_watermarked' => 'boolean',
        'processing_metadata' => 'array',
        'client_approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the display name for the file (original filename if available, otherwise file_name)
     */
    public function getFileNameAttribute($value)
    {
        return $this->original_file_name ?: $value;
    }

    /**
     * Boot the model and generate UUID on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model (use UUID instead of ID)
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Format bytes to human-readable format
     *
     * @param  int  $bytes
     * @param  int  $precision
     * @return string
     */
    public function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Get the full file path including storage path
     * Now returns a signed URL with a short expiration
     * This is used for display purposes like audio players
     *
     * @return string|null
     */
    public function getFullFilePathAttribute()
    {
        $user = auth()->user();

        return $this->getStreamingUrl($user);
    }

    /**
     * Get a signed URL for downloading the file (accessor)
     * This uses a longer expiration time and appropriate headers for downloading
     *
     * @param  int  $expirationMinutes  Minutes until URL expires (default: 60)
     * @return string|null
     */
    public function getSignedUrlAttribute($expirationMinutes = 60)
    {
        return $this->getSignedUrl($expirationMinutes);
    }

    /**
     * Get a signed URL for downloading the file (method)
     * This uses a longer expiration time and appropriate headers for downloading
     *
     * @param  int  $expirationMinutes  Minutes until URL expires (default: 60)
     * @return string|null
     */
    public function getSignedUrl($expirationMinutes = 60)
    {
        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('s3');
            if (method_exists($disk, 'temporaryUrl')) {
                return $disk->temporaryUrl(
                    $this->file_path,
                    now()->addMinutes($expirationMinutes),
                    [
                        'ResponseContentDisposition' => 'attachment; filename="'.$this->file_name.'"',
                    ]
                );
            }

            return null;
        } catch (Exception $e) {
            Log::error('Error generating signed download URL for pitch file', [
                'file_id' => $this->id,
                'file_path' => $this->file_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get the formatted file size
     *
     * @return string
     */
    public function getFormattedSizeAttribute()
    {
        // If size is stored in the database, use that
        if (isset($this->attributes['size']) && $this->attributes['size']) {
            return $this->formatBytes($this->attributes['size']);
        }

        // Otherwise try to get the size from storage, only if file_path exists
        if ($this->file_path) {
            try {
                $size = Storage::disk('s3')->size($this->file_path);

                return $this->formatBytes($size);
            } catch (Exception $e) {
                // Log error or handle as needed
                Log::warning("Could not get size for file path: {$this->file_path}", ['error' => $e->getMessage()]);

                return '-';
            }
        }

        return '-'; // Return default if no size attribute and no file_path
    }

    /**
     * Get waveform peaks data as PHP array
     *
     * @return array|null
     */
    public function getWaveformPeaksArrayAttribute()
    {
        if (! $this->waveform_peaks) {
            return null;
        }

        return json_decode($this->waveform_peaks, true);
    }

    public function pitch()
    {
        return $this->belongsTo(Pitch::class);
    }

    public function name()
    {
        $pathInfo = pathinfo($this->file_name);

        return $pathInfo['filename'];
    }

    public function extension()
    {
        $pathInfo = pathinfo($this->file_name);

        return $pathInfo['extension'];
    }

    /**
     * Get all comments for this pitch file (using new polymorphic relationship)
     */
    public function comments()
    {
        return $this->morphMany(FileComment::class, 'commentable');
    }

    /**
     * Legacy relationship for backwards compatibility
     *
     * @deprecated Use comments() instead
     */
    public function pitchFileComments()
    {
        return $this->hasMany(PitchFileComment::class);
    }

    /**
     * Get the user who uploaded this file
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the formatted processed file size
     *
     * @return string
     */
    public function getFormattedProcessedSizeAttribute()
    {
        if (! $this->processed_file_size) {
            return '-';
        }

        return $this->formatBytes($this->processed_file_size);
    }

    /**
     * Check if the file should be watermarked based on project workflow
     */
    public function shouldBeWatermarked(): bool
    {
        $project = $this->pitch->project;
        $workflowConfig = config('audio.watermarking.workflows', []);

        // Check if watermarking is enabled for this workflow type
        if ($project->isStandard()) {
            return $workflowConfig['standard'] ?? true;
        } elseif ($project->isContest()) {
            return $workflowConfig['contest'] ?? false;
        } elseif ($project->isDirectHire()) {
            return $workflowConfig['direct_hire'] ?? false;
        } elseif ($project->isClientManagement()) {
            $configValue = $workflowConfig['client_management'] ?? false;

            // Handle user-controlled watermarking for client management
            if ($configValue === 'user_controlled') {
                return $this->pitch->watermarking_enabled ?? false;
            }

            return $configValue;
        }

        return false;
    }

    /**
     * Get the appropriate streaming URL based on user permissions
     */
    public function getStreamingUrl(?User $user = null, int $expiration = 7200): ?string
    {
        if ($this->shouldServeWatermarked($user)) {
            $processedUrl = $this->getProcessedFileUrl($expiration);

            // Fallback to original if no processed version available
            return $processedUrl ?: $this->getOriginalFileUrl($expiration);
        }

        return $this->getOriginalFileUrl($expiration);
    }

    /**
     * Determine if watermarked version should be served to user
     */
    public function shouldServeWatermarked(?User $user = null): bool
    {
        // If no user provided, serve watermarked version
        if (! $user) {
            return true;
        }

        // If file is not processed or watermarked, serve original
        if (! $this->audio_processed || ! $this->is_watermarked) {
            return false;
        }

        // Check if user can access original file
        return ! $this->canAccessOriginalFile($user);
    }

    /**
     * Check if user can access the original (non-watermarked) file
     */
    public function canAccessOriginalFile(?User $user = null): bool
    {
        if (! $user) {
            return false;
        }

        // Pitch owner can always access original
        if ($user->id === $this->pitch->user_id) {
            return true;
        }

        // Project owner can access original if fully paid either via full payment or all milestones paid
        if ($user->id === $this->pitch->project->user_id) {
            $fullyPaid = $this->pitch->isAcceptedCompletedAndPaid();
            if (! $fullyPaid) {
                // Allow originals when all milestones with positive amounts are paid
                $unpaidMilestones = $this->pitch->milestones()
                    ->where('amount', '>', 0)
                    ->where('payment_status', '!=', Pitch::PAYMENT_STATUS_PAID)
                    ->count();
                $fullyPaid = $unpaidMilestones === 0 && $this->pitch->milestones()->exists();
            }

            return $fullyPaid;
        }

        return false;
    }

    /**
     * Get original file URL with appropriate expiration
     */
    public function getOriginalFileUrl(int $expirationMinutes = 30): ?string
    {
        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('s3');
            if (method_exists($disk, 'temporaryUrl')) {
                return $disk->temporaryUrl(
                    $this->file_path,
                    now()->addMinutes($expirationMinutes)
                );
            }

            return null;
        } catch (Exception $e) {
            Log::error('Error generating original file URL', [
                'file_id' => $this->id,
                'file_path' => $this->file_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get processed file URL with appropriate expiration
     */
    public function getProcessedFileUrl(int $expirationMinutes = 30): ?string
    {
        if (! $this->processed_file_path) {
            return null;
        }

        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('s3');
            if (method_exists($disk, 'temporaryUrl')) {
                return $disk->temporaryUrl(
                    $this->processed_file_path,
                    now()->addMinutes($expirationMinutes)
                );
            }

            return null;
        } catch (Exception $e) {
            Log::error('Error generating processed file URL', [
                'file_id' => $this->id,
                'processed_file_path' => $this->processed_file_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get download URL based on user permissions
     */
    public function getDownloadUrl(?User $user = null, int $expiration = 900): ?string
    {
        if ($this->shouldServeWatermarked($user)) {
            return $this->getProcessedFileUrl($expiration);
        }

        return $this->getOriginalFileUrl($expiration);
    }

    /**
     * Check if file is an audio file that can be processed
     */
    public function isAudioFile(): bool
    {
        if (! $this->mime_type) {
            return false;
        }

        $audioMimes = [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/ogg',
            'audio/aac',
            'audio/m4a',
            'audio/flac',
            'audio/x-wav',
            'audio/x-flac',
        ];

        return in_array($this->mime_type, $audioMimes);
    }

    /**
     * Check if file needs processing
     */
    public function needsProcessing(): bool
    {
        return $this->isAudioFile() &&
               $this->shouldBeWatermarked() &&
               ! $this->audio_processed;
    }

    /**
     * Mark file as processed and store results
     */
    public function markAsProcessed(array $processedData): void
    {
        $this->update([
            'audio_processed' => true,
            'audio_processed_at' => now(),
            'processed_file_path' => $processedData['output_path'] ?? null,
            'processed_file_name' => $processedData['output_filename'] ?? null,
            'processed_file_size' => $processedData['output_size'] ?? null,
            'is_transcoded' => $processedData['transcoded'] ?? false,
            'is_watermarked' => $processedData['watermarked'] ?? false,
            'processed_format' => $processedData['format'] ?? null,
            'processed_bitrate' => $processedData['bitrate'] ?? null,
            'processing_metadata' => $processedData['metadata'] ?? null,
            'processing_error' => null, // Clear any previous errors
        ]);
    }

    /**
     * Mark file as failed processing
     */
    public function markAsProcessingFailed(string $error): void
    {
        $this->update([
            'audio_processed' => false,
            'processing_error' => $error,
        ]);
    }
}
