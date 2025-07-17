<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_REQUESTED = 'requested';

    const STATUS_APPROVED = 'approved';

    const STATUS_DENIED = 'denied';

    const STATUS_PROCESSED = 'processed';

    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'pitch_id',
        'payout_schedule_id',
        'requested_by_email',
        'reason',
        'amount',
        'status',
        'approved_by',
        'approved_at',
        'denied_by',
        'denied_at',
        'denial_reason',
        'stripe_refund_id',
        'processed_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'denied_at' => 'datetime',
        'processed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the pitch associated with this refund request
     */
    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'producer_user_id');
    }

    /**
     * Get the payout schedule associated with this refund request
     */
    public function payoutSchedule(): BelongsTo
    {
        return $this->belongsTo(PayoutSchedule::class);
    }

    /**
     * Get the user who approved this refund request
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who denied this refund request
     */
    public function denier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'denied_by');
    }

    // ========== STATUS METHODS ==========

    /**
     * Check if refund request is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    /**
     * Check if refund request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if refund request is denied
     */
    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    /**
     * Check if refund request is processed
     */
    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /**
     * Check if refund request has expired
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || $this->expires_at->isPast();
    }

    /**
     * Check if refund request can be responded to
     */
    public function canBeRespondedTo(): bool
    {
        return $this->isPending() && ! $this->isExpired();
    }

    // ========== BUSINESS LOGIC METHODS ==========

    /**
     * Approve the refund request
     */
    public function approve(User $approver): void
    {
        if (! $this->canBeRespondedTo()) {
            throw new \Exception('Refund request cannot be approved in current state');
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Deny the refund request
     */
    public function deny(User $denier, string $reason): void
    {
        if (! $this->canBeRespondedTo()) {
            throw new \Exception('Refund request cannot be denied in current state');
        }

        $this->update([
            'status' => self::STATUS_DENIED,
            'denied_by' => $denier->id,
            'denied_at' => now(),
            'denial_reason' => $reason,
        ]);
    }

    /**
     * Mark refund as processed
     */
    public function markAsProcessed(string $stripeRefundId): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'stripe_refund_id' => $stripeRefundId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark refund as expired
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Get the producer associated with this refund request
     */
    public function getProducer(): ?User
    {
        return $this->pitch?->user;
    }

    /**
     * Get the project associated with this refund request
     */
    public function getProject(): ?Project
    {
        return $this->pitch?->project;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$'.number_format($this->amount, 2);
    }

    /**
     * Get readable status
     */
    public function getReadableStatusAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_REQUESTED => 'Pending Response',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DENIED => 'Denied',
            self::STATUS_PROCESSED => 'Refunded',
            self::STATUS_EXPIRED => 'Expired',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get time remaining to respond
     */
    public function getTimeRemainingAttribute(): ?string
    {
        if (! $this->isPending()) {
            return null;
        }

        $now = now();
        if ($this->expires_at->isPast()) {
            return 'Expired';
        }

        $diff = $now->diff($this->expires_at);

        if ($diff->days > 0) {
            return $diff->days.' day'.($diff->days > 1 ? 's' : '').' remaining';
        } elseif ($diff->h > 0) {
            return $diff->h.' hour'.($diff->h > 1 ? 's' : '').' remaining';
        } else {
            return $diff->i.' minute'.($diff->i > 1 ? 's' : '').' remaining';
        }
    }

    // ========== SCOPES ==========

    /**
     * Scope to only pending refund requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_REQUESTED);
    }

    /**
     * Scope to expired refund requests
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', self::STATUS_REQUESTED);
    }

    /**
     * Scope to refund requests for a specific email
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('requested_by_email', $email);
    }

    /**
     * Scope to refund requests that need producer response
     */
    public function scopeAwaitingResponse($query)
    {
        return $query->pending()
            ->where('expires_at', '>', now());
    }
}
