<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPayoutAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'account_id',
        'status',
        'is_primary',
        'is_verified',
        'verified_at',
        'last_used_at',
        'account_data',
        'capabilities',
        'requirements',
        'metadata',
        'created_by',
        'setup_completed_at',
        'last_status_check',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime',
        'account_data' => 'array',
        'capabilities' => 'array',
        'requirements' => 'array',
        'metadata' => 'array',
        'setup_completed_at' => 'datetime',
        'last_status_check' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_RESTRICTED = 'restricted';

    public const STATUS_DISABLED = 'disabled';

    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUS_UNDER_REVIEW = 'under_review';

    // Provider constants
    public const PROVIDER_STRIPE = 'stripe';

    public const PROVIDER_PAYPAL = 'paypal';

    public const PROVIDER_WISE = 'wise';

    public const PROVIDER_DWOLLA = 'dwolla';

    /**
     * Get the user that owns the payout account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get accounts by provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to get active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get verified accounts
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get primary accounts
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Check if account is ready for payouts
     */
    public function isReadyForPayouts(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->is_verified;
    }

    /**
     * Check if account needs setup
     */
    public function needsSetup(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_INCOMPLETE,
        ]);
    }

    /**
     * Check if account is restricted
     */
    public function isRestricted(): bool
    {
        return in_array($this->status, [
            self::STATUS_RESTRICTED,
            self::STATUS_DISABLED,
        ]);
    }

    /**
     * Mark account as primary (and unset others)
     */
    public function markAsPrimary(): void
    {
        // First, unset all other primary accounts for this user and provider
        static::where('user_id', $this->user_id)
            ->where('provider', $this->provider)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Then set this account as primary
        $this->update(['is_primary' => true]);
    }

    /**
     * Mark account as verified
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Update last used timestamp
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Update status check timestamp
     */
    public function markStatusChecked(): void
    {
        $this->update(['last_status_check' => now()]);
    }

    /**
     * Get display name for the provider
     */
    public function getProviderDisplayNameAttribute(): string
    {
        return match ($this->provider) {
            self::PROVIDER_STRIPE => 'Stripe Connect',
            self::PROVIDER_PAYPAL => 'PayPal',
            self::PROVIDER_WISE => 'Wise',
            self::PROVIDER_DWOLLA => 'Dwolla',
            default => ucfirst($this->provider),
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Setup Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_RESTRICTED => 'Restricted',
            self::STATUS_DISABLED => 'Disabled',
            self::STATUS_INCOMPLETE => 'Setup Incomplete',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_PENDING, self::STATUS_UNDER_REVIEW => 'blue',
            self::STATUS_INCOMPLETE => 'amber',
            self::STATUS_RESTRICTED, self::STATUS_DISABLED => 'red',
            default => 'gray',
        };
    }
}
