<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class NotificationService
{
    protected EmailService $emailService;

    /**
     * Constructor to inject services.
     */
    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Create a notification for a user
     *
     * @param  User  $user  The user to notify
     * @param  string  $type  The notification type
     * @param  Model  $related  The related model
     * @param  array  $data  Additional data
     * @return Notification|null Returns the created notification or null if preferences prevent it
     */
    public function createNotification(User $user, string $type, Model $related, array $data = []): ?Notification
    {
        try {
            // --- Start: Check User Preferences ---
            $preference = NotificationPreference::where('user_id', $user->id)
                ->where('notification_type', $type)
                ->first();

            // If preference exists and is specifically disabled, return null
            if ($preference && ! $preference->is_enabled) {
                Log::info('Notification creation skipped due to user preference', [
                    'user_id' => $user->id,
                    'notification_type' => $type,
                ]);

                return null; // <-- Return null if disabled
            }
            // --- End: Check User Preferences ---

            // Extra debugging for SQL query issue
            DB::enableQueryLog();

            // Debug logging to verify notification creation
            Log::info('Creating notification', [
                'user_id' => $user->id,
                'user_exists' => User::find($user->id) ? 'Yes' : 'No',
                'type' => $type,
                'related_type' => get_class($related),
                'related_id' => $related->id,
                'data' => $data,
            ]);

            // Check if a similar notification was created in the last 5 minutes
            // This helps avoid duplicate notifications for the same action
            $recentTimeWindow = now()->subMinutes(5);
            $existingNotification = Notification::where('user_id', $user->id)
                ->where('related_id', $related->id)
                ->where('related_type', get_class($related))
                ->where('type', $type)
                ->where('created_at', '>=', $recentTimeWindow)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingNotification) {
                Log::info('Recent similar notification found - skipping duplicate', [
                    'notification_id' => $existingNotification->id,
                    'created_at' => $existingNotification->created_at,
                    'minutes_ago' => $existingNotification->created_at->diffInMinutes(now()),
                ]);

                // Return the existing notification instead of creating a duplicate
                // Note: We still return the existing one even if preferences changed,
                // as it represents a notification that *was* sent previously.
                // If the preference check was before this, we might skip showing
                // an already existing (but now disabled) notification.
                return $existingNotification;
            }

            // Check if a notification with the same data already exists
            $existingNotification = Notification::where('user_id', $user->id)
                ->where('related_id', $related->id)
                ->where('related_type', get_class($related))
                ->where('type', $type)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingNotification) {
                Log::info('Existing notification found', [
                    'notification_id' => $existingNotification->id,
                    'created_at' => $existingNotification->created_at,
                ]);
            }

            // Create notification with full error trapping
            try {
                $notification = new Notification;
                $notification->user_id = $user->id;
                $notification->related_id = $related->id;
                $notification->related_type = get_class($related);
                $notification->type = $type;
                $notification->data = $data;
                $notification->save();

                Log::info('SQL Queries executed for notification creation:', [
                    'queries' => DB::getQueryLog(),
                ]);

                // Broadcast the notification event
                event(new \App\Events\NotificationCreated($notification));

                // Confirm notification was saved
                Log::info('Notification created successfully', [
                    'notification_id' => $notification->id,
                    'exists_in_db' => Notification::find($notification->id) ? 'Yes' : 'No',
                ]);

                return $notification;
            } catch (\Exception $innerException) {
                Log::error('Database exception in notification creation', [
                    'message' => $innerException->getMessage(),
                    'trace' => $innerException->getTraceAsString(),
                ]);
                throw $innerException;
            }
        } catch (\Exception $e) {
            Log::error('Failed to create notification', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'type' => $type,
                'related_type' => get_class($related),
                'related_id' => $related->id,
            ]);

            throw $e;
        }
    }

    /**
     * Notify a user about a pitch status change
     *
     * @param  Pitch  $pitch  The pitch
     * @param  string  $status  The new status
     */
    public function notifyPitchStatusChange(Pitch $pitch, string $status): ?Notification
    {
        // Notify the pitch creator about status changes
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about pitch status change: user not found', [
                'pitch_id' => $pitch->id,
                'status' => $status,
            ]);

            return null;
        }

        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_PITCH_STATUS_CHANGE,
                $pitch,
                [
                    'status' => $status,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch status change', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
                'status' => $status,
            ]);

            return null;
        }
    }

    /**
     * Notify a user about a pitch submission being canceled
     *
     * @param  Pitch  $pitch  The pitch
     */
    public function notifyPitchCancellation(Pitch $pitch): ?Notification
    {
        // Notify the pitch creator
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about pitch cancellation: user not found', [
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_PITCH_STATUS_CHANGE, // Using the existing status update type
                $pitch,
                [
                    'status' => $pitch->status,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'action' => 'canceled',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch cancellation', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }
    }

    /**
     * Notify a user about a pitch being completed
     *
     * @param  Pitch  $pitch  The pitch
     * @param  string|null  $feedback  Completion feedback
     */
    public function notifyPitchCompleted(Pitch $pitch, ?string $feedback = null): ?Notification
    {
        // Notify the pitch creator
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about pitch completion: user not found', [
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        $notification = null; // Initialize
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_PITCH_COMPLETED,
                $pitch,
                [
                    'feedback' => $feedback,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                ]
            );

            // Dispatch UserNotification if DB record created
            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch completion', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        return $notification; // Return the DB notification record
    }

    /**
     * Notify a user about a new comment on their pitch
     *
     * @param  Pitch  $pitch  The pitch
     * @param  string  $comment  The comment text
     * @param  int  $commenterId  The user ID of the commenter
     */
    public function notifyPitchComment(Pitch $pitch, string $comment, int $commenterId): ?Notification
    {
        // Don't notify if the comment is by the pitch owner
        if ($pitch->user_id === $commenterId) {
            return null;
        }

        // Notify the pitch creator
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about pitch comment: user not found', [
                'pitch_id' => $pitch->id,
                'commenter_id' => $commenterId,
            ]);

            return null;
        }

        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_PITCH_COMMENT,
                $pitch,
                [
                    'comment' => $comment,
                    'commenter_id' => $commenterId,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch comment', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
                'commenter_id' => $commenterId,
            ]);

            return null;
        }
    }

    /**
     * Notify a user about a snapshot being approved
     *
     * @param  PitchSnapshot  $snapshot  The snapshot
     */
    public function notifySnapshotApproved(PitchSnapshot $snapshot): ?Notification
    {
        // Get the pitch
        $pitch = $snapshot->pitch;

        if (! $pitch) {
            Log::warning('Failed to notify about snapshot approval: pitch not found', [
                'snapshot_id' => $snapshot->id,
            ]);

            return null;
        }

        // Notify the pitch creator
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about snapshot approval: user not found', [
                'snapshot_id' => $snapshot->id,
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_SNAPSHOT_APPROVED,
                $snapshot,
                [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'version' => $snapshot->snapshot_data['version'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about snapshot approval', [
                'message' => $e->getMessage(),
                'snapshot_id' => $snapshot->id,
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }
    }

    /**
     * Notify a user about a snapshot being denied
     *
     * @param  PitchSnapshot  $snapshot  The snapshot
     * @param  string  $reason  The reason for denial
     */
    public function notifySnapshotDenied(PitchSnapshot $snapshot, string $reason): ?Notification
    {
        // Get the pitch
        $pitch = $snapshot->pitch;

        if (! $pitch) {
            Log::warning('Failed to notify about snapshot denial: pitch not found', [
                'snapshot_id' => $snapshot->id,
            ]);

            return null;
        }

        // Notify the pitch creator
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about snapshot denial: user not found', [
                'snapshot_id' => $snapshot->id,
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_SNAPSHOT_DENIED,
                $snapshot,
                [
                    'reason' => $reason,
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'version' => $snapshot->snapshot_data['version'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about snapshot denial', [
                'message' => $e->getMessage(),
                'snapshot_id' => $snapshot->id,
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }
    }

    /**
     * Notify a user about a snapshot having revisions requested
     *
     * @param  PitchSnapshot  $snapshot  The snapshot
     * @param  string  $reason  The reason for requesting revisions
     * @return Notification|null
     */
    public function notifySnapshotRevisionsRequested(PitchSnapshot $snapshot, string $reason)
    {
        $pitch = $snapshot->pitch;
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about snapshot revisions requested: user not found', [
                'snapshot_id' => $snapshot->id,
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        $notification = null; // Initialize
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_SNAPSHOT_REVISIONS_REQUESTED,
                $snapshot, // <-- Related model is the snapshot
                [
                    'reason' => $reason,
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'version' => $snapshot->snapshot_data['version'] ?? null,
                ]
            );

            // Dispatch UserNotification if DB record created
            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id, // This will be snapshot ID
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to create snapshot revisions requested notification: '.$e->getMessage(), [
                'snapshot_id' => $snapshot->id,
                'pitch_id' => $pitch->id,
                'user_id' => $user->id,
            ]);

            return null;
        }

        return $notification; // Return the DB notification record
    }

    /**
     * Notify project owner about a pitch being edited
     *
     * @param  Pitch  $pitch  The pitch
     */
    public function notifyPitchEdited(Pitch $pitch): ?Notification
    {
        // Notify project owner about significant edits to a pitch
        $projectOwner = $pitch->project->user;

        if (! $projectOwner) {
            Log::warning('Failed to notify about pitch edit: project owner not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
            ]);

            return null;
        }

        // Don't notify if the editor is the project owner
        if ($projectOwner->id === auth()->id()) {
            return null;
        }

        try {
            return $this->createNotification(
                $projectOwner,
                Notification::TYPE_PITCH_EDITED,
                $pitch,
                [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'editor_id' => auth()->id(),
                    'editor_name' => auth()->user()->name,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch edit', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }
    }

    /**
     * Notify project owner about a new file upload
     *
     * @param  Pitch  $pitch  The pitch
     * @param  \App\Models\PitchFile  $file  The uploaded file
     */
    public function notifyFileUploaded(Pitch $pitch, \App\Models\PitchFile $file): ?Notification
    {
        // Notify project owner about new file uploads
        $projectOwner = $pitch->project->user;

        if (! $projectOwner) {
            Log::warning('Failed to notify about file upload: project owner not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
                'file_id' => $file->id,
            ]);

            return null;
        }

        // Don't notify if the uploader is the project owner
        if ($projectOwner->id === auth()->id()) {
            return null;
        }

        try {
            return $this->createNotification(
                $projectOwner,
                Notification::TYPE_FILE_UPLOADED,
                $pitch,
                [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'file_id' => $file->id,
                    'file_name' => $file->original_name,
                    'file_size' => $file->size,
                    'uploader_id' => auth()->id(),
                    'uploader_name' => auth()->user()->name,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about file upload', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
                'file_id' => $file->id,
            ]);

            return null;
        }
    }

    /**
     * Notify project owner about a pitch revision being submitted
     *
     * @param  Pitch  $pitch  The pitch
     */
    public function notifyPitchRevisionSubmitted(Pitch $pitch): ?Notification
    {
        // Notify project owner about a revision submission
        $projectOwner = $pitch->project->user;

        if (! $projectOwner) {
            Log::warning('Failed to notify about revision submission: project owner not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
            ]);

            return null;
        }

        try {
            return $this->createNotification(
                $projectOwner,
                Notification::TYPE_PITCH_REVISION,
                $pitch,
                [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'submitter_id' => auth()->id(),
                    'submitter_name' => auth()->user()->name,
                    'snapshot_id' => $pitch->current_snapshot_id,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about revision submission', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }
    }

    /**
     * Notify a user about a new comment on their pitch file
     *
     * @param  \App\Models\PitchFile  $pitchFile  The pitch file
     * @param  \App\Models\PitchFileComment  $comment  The comment
     * @param  int  $commenterId  The user ID of the commenter
     */
    public function notifyPitchFileComment(\App\Models\PitchFile $pitchFile, \App\Models\PitchFileComment $comment, int $commenterId): ?Notification
    {
        // Get commenter's name for the notification
        $commenter = User::find($commenterId);
        $commenterName = $commenter ? $commenter->name : 'Someone';

        // Create notification data
        $notificationData = [
            'comment_id' => $comment->id,
            'comment_text' => $comment->comment,
            'commenter_id' => $commenterId,
            'user_name' => $commenterName,
            'timestamp' => $comment->timestamp,
            'formatted_timestamp' => $comment->formattedTimestamp,
            'pitch_id' => $pitchFile->pitch->id,
            'is_reply' => $comment->parent_id !== null,
            'parent_comment_id' => $comment->parent_id,
        ];

        $notifiedUsers = [];
        $notifications = [];

        // If this is a reply, notify the parent comment author
        if ($comment->parent_id) {
            $parentComment = \App\Models\PitchFileComment::find($comment->parent_id);
            if ($parentComment && $parentComment->user_id !== $commenterId && ! in_array($parentComment->user_id, $notifiedUsers)) {
                $parentCommentAuthor = User::find($parentComment->user_id);

                if ($parentCommentAuthor) {
                    try {
                        $notifications[] = $this->createNotification(
                            $parentCommentAuthor,
                            Notification::TYPE_PITCH_FILE_COMMENT,
                            $pitchFile,
                            array_merge($notificationData, [
                                'replying_to_your_comment' => true,
                            ])
                        );
                        $notifiedUsers[] = $parentCommentAuthor->id;
                    } catch (\Exception $e) {
                        Log::error('Failed to notify parent comment author about reply', [
                            'message' => $e->getMessage(),
                            'pitch_file_id' => $pitchFile->id,
                            'parent_comment_id' => $comment->parent_id,
                        ]);
                    }
                }

                // If this is a reply to a reply, also notify the original top-level comment author
                if ($parentComment->parent_id) {
                    $topLevelComment = \App\Models\PitchFileComment::find($parentComment->parent_id);
                    if ($topLevelComment && $topLevelComment->user_id !== $commenterId &&
                        ! in_array($topLevelComment->user_id, $notifiedUsers)) {
                        $topLevelAuthor = User::find($topLevelComment->user_id);

                        if ($topLevelAuthor) {
                            try {
                                $notifications[] = $this->createNotification(
                                    $topLevelAuthor,
                                    Notification::TYPE_PITCH_FILE_COMMENT,
                                    $pitchFile,
                                    array_merge($notificationData, [
                                        'nested_reply_to_your_thread' => true,
                                    ])
                                );
                                $notifiedUsers[] = $topLevelAuthor->id;
                            } catch (\Exception $e) {
                                Log::error('Failed to notify top-level comment author about nested reply', [
                                    'message' => $e->getMessage(),
                                    'pitch_file_id' => $pitchFile->id,
                                    'top_level_comment_id' => $topLevelComment->id,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // Notify the pitch file owner if not already notified
        if ($pitchFile->user_id !== $commenterId && ! in_array($pitchFile->user_id, $notifiedUsers)) {
            $fileOwner = User::find($pitchFile->user_id);

            if ($fileOwner) {
                try {
                    $notifications[] = $this->createNotification(
                        $fileOwner,
                        Notification::TYPE_PITCH_FILE_COMMENT,
                        $pitchFile,
                        $notificationData
                    );
                    $notifiedUsers[] = $fileOwner->id;
                } catch (\Exception $e) {
                    Log::error('Failed to notify file owner about pitch file comment', [
                        'message' => $e->getMessage(),
                        'pitch_file_id' => $pitchFile->id,
                        'file_owner_id' => $pitchFile->user_id,
                    ]);
                }
            }
        }

        // Notify the pitch owner if different from file owner and not already notified
        $pitchOwner = $pitchFile->pitch->user;
        if ($pitchOwner && $pitchOwner->id !== $commenterId && $pitchOwner->id !== $pitchFile->user_id && ! in_array($pitchOwner->id, $notifiedUsers)) {
            try {
                $notifications[] = $this->createNotification(
                    $pitchOwner,
                    Notification::TYPE_PITCH_FILE_COMMENT,
                    $pitchFile,
                    $notificationData
                );
                $notifiedUsers[] = $pitchOwner->id;
            } catch (\Exception $e) {
                Log::error('Failed to notify pitch owner about pitch file comment', [
                    'message' => $e->getMessage(),
                    'pitch_file_id' => $pitchFile->id,
                    'pitch_owner_id' => $pitchOwner->id,
                ]);
            }
        }

        // Return the first notification created (or null if none were)
        return $notifications[0] ?? null;
    }

    /**
     * Notify a user about successful payment processing
     *
     * @param  Pitch  $pitch  The pitch
     * @param  float  $amount  The payment amount
     * @param  string|null  $invoiceId  The invoice ID
     */
    public function notifyPaymentProcessed(Pitch $pitch, float $amount, ?string $invoiceId = null): ?Notification
    {
        // Notify the pitch creator
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about payment processing: user not found', [
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_PAYMENT_PROCESSED,
                $pitch,
                [
                    'amount' => $amount,
                    'invoice_id' => $invoiceId,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about payment processing', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }
    }

    /**
     * Notify a user about failed payment
     *
     * @param  Pitch  $pitch  The pitch
     * @param  string  $reason  The failure reason
     */
    public function notifyPaymentFailed(Pitch $pitch, string $reason): ?Notification
    {
        // Notify the pitch creator
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about payment failure: user not found', [
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_PAYMENT_FAILED,
                $pitch,
                [
                    'reason' => $reason,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about payment failure', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }
    }

    /**
     * Notify the project owner when a new pitch is submitted for their project.
     *
     * @param  Pitch  $pitch  The newly submitted pitch.
     */
    public function notifyPitchSubmitted(Pitch $pitch): ?Notification
    {
        $projectOwner = $pitch->project->user;
        $pitchCreator = $pitch->user;

        if (! $projectOwner) {
            Log::warning('Failed to notify about pitch submission: project owner not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
            ]);

            return null;
        }
        if (! $pitchCreator) {
            Log::warning('Failed to notify about pitch submission: pitch creator not found', [
                'pitch_id' => $pitch->id,
                'user_id' => $pitch->user_id,
            ]);
            // Continue, but creator name might be missing
        }

        $notification = null; // Initialize notification variable
        try {
            // Create the notification for the Project Owner
            $notification = $this->createNotification(
                $projectOwner,
                Notification::TYPE_PITCH_SUBMITTED,
                $pitch,
                [
                    'pitch_id' => $pitch->id,
                    'pitch_slug' => $pitch->slug,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'producer_id' => $pitchCreator?->id,
                    'producer_name' => $pitchCreator?->name ?? 'A producer',
                ]
            );

            // Dispatch UserNotification if DB record created
            if ($notification) {
                try {
                    $projectOwner->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $projectOwner->id,
                    ]);
                }
            }

            // Send email notification via EmailService (Keep this commented unless implemented)
            // try { ... email service call ... } catch { ... } ...

            return $notification; // Return the DB notification record

        } catch (\Exception $e) {
            Log::error('Failed to notify project owner about new pitch submission', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
                'project_owner_id' => $projectOwner->id,
            ]);

            return null;
        }
    }

    /**
     * Notify a user about a pitch being approved (initial approval)
     *
     * @param  Pitch  $pitch  The pitch
     */
    public function notifyPitchApproved(Pitch $pitch): ?Notification
    {
        // Notify the pitch creator about their pitch being approved
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about pitch approval: user not found', [
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        $notification = null; // Initialize
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_PITCH_APPROVED,
                $pitch,
                [
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'status' => $pitch->status,
                ]
            );

            // Dispatch UserNotification if DB record created
            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch approval', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        return $notification; // Return the DB notification record
    }

    /**
     * Notify about a pitch submission being approved
     *
     * @param  Pitch  $pitch  The pitch
     * @param  int|PitchSnapshot  $snapshot  The snapshot ID or object
     */
    public function notifyPitchSubmissionApproved(Pitch $pitch, $snapshot): ?Notification
    {
        // Resolve snapshot ID or object
        $snapshotId = $snapshot instanceof PitchSnapshot ? $snapshot->id : $snapshot;

        // Get the snapshot object if we only have an ID
        if (! ($snapshot instanceof PitchSnapshot)) {
            $snapshot = PitchSnapshot::find($snapshotId);
            if (! $snapshot) {
                Log::warning('Failed to notify about pitch submission approval: snapshot not found', [
                    'pitch_id' => $pitch->id,
                    'snapshot_id' => $snapshotId,
                ]);

                return null;
            }
        }

        // Notify the pitch creator
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about pitch submission approval: user not found', [
                'pitch_id' => $pitch->id,
                'snapshot_id' => $snapshotId,
            ]);

            return null;
        }

        $notification = null; // Initialize
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_SNAPSHOT_APPROVED,
                $snapshot,
                [
                    'snapshot_id' => $snapshotId,
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'version' => $snapshot->snapshot_data['version'] ?? null,
                ]
            );

            // Dispatch UserNotification if DB record created
            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch submission approval', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
                'snapshot_id' => $snapshotId,
            ]);

            return null;
        }

        return $notification; // Return the DB notification record
    }

    /**
     * Notify about a pitch submission being denied
     *
     * @param  Pitch  $pitch  The pitch
     * @param  int|PitchSnapshot  $snapshot  The snapshot ID or object
     * @param  string|null  $reason  The reason for denial
     */
    public function notifyPitchSubmissionDenied(Pitch $pitch, $snapshot, ?string $reason = null): ?Notification
    {
        // Resolve snapshot ID or object
        $snapshotId = $snapshot instanceof PitchSnapshot ? $snapshot->id : $snapshot;
        if (! ($snapshot instanceof PitchSnapshot)) {
            $snapshot = PitchSnapshot::find($snapshotId);
            if (! $snapshot) {
                Log::warning('Failed to notify about pitch submission denial: snapshot not found', [
                    'pitch_id' => $pitch->id,
                    'snapshot_id' => $snapshotId,
                ]);

                return null;
            }
        }

        // Notify the pitch creator
        $user = $pitch->user;
        if (! $user) {
            Log::warning('Failed to notify about pitch submission denial: user not found', [
                'pitch_id' => $pitch->id,
                'snapshot_id' => $snapshotId,
            ]);

            return null;
        }

        $notification = null; // Initialize
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_SNAPSHOT_DENIED,
                $snapshot,
                [
                    'snapshot_id' => $snapshotId,
                    'reason' => $reason,
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'version' => $snapshot->snapshot_data['version'] ?? null,
                ]
            );

            // Dispatch UserNotification if DB record created
            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch submission denial', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
                'snapshot_id' => $snapshotId,
            ]);

            return null;
        }

        return $notification; // Return the DB notification record
    }

    /**
     * Notify about a pitch submission being cancelled
     *
     * @param  Pitch  $pitch  The pitch
     */
    public function notifyPitchSubmissionCancelled(Pitch $pitch): ?Notification
    {
        // Notify the project owner about the cancellation
        $projectOwner = $pitch->project->user;

        if (! $projectOwner) {
            Log::warning('Failed to notify about pitch submission cancellation: project owner not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
            ]);

            return null;
        }

        try {
            return $this->createNotification(
                $projectOwner,
                Notification::TYPE_PITCH_SUBMISSION_CANCELLED,
                $pitch,
                [
                    'producer_name' => $pitch->user->name,
                    'producer_id' => $pitch->user_id,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch submission cancellation', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }
    }

    /**
     * Notify project owner that a pitch is ready for review
     *
     * @param  Pitch  $pitch  The pitch that's ready for review
     * @param  PitchSnapshot  $snapshot  The snapshot that was created
     */
    public function notifyPitchReadyForReview(Pitch $pitch, PitchSnapshot $snapshot): ?Notification
    {
        // Notify the project owner
        $user = $pitch->project->user;

        if (! $user) {
            Log::warning('Failed to notify about pitch ready for review: project owner not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
                'snapshot_id' => $snapshot->id,
            ]);

            return null;
        }

        $notification = null; // Initialize
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_PITCH_READY_FOR_REVIEW,
                $pitch, // <-- Related model is the pitch
                [
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'snapshot_id' => $snapshot->id,
                    'snapshot_version' => $snapshot->snapshot_data['version'] ?? 1,
                    'is_resubmission' => ($snapshot->snapshot_data['version'] ?? 1) > 1,
                ]
            );

            // Dispatch UserNotification if DB record created
            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id, // This will be pitch ID
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch ready for review', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
                'snapshot_id' => $snapshot->id,
            ]);

            return null;
        }

        return $notification; // Return the DB notification record
    }

    /**
     * Notify a user about a pitch being closed
     *
     * @param  Pitch  $pitch  The pitch that was closed
     */
    public function notifyPitchClosed(Pitch $pitch): ?Notification
    {
        // Notify the pitch creator
        $user = $pitch->user;

        if (! $user) {
            Log::warning('Failed to notify about pitch being closed: user not found', [
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_PITCH_CLOSED,
                $pitch,
                [
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'reason' => 'Another pitch was completed for this project.',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch being closed', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }
    }

    // <<< PHASE 3: CONTEST NOTIFICATIONS >>>

    /**
     * Notify the winner of a contest (with prize).
     *
     * @param  Pitch  $pitch  The winning pitch.
     */
    public function notifyContestWinnerSelected(Pitch $pitch): ?Notification
    {
        $user = $pitch->user;
        if (! $user) {
            Log::warning('notifyContestWinnerSelected: user not found', ['pitch_id' => $pitch->id]);

            return null;
        }

        $notification = null;
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_CONTEST_WINNER_SELECTED,
                $pitch,
                [
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'prize_amount' => $pitch->project->prize_amount,
                    'prize_currency' => $pitch->project->prize_currency,
                ]
            );

            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify contest winner', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        return $notification;
    }

    /**
     * Notify the winner of a contest (no prize).
     *
     * @param  Pitch  $pitch  The winning pitch.
     */
    public function notifyContestWinnerSelectedNoPrize(Pitch $pitch): ?Notification
    {
        $user = $pitch->user;
        if (! $user) {
            Log::warning('notifyContestWinnerSelectedNoPrize: user not found', ['pitch_id' => $pitch->id]);

            return null;
        }

        $notification = null;
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_CONTEST_WINNER_SELECTED_NO_PRIZE,
                $pitch,
                [
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                ]
            );

            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify contest winner (no prize)', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        return $notification;
    }

    /**
     * Notify the project owner about winner selection (with prize).
     *
     * @param  Pitch  $pitch  The winning pitch.
     */
    public function notifyContestWinnerSelectedOwner(Pitch $pitch): ?Notification
    {
        $owner = $pitch->project->user;
        if (! $owner) {
            Log::warning('notifyContestWinnerSelectedOwner: owner not found', ['project_id' => $pitch->project_id]);

            return null;
        }

        $notification = null;
        try {
            $notification = $this->createNotification(
                $owner,
                Notification::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION,
                $pitch,
                [
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'winner_id' => $pitch->user_id,
                    'winner_name' => $pitch->user->name,
                    'prize_amount' => $pitch->project->prize_amount,
                    'prize_currency' => $pitch->project->prize_currency,
                ]
            );

            if ($notification) {
                try {
                    $owner->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $owner->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify owner about contest winner', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        return $notification;
    }

    /**
     * Notify the project owner about winner selection (no prize).
     *
     * @param  Pitch  $pitch  The winning pitch.
     */
    public function notifyContestWinnerSelectedOwnerNoPrize(Pitch $pitch): ?Notification
    {
        $owner = $pitch->project->user;
        if (! $owner) {
            Log::warning('notifyContestWinnerSelectedOwnerNoPrize: owner not found', ['project_id' => $pitch->project_id]);

            return null;
        }

        $notification = null;
        try {
            $notification = $this->createNotification(
                $owner,
                Notification::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION_NO_PRIZE,
                $pitch,
                [
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'winner_id' => $pitch->user_id,
                    'winner_name' => $pitch->user->name,
                ]
            );

            if ($notification) {
                try {
                    $owner->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $owner->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify owner about contest winner (no prize)', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        return $notification;
    }

    /**
     * Notify a user that their contest entry was not selected
     *
     * @param  Pitch  $pitch  The pitch that was not selected.
     */
    public function notifyContestEntryNotSelected(Pitch $pitch): ?Notification
    {
        $user = $pitch->user; // Producer whose entry wasn't selected
        if (! $user) {
            Log::warning('Failed to notify contest entry not selected: user not found', ['pitch_id' => $pitch->id]);

            return null;
        }

        $notification = null;
        try {
            // Avoid notifying if they were winner or runner-up (shouldn't happen with workflow, but safety check)
            if ($pitch->status === Pitch::STATUS_CONTEST_WINNER || $pitch->status === Pitch::STATUS_CONTEST_RUNNER_UP) {
                return null;
            }

            $notification = $this->createNotification(
                $user,
                Notification::TYPE_CONTEST_ENTRY_NOT_SELECTED, // Assumes this type exists
                $pitch,
                [
                    'project_name' => $pitch->project->title,
                    'project_id' => $pitch->project_id,
                ]
            );

            // Dispatch UserNotification if DB record created
            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify contest entry not selected', ['message' => $e->getMessage(), 'pitch_id' => $pitch->id]);

            return null;
        }

        return $notification;
    }

    // Optional: Implement notifyContestEntrySubmitted if specific logic needed
    /*
    public function notifyContestEntrySubmitted(Pitch $pitch): ?Notification
    {
        // Notify project owner about a new contest entry
        $projectOwner = $pitch->project->user;
        if (!$projectOwner) {
            Log::warning('Failed to notify about contest entry submission: project owner not found', ['pitch_id' => $pitch->id]);
            return null;
        }

        try {
            return $this->createNotification(
                $projectOwner,
                Notification::TYPE_CONTEST_ENTRY_SUBMITTED, // Assumes this type exists
                $pitch,
                [
                    'project_name' => $pitch->project->title,
                    'project_id' => $pitch->project_id,
                    'producer_name' => $pitch->user->name,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about contest entry submission', ['message' => $e->getMessage(), 'pitch_id' => $pitch->id]);
            return null;
        }
    }
    */

    // <<< END PHASE 3: CONTEST NOTIFICATIONS >>>

    /**
     * Notify the producer when they are assigned to a Direct Hire project (Implicit Flow).
     *
     * @param  Pitch  $pitch  The automatically created pitch for the direct hire.
     */
    public function notifyDirectHireAssignment(Pitch $pitch): ?Notification
    {
        $producer = $pitch->user;
        if (! $producer) {
            Log::warning('Failed to send Direct Hire assignment notification: producer not found', ['pitch_id' => $pitch->id]);

            return null;
        }

        Log::info('Attempting to send Direct Hire assignment notification', ['pitch_id' => $pitch->id, 'producer_id' => $producer->id]);

        // Placeholder for actual notification logic (e.g., createNotification, emailService call)
        // $notification = $this->createNotification(...);
        // $this->emailService->sendDirectHireAssignmentEmail($pitch);
        try {
            $notification = $this->createNotification(
                $producer, // User to notify
                Notification::TYPE_DIRECT_HIRE_ASSIGNMENT, // Define this constant in Notification model
                $pitch, // Related model
                [
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'project_owner_id' => $pitch->project->user_id,
                    'project_owner_name' => $pitch->project->user->name,
                ]
            );

            // Optional: Trigger email notification
            // $this->emailService->sendDirectHireAssignmentEmail($pitch);

            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to create Direct Hire assignment notification', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
                'producer_id' => $producer->id,
            ]);

            return null;
        }
        // Return null for now as no actual notification is created yet.
        // return null;
    }

    /**
     * Notify the producer when offered a Direct Hire project (Explicit Flow).
     */
    public function notifyDirectHireOffer(Pitch $pitch): ?Notification
    {
        // Notify the producer (pitch->user)
        $producer = $pitch->user;
        if (! $producer) {
            return null;
        }

        return $this->createNotification(
            $producer,
            Notification::TYPE_DIRECT_HIRE_OFFER,
            $pitch,
            [
                'project_title' => $pitch->project->title,
                'project_id' => $pitch->project_id,
                'owner_name' => $pitch->project->user->name, // Assuming owner relationship exists
            ]
        );
    }

    // --- Client Management Notifications ---

    /**
     * Notify the client that their project invite is ready.
     * Sends an email directly to the client's email address.
     *
     * @param  Project  $project  The client management project.
     * @param  string  $signedUrl  The secure link to the client portal.
     */
    public function notifyClientProjectInvite(Project $project, string $signedUrl): void
    {
        if (! $project->isClientManagement() || ! $project->client_email) {
            Log::warning('Attempted to send client invite for non-client project or missing email', [
                'project_id' => $project->id,
                'workflow_type' => $project->workflow_type,
            ]);

            return;
        }

        // Use EmailService to send the email
        $this->emailService->sendClientInviteEmail(
            $project->client_email,
            $project->client_name,
            $project,
            $signedUrl
        );
    }

    /**
     * Notify the client that the producer has submitted work for review.
     *
     * @param  Pitch  $pitch  The pitch containing the work.
     * @param  string  $signedUrl  The secure link to the client portal view.
     */
    public function notifyClientReviewReady(Pitch $pitch, string $signedUrl): void
    {
        $project = $pitch->project;
        if (! $project->isClientManagement() || ! $project->client_email) {
            Log::warning('Attempted to send client review ready notification for non-client project or missing email', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
            ]);

            return;
        }

        // Use EmailService to send the email
        $this->emailService->sendClientReviewReadyEmail(
            $project->client_email,
            $project->client_name,
            $project,
            $pitch,
            $signedUrl
        );
    }

    /**
     * Notify the producer that the client has added a comment.
     *
     * @param  Pitch  $pitch  The relevant pitch.
     * @param  string  $commentContent  The content of the client's comment.
     */
    public function notifyProducerClientCommented(Pitch $pitch, string $commentContent): ?Notification
    {
        $producer = $pitch->user;
        if (! $producer) {
            return null;
        }

        // Send email notification
        try {
            $this->emailService->sendProducerClientCommented(
                $producer,
                $pitch->project,
                $pitch,
                $commentContent
            );
            Log::info('Sent producer client commented email', [
                'producer_id' => $producer->id,
                'pitch_id' => $pitch->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send producer client commented email', [
                'producer_id' => $producer->id,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->createNotification(
            $producer,
            Notification::TYPE_CLIENT_COMMENT_ADDED,
            $pitch,
            [
                'project_id' => $pitch->project_id,
                'project_title' => $pitch->project->title,
                'client_name' => $pitch->project->client_name ?? 'Client',
                'comment_excerpt' => Str::limit($commentContent, 100),
            ]
        );
    }

    /**
     * Notify the producer that the client has approved the pitch/submission.
     *
     * @param  Pitch  $pitch  The approved pitch.
     */
    public function notifyProducerClientApproved(Pitch $pitch): ?Notification
    {
        $producer = $pitch->user;
        if (! $producer) {
            return null;
        }

        return $this->createNotification(
            $producer,
            Notification::TYPE_CLIENT_APPROVED_PITCH,
            $pitch,
            [
                'project_id' => $pitch->project_id,
                'project_title' => $pitch->project->title,
                'client_name' => $pitch->project->client_name ?? 'Client',
            ]
        );
    }

    /**
     * Notify the producer that the client has requested revisions.
     *
     * @param  Pitch  $pitch  The pitch requiring revisions.
     * @param  string  $feedback  The client's feedback.
     */
    public function notifyProducerClientRevisionsRequested(Pitch $pitch, string $feedback): ?Notification
    {
        $producer = $pitch->user;
        if (! $producer) {
            return null;
        }

        // Send email notification
        try {
            $this->emailService->sendProducerClientRevisionsRequested(
                $producer,
                $pitch->project,
                $pitch,
                $feedback
            );
            Log::info('Sent producer client revisions requested email', [
                'producer_id' => $producer->id,
                'pitch_id' => $pitch->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send producer client revisions requested email', [
                'producer_id' => $producer->id,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->createNotification(
            $producer,
            Notification::TYPE_CLIENT_REQUESTED_REVISIONS,
            $pitch,
            [
                'project_id' => $pitch->project_id,
                'project_title' => $pitch->project->title,
                'client_name' => $pitch->project->client_name ?? 'Client',
                'feedback_excerpt' => Str::limit($feedback, 100),
            ]
        );
    }

    /**
     * Notify the client that the producer has added a comment.
     *
     * @param  Pitch  $pitch  The relevant pitch.
     * @param  string  $comment  The producer's comment.
     */
    public function notifyClientProducerCommented(Pitch $pitch, string $comment): void
    {
        $project = $pitch->project;
        if (! $project->isClientManagement() || ! $project->client_email) {
            Log::warning('Attempted to send producer comment notification for non-client project or missing email', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
            ]);

            return;
        }

        // Generate signed portal URL
        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'client.portal.view',
            now()->addDays(7),
            ['project' => $project->id]
        );

        // Send email notification
        $this->emailService->sendClientProducerCommentEmail(
            $project->client_email,
            $project->client_name,
            $project,
            $pitch,
            $comment,
            $signedUrl
        );

        Log::info('Producer comment notification sent to client', [
            'project_id' => $project->id,
            'pitch_id' => $pitch->id,
            'client_email' => $project->client_email,
            'comment_length' => strlen($comment),
        ]);
    }

    /**
     * Notify the client that the project managed by the producer is complete.
     *
     * @param  Pitch  $pitch  The completed pitch (contains project relation).
     * @param  string  $signedUrl  A potentially non-actionable link to the portal.
     * @param  string|null  $feedback  Feedback left by the producer for the client (if any).
     * @param  int|null  $rating  Rating given by the producer (if any).
     */
    public function notifyClientProjectCompleted(Pitch $pitch, string $signedUrl, ?string $feedback, ?int $rating): void
    {
        $project = $pitch->project;
        if (! $project->isClientManagement() || ! $project->client_email) {
            Log::warning('Attempted to send client project completed notification for non-client project or missing email', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
            ]);

            return;
        }

        // Use EmailService to send the email
        $this->emailService->sendClientProjectCompletedEmail(
            $project->client_email,
            $project->client_name,
            $project,
            $pitch,
            $signedUrl,
            $feedback, // Pass feedback
            $rating    // Pass rating
        );
    }

    /**
     * Notify the producer that the client has approved and the project is completed.
     * This combines approval and completion notifications for client management workflow.
     *
     * @param  Pitch  $pitch  The approved and completed pitch.
     */
    public function notifyProducerClientApprovedAndCompleted(Pitch $pitch): ?Notification
    {
        $producer = $pitch->user;
        if (! $producer) {
            return null;
        }

        $project = $pitch->project;
        $paymentAmount = $pitch->payment_amount;
        $hasPayment = $paymentAmount > 0;

        // Create in-app notification
        $notification = $this->createNotification(
            $producer,
            Notification::TYPE_CLIENT_APPROVED_AND_COMPLETED,
            $pitch,
            [
                'project_id' => $project->id,
                'project_title' => $project->title,
                'client_name' => $project->client_name ?? 'Client',
                'client_email' => $project->client_email,
                'payment_amount' => $paymentAmount,
                'has_payment' => $hasPayment,
                'message' => $hasPayment
                    ? "Great news! {$project->client_name} has approved and paid for '{$project->title}'. Your payout is being processed."
                    : "Great news! {$project->client_name} has approved '{$project->title}' and the project is now complete!",
            ]
        );

        // Send email notification using EmailService
        try {
            $this->emailService->sendProducerClientApprovedAndCompletedEmail(
                $producer,
                $project,
                $pitch,
                $hasPayment
            );
        } catch (\Exception $e) {
            Log::error('Failed to send producer client approved and completed email', [
                'producer_id' => $producer->id,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $notification;
    }

    /**
     * Notify the producer that their payout has been scheduled.
     *
     * @param  User  $producer  The producer user.
     * @param  float  $netAmount  The net payout amount.
     * @param  \App\Models\PayoutSchedule  $payoutSchedule  The payout schedule record.
     */
    public function notifyProducerPayoutScheduled(User $producer, float $netAmount, \App\Models\PayoutSchedule $payoutSchedule): ?Notification
    {
        if (! $producer) {
            return null;
        }

        // Create in-app notification
        $notification = $this->createNotification(
            $producer,
            Notification::TYPE_PAYOUT_SCHEDULED,
            $payoutSchedule,
            [
                'payout_schedule_id' => $payoutSchedule->id,
                'net_amount' => $netAmount,
                'project_id' => $payoutSchedule->project_id,
                'project_title' => $payoutSchedule->project->title ?? 'Project',
                'hold_release_date' => $payoutSchedule->hold_release_date,
                'message' => 'Your payout of $'.number_format($netAmount, 2).' has been scheduled and will be released on '.$payoutSchedule->hold_release_date->format('M d, Y'),
            ]
        );

        // Send email notification
        try {
            $this->emailService->sendProducerPayoutScheduledEmail(
                $producer,
                $netAmount,
                $payoutSchedule
            );
        } catch (\Exception $e) {
            Log::error('Failed to send producer payout scheduled email', [
                'producer_id' => $producer->id,
                'payout_schedule_id' => $payoutSchedule->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $notification;
    }

    // --- End Client Management Notifications ---

    /**
     * Send a notification to a user using Laravel's notification system.
     *
     * @param  mixed  $notifiable  The user or entity to notify
     * @param  mixed  $notification  The notification instance to send
     */
    public function notify($notifiable, $notification): void
    {
        try {
            $notifiable->notify($notification);
            Log::info('Notification sent', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'notification_type' => get_class($notification),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'notification_type' => get_class($notification),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify about initial pitch application denial
     *
     * @param  Pitch  $pitch  The denied pitch
     * @param  string|null  $reason  Optional reason for denial
     */
    public function notifyInitialPitchDenied(Pitch $pitch, ?string $reason = null): ?Notification
    {
        // Notify the pitch creator
        $user = $pitch->user;
        if (! $user) { /* ... logging ... */ return null;
        }

        $notification = null;
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_INITIAL_PITCH_DENIED,
                $pitch,
                [
                    'reason' => $reason,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                ]
            );

            // Dispatch UserNotification if DB record created
            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    // Log the notification dispatch error but don't fail the whole operation
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify about initial pitch denial', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);

            return null;
        }

        return $notification;
    }

    // --- Payout Notifications ---

    /**
     * Notify a user that their payout has been scheduled
     *
     * @param  User  $user  The recipient
     * @param  PayoutSchedule  $payoutSchedule  The scheduled payout
     */
    public function notifyPayoutScheduled(User $user, $payoutSchedule): ?Notification
    {
        if (! $user) {
            Log::warning('notifyPayoutScheduled: user not found');

            return null;
        }

        $notification = null;
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_PAYOUT_SCHEDULED,
                $payoutSchedule,
                [
                    'project_id' => $payoutSchedule->project_id,
                    'project_name' => $payoutSchedule->project->name ?? 'Project',
                    'gross_amount' => $payoutSchedule->gross_amount,
                    'net_amount' => $payoutSchedule->net_amount,
                    'currency' => $payoutSchedule->currency,
                    'hold_release_date' => $payoutSchedule->hold_release_date->toDateString(),
                ]
            );

            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify payout scheduled', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
                'payout_schedule_id' => $payoutSchedule->id,
            ]);

            return null;
        }

        return $notification;
    }

    /**
     * Notify a winner that their contest prize payout has been scheduled
     *
     * @param  User  $user  The winner
     * @param  PayoutSchedule  $payoutSchedule  The scheduled payout
     * @param  ContestPrize  $prize  The contest prize
     */
    public function notifyContestPayoutScheduled(User $user, $payoutSchedule, $prize): ?Notification
    {
        if (! $user) {
            Log::warning('notifyContestPayoutScheduled: user not found');

            return null;
        }

        $notification = null;
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_CONTEST_PAYOUT_SCHEDULED,
                $payoutSchedule,
                [
                    'project_id' => $payoutSchedule->project_id,
                    'project_name' => $payoutSchedule->project->name ?? 'Contest',
                    'prize_placement' => $prize->placement,
                    'gross_amount' => $payoutSchedule->gross_amount,
                    'net_amount' => $payoutSchedule->net_amount,
                    'currency' => $payoutSchedule->currency,
                    'hold_release_date' => $payoutSchedule->hold_release_date->toDateString(),
                ]
            );

            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify contest payout scheduled', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
                'payout_schedule_id' => $payoutSchedule->id,
            ]);

            return null;
        }

        return $notification;
    }

    /**
     * Notify a user that their payout has been completed
     *
     * @param  User  $user  The recipient
     * @param  PayoutSchedule  $payoutSchedule  The completed payout
     */
    public function notifyPayoutCompleted(User $user, $payoutSchedule): ?Notification
    {
        if (! $user) {
            Log::warning('notifyPayoutCompleted: user not found');

            return null;
        }

        $notification = null;
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_PAYOUT_COMPLETED,
                $payoutSchedule,
                [
                    'project_id' => $payoutSchedule->project_id,
                    'project_name' => $payoutSchedule->project->name ?? 'Project',
                    'net_amount' => $payoutSchedule->net_amount,
                    'currency' => $payoutSchedule->currency,
                    'stripe_transfer_id' => $payoutSchedule->stripe_transfer_id,
                    'completed_at' => $payoutSchedule->completed_at->toDateString(),
                ]
            );

            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify payout completed', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
                'payout_schedule_id' => $payoutSchedule->id,
            ]);

            return null;
        }

        return $notification;
    }

    /**
     * Notify a user that their payout has failed
     *
     * @param  User  $user  The recipient
     * @param  PayoutSchedule  $payoutSchedule  The failed payout
     * @param  string  $reason  The failure reason
     */
    public function notifyPayoutFailed(User $user, $payoutSchedule, string $reason): ?Notification
    {
        if (! $user) {
            Log::warning('notifyPayoutFailed: user not found');

            return null;
        }

        $notification = null;
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_PAYOUT_FAILED,
                $payoutSchedule,
                [
                    'project_id' => $payoutSchedule->project_id,
                    'project_name' => $payoutSchedule->project->name ?? 'Project',
                    'net_amount' => $payoutSchedule->net_amount,
                    'currency' => $payoutSchedule->currency,
                    'failure_reason' => $reason,
                    'failed_at' => $payoutSchedule->failed_at->toDateString(),
                ]
            );

            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify payout failed', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
                'payout_schedule_id' => $payoutSchedule->id,
            ]);

            return null;
        }

        return $notification;
    }

    /**
     * Notify a user that their payout has been cancelled
     *
     * @param  User  $user  The recipient
     * @param  PayoutSchedule  $payoutSchedule  The cancelled payout
     * @param  string  $reason  The cancellation reason
     */
    public function notifyPayoutCancelled(User $user, $payoutSchedule, string $reason): ?Notification
    {
        if (! $user) {
            Log::warning('notifyPayoutCancelled: user not found');

            return null;
        }

        $notification = null;
        try {
            $notification = $this->createNotification(
                $user,
                Notification::TYPE_PAYOUT_CANCELLED,
                $payoutSchedule,
                [
                    'project_id' => $payoutSchedule->project_id,
                    'project_name' => $payoutSchedule->project->name ?? 'Project',
                    'net_amount' => $payoutSchedule->net_amount,
                    'currency' => $payoutSchedule->currency,
                    'cancellation_reason' => $reason,
                    'cancelled_at' => $payoutSchedule->cancelled_at->toDateString(),
                ]
            );

            if ($notification) {
                try {
                    $user->notify(new UserNotification(
                        $notification->type,
                        $notification->related_id,
                        $notification->related_type,
                        $notification->data
                    ));
                } catch (\Exception $notifyException) {
                    Log::warning('Failed to dispatch Laravel notification', [
                        'message' => $notifyException->getMessage(),
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify payout cancelled', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
                'payout_schedule_id' => $payoutSchedule->id,
            ]);

            return null;
        }

        return $notification;
    }
} // End of NotificationService class
