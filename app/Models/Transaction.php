<?php

namespace App\Models;

use App\Traits\HasTimezoneDisplay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;
    use HasTimezoneDisplay;

    /**
     * Transaction type constants
     */
    const TYPE_PAYMENT = 'payment';

    const TYPE_REFUND = 'refund';

    const TYPE_ADJUSTMENT = 'adjustment';

    const TYPE_BONUS = 'bonus';

    /**
     * Transaction status constants
     */
    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'project_id',
        'pitch_id',
        'amount',
        'commission_rate',
        'commission_amount',
        'net_amount',
        'type',
        'status',
        'payment_method',
        'external_transaction_id',
        'user_subscription_plan',
        'user_subscription_tier',
        'description',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the user who made this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project associated with this transaction
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the pitch associated with this transaction (if any)
     */
    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    // ========== STATIC CREATION METHODS ==========

    /**
     * Create a new transaction with automatic commission calculation
     *
     * @return static
     */
    public static function createWithCommission(
        User $user,
        Project $project,
        float $amount,
        array $additionalData = []
    ): self {
        $commissionRate = $user->getPlatformCommissionRate();
        $commissionAmount = $amount * ($commissionRate / 100);
        $netAmount = $amount - $commissionAmount;

        return self::create(array_merge([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'amount' => $amount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'net_amount' => $netAmount,
            'user_subscription_plan' => $user->subscription_plan,
            'user_subscription_tier' => $user->subscription_tier,
        ], $additionalData));
    }

    /**
     * Create a transaction for a pitch completion
     *
     * @return static
     */
    public static function createForPitch(
        User $user,
        Project $project,
        Pitch $pitch,
        float $amount,
        array $additionalData = []
    ): self {
        return self::createWithCommission($user, $project, $amount, array_merge([
            'pitch_id' => $pitch->id,
            'description' => "Payment for pitch: {$pitch->name}",
        ], $additionalData));
    }

    // ========== INSTANCE METHODS ==========

    /**
     * Recalculate commission based on current user subscription
     */
    public function recalculateCommission(): void
    {
        $this->commission_rate = $this->user->getPlatformCommissionRate();
        $this->commission_amount = $this->amount * ($this->commission_rate / 100);
        $this->net_amount = $this->amount - $this->commission_amount;

        // Update subscription context
        $this->user_subscription_plan = $this->user->subscription_plan;
        $this->user_subscription_tier = $this->user->subscription_tier;
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(?string $externalTransactionId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
            'external_transaction_id' => $externalTransactionId ?? $this->external_transaction_id,
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(?string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['failure_reason'] = $reason;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get commission savings compared to a higher rate
     */
    public function getCommissionSavings(float $comparisonRate): float
    {
        $wouldBeCommission = $this->amount * ($comparisonRate / 100);

        return $wouldBeCommission - $this->commission_amount;
    }

    /**
     * Check if this is a payment transaction
     */
    public function isPayment(): bool
    {
        return $this->type === self::TYPE_PAYMENT;
    }

    /**
     * Check if this is a refund transaction
     */
    public function isRefund(): bool
    {
        return $this->type === self::TYPE_REFUND;
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$'.number_format($this->amount, 2);
    }

    /**
     * Get formatted commission amount
     */
    public function getFormattedCommissionAttribute(): string
    {
        return '$'.number_format($this->commission_amount, 2);
    }

    /**
     * Get formatted net amount
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return '$'.number_format($this->net_amount, 2);
    }

    // ========== SCOPES ==========

    /**
     * Scope to only completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to only payment transactions
     */
    public function scopePayments($query)
    {
        return $query->where('type', self::TYPE_PAYMENT);
    }

    /**
     * Scope to transactions for a specific subscription plan
     */
    public function scopeForPlan($query, string $plan, ?string $tier = null)
    {
        $query->where('user_subscription_plan', $plan);

        if ($tier) {
            $query->where('user_subscription_tier', $tier);
        }

        return $query;
    }

    /**
     * Scope to transactions within a date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
