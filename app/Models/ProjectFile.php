<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProjectFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'file_path',
        'storage_path',
        'file_name',
        'original_file_name',
        'mime_type',
        'user_id',
        'size',
        'is_preview_track',
        'metadata',
    ];

    public function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    public function getFormattedSizeAttribute()
    {
        // Assuming the column where you store the file size in bytes is called 'size'
        $bytes = $this->attributes['size'];

        // Use the helper function to format the bytes
        return $this->formatBytes($bytes);
    }

    /**
     * Get the display name for the file (original filename if available, otherwise basename of file_path)
     */
    public function getFileNameAttribute()
    {
        return $this->original_file_name ?: basename($this->file_path);
    }

    /**
     * Get the full file path - now returns a signed URL with a short expiration
     * This is used for display purposes like audio players
     *
     * @return string|null
     */
    public function getFullFilePathAttribute()
    {
        try {
            // Return a signed URL with a longer expiration (30 minutes) for audio playback
            return Storage::disk('s3')->temporaryUrl(
                $this->file_path,
                now()->addMinutes(30),
                [
                    'ResponseContentType' => 'audio/mpeg',
                    'ResponseCacheControl' => 'no-cache',
                ]
            );
        } catch (Exception $e) {
            \Log::error('Error generating signed S3 URL for file', [
                'file_path' => $this->file_path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Get a signed URL for downloading the file
     * This uses a longer expiration time and appropriate headers for downloading
     *
     * @param  int  $expirationMinutes  Minutes until URL expires (default: 60)
     * @return string|null
     */
    public function getSignedUrlAttribute($expirationMinutes = 60)
    {
        try {
            return Storage::disk('s3')->temporaryUrl(
                $this->file_path,
                now()->addMinutes($expirationMinutes),
                [
                    'ResponseContentDisposition' => 'attachment; filename="'.$this->getFileNameAttribute().'"',
                ]
            );
        } catch (Exception $e) {
            \Log::error('Error generating signed download URL for file', [
                'file_path' => $this->file_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate a signed URL with custom expiration time
     * This is a method implementation of the getSignedUrlAttribute accessor
     * that allows passing a custom expiration time
     *
     * @param  int  $expirationMinutes  Minutes until URL expires
     * @return string|null
     */
    public function signedUrl($expirationMinutes = 60)
    {
        try {
            return Storage::disk('s3')->temporaryUrl(
                $this->file_path,
                now()->addMinutes($expirationMinutes),
                [
                    'ResponseContentDisposition' => 'attachment; filename="'.$this->getFileNameAttribute().'"',
                ]
            );
        } catch (Exception $e) {
            \Log::error('Error generating signed download URL for file', [
                'file_path' => $this->file_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if file is an audio file
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
            'audio/wave',
            'audio/x-wav',
            'audio/ogg',
            'audio/aac',
            'audio/m4a',
            'audio/mp4',
            'audio/flac',
            'audio/x-flac',
            'audio/webm',
        ];

        return in_array($this->mime_type, $audioMimes);
    }
}
