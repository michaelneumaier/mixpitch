<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Notification model for user notifications
 */
class Notification extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'related_id',
        'related_type',
        'type',
        'data',
        'read_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];
    
    /**
     * Notification types
     */
    const TYPE_PITCH_CREATED = 'pitch_created';
    const TYPE_PITCH_STATUS_CHANGE = 'pitch_status_change';
    const TYPE_PITCH_COMMENT = 'pitch_comment';
    const TYPE_SNAPSHOT_APPROVED = 'snapshot_approved';
    const TYPE_SNAPSHOT_DENIED = 'snapshot_denied';
    const TYPE_SNAPSHOT_REVISIONS_REQUESTED = 'snapshot_revisions_requested';
    const TYPE_PITCH_COMPLETED = 'pitch_completed';
    const TYPE_NEW_SUBMISSION = 'new_submission';
    const TYPE_PITCH_EDITED = 'pitch_edited';
    const TYPE_FILE_UPLOADED = 'file_uploaded';
    const TYPE_PITCH_REVISION = 'pitch_revision';
    const TYPE_PITCH_CANCELLED = 'pitch_cancelled';
    
    /**
     * Get the related model
     */
    public function related()
    {
        return $this->morphTo();
    }
    
    /**
     * Get the user for the notification
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Mark the notification as read
     */
    public function markAsRead()
    {
        $this->read_at = now();
        $this->save();
        
        return $this;
    }
    
    /**
     * Check if the notification is read
     */
    public function isRead()
    {
        return $this->read_at !== null;
    }
    
    /**
     * Scope a query to only include unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
    
    /**
     * Get the URL for this notification
     */
    public function getUrl()
    {
        $data = $this->data ?? [];
        
        // For all pitch-related notifications, direct to the pitch page
        if ($this->related_type === 'App\\Models\\Pitch') {
            $pitch = Pitch::find($this->related_id);
            if ($pitch) {
                // For comments, include the comment anchor
                if ($this->type === self::TYPE_PITCH_COMMENT && isset($data['comment_id'])) {
                    return route('pitches.show', $pitch) . '#comment-' . $data['comment_id'];
                }
                
                // For all other pitch-related notifications, go to the pitch page
                return route('pitches.show', $pitch);
            }
        }
        
        // Default to dashboard if we can't determine a specific URL
        return route('dashboard');
    }
    
    /**
     * Get a readable description of the notification
     */
    public function getReadableDescription()
    {
        $data = $this->data ?? [];
        
        switch ($this->type) {
            case self::TYPE_PITCH_STATUS_CHANGE:
                return 'Pitch status updated to "' . ($data['status'] ?? 'unknown') . '"';
                
            case self::TYPE_PITCH_COMPLETED:
                return 'Your pitch has been marked as completed';
                
            case self::TYPE_SNAPSHOT_APPROVED:
                return 'Your pitch has been approved';
                
            case self::TYPE_SNAPSHOT_DENIED:
                return 'Your pitch has been denied';
                
            case self::TYPE_SNAPSHOT_REVISIONS_REQUESTED:
                $reason = $data['reason'] ?? '';
                $reasonText = !empty($reason) ? ': "' . $reason . '"' : '';
                return 'Revisions have been requested for your pitch' . $reasonText;
                
            case self::TYPE_PITCH_COMMENT:
                return isset($data['user_name']) 
                    ? $data['user_name'] . ' commented on your pitch' 
                    : 'New comment on your pitch';
                
            case self::TYPE_NEW_SUBMISSION:
                return 'New pitch submission needs your review';
                
            case self::TYPE_FILE_UPLOADED:
                return 'New file has been uploaded to a pitch';
                
            case self::TYPE_PITCH_REVISION:
                return 'A revised pitch has been submitted for review';
                
            case self::TYPE_PITCH_CANCELLED:
                return 'A pitch submission has been cancelled';
                
            default:
                return 'New notification';
        }
    }
}
