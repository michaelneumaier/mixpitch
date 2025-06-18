<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class PayoutSchedule extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DISPUTED = 'disputed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';
    const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'pitch_id',
        'contest_prize_id',
        'project_id',
        'transaction_id',
        'producer_user_id',
        'producer_stripe_account_id',
        'gross_amount',
        'commission_rate',
        'commission_amount',
        'net_amount',
        'currency',
        'hold_release_date',
        'status',
        'processed_at',
        'completed_at',
        'failed_at',
        'cancelled_at',
        'reversed_at',
        'stripe_payment_intent_id',
        'stripe_transfer_id',
        'stripe_reversal_id',
        'failure_reason',
        'workflow_type',
        'subscription_plan',
        'subscription_tier',
        'metadata',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'hold_release_date' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reversed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the pitch associated with this payout (for standard/client management)
     */
    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'producer_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the contest prize associated with this payout (for contests)
     */
    public function contestPrize(): BelongsTo
    {
        return $this->belongsTo(ContestPrize::class);
    }

    /**
     * Get the user who will receive this payout (producer/winner)
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'producer_user_id');
    }

    /**
     * Get the transaction associated with this payout
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    /**
     * Get refund requests for this payout
     */
    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    // ========== STATUS METHODS ==========

    /**
     * Check if payout is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if payout is ready for release
     */
    public function isReadyForRelease(): bool
    {
        return $this->isScheduled() && $this->hold_release_date->isPast();
    }

    /**
     * Check if payout can be disputed
     */
    public function canBeDisputed(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_PROCESSING]);
    }

    /**
     * Check if payout has active refund requests
     */
    public function hasActiveRefundRequests(): bool
    {
        return $this->refundRequests()
            ->whereIn('status', ['requested', 'approved'])
            ->exists();
    }

    // ========== BUSINESS LOGIC METHODS ==========

    /**
     * Mark payout as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Mark payout as completed
     */
    public function markAsCompleted(string $stripeTransferId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'stripe_transfer_id' => $stripeTransferId,
        ]);
    }

    /**
     * Mark payout as disputed
     */
    public function markAsDisputed(): void
    {
        $this->update(['status' => self::STATUS_DISPUTED]);
    }

    /**
     * Cancel the payout
     */
    public function cancel(string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['cancellation_reason'] = $reason;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get the project associated with this payout
     */
    public function getProject(): ?Project
    {
        if ($this->project_id) {
            return $this->project;
        }
        
        if ($this->pitch) {
            return $this->pitch->project;
        }
        
        if ($this->contestPrize) {
            return $this->contestPrize->project;
        }
        
        return null;
    }

    /**
     * Get formatted gross amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->gross_amount, 2);
    }

    /**
     * Get formatted net amount
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return '$' . number_format($this->net_amount, 2);
    }

    /**
     * Get formatted commission
     */
    public function getFormattedCommissionAttribute(): string
    {
        return '$' . number_format($this->commission_amount, 2);
    }

    /**
     * Get readable status
     */
    public function getReadableStatusAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_DISPUTED => 'Disputed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_REVERSED => 'Reversed',
            default => ucfirst($this->status),
        };
    }

    // ========== SCOPES ==========

    /**
     * Scope to only scheduled payouts
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope to payouts ready for release
     */
    public function scopeReadyForRelease($query)
    {
        return $query->scheduled()
            ->where('hold_release_date', '<=', now());
    }

    /**
     * Scope to payouts for a specific workflow type
     */
    public function scopeForWorkflowType($query, string $workflowType)
    {
        return $query->where('workflow_type', $workflowType);
    }

    /**
     * Scope to payouts for a specific user
     */
    public function scopeForRecipient($query, User $user)
    {
        return $query->where('producer_user_id', $user->id);
    }
} 