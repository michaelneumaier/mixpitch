<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZapierUsageLog extends Model
{
    use HasFactory;

    public $timestamps = false; // Only using created_at

    protected $fillable = [
        'user_id',
        'endpoint',
        'method',
        'request_data',
        'response_status',
        'response_time_ms',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_status' => 'integer',
        'response_time_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    public function scopeErrors($query)
    {
        return $query->where('response_status', '>=', 400);
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
}
