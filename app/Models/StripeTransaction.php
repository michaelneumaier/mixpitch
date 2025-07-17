<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StripeTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'stripe_transaction_id',
        'type',
        'status',
        'amount',
        'currency',
        'fee_amount',
        'user_id',
        'project_id',
        'pitch_id',
        'payout_schedule_id',
        'stripe_customer_id',
        'stripe_account_id',
        'stripe_invoice_id',
        'payment_method_id',
        'description',
        'failure_reason',
        'stripe_metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'stripe_metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    public function payoutSchedule(): BelongsTo
    {
        return $this->belongsTo(PayoutSchedule::class);
    }

    // Scopes
    public function scopeSucceeded($query)
    {
        return $query->where('status', 'succeeded');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaymentIntents($query)
    {
        return $query->where('type', 'payment_intent');
    }

    public function scopeWithoutPayout($query)
    {
        return $query->whereNull('payout_schedule_id');
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return '$'.number_format($this->amount, 2);
    }

    public function getFormattedFeeAmountAttribute(): string
    {
        return '$'.number_format($this->fee_amount ?? 0, 2);
    }

    public function getNetAmountAttribute(): float
    {
        return $this->amount - ($this->fee_amount ?? 0);
    }

    public function getFormattedNetAmountAttribute(): string
    {
        return '$'.number_format($this->getNetAmountAttribute(), 2);
    }

    // Status checks
    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function hasPayoutSchedule(): bool
    {
        return $this->payout_schedule_id !== null;
    }

    // Type checks
    public function isPaymentIntent(): bool
    {
        return $this->type === 'payment_intent';
    }

    public function isTransfer(): bool
    {
        return $this->type === 'transfer';
    }

    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }

    // Utility methods
    public function getStripeUrl(): string
    {
        $baseUrl = 'https://dashboard.stripe.com';

        return match ($this->type) {
            'payment_intent' => "{$baseUrl}/payments/{$this->stripe_transaction_id}",
            'transfer' => "{$baseUrl}/transfers/{$this->stripe_transaction_id}",
            'refund' => "{$baseUrl}/refunds/{$this->stripe_transaction_id}",
            'payout' => "{$baseUrl}/payouts/{$this->stripe_transaction_id}",
            'invoice' => "{$baseUrl}/invoices/{$this->stripe_transaction_id}",
            'subscription' => "{$baseUrl}/subscriptions/{$this->stripe_transaction_id}",
            default => "{$baseUrl}/search?query={$this->stripe_transaction_id}",
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'succeeded' => 'success',
            'pending' => 'warning',
            'processing' => 'info',
            'failed' => 'danger',
            'canceled' => 'gray',
            'requires_action' => 'warning',
            default => 'gray',
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->type) {
            'payment_intent' => 'success',
            'transfer' => 'info',
            'refund' => 'warning',
            'payout' => 'primary',
            'invoice' => 'gray',
            'subscription' => 'indigo',
            default => 'gray',
        };
    }
}
