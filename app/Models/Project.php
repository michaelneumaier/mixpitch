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
use Illuminate\Support\Number;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
        'deadline' => 'datetime',
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
        return $this->pitches()->where('user_id', $userId)->with('project')->first();
    }

    /**
     * Check if the project is open and accepting new pitches.
     *
     * @return bool
     */
    public function isOpenForPitches(): bool
    {
        return $this->status === self::STATUS_OPEN;
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
     * Check if the project has available storage capacity
     * 
     * @param int $additionalBytes Additional bytes to check if they would fit
     * @return bool
     */
    public function hasStorageCapacity($additionalBytes = 0)
    {
        // Use the limit set in the database if it exists, otherwise fall back to constant
        $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
        
        return ($this->total_storage_used + $additionalBytes) <= $storageLimit;
    }
    
    /**
     * Get remaining storage capacity in bytes
     * 
     * @return int
     */
    public function getRemainingStorageBytes()
    {
        $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
        $remaining = $storageLimit - $this->total_storage_used;
        return max(0, $remaining);
    }
    
    /**
     * Get the percentage of storage used
     * 
     * @return float
     */
    public function getStorageUsedPercentage()
    {
        $storageLimit = $this->total_storage_limit_bytes ?? self::MAX_STORAGE_BYTES;
        return round(($this->total_storage_used / $storageLimit) * 100, 2);
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
        $used = Number::fileSize($this->total_storage_used, precision: 2);
        $total = Number::fileSize(self::MAX_STORAGE_BYTES, precision: 2);
        $remaining = Number::fileSize($this->getRemainingStorageBytes(), precision: 2);
        
        return "Using $used of $total ($remaining available)";
    }

    /**
     * Atomically increment the total storage used.
     */
    public function incrementStorageUsed(int $bytes): bool
    {
        return $this->increment('total_storage_used', $bytes);
    }

    /**
     * Atomically decrement the total storage used.
     */
    public function decrementStorageUsed(int $bytes): bool
    {
        return $this->update([
            'total_storage_used' => DB::raw("GREATEST(0, total_storage_used - $bytes)")
        ]);
    }

    /**
     * Scope a query to apply filters and sorting.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterAndSort(Builder $query, array $filters): Builder
    {
        // Apply filters
        $query->when($filters['genres'] ?? null, function ($q, $genres) {
            $q->whereIn('genre', $genres);
        });

        $query->when($filters['statuses'] ?? null, function ($q, $statuses) {
            $q->whereIn('status', $statuses);
        });

        $query->when($filters['projectTypes'] ?? null, function ($q, $projectTypes) {
            $q->whereIn('project_type', $projectTypes);
        });

        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('name', 'like', '%' . $search . '%')
                   ->orWhere('description', 'like', '%' . $search . '%');
            });
        });

        $minBudget = $filters['min_budget'] ?? null;
        $maxBudget = $filters['max_budget'] ?? null;
        $query->when($minBudget && $maxBudget, function ($q) use ($minBudget, $maxBudget) {
            $q->whereBetween('budget', [(int)$minBudget, (int)$maxBudget]);
        })->when($minBudget && !$maxBudget, function ($q) use ($minBudget) {
            $q->where('budget', '>=', (int)$minBudget);
        })->when(!$minBudget && $maxBudget, function ($q) use ($maxBudget) {
            $q->where('budget', '<=', (int)$maxBudget);
        });

        $deadlineStart = $filters['deadline_start'] ?? null;
        $deadlineEnd = $filters['deadline_end'] ?? null;

        // Try using Carbon objects directly for comparison
        $start = $deadlineStart ? \Carbon\Carbon::parse($deadlineStart)->startOfDay() : null;
        $end = $deadlineEnd ? \Carbon\Carbon::parse($deadlineEnd)->endOfDay() : null;

        $query->when($start && $end, function ($q) use ($start, $end) {
            $q->whereBetween('deadline', [$start, $end]);
        })->when($start && !$end, function ($q) use ($start) {
            $q->where('deadline', '>=', $start); // where() should handle Carbon comparison
        })->when(!$start && $end, function ($q) use ($end) {
            $q->where('deadline', '<=', $end); // where() should handle Carbon comparison
        });

        // Collaboration Type Filtering (JSON array)
        $query->when($filters['selected_collaboration_types'] ?? null, function ($q, $types) {
            // Ensure $types is an array
            if (!is_array($types) || empty($types)) {
                return;
            }
            
            // Check if we're using SQLite
            $isSqlite = \DB::connection()->getDriverName() === 'sqlite';
            
            if ($isSqlite) {
                // SQLite-compatible alternative approach
                // This is a fallback that works in SQLite but might be less efficient
                $q->where(function (Builder $subQuery) use ($types) {
                    foreach ($types as $type) {
                        // Use LIKE for SQLite as a simple workaround
                        // This is less precise but allows filtering to work
                        $subQuery->orWhere('collaboration_type', 'LIKE', '%"' . $type . '"%');
                    }
                });
            } else {
                // Standard approach for MySQL/PostgreSQL
                $q->where(function (Builder $subQuery) use ($types) {
                    foreach ($types as $type) {
                        $subQuery->orWhereJsonContains('collaboration_type', $type);
                    }
                });
            }
        });

        // Apply sorting
        $sortBy = $filters['sortBy'] ?? 'latest';
        switch ($sortBy) {
            case 'budget_high_low':
                $query->orderBy('budget', 'desc');
                break;
            case 'budget_low_high':
                $query->orderBy('budget', 'asc');
                break;
            case 'deadline':
                $query->orderBy('deadline', 'asc');
                break;
            case 'oldest':
                $query->oldest();
                break;
            default: // 'latest'
                $query->latest();
                break;
        }

        return $query;
    }
}
