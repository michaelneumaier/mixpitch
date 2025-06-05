<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VisibilityBoost extends Model
{
    use HasFactory;

    /**
     * Boost type constants
     */
    const TYPE_PROJECT = 'project';
    const TYPE_PITCH = 'pitch';
    const TYPE_PROFILE = 'profile';

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Default boost duration in hours
     */
    const DEFAULT_DURATION_HOURS = 72;

    protected $fillable = [
        'user_id',
        'project_id',
        'pitch_id',
        'boost_type',
        'started_at',
        'expires_at',
        'status',
        'views_before',
        'views_during',
        'ranking_multiplier',
        'month_year',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'ranking_multiplier' => 'decimal:2',
        'metadata' => 'array',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the user who created this boost
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project being boosted (if applicable)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the pitch being boosted (if applicable)
     */
    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    // ========== STATIC CREATION METHODS ==========

    /**
     * Create a boost for a project
     *
     * @param User $user
     * @param Project $project
     * @param int $durationHours
     * @param array $additionalData
     * @return static|null
     */
    public static function createForProject(
        User $user,
        Project $project,
        int $durationHours = self::DEFAULT_DURATION_HOURS,
        array $additionalData = []
    ): ?self {
        // Check if user can create boost
        if (!self::canUserCreateBoost($user)) {
            return null;
        }

        $boost = self::create(array_merge([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'boost_type' => self::TYPE_PROJECT,
            'started_at' => now(),
            'expires_at' => now()->addHours($durationHours),
            'month_year' => now()->format('Y-m'),
            'ranking_multiplier' => 2.0,
        ], $additionalData));

        // Update monthly usage
        self::incrementMonthlyUsage($user);

        return $boost;
    }

    /**
     * Create a boost for a pitch
     *
     * @param User $user
     * @param Pitch $pitch
     * @param int $durationHours
     * @param array $additionalData
     * @return static|null
     */
    public static function createForPitch(
        User $user,
        Pitch $pitch,
        int $durationHours = self::DEFAULT_DURATION_HOURS,
        array $additionalData = []
    ): ?self {
        if (!self::canUserCreateBoost($user)) {
            return null;
        }

        $boost = self::create(array_merge([
            'user_id' => $user->id,
            'pitch_id' => $pitch->id,
            'boost_type' => self::TYPE_PITCH,
            'started_at' => now(),
            'expires_at' => now()->addHours($durationHours),
            'month_year' => now()->format('Y-m'),
            'ranking_multiplier' => 2.0,
        ], $additionalData));

        self::incrementMonthlyUsage($user);
        return $boost;
    }

    /**
     * Create a profile boost
     *
     * @param User $user
     * @param int $durationHours
     * @param array $additionalData
     * @return static|null
     */
    public static function createForProfile(
        User $user,
        int $durationHours = self::DEFAULT_DURATION_HOURS,
        array $additionalData = []
    ): ?self {
        if (!self::canUserCreateBoost($user)) {
            return null;
        }

        $boost = self::create(array_merge([
            'user_id' => $user->id,
            'boost_type' => self::TYPE_PROFILE,
            'started_at' => now(),
            'expires_at' => now()->addHours($durationHours),
            'month_year' => now()->format('Y-m'),
            'ranking_multiplier' => 1.5, // Lower multiplier for profile boosts
        ], $additionalData));

        self::incrementMonthlyUsage($user);
        return $boost;
    }

    // ========== STATIC UTILITY METHODS ==========

    /**
     * Check if user can create a new boost this month
     *
     * @param User $user
     * @return bool
     */
    public static function canUserCreateBoost(User $user): bool
    {
        $monthlyLimit = $user->getMonthlyVisibilityBoosts();
        
        if ($monthlyLimit === 0) {
            return false; // Free users can't create boosts
        }

        $currentMonth = now()->format('Y-m');
        $usedThisMonth = self::where('user_id', $user->id)
            ->where('month_year', $currentMonth)
            ->count();

        return $usedThisMonth < $monthlyLimit;
    }

    /**
     * Get remaining boosts for user this month
     *
     * @param User $user
     * @return int
     */
    public static function getRemainingBoosts(User $user): int
    {
        $monthlyLimit = $user->getMonthlyVisibilityBoosts();
        $currentMonth = now()->format('Y-m');
        $usedThisMonth = self::where('user_id', $user->id)
            ->where('month_year', $currentMonth)
            ->count();

        return max(0, $monthlyLimit - $usedThisMonth);
    }

    /**
     * Increment monthly usage tracking
     *
     * @param User $user
     * @return void
     */
    private static function incrementMonthlyUsage(User $user): void
    {
        $currentMonth = now()->format('Y-m');
        
        $monthlyLimit = UserMonthlyLimit::updateOrCreate(
            ['user_id' => $user->id, 'month_year' => $currentMonth],
            ['last_reset_at' => now()]
        );
        
        $monthlyLimit->increment('visibility_boosts_used');
    }

    // ========== INSTANCE METHODS ==========

    /**
     * Check if boost is currently active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && now()->lessThan($this->expires_at);
    }

    /**
     * Check if boost has expired
     *
     * @return bool
     */
    public function hasExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->expires_at);
    }

    /**
     * Cancel the boost
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Mark boost as expired
     *
     * @return void
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Get time remaining in human readable format
     *
     * @return string
     */
    public function getTimeRemainingAttribute(): string
    {
        if ($this->hasExpired()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Get boost effectiveness percentage
     *
     * @return float
     */
    public function getEffectivenessPercentage(): float
    {
        if ($this->views_before === 0) {
            return 0;
        }

        return round(($this->views_during / $this->views_before) * 100, 2);
    }

    /**
     * Update view tracking
     *
     * @param int $newViews
     * @return void
     */
    public function updateViewsDuring(int $newViews): void
    {
        $this->increment('views_during', $newViews);
    }

    /**
     * Get the target being boosted
     *
     * @return Model|null
     */
    public function getTarget()
    {
        return match($this->boost_type) {
            self::TYPE_PROJECT => $this->project,
            self::TYPE_PITCH => $this->pitch,
            self::TYPE_PROFILE => $this->user,
            default => null,
        };
    }

    // ========== SCOPES ==========

    /**
     * Scope to only active boosts
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to expired boosts
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
              ->orWhere('expires_at', '<=', now());
        });
    }

    /**
     * Scope by boost type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('boost_type', $type);
    }

    /**
     * Scope by month
     */
    public function scopeByMonth($query, string $monthYear)
    {
        return $query->where('month_year', $monthYear);
    }

    /**
     * Scope for current month
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('month_year', now()->format('Y-m'));
    }

    // ========== AUTOMATED CLEANUP ==========

    /**
     * Mark expired boosts as expired (for scheduled job)
     *
     * @return int Number of boosts marked as expired
     */
    public static function markExpiredBoosts(): int
    {
        return self::where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->update(['status' => self::STATUS_EXPIRED]);
    }
}
