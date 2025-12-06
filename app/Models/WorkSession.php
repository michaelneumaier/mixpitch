<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkSession extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_ENDED = 'ended';

    // Presence visibility constants
    public const VISIBILITY_FULL = 'full';

    public const VISIBILITY_SUMMARY = 'summary';

    public const VISIBILITY_MINIMAL = 'minimal';

    protected $fillable = [
        'user_id',
        'pitch_id',
        'status',
        'started_at',
        'paused_at',
        'ended_at',
        'total_duration_seconds',
        'notes',
        'is_visible_to_client',
        'focus_mode',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_visible_to_client' => 'boolean',
        'focus_mode' => 'boolean',
        'total_duration_seconds' => 'integer',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePaused($query)
    {
        return $query->where('status', self::STATUS_PAUSED);
    }

    public function scopeEnded($query)
    {
        return $query->where('status', self::STATUS_ENDED);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PAUSED]);
    }

    public function scopeForPitch($query, Pitch $pitch)
    {
        return $query->where('pitch_id', $pitch->id);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeVisibleToClient($query)
    {
        return $query->where('is_visible_to_client', true);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    // Helper methods

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    public function isEnded(): bool
    {
        return $this->status === self::STATUS_ENDED;
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PAUSED]);
    }

    /**
     * Get the current duration including any active time
     */
    public function getCurrentDuration(): int
    {
        $duration = $this->total_duration_seconds;

        // If session is active, add time since last resume/start
        if ($this->isActive()) {
            $activeStart = $this->paused_at ?? $this->started_at;
            $duration += now()->diffInSeconds($activeStart);
        }

        return $duration;
    }

    /**
     * Get formatted duration string
     */
    public function getFormattedDuration(): string
    {
        $seconds = $this->getCurrentDuration();

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        return sprintf('%dm', $minutes);
    }

    /**
     * Get a human-readable status
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Working',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_ENDED => 'Completed',
            default => 'Unknown',
        };
    }
}
