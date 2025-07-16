<?php

// app/Models/PitchFile.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Models\User;

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
        'processing_error'
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
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
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
     * Get a signed URL for downloading the file
     * This uses a longer expiration time and appropriate headers for downloading
     *
     * @param int $expirationMinutes Minutes until URL expires (default: 60)
     * @return string|null
     */
    public function getSignedUrlAttribute($expirationMinutes = 60)
    {
        try {
            return Storage::disk('s3')->temporaryUrl(
                $this->file_path,
                now()->addMinutes($expirationMinutes),
                [
                    'ResponseContentDisposition' => 'attachment; filename="' . $this->file_name . '"'
                ]
            );
        } catch (Exception $e) {
            \Log::error('Error generating signed download URL for pitch file', [
                'file_id' => $this->id,
                'file_path' => $this->file_path,
                'error' => $e->getMessage()
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
                \Log::warning("Could not get size for file path: {$this->file_path}", ['error' => $e->getMessage()]);
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
        if (!$this->waveform_peaks) {
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
     * Get all comments for this pitch file
     */
    public function comments()
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
        if (!$this->processed_file_size) {
            return '-';
        }
        
        return $this->formatBytes($this->processed_file_size);
    }

    /**
     * Check if the file should be watermarked based on project workflow
     *
     * @return bool
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
            return $workflowConfig['client_management'] ?? false;
        }
        
        return false;
    }

    /**
     * Get the appropriate streaming URL based on user permissions
     *
     * @param User|null $user
     * @param int $expiration
     * @return string|null
     */
    public function getStreamingUrl(?User $user = null, int $expiration = 7200): ?string
    {
        if ($this->shouldServeWatermarked($user)) {
            return $this->getProcessedFileUrl($expiration);
        }

        return $this->getOriginalFileUrl($expiration);
    }

    /**
     * Determine if watermarked version should be served to user
     *
     * @param User|null $user
     * @return bool
     */
    public function shouldServeWatermarked(?User $user = null): bool
    {
        // If no user provided, serve watermarked version
        if (!$user) {
            return true;
        }

        // If file is not processed or watermarked, serve original
        if (!$this->audio_processed || !$this->is_watermarked) {
            return false;
        }

        // Check if user can access original file
        return !$this->canAccessOriginalFile($user);
    }

    /**
     * Check if user can access the original (non-watermarked) file
     *
     * @param User|null $user
     * @return bool
     */
    public function canAccessOriginalFile(?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        // Pitch owner can always access original
        if ($user->id === $this->pitch->user_id) {
            return true;
        }

        // Project owner can access original only if pitch is accepted, completed, and paid
        if ($user->id === $this->pitch->project->user_id) {
            return $this->pitch->isAcceptedCompletedAndPaid();
        }

        return false;
    }

    /**
     * Get original file URL with appropriate expiration
     *
     * @param int $expirationMinutes
     * @return string|null
     */
    public function getOriginalFileUrl(int $expirationMinutes = 30): ?string
    {
        try {
            return Storage::disk('s3')->temporaryUrl(
                $this->file_path,
                now()->addMinutes($expirationMinutes)
            );
        } catch (Exception $e) {
            \Log::error('Error generating original file URL', [
                'file_id' => $this->id,
                'file_path' => $this->file_path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get processed file URL with appropriate expiration
     *
     * @param int $expirationMinutes
     * @return string|null
     */
    public function getProcessedFileUrl(int $expirationMinutes = 30): ?string
    {
        if (!$this->processed_file_path) {
            return null;
        }
        
        try {
            return Storage::disk('s3')->temporaryUrl(
                $this->processed_file_path,
                now()->addMinutes($expirationMinutes)
            );
        } catch (Exception $e) {
            \Log::error('Error generating processed file URL', [
                'file_id' => $this->id,
                'processed_file_path' => $this->processed_file_path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get download URL based on user permissions
     *
     * @param User|null $user
     * @param int $expiration
     * @return string|null
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
     *
     * @return bool
     */
    public function isAudioFile(): bool
    {
        if (!$this->mime_type) {
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
            'audio/x-flac'
        ];
        
        return in_array($this->mime_type, $audioMimes);
    }

    /**
     * Check if file needs processing
     *
     * @return bool
     */
    public function needsProcessing(): bool
    {
        return $this->isAudioFile() && 
               $this->shouldBeWatermarked() && 
               !$this->audio_processed;
    }

    /**
     * Mark file as processed and store results
     *
     * @param array $processedData
     * @return void
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
            'processing_error' => null // Clear any previous errors
        ]);
    }

    /**
     * Mark file as failed processing
     *
     * @param string $error
     * @return void
     */
    public function markAsProcessingFailed(string $error): void
    {
        $this->update([
            'audio_processed' => false,
            'processing_error' => $error
        ]);
    }
}
