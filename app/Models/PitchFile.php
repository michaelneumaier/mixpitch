<?php

// app/Models/PitchFile.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'duration'
    ];

    protected $casts = [
        'waveform_processed' => 'boolean',
        'waveform_processed_at' => 'datetime',
        'duration' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
        try {
            // Return a signed URL with a longer expiration (30 minutes) for audio playback
            $url = Storage::disk('s3')->temporaryUrl(
                $this->file_path,
                now()->addMinutes(30)
            );
            
            // Ensure the URL is properly formatted for JavaScript
            return $url;
        } catch (Exception $e) {
            \Log::error('Error generating signed S3 URL for pitch file', [
                'file_id' => $this->id,
                'file_path' => $this->file_path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
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
}
