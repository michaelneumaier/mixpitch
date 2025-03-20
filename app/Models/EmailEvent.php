<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailEvent extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'message_id',
        'event_type',
        'email_type',
        'metadata'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array'
    ];
    
    /**
     * Log an email event
     *
     * @param string $email
     * @param string $eventType
     * @param string|null $emailType
     * @param array|null $metadata
     * @return \App\Models\EmailEvent
     */
    public static function logEvent(string $email, string $eventType, ?string $emailType = null, ?array $metadata = null)
    {
        return static::create([
            'email' => $email,
            'event_type' => $eventType,
            'email_type' => $emailType,
            'metadata' => $metadata
        ]);
    }
}
