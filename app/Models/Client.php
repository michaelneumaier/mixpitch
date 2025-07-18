<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    // Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'user_id',
        'email',
        'name',
        'company',
        'phone',
        'timezone',
        'preferences',
        'notes',
        'tags',
        'status',
        'last_contacted_at',
        'total_spent',
        'total_projects',
    ];

    protected $casts = [
        'preferences' => 'array',
        'tags' => 'array',
        'last_contacted_at' => 'datetime',
        'total_spent' => 'decimal:2',
        'total_projects' => 'integer',
    ];

    /**
     * Get the producer who owns this client.
     */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all projects for this client.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_email', 'email');
    }

    /**
     * Get active projects for this client.
     */
    public function activeProjects(): HasMany
    {
        return $this->projects()->whereNotIn('status', [
            Project::STATUS_COMPLETED,
        ]);
    }

    /**
     * Get completed projects for this client.
     */
    public function completedProjects(): HasMany
    {
        return $this->projects()->where('status', Project::STATUS_COMPLETED);
    }

    /**
     * Get the most recent project for this client.
     */
    public function latestProject()
    {
        return $this->projects()->latest()->first();
    }

    /**
     * Update client statistics based on their projects.
     */
    public function updateStats(): void
    {
        $projectStats = $this->projects()
            ->selectRaw('
                COUNT(*) as project_count,
                COALESCE(SUM(CASE WHEN pitches.payment_status = "paid" THEN pitches.payment_amount ELSE 0 END), 0) as total_revenue
            ')
            ->leftJoin('pitches', 'projects.id', '=', 'pitches.project_id')
            ->first();

        $this->update([
            'total_projects' => $projectStats->project_count ?? 0,
            'total_spent' => $projectStats->total_revenue ?? 0,
        ]);
    }

    /**
     * Update the last contacted timestamp.
     */
    public function markAsContacted(): void
    {
        $this->update(['last_contacted_at' => now()]);
    }

    /**
     * Scope for active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for inactive clients.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope for blocked clients.
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', self::STATUS_BLOCKED);
    }

    /**
     * Scope for clients with recent activity.
     */
    public function scopeRecentlyContacted($query, $days = 30)
    {
        return $query->where('last_contacted_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for clients needing follow-up.
     */
    public function scopeNeedsFollowUp($query, $days = 14)
    {
        return $query->where(function ($q) use ($days) {
            $q->whereNull('last_contacted_at')
              ->orWhere('last_contacted_at', '<', now()->subDays($days));
        });
    }

    /**
     * Get the client's display name (name or email if no name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->email;
    }

    /**
     * Get the client's initials for avatar display.
     */
    public function getInitialsAttribute(): string
    {
        if ($this->name) {
            $nameParts = explode(' ', trim($this->name));
            if (count($nameParts) >= 2) {
                return strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
            }
            return strtoupper(substr($this->name, 0, 2));
        }
        
        return strtoupper(substr($this->email, 0, 2));
    }

    /**
     * Check if the client is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the client is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Check if the client is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * Get all available status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive', 
            self::STATUS_BLOCKED => 'Blocked',
        ];
    }

    /**
     * Get the status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the CSS class for status badge.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'bg-green-100 text-green-800',
            self::STATUS_INACTIVE => 'bg-gray-100 text-gray-800',
            self::STATUS_BLOCKED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
