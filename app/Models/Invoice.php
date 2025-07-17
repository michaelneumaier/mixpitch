<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    // Status Constants
    const STATUS_PENDING = 'pending';

    const STATUS_PAID = 'paid';

    const STATUS_FAILED = 'failed';

    const STATUS_VOID = 'void';

    const STATUS_REFUNDED = 'refunded';

    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'order_id',
        'pitch_id',
        'invoice_number',
        'stripe_invoice_id',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'amount',
        'currency',
        'status',
        'paid_at',
        'description',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
        });
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    // Helpers

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public static function generateInvoiceNumber(): string
    {
        // Generate a unique invoice number, e.g., INV-YYYYMMDD-XXXXXX
        return 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    public function getReadableStatusAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_VOID => 'Void',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_PARTIALLY_REFUNDED => 'Partially Refunded',
            default => ucwords(str_replace('_', ' ', $this->status ?? '')),
        };
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PAID,
            self::STATUS_FAILED,
            self::STATUS_VOID,
            self::STATUS_REFUNDED,
            self::STATUS_PARTIALLY_REFUNDED,
        ];
    }
}
