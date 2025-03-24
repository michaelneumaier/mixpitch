<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Create a notification for a user
     *
     * @param User $user The user to notify
     * @param string $type The notification type
     * @param Model $related The related model
     * @param array $data Additional data
     * @return Notification
     */
    public function createNotification(User $user, string $type, Model $related, array $data = []): Notification
    {
        try {
            // Extra debugging for SQL query issue
            DB::enableQueryLog();
            
            // Debug logging to verify notification creation
            Log::info('Creating notification', [
                'user_id' => $user->id,
                'user_exists' => User::find($user->id) ? 'Yes' : 'No',
                'type' => $type,
                'related_type' => get_class($related),
                'related_id' => $related->id, 
                'data' => $data
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
                    'minutes_ago' => $existingNotification->created_at->diffInMinutes(now())
                ]);
                
                // Return the existing notification instead of creating a duplicate
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
                    'created_at' => $existingNotification->created_at
                ]);
            }
            
            // Create notification with full error trapping
            try {
                $notification = new Notification();
                $notification->user_id = $user->id;
                $notification->related_id = $related->id;
                $notification->related_type = get_class($related);
                $notification->type = $type;
                $notification->data = $data;
                $notification->save();
                
                Log::info('SQL Queries executed for notification creation:', [
                    'queries' => DB::getQueryLog()
                ]);
                
                // Broadcast the notification event
                event(new \App\Events\NotificationCreated($notification));
                
                // Confirm notification was saved
                Log::info('Notification created successfully', [
                    'notification_id' => $notification->id,
                    'exists_in_db' => Notification::find($notification->id) ? 'Yes' : 'No'
                ]);
                
                return $notification;
            } catch (\Exception $innerException) {
                Log::error('Database exception in notification creation', [
                    'message' => $innerException->getMessage(),
                    'trace' => $innerException->getTraceAsString()
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
     * @param Pitch $pitch The pitch
     * @param string $status The new status
     * @return Notification|null
     */
    public function notifyPitchStatusChange(Pitch $pitch, string $status): ?Notification
    {
        // Notify the pitch creator about status changes
        $user = $pitch->user;
        
        if (!$user) {
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
     * @param Pitch $pitch The pitch
     * @return Notification|null
     */
    public function notifyPitchCancellation(Pitch $pitch): ?Notification
    {
        // Notify the pitch creator
        $user = $pitch->user;
        
        if (!$user) {
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
     * @param Pitch $pitch The pitch
     * @param string|null $feedback Completion feedback
     * @return Notification|null
     */
    public function notifyPitchCompleted(Pitch $pitch, ?string $feedback = null): ?Notification
    {
        // Notify the pitch creator
        $user = $pitch->user;
        
        if (!$user) {
            Log::warning('Failed to notify about pitch completion: user not found', [
                'pitch_id' => $pitch->id,
            ]);
            return null;
        }
        
        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_PITCH_COMPLETED,
                $pitch,
                [
                    'feedback' => $feedback,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch completion', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);
            
            return null;
        }
    }
    
    /**
     * Notify a user about a new comment on their pitch
     *
     * @param Pitch $pitch The pitch
     * @param string $comment The comment text
     * @param int $commenterId The user ID of the commenter
     * @return Notification|null
     */
    public function notifyPitchComment(Pitch $pitch, string $comment, int $commenterId): ?Notification
    {
        // Don't notify if the comment is by the pitch owner
        if ($pitch->user_id === $commenterId) {
            return null;
        }
        
        // Notify the pitch creator
        $user = $pitch->user;
        
        if (!$user) {
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
     * @param PitchSnapshot $snapshot The snapshot
     * @return Notification|null
     */
    public function notifySnapshotApproved(PitchSnapshot $snapshot): ?Notification
    {
        // Get the pitch
        $pitch = $snapshot->pitch;
        
        if (!$pitch) {
            Log::warning('Failed to notify about snapshot approval: pitch not found', [
                'snapshot_id' => $snapshot->id,
            ]);
            return null;
        }
        
        // Notify the pitch creator
        $user = $pitch->user;
        
        if (!$user) {
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
     * @param PitchSnapshot $snapshot The snapshot
     * @param string $reason The reason for denial
     * @return Notification|null
     */
    public function notifySnapshotDenied(PitchSnapshot $snapshot, string $reason): ?Notification
    {
        // Get the pitch
        $pitch = $snapshot->pitch;
        
        if (!$pitch) {
            Log::warning('Failed to notify about snapshot denial: pitch not found', [
                'snapshot_id' => $snapshot->id,
            ]);
            return null;
        }
        
        // Notify the pitch creator
        $user = $pitch->user;
        
        if (!$user) {
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
     * @param PitchSnapshot $snapshot The snapshot
     * @param string $reason The reason for requesting revisions
     * @return Notification|null
     */
    public function notifySnapshotRevisionsRequested(PitchSnapshot $snapshot, string $reason)
    {
        $pitch = $snapshot->pitch;
        $user = $pitch->user;
        
        if (!$user) {
            Log::warning('Failed to notify about snapshot revisions requested: user not found', [
                'snapshot_id' => $snapshot->id,
                'pitch_id' => $pitch->id,
            ]);
            return null;
        }
        
        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_SNAPSHOT_REVISIONS_REQUESTED,
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
            Log::error('Failed to create snapshot revisions requested notification: ' . $e->getMessage(), [
                'snapshot_id' => $snapshot->id,
                'pitch_id' => $pitch->id,
                'user_id' => $user->id,
            ]);
            return null;
        }
    }
    
    /**
     * Notify a project owner about a new pitch submission
     *
     * @param Pitch $pitch The pitch
     * @return Notification|null
     */
    public function notifyNewSubmission(Pitch $pitch): ?Notification
    {
        // Notify the project owner
        $user = $pitch->project->user;
        
        if (!$user) {
            Log::warning('Failed to notify about new submission: project owner not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
            ]);
            return null;
        }
        
        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_NEW_SUBMISSION,
                $pitch,
                [
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'pitch_title' => $pitch->title,
                    'submitter_id' => $pitch->user_id,
                    'submitter_name' => $pitch->user->name ?? 'Unknown',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about new submission', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
            ]);
            
            return null;
        }
    }
    
    /**
     * Notify project owner about a pitch being edited
     *
     * @param Pitch $pitch The pitch
     * @return Notification|null
     */
    public function notifyPitchEdited(Pitch $pitch): ?Notification
    {
        // Notify project owner about significant edits to a pitch
        $projectOwner = $pitch->project->user;
        
        if (!$projectOwner) {
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
                    'editor_name' => auth()->user()->name
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
     * @param Pitch $pitch The pitch
     * @param \App\Models\PitchFile $file The uploaded file
     * @return Notification|null
     */
    public function notifyFileUploaded(Pitch $pitch, \App\Models\PitchFile $file): ?Notification
    {
        // Notify project owner about new file uploads
        $projectOwner = $pitch->project->user;
        
        if (!$projectOwner) {
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
                    'uploader_name' => auth()->user()->name
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
     * @param Pitch $pitch The pitch
     * @return Notification|null
     */
    public function notifyPitchRevisionSubmitted(Pitch $pitch): ?Notification
    {
        // Notify project owner about a revision submission
        $projectOwner = $pitch->project->user;
        
        if (!$projectOwner) {
            Log::warning('Failed to notify about revision submission: project owner not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
            ]);
            return null;
        }
        
        try {
            return $this->createNotification(
                $projectOwner,
                Notification::TYPE_PITCH_REVISION_SUBMITTED,
                $pitch,
                [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'submitter_id' => auth()->id(),
                    'submitter_name' => auth()->user()->name,
                    'snapshot_id' => $pitch->current_snapshot_id
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
     * Notify project owner about a pitch submission being cancelled
     *
     * @param Pitch $pitch The pitch
     * @return Notification|null
     */
    public function notifyPitchCancelled(Pitch $pitch): ?Notification
    {
        // Notify project owner about a submission cancellation
        $projectOwner = $pitch->project->user;
        
        if (!$projectOwner) {
            Log::warning('Failed to notify about pitch cancellation: project owner not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
            ]);
            return null;
        }
        
        try {
            return $this->createNotification(
                $projectOwner,
                Notification::TYPE_PITCH_CANCELLED,
                $pitch,
                [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'canceller_id' => auth()->id(),
                    'canceller_name' => auth()->user()->name
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
     * Notify a user about a pitch submission being canceled
     *
     * @param Pitch $pitch The pitch
     * @return Notification|null
     */
    public function notifyPitchCancelledByCreator(Pitch $pitch): ?Notification
    {
        // Notify the project owner when a pitch creator cancels their submission
        $user = $pitch->project->user;
        
        if (!$user) {
            Log::warning('Failed to notify about pitch cancellation: project user not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
            ]);
            return null;
        }
        
        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_PITCH_CANCELLED,
                $pitch,
                [
                    'status' => $pitch->status,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'creator_name' => $pitch->user->name,
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
     * Notify a project owner about a file upload
     *
     * @param Pitch $pitch The pitch
     * @param \App\Models\PitchFile $file The uploaded file
     * @return Notification|null
     */
    public function notifyFileUploadedToProject(Pitch $pitch, \App\Models\PitchFile $file): ?Notification
    {
        // Notify the project owner
        $user = $pitch->project->user;
        
        if (!$user) {
            Log::warning('Failed to notify about file upload: project user not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
            ]);
            return null;
        }
        
        try {
            return $this->createNotification(
                $user,
                Notification::TYPE_FILE_UPLOADED,
                $pitch,
                [
                    'file_id' => $file->id,
                    'file_name' => $file->file_name,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'uploader_name' => $pitch->user->name,
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
     * Notify a project owner about a pitch revision submission
     *
     * @param Pitch $pitch The pitch
     * @return Notification|null
     */
    public function notifyPitchRevisionSubmittedToProject(Pitch $pitch): ?Notification
    {
        // Notify the project owner
        $user = $pitch->project->user;
        
        if (!$user) {
            Log::warning('Failed to notify about pitch revision: project user not found', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
            ]);
            return null;
        }
        
        try {
            // Get snapshot count for revision number
            $snapshotCount = $pitch->snapshots()->count();
            
            return $this->createNotification(
                $user,
                Notification::TYPE_PITCH_REVISION,
                $pitch,
                [
                    'revision_number' => $snapshotCount,
                    'project_id' => $pitch->project_id,
                    'project_name' => $pitch->project->name,
                    'creator_name' => $pitch->user->name,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify about pitch revision', [
                'message' => $e->getMessage(),
                'pitch_id' => $pitch->id,
            ]);
            
            return null;
        }
    }

    /**
     * Notify a user about a new comment on their pitch file
     *
     * @param \App\Models\PitchFile $pitchFile The pitch file
     * @param \App\Models\PitchFileComment $comment The comment
     * @param int $commenterId The user ID of the commenter
     * @return Notification|null
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
            if ($parentComment && $parentComment->user_id !== $commenterId && !in_array($parentComment->user_id, $notifiedUsers)) {
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
                        !in_array($topLevelComment->user_id, $notifiedUsers)) {
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
        if ($pitchFile->user_id !== $commenterId && !in_array($pitchFile->user_id, $notifiedUsers)) {
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
        $pitch = $pitchFile->pitch;
        if ($pitch && $pitch->user_id !== $commenterId && !in_array($pitch->user_id, $notifiedUsers)) {
            $pitchOwner = User::find($pitch->user_id);
            
            if ($pitchOwner) {
                try {
                    $notifications[] = $this->createNotification(
                        $pitchOwner,
                        Notification::TYPE_PITCH_FILE_COMMENT,
                        $pitchFile,
                        $notificationData
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to notify pitch owner about pitch file comment', [
                        'message' => $e->getMessage(),
                        'pitch_file_id' => $pitchFile->id,
                        'pitch_id' => $pitch->id,
                        'pitch_owner_id' => $pitch->user_id,
                    ]);
                }
            }
        }
        
        return $notifications[0] ?? null;
    }

    /**
     * Notify a user about a payment being processed for their pitch
     *
     * @param Pitch $pitch The pitch
     * @param float $amount Payment amount
     * @param string $invoiceId Invoice ID
     * @return Notification|null
     */
    public function notifyPaymentProcessed(Pitch $pitch, float $amount, string $invoiceId): ?Notification
    {
        // Notify the pitch creator
        $user = $pitch->user;
        
        if (!$user) {
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
     * Notify a user about a payment failure for their pitch
     *
     * @param Pitch $pitch The pitch
     * @param string $error Error message
     * @return Notification|null
     */
    public function notifyPaymentFailed(Pitch $pitch, string $error): ?Notification
    {
        // Notify the pitch creator
        $user = $pitch->user;
        
        if (!$user) {
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
                    'error' => $error,
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
}
