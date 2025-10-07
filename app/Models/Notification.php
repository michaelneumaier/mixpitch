<?php

namespace App\Models;

use App\Traits\HasTimezoneDisplay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Notification model for user notifications
 */
class Notification extends Model
{
    use HasFactory;
    use HasTimezoneDisplay;

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

    const TYPE_PROJECT_UPDATE = 'project_update';

    const TYPE_CONTEST_WINNER_SELECTED = 'contest_winner_selected';

    const TYPE_CONTEST_RUNNER_UP_SELECTED = 'contest_runner_up_selected';

    const TYPE_CONTEST_ENTRY_NOT_SELECTED = 'contest_entry_not_selected';

    const TYPE_CONTEST_ENTRY_SUBMITTED = 'contest_entry_submitted';

    // Phase 3: Added Types for No-Prize/Owner Notifications
    const TYPE_CONTEST_WINNER_SELECTED_NO_PRIZE = 'contest_winner_selected_no_prize';

    const TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION = 'contest_winner_selected_owner_notification';

    const TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION_NO_PRIZE = 'contest_winner_selected_owner_notification_no_prize';

    // Phase 4: Direct Hire Types
    const TYPE_DIRECT_HIRE_ASSIGNMENT = 'direct_hire_assignment';

    const TYPE_DIRECT_HIRE_OFFER = 'direct_hire_offer'; // Placeholder for explicit flow

    const TYPE_DIRECT_HIRE_ACCEPTED = 'direct_hire_accepted'; // Placeholder for explicit flow

    const TYPE_DIRECT_HIRE_REJECTED = 'direct_hire_rejected'; // Placeholder for explicit flow

    // Phase 7: Added Denial Type
    const TYPE_INITIAL_PITCH_DENIED = 'initial_pitch_denied';

    // Client Management Notification Types
    const TYPE_CLIENT_COMMENT_ADDED = 'client_comment_added';

    const TYPE_CLIENT_APPROVED_PITCH = 'client_approved_pitch';

    const TYPE_CLIENT_REQUESTED_REVISIONS = 'client_requested_revisions';

    const TYPE_CLIENT_APPROVED_AND_COMPLETED = 'client_approved_and_completed';

    // Payout Notification Types
    const TYPE_CONTEST_PAYOUT_SCHEDULED = 'contest_payout_scheduled';

    const TYPE_PAYOUT_SCHEDULED = 'payout_scheduled';

    const TYPE_PAYOUT_COMPLETED = 'payout_completed';

    const TYPE_PAYOUT_FAILED = 'payout_failed';

    const TYPE_PAYOUT_CANCELLED = 'payout_cancelled';

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
            self::TYPE_PROJECT_UPDATE => 'Project Update',
            self::TYPE_CONTEST_WINNER_SELECTED => 'Contest Winner Selected',
            self::TYPE_CONTEST_RUNNER_UP_SELECTED => 'Contest Runner-Up Selected',
            self::TYPE_CONTEST_ENTRY_NOT_SELECTED => 'Contest Entry Not Selected',
            self::TYPE_CONTEST_ENTRY_SUBMITTED => 'Contest Entry Submitted',

            // Phase 3 Added Labels
            self::TYPE_CONTEST_WINNER_SELECTED_NO_PRIZE => 'Contest Winner Selected (No Prize)',
            self::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION => 'You Selected a Contest Winner',
            self::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION_NO_PRIZE => 'You Selected a Contest Winner (No Prize)',

            // Phase 4 Labels
            self::TYPE_DIRECT_HIRE_ASSIGNMENT => 'New Direct Hire Assignment',
            self::TYPE_DIRECT_HIRE_OFFER => 'New Direct Hire Offer', // Placeholder
            self::TYPE_DIRECT_HIRE_ACCEPTED => 'Direct Hire Offer Accepted', // Placeholder
            self::TYPE_DIRECT_HIRE_REJECTED => 'Direct Hire Offer Rejected', // Placeholder

            // Phase 7 Added Label
            self::TYPE_INITIAL_PITCH_DENIED => 'Initial Pitch Application Denied',

            // Client Management Labels
            self::TYPE_CLIENT_COMMENT_ADDED => 'Client Added a Comment',
            self::TYPE_CLIENT_APPROVED_PITCH => 'Client Approved Your Submission',
            self::TYPE_CLIENT_REQUESTED_REVISIONS => 'Client Requested Revisions',
            self::TYPE_CLIENT_APPROVED_AND_COMPLETED => 'Client Approved & Project Completed',

            // Payout Labels
            self::TYPE_CONTEST_PAYOUT_SCHEDULED => 'Contest Prize Payout Scheduled',
            self::TYPE_PAYOUT_SCHEDULED => 'Payout Scheduled',
            self::TYPE_PAYOUT_COMPLETED => 'Payout Completed',
            self::TYPE_PAYOUT_FAILED => 'Payout Failed',
            self::TYPE_PAYOUT_CANCELLED => 'Payout Cancelled',
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

        // Handle Contest notifications FIRST (before pitch routing)
        if (in_array($this->type, [
            self::TYPE_CONTEST_WINNER_SELECTED,
            self::TYPE_CONTEST_RUNNER_UP_SELECTED,
            self::TYPE_CONTEST_ENTRY_NOT_SELECTED,
            self::TYPE_CONTEST_WINNER_SELECTED_NO_PRIZE,
        ])) {
            // Route to contest results page
            if (isset($data['project_id'])) {
                try {
                    $project = \App\Models\Project::find($data['project_id']);
                    if ($project) {
                        return route('projects.contest.results', $project);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not generate contest results URL for notification.', [
                        'notification_id' => $this->id,
                        'project_id' => $data['project_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback to dashboard if contest routing fails
            return route('dashboard');
        }

        // Handle Contest owner notifications
        if (in_array($this->type, [
            self::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION,
            self::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION_NO_PRIZE,
        ])) {
            // Route to contest management/judging page
            if (isset($data['project_id'])) {
                try {
                    $project = \App\Models\Project::find($data['project_id']);
                    if ($project) {
                        return route('projects.contest.judging', $project);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not generate contest judging URL for notification.', [
                        'notification_id' => $this->id,
                        'project_id' => $data['project_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback to dashboard if contest routing fails
            return route('dashboard');
        }

        // Handle Contest entry submissions
        if ($this->type === self::TYPE_CONTEST_ENTRY_SUBMITTED) {
            // Route to the project page for contest entries
            if (isset($data['project_id'])) {
                try {
                    $project = \App\Models\Project::find($data['project_id']);
                    if ($project) {
                        return route('projects.show', $project);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not generate project URL for contest entry notification.', [
                        'notification_id' => $this->id,
                        'project_id' => $data['project_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback to dashboard if contest routing fails
            return route('dashboard');
        }

        // Handle Payout notifications
        if (in_array($this->type, [
            self::TYPE_CONTEST_PAYOUT_SCHEDULED,
            self::TYPE_PAYOUT_SCHEDULED,
            self::TYPE_PAYOUT_COMPLETED,
            self::TYPE_PAYOUT_FAILED,
            self::TYPE_PAYOUT_CANCELLED,
        ])) {
            // Try to route to specific payout if we have a payout ID
            if (isset($data['payout_id'])) {
                try {
                    return route('payouts.show', $data['payout_id']);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not generate specific payout URL for notification.', [
                        'notification_id' => $this->id,
                        'payout_id' => $data['payout_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Otherwise route to general payouts page
            try {
                return route('payouts.index');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Could not generate payouts index URL for notification.', [
                    'notification_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Handle Direct Hire notifications
        if (in_array($this->type, [
            self::TYPE_DIRECT_HIRE_ASSIGNMENT,
            self::TYPE_DIRECT_HIRE_ACCEPTED,
        ])) {
            // Route to project management page
            if (isset($data['project_id'])) {
                try {
                    $project = \App\Models\Project::find($data['project_id']);
                    if ($project) {
                        return route('projects.manage', $project);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not generate project management URL for direct hire notification.', [
                        'notification_id' => $this->id,
                        'project_id' => $data['project_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback to projects index
            return route('projects.index');
        }

        // Handle Direct Hire offers (rejected/pending)
        if (in_array($this->type, [
            self::TYPE_DIRECT_HIRE_OFFER,
            self::TYPE_DIRECT_HIRE_REJECTED,
        ])) {
            // Route to project page (where offers are typically managed)
            if (isset($data['project_id'])) {
                try {
                    $project = \App\Models\Project::find($data['project_id']);
                    if ($project) {
                        return route('projects.show', $project);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not generate project URL for direct hire offer notification.', [
                        'notification_id' => $this->id,
                        'project_id' => $data['project_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback to projects index
            return route('projects.index');
        }

        // Handle Client Management notifications
        if (in_array($this->type, [
            self::TYPE_CLIENT_COMMENT_ADDED,
            self::TYPE_CLIENT_APPROVED_PITCH,
            self::TYPE_CLIENT_REQUESTED_REVISIONS,
            self::TYPE_CLIENT_APPROVED_AND_COMPLETED,
        ])) {
            // Route to client project management page
            if (isset($data['project_id'])) {
                try {
                    $project = \App\Models\Project::find($data['project_id']);
                    if ($project) {
                        return route('projects.manage-client', $project);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not generate client project management URL for notification.', [
                        'notification_id' => $this->id,
                        'project_id' => $data['project_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback to projects index
            return route('projects.index');
        }

        // Handle Project Update notifications
        if ($this->type === self::TYPE_PROJECT_UPDATE) {
            // Route to project page
            if (isset($data['project_id'])) {
                try {
                    $project = \App\Models\Project::find($data['project_id']);
                    if ($project) {
                        return route('projects.show', $project);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not generate project URL for project update notification.', [
                        'notification_id' => $this->id,
                        'project_id' => $data['project_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback to projects index
            return route('projects.index');
        }

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
                    $pitch->project->slug = Str::slug($pitch->project->name ?? 'project').'-'.$pitch->project->id;
                    $pitch->project->save();
                }

                // Now we can safely check both slugs are present
                if ($pitch->slug && $pitch->project->slug) {
                    // For comments, include the comment anchor
                    if ($this->type === self::TYPE_PITCH_COMMENT && isset($data['comment_id'])) {
                        return route('projects.pitches.show', ['project' => $pitch->project, 'pitch' => $pitch]).'#comment-'.$data['comment_id'];
                    }

                    // For all other pitch-related notifications, go to the pitch page
                    return route('projects.pitches.show', ['project' => $pitch->project, 'pitch' => $pitch]);
                }
            }

            // Log the issue if pitch, project, or slugs are missing
            \Illuminate\Support\Facades\Log::warning('Could not generate URL for pitch notification due to missing data.', [
                'notification_id' => $this->id,
                'pitch_id' => $this->related_id,
                'pitch_found' => ! is_null($pitch),
                'project_found' => $pitch ? ! is_null($pitch->project) : false,
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
                    return route('pitch-files.show', $pitchFile).'#comment-'.$data['comment_id'];
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
        $baseSlug = ! empty($pitch->title)
            ? Str::slug($pitch->title)
            : 'pitch-'.$pitch->id;

        $slug = $baseSlug;
        $count = 1;

        // Find a unique slug by checking for existing values
        while (
            Pitch::where('project_id', $pitch->project_id)
                ->where('id', '!=', $pitch->id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$count;
            $count++;
        }

        $pitch->slug = $slug;
        $pitch->save();

        \Illuminate\Support\Facades\Log::info('Generated slug for pitch in notification URL generation', [
            'notification_id' => $this->id,
            'pitch_id' => $pitch->id,
            'slug' => $slug,
        ]);

        return $pitch;
    }

    /**
     * Get a readable description of the notification
     */
    public function getReadableDescription()
    {
        $data = $this->data ?? [];
        $projectName = isset($data['project_name']) ? (' "'.Str::limit($data['project_name'], 30).'"') : '';

        switch ($this->type) {
            case self::TYPE_PITCH_SUBMITTED:
                $submitterName = $data['submitter_name'] ?? 'A producer';

                return $submitterName.' submitted a pitch for project'.$projectName;
            case self::TYPE_PITCH_STATUS_CHANGE:
                $statusText = $data['status'] ?? 'updated';
                // Add context for cancellation if present
                $actionText = ($data['action'] ?? '') === 'canceled' ? ' cancelled their pitch for' : ' status updated to "'.$statusText.'" for project';

                return 'Pitch'.$actionText.$projectName;
            case self::TYPE_PITCH_COMMENT:
                $commenterName = $data['commenter_name'] ?? 'Someone';

                return $commenterName.' commented on your pitch for project'.$projectName;
            case self::TYPE_PITCH_FILE_COMMENT:
                $commenterName = $data['commenter_name'] ?? 'Someone';
                $fileName = $data['file_name'] ?? 'your audio file'; // Use data or fallback
                // Add logic for replies vs new comments if needed later, based on data
                if (isset($data['replying_to_your_comment']) && $data['replying_to_your_comment']) {
                    return $commenterName.' replied to your comment on '.$fileName;
                } elseif (isset($data['nested_reply_to_your_thread']) && $data['nested_reply_to_your_thread']) {
                    return $commenterName.' replied in a thread you started on '.$fileName;
                } elseif (isset($data['is_reply']) && $data['is_reply']) {
                    return $commenterName.' replied to a comment on '.$fileName;
                } else {
                    return $commenterName.' commented on '.$fileName;
                }
            case self::TYPE_SNAPSHOT_APPROVED:
                return 'Your snapshot for pitch on project'.$projectName.' was approved.';
            case self::TYPE_SNAPSHOT_DENIED:
                return 'Your snapshot for pitch on project'.$projectName.' was denied.';
            case self::TYPE_SNAPSHOT_REVISIONS_REQUESTED:
                return 'Revisions requested for your snapshot on project'.$projectName.'.';
            case self::TYPE_PITCH_COMPLETED:
                return 'Your pitch for project'.$projectName.' was marked as completed.';
            case self::TYPE_PITCH_EDITED:
                $editorName = $data['editor_name'] ?? 'The producer';

                return $editorName.' edited their pitch for project'.$projectName;
            case self::TYPE_FILE_UPLOADED:
                $uploaderName = $data['uploader_name'] ?? 'Someone';
                $fileName = $data['file_name'] ?? 'a file';

                return $uploaderName.' uploaded '.$fileName.' to a pitch on project'.$projectName;
            case self::TYPE_PITCH_CANCELLED:
                $cancellerName = $data['canceller_name'] ?? ($data['creator_name'] ?? 'The producer');

                return $cancellerName.' cancelled their pitch for project'.$projectName;
            case self::TYPE_PAYMENT_PROCESSED:
                return 'Payment processed for your pitch on project'.$projectName;
            case self::TYPE_PAYMENT_FAILED:
                return 'Payment failed for pitch on project'.$projectName;
            case self::TYPE_PITCH_APPROVED:
                return 'Your pitch for project'.$projectName.' was approved.';
            case self::TYPE_PITCH_SUBMISSION_APPROVED:
                return 'A pitch submission was approved';
            case self::TYPE_PITCH_SUBMISSION_DENIED:
                $reason = $data['reason'] ?? '';
                $reasonText = ! empty($reason) ? ': "'.$reason.'"' : '';

                return 'A pitch submission was denied'.$reasonText;
            case self::TYPE_PITCH_SUBMISSION_CANCELLED:
                return 'A pitch submission was cancelled';
            case self::TYPE_PITCH_READY_FOR_REVIEW:
                return 'A pitch is ready for your review';
            case self::TYPE_PITCH_CLOSED:
                return 'A pitch has been closed';
            case self::TYPE_PROJECT_UPDATE:
                $updateDescription = $data['update_description'] ?? 'Project has been updated';

                return $updateDescription.' for project'.$projectName;
            case self::TYPE_PITCH_REVISION:
                $revisorName = $data['revisor_name'] ?? 'Someone';

                return $revisorName.' submitted a revision for their pitch on project'.$projectName;
            case self::TYPE_CONTEST_WINNER_SELECTED:
                $contestName = $data['contest_name'] ?? $projectName;
                $prizeMoney = isset($data['prize_money']) ? ' ($'.number_format($data['prize_money']).' prize)' : '';

                return 'Congratulations! You won the contest'.$contestName.$prizeMoney;
            case self::TYPE_CONTEST_RUNNER_UP_SELECTED:
                $contestName = $data['contest_name'] ?? $projectName;
                $prizeMoney = isset($data['prize_money']) ? ' ($'.number_format($data['prize_money']).' prize)' : '';

                return 'You were selected as runner-up in the contest'.$contestName.$prizeMoney;
            case self::TYPE_CONTEST_ENTRY_NOT_SELECTED:
                $contestName = $data['contest_name'] ?? $projectName;

                return 'Your entry was not selected for the contest'.$contestName;
            case self::TYPE_CONTEST_ENTRY_SUBMITTED:
                $contestName = $data['contest_name'] ?? $projectName;

                return 'Your contest entry was submitted for'.$contestName;
            case self::TYPE_CONTEST_WINNER_SELECTED_NO_PRIZE:
                $contestName = $data['contest_name'] ?? $projectName;

                return 'Congratulations! You won the contest'.$contestName;
            case self::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION:
                $winnerName = $data['winner_name'] ?? 'A participant';
                $contestName = $data['contest_name'] ?? $projectName;
                $prizeMoney = isset($data['prize_money']) ? ' ($'.number_format($data['prize_money']).' prize)' : '';

                return $winnerName.' won your contest'.$contestName.$prizeMoney;
            case self::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION_NO_PRIZE:
                $winnerName = $data['winner_name'] ?? 'A participant';
                $contestName = $data['contest_name'] ?? $projectName;

                return $winnerName.' won your contest'.$contestName;
            case self::TYPE_DIRECT_HIRE_ASSIGNMENT:
                $assignerName = $data['assigner_name'] ?? 'Someone';

                return $assignerName.' assigned you a direct hire project'.$projectName;
            case self::TYPE_DIRECT_HIRE_OFFER:
                $offerAmount = isset($data['offer_amount']) ? ' ($'.number_format($data['offer_amount']).')' : '';

                return 'You received a direct hire offer for project'.$projectName.$offerAmount;
            case self::TYPE_DIRECT_HIRE_ACCEPTED:
                $accepterName = $data['accepter_name'] ?? 'The producer';

                return $accepterName.' accepted your direct hire offer for project'.$projectName;
            case self::TYPE_DIRECT_HIRE_REJECTED:
                $rejecterName = $data['rejecter_name'] ?? 'The producer';

                return $rejecterName.' declined your direct hire offer for project'.$projectName;
            case self::TYPE_INITIAL_PITCH_DENIED:
                $reason = $data['reason'] ?? '';
                $reasonText = ! empty($reason) ? ': "'.Str::limit($reason, 100).'"' : '';

                return 'Your initial pitch application for project'.$projectName.' was denied'.$reasonText;
            case self::TYPE_CLIENT_COMMENT_ADDED:
                $clientName = $data['client_name'] ?? 'The client';

                return $clientName.' added a comment on your project'.$projectName;
            case self::TYPE_CLIENT_APPROVED_PITCH:
                $clientName = $data['client_name'] ?? 'The client';

                return $clientName.' approved your submission for project'.$projectName;
            case self::TYPE_CLIENT_REQUESTED_REVISIONS:
                $clientName = $data['client_name'] ?? 'The client';
                $revisionNotes = isset($data['revision_notes']) && ! empty($data['revision_notes']) ? ': "'.Str::limit($data['revision_notes'], 100).'"' : '';

                return $clientName.' requested revisions for project'.$projectName.$revisionNotes;
            case self::TYPE_CLIENT_APPROVED_AND_COMPLETED:
                $clientName = $data['client_name'] ?? 'The client';
                $hasPayment = $data['has_payment'] ?? false;
                $paymentAmount = $data['payment_amount'] ?? 0;
                if ($hasPayment && $paymentAmount > 0) {
                    return $clientName.' approved and paid for project'.$projectName.'. Your payout of $'.number_format($paymentAmount, 2).' is being processed.';
                } else {
                    return $clientName.' approved project'.$projectName.' and it\'s now complete!';
                }
            case self::TYPE_CONTEST_PAYOUT_SCHEDULED:
                $payoutAmount = isset($data['net_amount']) ? '$'.number_format($data['net_amount']).' ' : (isset($data['payout_amount']) ? '$'.number_format($data['payout_amount']).' ' : '');
                $payoutDate = isset($data['hold_release_date']) ? ' on '.date('M j, Y', strtotime($data['hold_release_date'])) : (isset($data['payout_date']) ? ' on '.date('M j, Y', strtotime($data['payout_date'])) : '');

                return 'Your contest prize payout of '.$payoutAmount.'has been scheduled'.$payoutDate;
            case self::TYPE_PAYOUT_SCHEDULED:
                $payoutAmount = isset($data['net_amount']) ? '$'.number_format($data['net_amount']).' ' : (isset($data['payout_amount']) ? '$'.number_format($data['payout_amount']).' ' : '');
                $payoutDate = isset($data['hold_release_date']) ? ' on '.date('M j, Y', strtotime($data['hold_release_date'])) : (isset($data['payout_date']) ? ' on '.date('M j, Y', strtotime($data['payout_date'])) : '');

                return 'Your payout of '.$payoutAmount.'has been scheduled'.$payoutDate;
            case self::TYPE_PAYOUT_COMPLETED:
                $payoutAmount = isset($data['payout_amount']) ? '$'.number_format($data['payout_amount']).' ' : '';
                $payoutMethod = isset($data['payout_method']) ? ' via '.$data['payout_method'] : '';

                return 'Your payout of '.$payoutAmount.'has been completed'.$payoutMethod;
            case self::TYPE_PAYOUT_FAILED:
                $payoutAmount = isset($data['payout_amount']) ? '$'.number_format($data['payout_amount']).' ' : '';
                $failureReason = isset($data['failure_reason']) ? ': '.$data['failure_reason'] : '';

                return 'Your payout of '.$payoutAmount.'failed'.$failureReason.'. Please update your payment information.';
            case self::TYPE_PAYOUT_CANCELLED:
                $payoutAmount = isset($data['payout_amount']) ? '$'.number_format($data['payout_amount']).' ' : '';
                $cancellationReason = isset($data['cancellation_reason']) ? ': '.$data['cancellation_reason'] : '';

                return 'Your payout of '.$payoutAmount.'was cancelled'.$cancellationReason;
            default:
                return 'New notification';
        }
    }
}
