<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEvent extends Model
{
    use HasFactory;

    // Event Type Constants (Examples - adjust as needed for workflow)
    const EVENT_CREATED = 'order_created';

    const EVENT_REQUIREMENTS_SUBMITTED = 'requirements_submitted';

    const EVENT_PRODUCER_STARTED = 'producer_started'; // Explicit start action?

    const EVENT_CLARIFICATION_REQUESTED = 'clarification_requested'; // By producer

    const EVENT_CLARIFICATION_PROVIDED = 'clarification_provided'; // By client

    const EVENT_DELIVERY_SUBMITTED = 'delivery_submitted';

    const EVENT_REVISIONS_REQUESTED = 'revisions_requested'; // By client

    const EVENT_REVISIONS_SUBMITTED = 'revisions_submitted'; // By producer

    const EVENT_COMPLETED = 'order_completed';

    const EVENT_CANCELLED = 'order_cancelled';

    const EVENT_DISPUTE_OPENED = 'dispute_opened';

    const EVENT_STATUS_CHANGE = 'status_change'; // Generic status change

    const EVENT_PAYMENT_RECEIVED = 'payment_received';

    const EVENT_PAYMENT_FAILED = 'payment_failed';

    const EVENT_MESSAGE = 'message'; // For general communication

    const EVENT_DELIVERY_ACCEPTED = 'delivery_accepted'; // When client accepts the delivery

    const EVENT_ORDER_CANCELLED = 'order_cancelled'; // Alias for EVENT_CANCELLED for consistency

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'user_id', // User initiating event (nullable for system)
        'event_type',
        'comment',
        'status_from',
        'status_to',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    // Relationships

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who initiated the event (if applicable).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
