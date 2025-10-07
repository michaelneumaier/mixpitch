<?php

namespace App\Models;

use App\Traits\HasTimezoneDisplay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;
    use HasTimezoneDisplay;

    // Order Status Constants
    const STATUS_PENDING_PAYMENT = 'pending_payment';

    const STATUS_PENDING_REQUIREMENTS = 'pending_requirements';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_NEEDS_CLARIFICATION = 'needs_clarification';

    const STATUS_READY_FOR_REVIEW = 'ready_for_review'; // Producer delivered

    const STATUS_REVISIONS_REQUESTED = 'revisions_requested'; // Client requested revisions

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_DISPUTED = 'disputed';

    // Order Payment Status Constants
    const PAYMENT_STATUS_PENDING = 'pending';

    const PAYMENT_STATUS_PAID = 'paid';

    const PAYMENT_STATUS_FAILED = 'failed';

    const PAYMENT_STATUS_REFUNDED = 'refunded';

    // Order Event Type Constants (copied from OrderEvent for convenience)
    const EVENT_CREATED = 'order_created';

    const EVENT_REQUIREMENTS_SUBMITTED = 'requirements_submitted';

    const EVENT_PRODUCER_STARTED = 'producer_started';

    const EVENT_CLARIFICATION_REQUESTED = 'clarification_requested';

    const EVENT_CLARIFICATION_PROVIDED = 'clarification_provided';

    const EVENT_DELIVERY_SUBMITTED = 'delivery_submitted';

    const EVENT_REVISIONS_REQUESTED = 'revisions_requested';

    const EVENT_REVISIONS_SUBMITTED = 'revisions_submitted';

    const EVENT_DELIVERY_ACCEPTED = 'delivery_accepted';

    const EVENT_COMPLETED = 'order_completed';

    const EVENT_CANCELLED = 'order_cancelled';

    const EVENT_DISPUTE_OPENED = 'dispute_opened';

    const EVENT_STATUS_CHANGE = 'status_change';

    const EVENT_PAYMENT_RECEIVED = 'payment_received';

    const EVENT_PAYMENT_FAILED = 'payment_failed';

    const EVENT_MESSAGE = 'message';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_package_id',
        'client_user_id',
        'producer_user_id',
        'status',
        'price',
        'currency',
        'payment_status',
        'invoice_id',
        'requirements_submitted',
        'revision_count',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'revision_count' => 'integer',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relationships

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'producer_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('created_at', 'asc');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Helper Methods

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canRequestRevision(): bool
    {
        // Logic depends on service package limits and current status
        if ($this->status !== self::STATUS_READY_FOR_REVIEW) {
            return false;
        }
        $revisionsAllowed = $this->servicePackage->revisions_included ?? 0;

        return $this->revision_count < $revisionsAllowed;
    }

    // Accessors

    public function getReadableStatusAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_PAYMENT => 'Pending Payment',
            self::STATUS_PENDING_REQUIREMENTS => 'Pending Requirements',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_NEEDS_CLARIFICATION => 'Needs Clarification',
            self::STATUS_READY_FOR_REVIEW => 'Ready for Review',
            self::STATUS_REVISIONS_REQUESTED => 'Revisions Requested',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_DISPUTED => 'Disputed',
            default => ucwords(str_replace('_', ' ', $this->status ?? '')),
        };
    }

    public function getReadablePaymentStatusAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_PENDING => 'Pending',
            self::PAYMENT_STATUS_PAID => 'Paid',
            self::PAYMENT_STATUS_FAILED => 'Failed',
            self::PAYMENT_STATUS_REFUNDED => 'Refunded',
            default => ucwords(str_replace('_', ' ', $this->payment_status ?? '')),
        };
    }

    // Static Helpers
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_PENDING_REQUIREMENTS,
            self::STATUS_IN_PROGRESS,
            self::STATUS_NEEDS_CLARIFICATION,
            self::STATUS_READY_FOR_REVIEW,
            self::STATUS_REVISIONS_REQUESTED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_DISPUTED,
        ];
    }

    public static function getPaymentStatuses(): array
    {
        return [
            self::PAYMENT_STATUS_PENDING,
            self::PAYMENT_STATUS_PAID,
            self::PAYMENT_STATUS_FAILED,
            self::PAYMENT_STATUS_REFUNDED,
        ];
    }
}
