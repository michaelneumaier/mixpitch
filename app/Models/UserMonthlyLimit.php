<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Carbon\Carbon;

class UserMonthlyLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'month_year',
        'visibility_boosts_used',
        'private_projects_created',
        'license_templates_created',
        'additional_usage',
        'last_reset_at',
        'auto_reset_enabled',
    ];

    protected $casts = [
        'additional_usage' => 'array',
        'last_reset_at' => 'datetime',
        'auto_reset_enabled' => 'boolean',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the user this limit tracking belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== STATIC METHODS ==========

    /**
     * Get or create monthly limit record for user
     *
     * @param User $user
     * @param string|null $monthYear
     * @return static
     */
    public static function getOrCreateForUser(User $user, ?string $monthYear = null): self
    {
        $monthYear = $monthYear ?? now()->format('Y-m');
        
        return self::updateOrCreate(
            ['user_id' => $user->id, 'month_year' => $monthYear],
            ['last_reset_at' => now()]
        );
    }

    /**
     * Reset all users' monthly limits for new month
     *
     * @return int Number of records reset
     */
    public static function resetForNewMonth(): int
    {
        $lastMonth = now()->subMonth()->format('Y-m');
        $currentMonth = now()->format('Y-m');
        
        // Archive last month's data by updating month_year
        // and reset counters for users who had usage
        $users = User::whereHas('monthlyLimits', function ($query) use ($lastMonth) {
            $query->where('month_year', $lastMonth);
        })->get();
        
        $resetCount = 0;
        foreach ($users as $user) {
            self::getOrCreateForUser($user, $currentMonth);
            $resetCount++;
        }
        
        return $resetCount;
    }

    // ========== VISIBILITY BOOST METHODS ==========

    /**
     * Increment visibility boost usage
     *
     * @return void
     */
    public function incrementVisibilityBoosts(): void
    {
        $this->increment('visibility_boosts_used');
    }

    /**
     * Check if user can use more visibility boosts
     *
     * @return bool
     */
    public function canUseVisibilityBoost(): bool
    {
        $limit = $this->user->getMonthlyVisibilityBoosts();
        return $limit === 0 ? false : $this->visibility_boosts_used < $limit;
    }

    /**
     * Get remaining visibility boosts
     *
     * @return int
     */
    public function getRemainingVisibilityBoosts(): int
    {
        $limit = $this->user->getMonthlyVisibilityBoosts();
        return max(0, $limit - $this->visibility_boosts_used);
    }

    // ========== PRIVATE PROJECT METHODS ==========

    /**
     * Increment private projects created
     *
     * @return void
     */
    public function incrementPrivateProjects(): void
    {
        $this->increment('private_projects_created');
    }

    /**
     * Check if user can create more private projects
     *
     * @return bool
     */
    public function canCreatePrivateProject(): bool
    {
        $limit = $this->user->getMaxPrivateProjectsMonthly();
        
        if ($limit === null) {
            return true; // Unlimited
        }
        
        if ($limit === 0) {
            return false; // Not allowed
        }
        
        return $this->private_projects_created < $limit;
    }

    /**
     * Get remaining private projects
     *
     * @return int|null
     */
    public function getRemainingPrivateProjects(): ?int
    {
        $limit = $this->user->getMaxPrivateProjectsMonthly();
        
        if ($limit === null) {
            return null; // Unlimited
        }
        
        return max(0, $limit - $this->private_projects_created);
    }

    // ========== LICENSE TEMPLATE METHODS ==========

    /**
     * Increment license templates created
     *
     * @return void
     */
    public function incrementLicenseTemplates(): void
    {
        $this->increment('license_templates_created');
    }

    /**
     * Check if user can create more license templates
     *
     * @return bool
     */
    public function canCreateLicenseTemplate(): bool
    {
        $limit = $this->user->getMaxLicenseTemplates();
        
        if ($limit === null) {
            return true; // Unlimited
        }
        
        // Note: License templates are usually lifetime limits, not monthly
        // But we track monthly creation for analytics
        $totalTemplates = $this->user->licenseTemplates()->count();
        return $totalTemplates < $limit;
    }

    // ========== GENERAL USAGE METHODS ==========

    /**
     * Add to additional usage tracking
     *
     * @param string $feature
     * @param int $amount
     * @return void
     */
    public function incrementAdditionalUsage(string $feature, int $amount = 1): void
    {
        $usage = $this->additional_usage ?? [];
        $usage[$feature] = ($usage[$feature] ?? 0) + $amount;
        $this->update(['additional_usage' => $usage]);
    }

    /**
     * Get usage for a specific feature
     *
     * @param string $feature
     * @return int
     */
    public function getAdditionalUsage(string $feature): int
    {
        return $this->additional_usage[$feature] ?? 0;
    }

    /**
     * Reset monthly counters
     *
     * @return void
     */
    public function resetCounters(): void
    {
        $this->update([
            'visibility_boosts_used' => 0,
            'private_projects_created' => 0,
            'license_templates_created' => 0,
            'additional_usage' => [],
            'last_reset_at' => now(),
        ]);
    }

    /**
     * Get usage summary
     *
     * @return array
     */
    public function getUsageSummary(): array
    {
        $user = $this->user;
        
        return [
            'month_year' => $this->month_year,
            'visibility_boosts' => [
                'used' => $this->visibility_boosts_used,
                'limit' => $user->getMonthlyVisibilityBoosts(),
                'remaining' => $this->getRemainingVisibilityBoosts(),
            ],
            'private_projects' => [
                'created' => $this->private_projects_created,
                'limit' => $user->getMaxPrivateProjectsMonthly(),
                'remaining' => $this->getRemainingPrivateProjects(),
            ],
            'license_templates' => [
                'created_this_month' => $this->license_templates_created,
                'total_templates' => $user->licenseTemplates()->count(),
                'limit' => $user->getMaxLicenseTemplates(),
            ],
            'additional_usage' => $this->additional_usage ?? [],
        ];
    }

    // ========== SCOPES ==========

    /**
     * Scope to current month
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('month_year', now()->format('Y-m'));
    }

    /**
     * Scope by specific month
     */
    public function scopeByMonth($query, string $monthYear)
    {
        return $query->where('month_year', $monthYear);
    }

    /**
     * Scope to users with usage
     */
    public function scopeWithUsage($query)
    {
        return $query->where(function ($q) {
            $q->where('visibility_boosts_used', '>', 0)
              ->orWhere('private_projects_created', '>', 0)
              ->orWhere('license_templates_created', '>', 0)
              ->orWhereNotNull('additional_usage');
        });
    }
}
