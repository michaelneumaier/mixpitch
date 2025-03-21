<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTest extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recipient_email',
        'subject',
        'template',
        'content_variables',
        'status',
        'result',
        'sent_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'content_variables' => 'array',
        'result' => 'array',
        'sent_at' => 'datetime',
    ];
    
    /**
     * Get the audit records associated with this test.
     */
    public function audits()
    {
        return $this->hasMany(EmailAudit::class, 'message_id', 'id');
    }
    
    /**
     * Get the email events associated with this test.
     */
    public function events()
    {
        return $this->hasMany(EmailEvent::class, 'email', 'recipient_email');
    }
}
