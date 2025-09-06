<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZapierWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'webhook_url',
        'is_active',
        'metadata',
        'last_triggered_at',
        'trigger_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'last_triggered_at' => 'datetime',
        'trigger_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function markTriggered(): void
    {
        $this->increment('trigger_count');
        $this->update(['last_triggered_at' => now()]);
    }
}
