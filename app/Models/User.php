<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\ReputationService;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use Billable;
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasRoles;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * Define user roles as constants.
     * This makes role checks consistent across the application.
     */
    const ROLE_CLIENT = 'client';

    const ROLE_PRODUCER = 'producer';

    const ROLE_ADMIN = 'admin'; // Assuming you might need an admin role too

    /**
     * Define subscription plan constants.
     */
    const PLAN_FREE = 'free';

    const PLAN_PRO = 'pro';

    const TIER_BASIC = 'basic';

    const TIER_ARTIST = 'artist';

    const TIER_ENGINEER = 'engineer';

    /**
     * Define billing period constants.
     */
    const BILLING_MONTHLY = 'monthly';

    const BILLING_YEARLY = 'yearly';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'bio',
        'website',
        'tipjar_link',
        'location',
        'timezone',
        'social_links',
        'username_locked',
        'featured_work',
        'headline',
        'portfolio_layout',
        'profile_completed',
        'provider',
        'provider_id',
        'provider_token',
        'provider_refresh_token',
        'role',
        'stripe_account_id',
        'subscription_plan',
        'subscription_tier',
        'billing_period',
        'subscription_price',
        'subscription_currency',
        'total_storage_used',
        'storage_limit_override_gb',
        'plan_started_at',
        'monthly_pitch_count',
        'monthly_pitch_reset_date',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'email_verified_at' => 'datetime',
        'social_links' => 'array',
        'username_locked' => 'boolean',
        'profile_completed' => 'boolean',
        'plan_started_at' => 'datetime',
        'monthly_pitch_reset_date' => 'date',
        'is_admin' => 'boolean',
        'timezone' => 'string',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Update the user's profile photo.
     * This method overrides the one from HasProfilePhoto to fix S3 storage issues.
     *
     * @return void
     */
    public function updateProfilePhoto(UploadedFile $photo, $storagePath = 'profile-photos')
    {
        $disk = $this->profilePhotoDisk();

        // Delete the previous photo if one exists
        if ($this->profile_photo_path) {
            try {
                Storage::disk($disk)->delete($this->profile_photo_path);
            } catch (\Exception $e) {
                Log::warning('Failed to delete old profile photo: '.$e->getMessage(), [
                    'user_id' => $this->id,
                    'path' => $this->profile_photo_path,
                ]);
            }
        }

        // Generate a unique name for the photo
        $fileName = $storagePath.'/'.Str::uuid().'.'.$photo->getClientOriginalExtension();

        // Special handling for Livewire temporary uploads
        if (method_exists($photo, 'getRealPath') &&
            ($photo->getPath() !== '' || $photo->getRealPath() !== '') &&
            file_exists($photo->getRealPath())) {
            // This is a real uploaded file, not just a path reference
            try {
                // Store the file directly to S3 in the profile-photos directory
                $stream = fopen($photo->getRealPath(), 'r');
                Storage::disk($disk)->put($fileName, $stream, [
                    'visibility' => 'public',
                ]);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            } catch (\Exception $e) {
                Log::error('Error uploading profile photo to S3', [
                    'user_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e; // Re-throw to handle in the component
            }
        } else {
            // This might be a Livewire temp path reference
            $tempPath = null;

            // Check if this is a string path to a temp file in livewire-tmp
            if (is_string($photo->getPathname()) && Str::contains($photo->getPathname(), 'livewire-tmp')) {
                $tempPath = $photo->getPathname();

                try {
                    // Copy from the temp S3 location to the final profile-photos location
                    if (Storage::disk($disk)->exists($tempPath)) {
                        Storage::disk($disk)->copy($tempPath, $fileName);
                    } else {
                        Log::error('Livewire temp file not found on S3', [
                            'path' => $tempPath,
                        ]);
                        throw new \Exception('Temporary file not found');
                    }
                } catch (\Exception $e) {
                    Log::error('Error copying S3 temp file to profile-photos', [
                        'user_id' => $this->id,
                        'error' => $e->getMessage(),
                        'tempPath' => $tempPath,
                        'finalPath' => $fileName,
                    ]);
                    throw $e; // Re-throw to handle in the component
                }
            } else {
                // Fallback to standard upload in case it's not working the way we expect
                try {
                    $fileName = $photo->storePubliclyAs($storagePath, Str::uuid().'.'.$photo->getClientOriginalExtension(), [
                        'disk' => $disk,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error in fallback profile photo upload', [
                        'user_id' => $this->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e; // Re-throw to handle in the component
                }
            }
        }

        // Update the user
        $this->forceFill([
            'profile_photo_path' => $fileName,
        ])->save();

        Log::info('Profile photo updated successfully', [
            'user_id' => $this->id,
            'path' => $fileName,
        ]);
    }

    /**
     * Get the URL to the user's profile photo.
     * This method overrides the one from the HasProfilePhoto trait
     * to ensure proper S3 signed URLs are used.
     */
    public function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->profile_photo_path) {
                return $this->defaultProfilePhotoUrl();
            }

            try {
                // Generate a temporary URL with a 1 hour expiration
                $disk = Storage::disk($this->profilePhotoDisk());

                // Check if the storage driver supports temporaryUrl
                if (method_exists($disk->getAdapter(), 'temporaryUrl') ||
                    method_exists($disk, 'temporaryUrl')) {
                    return $disk->temporaryUrl(
                        $this->profile_photo_path,
                        now()->addHour()
                    );
                } else {
                    // Fallback for storage drivers that don't support temporary URLs
                    return $disk->url($this->profile_photo_path);
                }
            } catch (\Exception $e) {
                Log::error('Error getting signed profile photo URL', [
                    'user_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);

                return $this->defaultProfilePhotoUrl();
            }
        });
    }

    /**
     * Check if user has a specific role.
     *
     * This overrides the temporary hasRole method previously in place.
     * It now directly checks the 'role' column.
     *
     * If you were using Spatie permissions before, you might need to adjust
     * or remove this method depending on your setup.
     *
     * @param  string  $role  The role to check for (e.g., User::ROLE_CLIENT)
     */
    public function hasRole($role): bool
    {
        // Check if the user's role matches the provided role constant
        return $this->role === $role;

        // If you decide to use Spatie Permissions later, you would replace
        // the above line with something like:
        // return parent::hasRole($role);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function pitches()
    {
        return $this->hasMany(Pitch::class);
    }

    /**
     * Check if the user has completed their profile
     *
     * @return bool
     */
    public function hasCompletedProfile()
    {
        // Consider a profile complete if the user has set their username and bio
        return ! empty($this->username) && ! empty($this->bio);
    }

    /**
     * Get profile completion status with detailed breakdown
     */
    public function getProfileCompletionStatus(): array
    {
        $fields = [
            'username' => ! empty($this->username),
            'bio' => ! empty($this->bio),
            'location' => ! empty($this->location),
            'website' => ! empty($this->website),
            'social_links' => ! empty($this->social_links),
        ];

        $completedCount = count(array_filter($fields));
        $totalCount = count($fields);
        $percentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;

        return [
            'is_complete' => $this->hasCompletedProfile(),
            'percentage' => $percentage,
            'completed_count' => $completedCount,
            'total_count' => $totalCount,
            'fields' => $fields,
        ];
    }

    /**
     * Get missing profile fields
     */
    public function getMissingProfileFields(): array
    {
        $status = $this->getProfileCompletionStatus();

        return array_keys(array_filter($status['fields'], function ($completed) {
            return ! $completed;
        }));
    }

    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access if the user has the admin role OR if it's the main app panel
        // For tests, handle the case when Panel could be missing an ID
        if ($panel === null) {
            return $this->role === self::ROLE_ADMIN;
        }

        // Get panel ID safely
        try {
            $panelId = $panel->getId();

            return $this->role === self::ROLE_ADMIN || $panelId === 'app';
        } catch (\Exception $e) {
            // If there's an issue getting the panel ID, only allow admins
            return $this->role === self::ROLE_ADMIN;
        }
    }

    /**
     * Get the user's name.
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * Get the user's avatar for Filament.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_photo_url;
    }

    /**
     * Scope a query to only include clients.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClients($query)
    {
        return $query->where('role', self::ROLE_CLIENT);
    }

    /**
     * Scope a query to only include producers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProducers($query)
    {
        return $query->where('role', self::ROLE_PRODUCER);
    }

    /**
     * Calculate the average rating for the user based on completed pitches they created.
     * This shows the ratings a user received for their submitted work, regardless of role.
     *
     * @return array{average: float|null, count: int}
     */
    public function calculateAverageRating(): array
    {
        // Get IDs of completed pitches created by this user
        $completedPitchIds = $this->pitches()
            ->where('status', Pitch::STATUS_COMPLETED)
            ->pluck('id');

        Log::debug('User: '.$this->id.' ('.$this->name.') - Completed pitch IDs:', $completedPitchIds->toArray());

        if ($completedPitchIds->isEmpty()) {
            Log::debug('User: '.$this->id.' - No completed pitches found');

            return ['average' => null, 'count' => 0];
        }

        // Get the ratings from the completion events for those pitches
        $ratings = PitchEvent::whereIn('pitch_id', $completedPitchIds)
            ->where('event_type', 'status_change')
            ->where('status', Pitch::STATUS_COMPLETED)
            ->whereNotNull('rating')
            ->get(['id', 'pitch_id', 'rating', 'created_at']);

        Log::debug('User: '.$this->id.' - Ratings found:', $ratings->toArray());

        if ($ratings->isEmpty()) {
            Log::debug('User: '.$this->id.' - No ratings found for completed pitches');

            return ['average' => null, 'count' => 0];
        }

        $average = $ratings->avg('rating');
        $count = $ratings->count();

        Log::debug('User: '.$this->id.' - Calculated ratings:', [
            'average' => $average,
            'count' => $count,
        ]);

        return [
            'average' => round($average, 1), // Round to one decimal place
            'count' => $count,
        ];
    }

    /**
     * Get the portfolio items for the user.
     */
    public function portfolioItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    /**
     * Get all tags for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function tags()
    {
        $relation = $this->morphToMany(Tag::class, 'taggable');
        Log::debug('User::tags() relation called', [
            'user_id' => $this->id,
            'sql' => $relation->toSql(),
            'bindings' => $relation->getBindings(),
        ]);

        return $relation;
    }

    // ========== SUBSCRIPTION METHODS ==========

    /**
     * Check if user is on free plan
     */
    public function isFreePlan(): bool
    {
        return $this->subscription_plan === self::PLAN_FREE;
    }

    /**
     * Check if user is on pro plan
     */
    public function isProPlan(): bool
    {
        return $this->subscription_plan === self::PLAN_PRO;
    }

    /**
     * Check if user is on monthly billing
     */
    public function isMonthlyBilling(): bool
    {
        return $this->billing_period === self::BILLING_MONTHLY;
    }

    /**
     * Check if user is on yearly billing
     */
    public function isYearlyBilling(): bool
    {
        return $this->billing_period === self::BILLING_YEARLY;
    }

    /**
     * Get the subscription display name
     */
    public function getSubscriptionDisplayName(): string
    {
        if ($this->isFreePlan()) {
            return 'Free';
        }

        $planName = ucfirst($this->subscription_plan);
        if ($this->subscription_tier !== 'basic') {
            $planName .= ' '.ucfirst($this->subscription_tier);
        }

        return $planName;
    }

    /**
     * Get the billing period display name
     */
    public function getBillingPeriodDisplayName(): string
    {
        $periods = config('subscription.billing_periods', []);

        return $periods[$this->billing_period]['name'] ?? ucfirst($this->billing_period);
    }

    /**
     * Get subscription price formatted for display
     */
    public function getFormattedSubscriptionPrice(): string
    {
        if ($this->isFreePlan() || ! $this->subscription_price) {
            return 'Free';
        }

        $currency = $this->subscription_currency ?? 'USD';
        $symbol = $currency === 'USD' ? '$' : $currency.' ';
        $price = number_format($this->subscription_price, 2);
        $period = $this->isYearlyBilling() ? '/year' : '/month';

        return $symbol.$price.$period;
    }

    /**
     * Get yearly savings amount if on yearly plan
     */
    public function getYearlySavings(): ?float
    {
        if (! $this->isYearlyBilling() || $this->isFreePlan()) {
            return null;
        }

        $plans = config('subscription.plans', []);
        $planKey = $this->subscription_plan === 'pro' ? 'pro_'.$this->subscription_tier : $this->subscription_plan;

        return $plans[$planKey]['yearly_savings'] ?? null;
    }

    /**
     * Get next billing date
     */
    public function getNextBillingDate(): ?\Carbon\Carbon
    {
        if ($this->isFreePlan() || ! $this->subscribed('default')) {
            return null;
        }

        $subscription = $this->subscription('default');
        if (! $subscription) {
            return null;
        }

        try {
            $stripeSubscription = $subscription->asStripeSubscription();

            return $stripeSubscription->current_period_end ?
                \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the subscription limits for this user
     *
     * @return SubscriptionLimit|null
     */
    public function getSubscriptionLimits()
    {
        return SubscriptionLimit::where('plan_name', $this->subscription_plan)
            ->where('plan_tier', $this->subscription_tier)
            ->first();
    }

    /**
     * Check if user can create a new project
     */
    public function canCreateProject(): bool
    {
        $limits = $this->getSubscriptionLimits();
        if (! $limits || $limits->max_projects_owned === null) {
            return true; // Unlimited
        }

        // Count only non-completed projects
        $activeProjectsCount = $this->getActiveProjectsCount();

        return $activeProjectsCount < $limits->max_projects_owned;
    }

    /**
     * Get the count of active (non-completed) projects
     */
    public function getActiveProjectsCount(): int
    {
        return $this->projects()
            ->whereNotIn('status', [Project::STATUS_COMPLETED])
            ->count();
    }

    /**
     * Get the count of completed projects
     */
    public function getCompletedProjectsCount(): int
    {
        return $this->projects()
            ->where('status', Project::STATUS_COMPLETED)
            ->count();
    }

    /**
     * Check if user can create a new pitch (general check)
     * Excludes client management from limits
     */
    public function canCreatePitch(): bool
    {
        $limits = $this->getSubscriptionLimits();
        if (! $limits || $limits->max_active_pitches === null) {
            return true; // Unlimited
        }
        $activePitches = $this->getActivePitchesCount();

        return $activePitches < $limits->max_active_pitches;
    }

    /**
     * Check if user can create a new pitch for a specific project
     */
    public function canCreatePitchForProject(Project $project): bool
    {
        // First check general pitch limits
        if (! $this->canCreatePitch()) {
            return false;
        }

        // Additional project-specific checks could go here if needed
        // For now, we just use the general check
        return true;
    }

    /**
     * Check if user can create monthly pitch (for Pro Engineer)
     */
    public function canCreateMonthlyPitch(): bool
    {
        $limits = $this->getSubscriptionLimits();
        if (! $limits || $limits->max_monthly_pitches === null) {
            return true; // Unlimited
        }

        // Reset monthly count if needed
        $this->resetMonthlyPitchCountIfNeeded();

        return $this->monthly_pitch_count < $limits->max_monthly_pitches;
    }

    /**
     * Increment the monthly pitch count (excluding client management)
     * Pass the pitch as an argument
     */
    public function incrementMonthlyPitchCount(Pitch $pitch): void
    {
        $this->resetMonthlyPitchCountIfNeeded();
        if ($pitch->project && ! $pitch->project->isClientManagement()) {
            $this->increment('monthly_pitch_count');
        }
    }

    /**
     * Reset monthly pitch count if needed
     */
    private function resetMonthlyPitchCountIfNeeded(): void
    {
        if (! $this->monthly_pitch_reset_date || $this->monthly_pitch_reset_date->isPast()) {
            $this->update([
                'monthly_pitch_count' => 0,
                'monthly_pitch_reset_date' => now()->addMonth()->startOfMonth()->toDateString(),
            ]);
        }
    }

    /**
     * Get project storage limit for this user in bytes
     */
    public function getProjectStorageLimit(): int
    {
        // Use the new GB-based method instead of the legacy MB method
        return $this->getProjectStorageCapacityBytes();
    }

    // ========== NEW ENHANCED SUBSCRIPTION METHODS ==========

    /**
     * Get project storage limit in GB for this user
     */
    public function getStoragePerProjectGB(): float
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->storage_per_project_gb : 1.0;
    }

    /**
     * Get project storage limit in bytes (using GB field)
     */
    public function getProjectStorageCapacityBytes(): int
    {
        $capacityGB = $this->getStoragePerProjectGB();

        return (int) ($capacityGB * 1024 * 1024 * 1024); // Convert GB to bytes
    }

    // ========== USER-BASED STORAGE METHODS ==========

    /**
     * Get user's total storage limit in GB (with override support)
     */
    public function getStorageLimitGB(): float
    {
        if ($this->storage_limit_override_gb) {
            return (float) $this->storage_limit_override_gb;
        }

        return $this->getSubscriptionLimits()->total_user_storage_gb;
    }

    /**
     * Get user's total storage limit in bytes
     */
    public function getStorageLimit(): int
    {
        return (int) ($this->getStorageLimitGB() * 1024 * 1024 * 1024);
    }

    /**
     * Get user's storage usage percentage
     */
    public function getStorageUsedPercentage(): float
    {
        return app(\App\Services\UserStorageService::class)->getUserStoragePercentage($this);
    }

    /**
     * Get formatted storage message
     */
    public function getStorageLimitMessage(): string
    {
        return app(\App\Services\UserStorageService::class)->getStorageLimitMessage($this);
    }

    /**
     * Check if user has storage capacity
     */
    public function hasStorageCapacity(int $additionalBytes): bool
    {
        return app(\App\Services\UserStorageService::class)->hasUserStorageCapacity($this, $additionalBytes);
    }

    /**
     * Get remaining storage in bytes
     */
    public function getRemainingStorageBytes(): int
    {
        return app(\App\Services\UserStorageService::class)->getUserStorageRemaining($this);
    }

    /**
     * Get user's current storage usage in bytes
     */
    public function getTotalStorageUsed(): int
    {
        return app(\App\Services\UserStorageService::class)->getUserStorageUsed($this);
    }

    /**
     * Get platform commission rate for this user
     */
    public function getPlatformCommissionRate(): float
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->platform_commission_rate : 10.0;
    }

    /**
     * Get reputation multiplier for this user
     */
    public function getReputationMultiplier(): float
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->reputation_multiplier : 1.0;
    }

    /**
     * Check if user has client portal access
     */
    public function hasClientPortalAccess(): bool
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->has_client_portal : false;
    }

    /**
     * Get user badge (emoji)
     */
    public function getUserBadge(): ?string
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->user_badge : null;
    }

    /**
     * Get analytics level for this user
     */
    public function getAnalyticsLevel(): string
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->analytics_level : 'basic';
    }

    /**
     * Get monthly visibility boosts available
     */
    public function getMonthlyVisibilityBoosts(): int
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->monthly_visibility_boosts : 0;
    }

    /**
     * Get max private projects per month
     */
    public function getMaxPrivateProjectsMonthly(): ?int
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->max_private_projects_monthly : 0;
    }

    /**
     * Check if user has judge access for challenges
     */
    public function hasJudgeAccess(): bool
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->has_judge_access : false;
    }

    /**
     * Get challenge early access hours
     */
    public function getChallengeEarlyAccessHours(): int
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->challenge_early_access_hours : 0;
    }

    /**
     * Get support SLA hours
     */
    public function getSupportSlaHours(): ?int
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->support_sla_hours : null;
    }

    /**
     * Get available support channels
     */
    public function getSupportChannels(): array
    {
        $limits = $this->getSubscriptionLimits();

        return $limits && $limits->support_channels ? $limits->support_channels : ['forum'];
    }

    /**
     * Get max license templates for this user
     */
    public function getMaxLicenseTemplates(): ?int
    {
        $limits = $this->getSubscriptionLimits();

        return $limits ? $limits->max_license_templates : 3;
    }

    /**
     * Get all transactions for this user
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the license templates for this user
     */
    public function licenseTemplates()
    {
        return $this->hasMany(LicenseTemplate::class);
    }

    /**
     * Get active license templates for this user
     */
    public function activeLicenseTemplates()
    {
        return $this->licenseTemplates()->active();
    }

    /**
     * Get user's default license template
     */
    public function defaultLicenseTemplate()
    {
        return $this->licenseTemplates()->default()->first();
    }

    /**
     * Get completed transactions for this user
     */
    public function completedTransactions()
    {
        return $this->transactions()->completed();
    }

    /**
     * Get total earnings (net amount after commissions)
     */
    public function getTotalEarnings(): float
    {
        return $this->completedTransactions()
            ->payments()
            ->sum('net_amount');
    }

    /**
     * Get total commission paid to platform
     */
    public function getTotalCommissionPaid(): float
    {
        return $this->completedTransactions()
            ->payments()
            ->sum('commission_amount');
    }

    /**
     * Get commission savings compared to free plan rate
     */
    public function getCommissionSavings(): float
    {
        $freeRate = 10.0; // Free plan commission rate
        $currentRate = $this->getPlatformCommissionRate();

        if ($currentRate >= $freeRate) {
            return 0; // No savings if rate is same or higher
        }

        $totalRevenue = $this->completedTransactions()
            ->payments()
            ->sum('amount');

        $wouldPayAtFreeRate = $totalRevenue * ($freeRate / 100);
        $actuallyPaid = $this->getTotalCommissionPaid();

        return $wouldPayAtFreeRate - $actuallyPaid;
    }

    /**
     * Get all visibility boosts for this user
     */
    public function visibilityBoosts()
    {
        return $this->hasMany(VisibilityBoost::class);
    }

    /**
     * Get active visibility boosts for this user
     */
    public function activeVisibilityBoosts()
    {
        return $this->visibilityBoosts()->active();
    }

    /**
     * Get the number of pitches created by this user in the current month
     * Excludes client management pitches
     */
    public function getMonthlyPitchCount(): int
    {
        return $this->pitches()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereHas('project', function ($query) {
                $query->where('workflow_type', '!=', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT);
            })
            ->count();
    }

    /**
     * Get count of active pitches (excluding client management)
     */
    public function getActivePitchesCount(): int
    {
        return $this->pitches()
            ->whereIn('status', [
                Pitch::STATUS_PENDING,
                Pitch::STATUS_IN_PROGRESS,
                Pitch::STATUS_READY_FOR_REVIEW,
                Pitch::STATUS_PENDING_REVIEW,
            ])
            ->whereHas('project', function ($query) {
                $query->where('workflow_type', '!=', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT);
            })
            ->count();
    }

    /**
     * Get monthly limit tracking records for this user
     */
    public function monthlyLimits()
    {
        return $this->hasMany(UserMonthlyLimit::class);
    }

    /**
     * Get current month's limit tracking
     */
    public function currentMonthLimits()
    {
        return $this->monthlyLimits()->currentMonth()->first()
            ?? UserMonthlyLimit::getOrCreateForUser($this);
    }

    /**
     * Check if user can create a visibility boost
     */
    public function canCreateVisibilityBoost(): bool
    {
        return VisibilityBoost::canUserCreateBoost($this);
    }

    /**
     * Get remaining visibility boosts for current month
     */
    public function getRemainingVisibilityBoosts(): int
    {
        return VisibilityBoost::getRemainingBoosts($this);
    }

    /**
     * Check if user can create a private project
     */
    public function canCreatePrivateProject(): bool
    {
        $monthlyLimit = $this->getMaxPrivateProjectsMonthly();

        // Unlimited for Pro Engineer
        if ($monthlyLimit === null) {
            return true;
        }

        // Not allowed for free users
        if ($monthlyLimit === 0) {
            return false;
        }

        // Check monthly usage
        $currentMonth = now()->format('Y-m');
        $usedThisMonth = $this->projects()
            ->where('is_private', true)
            ->where('privacy_month_year', $currentMonth)
            ->count();

        return $usedThisMonth < $monthlyLimit;
    }

    /**
     * Get remaining private projects for current month
     */
    public function getRemainingPrivateProjects(): ?int
    {
        $monthlyLimit = $this->getMaxPrivateProjectsMonthly();

        if ($monthlyLimit === null) {
            return null; // Unlimited
        }

        $currentMonth = now()->format('Y-m');
        $usedThisMonth = $this->projects()
            ->where('is_private', true)
            ->where('privacy_month_year', $currentMonth)
            ->count();

        return max(0, $monthlyLimit - $usedThisMonth);
    }

    /**
     * Get user's private projects for current month
     */
    public function currentMonthPrivateProjects()
    {
        return $this->projects()->userPrivateCurrentMonth($this);
    }

    // ========== REPUTATION METHODS ==========

    /**
     * Get the user's current reputation score
     */
    public function getReputation(bool $useCache = true): float
    {
        $reputationService = app(ReputationService::class);

        return $reputationService->calculateUserReputation($this, $useCache);
    }

    /**
     * Get reputation breakdown showing components and multiplier effect
     */
    public function getReputationBreakdown(): array
    {
        $reputationService = app(ReputationService::class);

        return $reputationService->getReputationBreakdown($this);
    }

    /**
     * Get user's reputation tier information
     */
    public function getReputationTier(): array
    {
        $reputation = $this->getReputation();
        $reputationService = app(ReputationService::class);

        return $reputationService->getReputationTier($reputation);
    }

    /**
     * Get user's reputation rank among all users
     */
    public function getReputationRank(): array
    {
        $reputationService = app(ReputationService::class);

        return $reputationService->getUserRank($this);
    }

    /**
     * Update reputation after significant events
     */
    public function updateReputation(string $event, array $context = []): float
    {
        $reputationService = app(ReputationService::class);

        return $reputationService->updateAfterEvent($this, $event, $context);
    }

    /**
     * Clear reputation cache
     */
    public function clearReputationCache(): void
    {
        $reputationService = app(ReputationService::class);
        $reputationService->clearUserCache($this);
    }

    /**
     * Get reputation with subscription multiplier breakdown
     */
    public function getReputationWithMultiplier(): array
    {
        $breakdown = $this->getReputationBreakdown();

        return [
            'base_reputation' => $breakdown['base_total'],
            'multiplier' => $this->getReputationMultiplier(),
            'multiplier_bonus' => $breakdown['multiplier_bonus'],
            'final_reputation' => $breakdown['final_total'],
            'subscription_benefit' => $this->getReputationMultiplier() > 1.0,
            'tier' => $this->getReputationTier(),
        ];
    }

    /**
     * Compare reputation with another user
     */
    public function compareReputationWith(User $otherUser): array
    {
        $reputationService = app(ReputationService::class);

        return $reputationService->compareUsers($this, $otherUser);
    }

    // ========== SUBSCRIPTION METHODS (CUSTOM) ==========

    /**
     * Custom subscription check that works correctly
     * This is a workaround for Laravel Cashier's subscribed() method issue
     */
    public function hasActiveSubscription(string $name = 'default'): bool
    {
        $subscription = $this->subscriptions()
            ->where('name', $name)
            ->where('stripe_status', 'active')
            ->whereNull('ends_at')
            ->first();

        return $subscription && $subscription->valid();
    }

    /**
     * Get active subscription by name
     *
     * @return \Laravel\Cashier\Subscription|null
     */
    public function getActiveSubscription(string $name = 'default')
    {
        return $this->subscriptions()
            ->where('name', $name)
            ->where('stripe_status', 'active')
            ->whereNull('ends_at')
            ->first();
    }

    /**
     * Check if user is actually subscribed (using our working method)
     * This overrides the problematic Laravel Cashier method
     */
    public function isSubscribedTo(string $name = 'default'): bool
    {
        $subscription = $this->getActiveSubscription($name);

        if (! $subscription) {
            return false;
        }

        // Check if subscription is active and not cancelled
        return $subscription->active() && ! $subscription->cancelled();
    }

    /**
     * Check if user has a valid Stripe Connect account
     */
    public function hasValidStripeConnectAccount(): bool
    {
        if (! $this->stripe_account_id) {
            return false;
        }

        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $account = $stripe->accounts->retrieve($this->stripe_account_id);

            // Use the same logic as StripeConnectService for consistency
            // Account is valid if it can receive payouts, regardless of minor pending requirements
            return $account->payouts_enabled &&
                   $account->charges_enabled &&
                   $account->details_submitted;

        } catch (\Exception $e) {
            \Log::warning('Failed to validate Stripe Connect account', [
                'user_id' => $this->id,
                'stripe_account_id' => $this->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get detailed Stripe Connect account status using the StripeConnectService
     */
    public function getStripeConnectStatus(): array
    {
        $stripeConnectService = app(\App\Services\StripeConnectService::class);

        return $stripeConnectService->getDetailedAccountStatus($this);
    }

    /**
     * Check if user can receive payouts using the StripeConnectService
     */
    public function canReceivePayouts(): bool
    {
        $stripeConnectService = app(\App\Services\StripeConnectService::class);

        return $stripeConnectService->isAccountReadyForPayouts($this);
    }

    // ========== TIMEZONE METHODS ==========

    /**
     * Get user's timezone preference
     */
    public function getTimezone(): string
    {
        return $this->timezone ?? config('timezone.default');
    }

    /**
     * Format date for this user's timezone
     */
    public function formatDate(\Carbon\Carbon $date, ?string $format = null): string
    {
        return app(\App\Services\TimezoneService::class)->formatForUser($date, $this, $format);
    }

    /**
     * Get current time in user's timezone
     */
    public function now(): \Carbon\Carbon
    {
        return app(\App\Services\TimezoneService::class)->now($this);
    }

    // If the trait doesn't automatically provide the relationship,
    // you might need to explicitly define it (uncomment if needed):
    // public function roles(): BelongsToMany
    // {
    //     return $this->morphToMany(
    //         config('permission.models.role'),
    //         'model',
    //         config('permission.table_names.model_has_roles'),
    //         config('permission.column_names.model_morph_key'),
    //         'role_id'
    //     );
    // }
}
