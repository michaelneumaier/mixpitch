<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Pitch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
    const TYPE_PITCH_SUBMITTED = 'pitch_submitted';
    const TYPE_PITCH_STATUS_CHANGE = 'pitch_status_change';
    const TYPE_PITCH_COMMENT = 'pitch_comment';
    const TYPE_PITCH_FILE_COMMENT = 'pitch_file_comment';
    const TYPE_SNAPSHOT_APPROVED = 'snapshot_approved';
    const TYPE_SNAPSHOT_DENIED = 'snapshot_denied';
    const TYPE_SNAPSHOT_REVISIONS_REQUESTED = 'snapshot_revisions_requested';
    const TYPE_PITCH_COMPLETED = 'pitch_completed';
    const TYPE_PITCH_EDITED = 'pitch_edited';
    const TYPE_FILE_UPLOADED = 'file_uploaded';
    const TYPE_PITCH_REVISION = 'pitch_revision';
    const TYPE_PITCH_CANCELLED = 'pitch_cancelled';
    const TYPE_PAYMENT_PROCESSED = 'payment_processed';
    const TYPE_PAYMENT_FAILED = 'payment_failed';
    const TYPE_PITCH_APPROVED = 'pitch_approved';
    const TYPE_PITCH_SUBMISSION_APPROVED = 'pitch_submission_approved';
    const TYPE_PITCH_SUBMISSION_DENIED = 'pitch_submission_denied';
    const TYPE_PITCH_SUBMISSION_CANCELLED = 'pitch_submission_cancelled';
    const TYPE_PITCH_READY_FOR_REVIEW = 'pitch_ready_for_review';
    const TYPE_PITCH_CLOSED = 'pitch_closed';
    
    /**
     * Get all defined notification types with user-friendly labels.
     *
     * @return array<string, string> Map of notification type constant => label
     */
    public static function getManageableTypes(): array
    {
        // Define labels for each type
        $labels = [
            self::TYPE_PITCH_SUBMITTED => 'New Pitch Submitted',
            self::TYPE_PITCH_STATUS_CHANGE => 'Pitch Status Changed',
            self::TYPE_PITCH_COMMENT => 'New Comment on Your Pitch',
            self::TYPE_PITCH_FILE_COMMENT => 'New Comment on Your File',
            self::TYPE_SNAPSHOT_APPROVED => 'Snapshot Approved',
            self::TYPE_SNAPSHOT_DENIED => 'Snapshot Denied',
            self::TYPE_SNAPSHOT_REVISIONS_REQUESTED => 'Snapshot Revisions Requested',
            self::TYPE_PITCH_COMPLETED => 'Pitch Completed',
            self::TYPE_PITCH_EDITED => 'Pitch Edited by Producer',
            self::TYPE_FILE_UPLOADED => 'New File Uploaded to Pitch/Project',
            self::TYPE_PITCH_REVISION => 'New Pitch Revision Submitted',
            self::TYPE_PITCH_CANCELLED => 'Pitch Cancelled',
            self::TYPE_PAYMENT_PROCESSED => 'Payment Processed',
            self::TYPE_PAYMENT_FAILED => 'Payment Failed',
            self::TYPE_PITCH_APPROVED => 'Pitch Approved',
            self::TYPE_PITCH_SUBMISSION_APPROVED => 'Pitch Submission Approved',
            self::TYPE_PITCH_SUBMISSION_DENIED => 'Pitch Submission Denied',
            self::TYPE_PITCH_SUBMISSION_CANCELLED => 'Pitch Submission Cancelled by Producer',
            self::TYPE_PITCH_READY_FOR_REVIEW => 'Pitch Ready for Review',
            self::TYPE_PITCH_CLOSED => 'Pitch Closed',
        ];

        // Sort alphabetically by label for display
        asort($labels);

        return $labels;
    }
    
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
        
        if ($this->related_type === 'App\\Models\\Pitch') {
            // Find pitch with project eagerly loaded
            $pitch = Pitch::with('project')->find($this->related_id);
            
            if ($pitch && $pitch->project) {
                // Ensure the pitch has a slug
                if (empty($pitch->slug)) {
                    $this->generateSlugForPitch($pitch);
                }
                
                // Ensure the project has a slug
                if (empty($pitch->project->slug)) {
                    // Project should always have a slug, but just in case
                    $pitch->project->slug = Str::slug($pitch->project->name ?? 'project') . '-' . $pitch->project->id;
                    $pitch->project->save();
                }
                
                // Now we can safely check both slugs are present
                if ($pitch->slug && $pitch->project->slug) {
                    // For comments, include the comment anchor
                    if ($this->type === self::TYPE_PITCH_COMMENT && isset($data['comment_id'])) {
                        return route('projects.pitches.show', ['project' => $pitch->project, 'pitch' => $pitch]) . '#comment-' . $data['comment_id'];
                    }
                    
                    // For all other pitch-related notifications, go to the pitch page
                    return route('projects.pitches.show', ['project' => $pitch->project, 'pitch' => $pitch]);
                }
            }
            
            // Log the issue if pitch, project, or slugs are missing
            \Illuminate\Support\Facades\Log::warning('Could not generate URL for pitch notification due to missing data.', [
                'notification_id' => $this->id,
                'pitch_id' => $this->related_id,
                'pitch_found' => !is_null($pitch),
                'project_found' => $pitch ? !is_null($pitch->project) : false,
                'pitch_slug' => $pitch ? $pitch->slug : null,
                'project_slug' => $pitch && $pitch->project ? $pitch->project->slug : null,
            ]);
            // Fallback to dashboard
            return route('dashboard');
        }
        
        // For pitch file comment notifications (ensure pitch file exists)
        if ($this->related_type === 'App\\Models\\PitchFile' && $this->type === self::TYPE_PITCH_FILE_COMMENT) {
            $pitchFile = \App\Models\PitchFile::find($this->related_id);
            if ($pitchFile) {
                // For comment/reply notifications, include the comment anchor for direct navigation
                if (isset($data['comment_id'])) {
                    return route('pitch-files.show', $pitchFile) . '#comment-' . $data['comment_id'];
                }
                
                // Without specific comment ID, just go to the file page
                return route('pitch-files.show', $pitchFile);
            } else {
                \Illuminate\Support\Facades\Log::warning('Could not generate URL for pitch file notification because PitchFile not found.', [
                    'notification_id' => $this->id,
                    'pitch_file_id' => $this->related_id,
                ]);
                // Fallback to dashboard
                 return route('dashboard');
            }
        }
        
        // Default to dashboard if we can't determine a specific URL
        return route('dashboard');
    }
    
    /**
     * Generate a slug for a pitch if it doesn't have one
     */
    private function generateSlugForPitch($pitch)
    {
        $baseSlug = !empty($pitch->title) 
            ? Str::slug($pitch->title)
            : 'pitch-' . $pitch->id;
        
        $slug = $baseSlug;
        $count = 1;
        
        // Find a unique slug by checking for existing values
        while (
            Pitch::where('project_id', $pitch->project_id)
                ->where('id', '!=', $pitch->id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $count;
            $count++;
        }
        
        $pitch->slug = $slug;
        $pitch->save();
        
        \Illuminate\Support\Facades\Log::info('Generated slug for pitch in notification URL generation', [
            'notification_id' => $this->id,
            'pitch_id' => $pitch->id,
            'slug' => $slug
        ]);
        
        return $pitch;
    }
    
    /**
     * Get a readable description of the notification
     */
    public function getReadableDescription()
    {
        $data = $this->data ?? [];
        $projectName = isset($data['project_name']) ? (' "' . Str::limit($data['project_name'], 30) . '"') : '';

        switch ($this->type) {
            case self::TYPE_PITCH_SUBMITTED:
                $submitterName = $data['submitter_name'] ?? 'A producer';
                return $submitterName . ' submitted a pitch for project' . $projectName;
            case self::TYPE_PITCH_STATUS_CHANGE:
                 $statusText = $data['status'] ?? 'updated';
                 // Add context for cancellation if present
                 $actionText = ($data['action'] ?? '') === 'canceled' ? ' cancelled their pitch for' : ' status updated to "' . $statusText . '" for project';
                 return 'Pitch' . $actionText . $projectName;
            case self::TYPE_PITCH_COMMENT:
                $commenterName = $data['commenter_name'] ?? 'Someone';
                return $commenterName . ' commented on your pitch for project' . $projectName;
            case self::TYPE_PITCH_FILE_COMMENT:
                $commenterName = $data['commenter_name'] ?? 'Someone';
                $fileName = $data['file_name'] ?? 'your audio file'; // Use data or fallback
                // Add logic for replies vs new comments if needed later, based on data
                if (isset($data['replying_to_your_comment']) && $data['replying_to_your_comment']) {
                    return $commenterName . ' replied to your comment on ' . $fileName;
                } else if (isset($data['nested_reply_to_your_thread']) && $data['nested_reply_to_your_thread']) {
                    return $commenterName . ' replied in a thread you started on ' . $fileName;
                } else if (isset($data['is_reply']) && $data['is_reply']) {
                     return $commenterName . ' replied to a comment on ' . $fileName;
                } else {
                    return $commenterName . ' commented on ' . $fileName;
                }
            case self::TYPE_SNAPSHOT_APPROVED:
                return 'Your snapshot for pitch on project' . $projectName . ' was approved.';
            case self::TYPE_SNAPSHOT_DENIED:
                return 'Your snapshot for pitch on project' . $projectName . ' was denied.';
            case self::TYPE_SNAPSHOT_REVISIONS_REQUESTED:
                return 'Revisions requested for your snapshot on project' . $projectName . '.';
            case self::TYPE_PITCH_COMPLETED:
                return 'Your pitch for project' . $projectName . ' was marked as completed.';
            case self::TYPE_PITCH_EDITED:
                $editorName = $data['editor_name'] ?? 'The producer';
                return $editorName . ' edited their pitch for project' . $projectName;
            case self::TYPE_FILE_UPLOADED:
                 $uploaderName = $data['uploader_name'] ?? 'Someone';
                 $fileName = $data['file_name'] ?? 'a file';
                 return $uploaderName . ' uploaded ' . $fileName . ' to a pitch on project' . $projectName;
            case self::TYPE_PITCH_CANCELLED:
                 $cancellerName = $data['canceller_name'] ?? ($data['creator_name'] ?? 'The producer');
                 return $cancellerName . ' cancelled their pitch for project' . $projectName;
            case self::TYPE_PAYMENT_PROCESSED:
                 return 'Payment processed for your pitch on project' . $projectName;
            case self::TYPE_PAYMENT_FAILED:
                 return 'Payment failed for pitch on project' . $projectName;
            case self::TYPE_PITCH_APPROVED:
                 return 'Your pitch for project' . $projectName . ' was approved.';
            case self::TYPE_PITCH_SUBMISSION_APPROVED:
                 return 'A pitch submission was approved';
            case self::TYPE_PITCH_SUBMISSION_DENIED:
                 $reason = $data['reason'] ?? '';
                 $reasonText = !empty($reason) ? ': "' . $reason . '"' : '';
                 return 'A pitch submission was denied' . $reasonText;
            case self::TYPE_PITCH_SUBMISSION_CANCELLED:
                return 'A pitch submission was cancelled';
            case self::TYPE_PITCH_READY_FOR_REVIEW:
                return 'A pitch is ready for your review';
            case self::TYPE_PITCH_CLOSED:
                return 'A pitch has been closed';
            default:
                return 'New notification';
        }
    }
}
