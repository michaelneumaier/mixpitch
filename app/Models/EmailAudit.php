<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailAudit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'recipient_name',
        'subject',
        'message_id',
        'status',
        'metadata',
        'headers',
        'content',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'headers' => 'array',
    ];

    /**
     * Log comprehensive email audit information
     *
     * @return \App\Models\EmailAudit
     */
    public static function log(
        string $email,
        string $subject,
        string $status,
        ?array $metadata = null,
        ?array $headers = null,
        ?string $content = null,
        ?string $messageId = null,
        ?string $recipientName = null
    ) {
        return static::create([
            'email' => $email,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'message_id' => $messageId,
            'status' => $status,
            'metadata' => $metadata,
            'headers' => $headers,
            'content' => $content,
        ]);
    }

    /**
     * Relationship to email events
     */
    public function events()
    {
        return $this->hasMany(EmailEvent::class, 'email', 'email');
    }

    /**
     * Get the test record that triggered this audit, if any
     */
    public function test()
    {
        // First try by message_id
        if ($this->message_id) {
            return $this->belongsTo(EmailTest::class, 'message_id', 'id');
        }

        // Fallback to a query on recipient and timing
        return null;
    }
}
