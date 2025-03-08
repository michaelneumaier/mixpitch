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

class Project extends Model
{
    use HasFactory;
    use Sluggable;

    // Constants for project statuses
    const STATUS_UNPUBLISHED = 'unpublished';
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

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
                return asset('storage/' . $track->file_path);
            } catch (Exception $e) {
                return null;
            }
        } else {
            return null;
        }
    }

    public function deleteProjectImage()
    {
        try {
            return Storage::disk('public')->delete($this->image_path);
        } catch (Exception $e) {
            return $e;
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
     * @return array [bool $canCreatePitch, string $errorMessage]
     */
    public function canCreatePitch()
    {
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
    }

    // public function stateMachine(): StateMachineInterface
    // {
    //     return new StateMachine($this, 'project_status', config('state-machine'));
    // }
}
