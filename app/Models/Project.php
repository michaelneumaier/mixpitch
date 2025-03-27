<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

use Illuminate\Support\Facades\Storage;
use Sebdesign\SM\StateMachine\StateMachine;
use Sebdesign\SM\StateMachine\StateMachineInterface;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;
    use Sluggable;

    // Constants for project statuses
    const STATUS_UNPUBLISHED = 'unpublished';
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    /**
     * The maximum storage allowed per project in bytes (1GB)
     */
    const MAX_STORAGE_BYTES = 1073741824; // 1GB in bytes
    
    /**
     * The maximum file size allowed per upload in bytes (200MB)
     */
    const MAX_FILE_SIZE_BYTES = 209715200; // 200MB in bytes

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'genre',
        'status',
        'image_path',
        'slug',
        'artist_name',
        'project_type',
        'collaboration_type',
        'budget',
        'deadline',
        'preview_track',
        'notes',
        'is_published',
        'completed_at'
    ];

    protected $casts = [
        'collaboration_type' => 'array',
        'is_published' => 'boolean',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_UNPUBLISHED,
        'is_published' => false
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOwnedByUser(User $user)
    {
        return $this->user_id == $user->id;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->save();
    }

    /**
     * Publish the project
     * 
     * @return void
     */
    public function publish()
    {
        $this->is_published = true;
        
        // Only change status if it's not already completed
        if ($this->status === self::STATUS_UNPUBLISHED) {
            $this->status = self::STATUS_OPEN;
        }
        
        $this->save();
    }
    
    /**
     * Unpublish the project
     * 
     * @return void
     */
    public function unpublish()
    {
        $this->is_published = false;
        
        // If the project is not completed, set status to unpublished
        if ($this->status !== self::STATUS_COMPLETED) {
            $this->status = self::STATUS_UNPUBLISHED;
        }
        
        $this->save();
    }

    public function hasPreviewTrack()
    {
        if ($this->preview_track) {
            return true;
        } else {
            return false;
        }
    }

    public function previewTrack()
    {
        return $this->hasOne(ProjectFile::class, 'id', 'preview_track');
    }
    public function previewTrackPath()
    {
        if ($this->hasPreviewTrack()) {
            $track = $this->previewTrack;
            try {
                return Storage::disk('s3')->temporaryUrl(
                    $track->file_path,
                    now()->addMinutes(15)
                );
            } catch (Exception $e) {
                \Log::error('Error getting signed preview track path', [
                    'track_id' => $this->preview_track,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        } else {
            return null;
        }
    }

    public function deleteProjectImage()
    {
        try {
            return Storage::disk('s3')->delete($this->image_path);
        } catch (Exception $e) {
            \Log::error('Error deleting project image', [
                'project_id' => $this->id,
                'image_path' => $this->image_path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function tracks()
    {
        return $this->hasMany(Track::class);
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function pitches()
    {
        return $this->hasMany(Pitch::class);
    }

    public function userPitch($userId)
    {
        return $this->pitches()->where('user_id', $userId)->first();
    }

    public function mixes()
    {
        return $this->hasMany(Mix::class);
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    /**
     * Sync project status with its pitches
     * Updates the project status based on the status of its pitches
     * 
     * @return bool Whether the status changed
     */
    public function syncStatusWithPitches()
    {
        $oldStatus = $this->status;
        $hasCompletedPitch = false;
        $hasApprovedPitch = false;
        $hasActivePitch = false;
        
        // If the project has no pitches, keep its current status
        if ($this->pitches()->count() === 0) {
            return false;
        }
        
        // Check for completed, approved, and active pitches
        foreach ($this->pitches as $pitch) {
            if ($pitch->status === Pitch::STATUS_COMPLETED) {
                $hasCompletedPitch = true;
            } elseif ($pitch->status === Pitch::STATUS_APPROVED) {
                $hasApprovedPitch = true;
            } elseif (!in_array($pitch->status, [Pitch::STATUS_CLOSED, Pitch::STATUS_DENIED])) {
                $hasActivePitch = true;
            }
        }
        
        // Determine the new status based on pitch statuses
        if ($hasCompletedPitch) {
            $this->status = self::STATUS_COMPLETED;
            $this->completed_at = $this->completed_at ?? now();
        } elseif ($hasApprovedPitch || $hasActivePitch) {
            // Projects with either approved pitches or active pitches should be OPEN
            $this->status = self::STATUS_OPEN;
            
            // If there was a completed_at date, clear it since the project is no longer completed
            if ($this->completed_at) {
                $this->completed_at = null;
            }
        }
        
        // Only save if status has changed
        if ($this->status !== $oldStatus) {
            $this->save();
            
            // Log the status change
            \Log::info('Project status changed', [
                'project_id' => $this->id,
                'old_status' => $oldStatus,
                'new_status' => $this->status,
                'completed_at' => $this->completed_at
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if a new pitch can be created for this project
     * 
     * @param User|null $user The user attempting to create a pitch
     * @return array [bool $canCreatePitch, string $errorMessage]
     */
    public function canCreatePitch($user = null)
    {
        // Check if user is the project owner
        if ($user && $this->isOwnedByUser($user)) {
            return [false, 'You cannot create a pitch for your own project.'];
        }
        
        // Cannot create pitch if project is completed
        if ($this->status === self::STATUS_COMPLETED) {
            return [false, 'This project has been completed and is not accepting new pitches.'];
        }
        
        // Cannot create pitch if project is unpublished
        if ($this->status === self::STATUS_UNPUBLISHED) {
            return [false, 'This project is unpublished and is not accepting pitches.'];
        }
        
        return [true, ''];
    }
    
    /**
     * Mark the project as completed
     * 
     * @param int|null $completedPitchId ID of the pitch that completed the project
     * @return bool
     */
    public function markAsCompleted($completedPitchId = null)
    {
        try {
            $this->status = self::STATUS_COMPLETED;
            $this->completed_at = now();
            
            // Log the completion event with the pitch ID if provided
            if ($completedPitchId) {
                \Log::info('Project marked as completed', [
                    'project_id' => $this->id,
                    'completed_by_pitch_id' => $completedPitchId,
                    'completed_at' => $this->completed_at
                ]);
            }
            
            return $this->save();
        } catch (\Exception $e) {
            \Log::error('Error marking project as completed', [
                'project_id' => $this->id,
                'completed_by_pitch_id' => $completedPitchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to ensure the calling method can handle it
        }
    }

    // public function stateMachine(): StateMachineInterface
    // {
    //     return new StateMachine($this, 'project_status', config('state-machine'));
    // }

    /**
     * Get the full URL for the project image
     *
     * @return string|null
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }
        
        try {
            return Storage::disk('s3')->temporaryUrl(
                $this->image_path,
                now()->addHours(1) // Longer expiration for images since they're used in UI
            );
        } catch (Exception $e) {
            \Log::error('Error getting signed project image URL', [
                'project_id' => $this->id,
                'image_path' => $this->image_path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate a unique hash representing the current state of project files
     * This is used for caching the ZIP file
     *
     * @return string
     */
    public function getFilesStateHash()
    {
        // Get all files sorted by id to ensure consistent ordering
        $files = $this->files()->orderBy('id')->get();
        
        // If no files, return a default hash
        if ($files->isEmpty()) {
            return md5('empty_project_' . $this->id);
        }
        
        // Build a string containing all file info: id, path, size, updated_at
        $filesData = $files->map(function($file) {
            return $file->id . $file->file_path . $file->size . $file->updated_at->timestamp;
        })->implode('|');
        
        // Create a hash for the current state
        return md5($filesData);
    }
    
    /**
     * Get the path where a cached ZIP would be stored in S3
     *
     * @return string
     */
    public function getCachedZipPath()
    {
        $hash = $this->getFilesStateHash();
        return 'project_archives/' . $this->id . '_' . $hash . '.zip';
    }
    
    /**
     * Check if a cached ZIP file exists for the current project state
     *
     * @return bool
     */
    public function hasCachedZip()
    {
        $zipPath = $this->getCachedZipPath();
        return Storage::disk('s3')->exists($zipPath);
    }
    
    /**
     * Get a signed URL for the cached ZIP file
     * 
     * @param int $expirationMinutes Minutes until URL expires (default: 30)
     * @return string|null
     */
    public function getCachedZipUrl($expirationMinutes = 30)
    {
        if (!$this->hasCachedZip()) {
            return null;
        }
        
        try {
            return Storage::disk('s3')->temporaryUrl(
                $this->getCachedZipPath(),
                now()->addMinutes($expirationMinutes),
                [
                    'ResponseContentDisposition' => 'attachment; filename="' . Str::slug($this->name) . '_files.zip"',
                    'ResponseContentType' => 'application/zip'
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Error generating signed URL for cached ZIP', [
                'project_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Delete the cached ZIP file for this project
     *
     * @return void
     */
    public function deleteCachedZip()
    {
        try {
            $zipPath = $this->getCachedZipPath();
            if (Storage::disk('s3')->exists($zipPath)) {
                Storage::disk('s3')->delete($zipPath);
                \Log::info('Deleted cached ZIP file', [
                    'project_id' => $this->id,
                    'zipPath' => $zipPath
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error deleting cached ZIP file', [
                'project_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if the project has available storage capacity
     * 
     * @param int $additionalBytes Additional bytes to check if they would fit
     * @return bool
     */
    public function hasStorageCapacity($additionalBytes = 0)
    {
        return ($this->total_storage_used + $additionalBytes) <= self::MAX_STORAGE_BYTES;
    }
    
    /**
     * Get remaining storage capacity in bytes
     * 
     * @return int
     */
    public function getRemainingStorageBytes()
    {
        $remaining = self::MAX_STORAGE_BYTES - $this->total_storage_used;
        return max(0, $remaining);
    }
    
    /**
     * Format bytes to human-readable size
     * 
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Get the percentage of storage used
     * 
     * @return float
     */
    public function getStorageUsedPercentage()
    {
        return min(100, round(($this->total_storage_used / self::MAX_STORAGE_BYTES) * 100, 1));
    }
    
    /**
     * Update the project's total storage used
     * 
     * @param int $bytes Amount to add (positive) or subtract (negative)
     * @return bool
     */
    public function updateStorageUsed($bytes)
    {
        $this->total_storage_used += $bytes;
        
        // Ensure we don't go below zero
        if ($this->total_storage_used < 0) {
            $this->total_storage_used = 0;
        }
        
        return $this->save();
    }
    
    /**
     * Check if a file size is within the allowed limit
     * 
     * @param int $fileSize File size in bytes
     * @return bool
     */
    public static function isFileSizeAllowed($fileSize)
    {
        return $fileSize <= self::MAX_FILE_SIZE_BYTES;
    }
    
    /**
     * Get user-friendly message about storage limits
     * 
     * @return string
     */
    public function getStorageLimitMessage()
    {
        $used = self::formatBytes($this->total_storage_used);
        $total = self::formatBytes(self::MAX_STORAGE_BYTES);
        $remaining = self::formatBytes($this->getRemainingStorageBytes());
        
        return "Using $used of $total ($remaining available)";
    }
}
