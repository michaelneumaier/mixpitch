<?php

namespace App\Models;

use App\Traits\HasTimezoneDisplay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PitchEvent extends Model
{
    use HasFactory;
    use HasTimezoneDisplay;

    /**
     * Delivery status constants
     */
    public const DELIVERY_PENDING = 'pending';

    public const DELIVERY_DELIVERED = 'delivered';

    public const DELIVERY_READ = 'read';

    /**
     * Communication event type constants
     */
    public const TYPE_PRODUCER_MESSAGE = 'producer_comment';

    public const TYPE_CLIENT_MESSAGE = 'client_comment';

    public const TYPE_STATUS_CHANGE = 'status_change';

    public const TYPE_CLIENT_APPROVED = 'client_approved';

    public const TYPE_CLIENT_REVISIONS_REQUESTED = 'client_revisions_requested';

    public const TYPE_FILE_UPLOADED = 'file_uploaded';

    public const TYPE_WORK_SESSION_COMPLETED = 'work_session_completed';

    protected $fillable = [
        'pitch_id',
        'event_type',
        'comment',
        'status', // Pitch status AT THE TIME of the event
        'snapshot_id',
        'created_by', // User ID who triggered the event
        'metadata', // For extra context like client email, feedback, etc.
        'rating',
        'read_at',
        'read_by',
        'delivery_status',
        'is_urgent',
        'thread_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'read_by' => 'array',
        'is_urgent' => 'boolean',
    ];

    public function pitch()
    {
        return $this->belongsTo(Pitch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the snapshot associated with this event
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function snapshot()
    {
        return $this->belongsTo(PitchSnapshot::class, 'snapshot_id');
    }

    /**
     * Create a status change event for a pitch
     *
     * @param  Pitch  $pitch  The pitch that had its status changed
     * @param  User  $user  The user who changed the status
     * @param  string  $oldStatus  The previous status
     * @param  string  $newStatus  The new status
     * @return PitchEvent
     */
    public static function createStatusChangeEvent(Pitch $pitch, User $user, string $oldStatus, string $newStatus)
    {
        return self::create([
            'pitch_id' => $pitch->id,
            'event_type' => 'status_change',
            'status' => $newStatus,
            'comment' => "Status changed from '{$oldStatus}' to '{$newStatus}'",
            'created_by' => $user->id,
        ]);
    }

    // =========================================================================
    // THREADING RELATIONSHIPS
    // =========================================================================

    /**
     * Get the parent thread event (if this is a reply)
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(PitchEvent::class, 'thread_id');
    }

    /**
     * Get replies to this event
     */
    public function replies(): HasMany
    {
        return $this->hasMany(PitchEvent::class, 'thread_id')->orderBy('created_at');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter only message-type events (producer/client comments)
     */
    public function scopeMessages(Builder $query): Builder
    {
        return $query->whereIn('event_type', [
            self::TYPE_PRODUCER_MESSAGE,
            self::TYPE_CLIENT_MESSAGE,
        ]);
    }

    /**
     * Scope to filter unread events (no read_at timestamp)
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to filter read events
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to get events relevant for the communication hub
     */
    public function scopeForCommunicationHub(Builder $query): Builder
    {
        return $query->whereIn('event_type', [
            self::TYPE_PRODUCER_MESSAGE,
            self::TYPE_CLIENT_MESSAGE,
            self::TYPE_CLIENT_APPROVED,
            self::TYPE_CLIENT_REVISIONS_REQUESTED,
            self::TYPE_STATUS_CHANGE,
            self::TYPE_FILE_UPLOADED,
            self::TYPE_WORK_SESSION_COMPLETED,
        ]);
    }

    /**
     * Scope to filter urgent messages
     */
    public function scopeUrgent(Builder $query): Builder
    {
        return $query->where('is_urgent', true);
    }

    /**
     * Scope to filter by delivery status
     */
    public function scopeWithDeliveryStatus(Builder $query, string $status): Builder
    {
        return $query->where('delivery_status', $status);
    }

    // =========================================================================
    // READ TRACKING METHODS
    // =========================================================================

    /**
     * Mark this event as read by a user
     *
     * @param  int|null  $userId  The user ID (null for guest/client)
     * @param  bool  $isClient  Whether this is a client reading the event
     * @param  string|null  $clientEmail  Client email if applicable
     */
    public function markAsRead(?int $userId = null, bool $isClient = false, ?string $clientEmail = null): void
    {
        // If already read, just add to the read_by array if not already there
        $readBy = $this->read_by ?? [];

        // Check if this reader already exists
        $alreadyRead = collect($readBy)->contains(function ($reader) use ($userId, $clientEmail) {
            if ($userId !== null) {
                return ($reader['user_id'] ?? null) === $userId;
            }

            return $clientEmail !== null && ($reader['client_email'] ?? null) === $clientEmail;
        });

        if (! $alreadyRead) {
            $readBy[] = [
                'user_id' => $userId,
                'is_client' => $isClient,
                'client_email' => $clientEmail,
                'read_at' => now()->toISOString(),
            ];

            $this->update([
                'read_at' => $this->read_at ?? now(),
                'read_by' => $readBy,
                'delivery_status' => self::DELIVERY_READ,
            ]);
        }
    }

    /**
     * Check if this event has been read by a specific user
     */
    public function isReadBy(?int $userId = null, ?string $clientEmail = null): bool
    {
        if (! $this->read_by) {
            return false;
        }

        return collect($this->read_by)->contains(function ($reader) use ($userId, $clientEmail) {
            if ($userId !== null) {
                return ($reader['user_id'] ?? null) === $userId;
            }

            return $clientEmail !== null && ($reader['client_email'] ?? null) === $clientEmail;
        });
    }

    /**
     * Check if this event is unread
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Check if this event is a message type
     */
    public function isMessage(): bool
    {
        return in_array($this->event_type, [
            self::TYPE_PRODUCER_MESSAGE,
            self::TYPE_CLIENT_MESSAGE,
        ]);
    }

    /**
     * Check if this is a producer message
     */
    public function isProducerMessage(): bool
    {
        return $this->event_type === self::TYPE_PRODUCER_MESSAGE;
    }

    /**
     * Check if this is a client message
     */
    public function isClientMessage(): bool
    {
        return $this->event_type === self::TYPE_CLIENT_MESSAGE;
    }

    /**
     * Get the sender name for display
     */
    public function getSenderNameAttribute(): string
    {
        if ($this->isProducerMessage()) {
            return $this->user?->name ?? 'Producer';
        }

        if ($this->isClientMessage()) {
            return $this->metadata['client_name'] ?? 'Client';
        }

        return 'System';
    }
}
