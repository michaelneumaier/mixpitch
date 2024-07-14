<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pitch extends Model
{
    use HasFactory;
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_READY_FOR_REVIEW = 'ready_for_review';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_DENIED = 'denied';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = ['status'];

    protected $attributes = [
        'max_files' => 25,
    ];

    protected static $transitions = [
        'forward' => [
            self::STATUS_PENDING => self::STATUS_IN_PROGRESS,
            self::STATUS_IN_PROGRESS => self::STATUS_READY_FOR_REVIEW,
            self::STATUS_PENDING_REVIEW => self::STATUS_READY_FOR_REVIEW,
            self::STATUS_READY_FOR_REVIEW => [
                self::STATUS_APPROVED,
            ],
        ],
        'backward' => [
            self::STATUS_IN_PROGRESS => self::STATUS_PENDING,
            self::STATUS_APPROVED => self::STATUS_IN_PROGRESS,
            self::STATUS_DENIED => self::STATUS_IN_PROGRESS,
            self::STATUS_PENDING_REVIEW => [
                self::STATUS_PENDING,
                self::STATUS_IN_PROGRESS,
            ],
            self::STATUS_READY_FOR_REVIEW => [
                self::STATUS_IN_PROGRESS,
                self::STATUS_DENIED,
                self::STATUS_PENDING_REVIEW,
            ],
        ],
    ];

    public function getReadableStatusAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOwnedByUser(User $user)
    {
        return $this->user_id == $user->id;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function files()
    {
        return $this->hasMany(PitchFile::class);
    }

    public function events()
    {
        return $this->hasMany(PitchEvent::class);
    }

    public function snapshots()
    {
        return $this->hasMany(PitchSnapshot::class);
    }

    public function createSnapshot()
    {
        // Retrieve the last snapshot for this pitch
        $lastSnapshot = $this->snapshots()->orderBy('created_at', 'desc')->first();

        // Determine the version number
        $version = $lastSnapshot ? ($lastSnapshot->snapshot_data['version'] + 1) : 1;

        // Prepare the snapshot data
        $snapshotData = [
            'version' => $version,
            'file_ids' => $this->files->pluck('id')->toArray(),
        ];

        // Create the new snapshot
        $this->snapshots()->create([
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'snapshot_data' => $snapshotData,
        ]);
    }

    public function changeStatus($direction, $newStatus = null)
    {
        if (!in_array($direction, ['forward', 'backward'])) {
            throw new \InvalidArgumentException("Invalid direction.");
        }

        $currentStatus = $this->status;
        $comment = '';

        if ($direction === 'forward' && is_array(self::$transitions[$direction][$currentStatus])) {
            if (!in_array($newStatus, self::$transitions[$direction][$currentStatus])) {
                throw new \Exception("Invalid status transition.");
            }
            $this->status = $newStatus;
        } else if ($direction === 'backward' && is_array(self::$transitions[$direction][$currentStatus])) {
            if (!in_array($newStatus, self::$transitions[$direction][$currentStatus])) {
                throw new \Exception("Invalid status transition.");
            }
            $this->status = $newStatus;
        } else {
            if (isset(self::$transitions[$direction][$currentStatus])) {
                $this->status = self::$transitions[$direction][$currentStatus];
            } else {
                throw new \Exception("Cannot change status in the $direction direction.");
            }
        }

        $this->save();

        // switch ($newStatus) {
        //     case 'pending':
        //         $comment = 'Pitch is Pending Access';
        //         break;
        //     case 'in_progress':
        //         $comment = 'Pitch Access is Approved';
        //         break;
        //     case 'ready_for_review':
        //         $comment = 'Pitch Submitted and Ready for Review';
        //         break;
        //     case 'pending_review':
        //         $comment = 'Pitch sent back for Review';
        //         break;
        //     case 'denied':
        //         $comment = 'Pitch is Denied';
        //         break;
        //     case 'approved':
        //         $comment = 'Pitch is Approved';
        //         break;
        //     default:
        // }

        $this->events()->create([
            'event_type' => 'status_change',
            'comment' => $comment,
            'status' => $this->status,
            'created_by' => auth()->id(),
        ]);
    }

    public function addComment($comment)
    {
        $this->events()->create([
            'event_type' => 'comment',
            'comment' => $comment,
            'created_by' => auth()->id(),
        ]);
    }

    public function deleteComment($commentId)
    {
        $comment = $this->events()->where('id', $commentId)->where('event_type', 'comment')->firstOrFail();

        if ($comment->created_by == auth()->id()) {
            $comment->delete();
        } else {
            throw new \Exception('Unauthorized action.');
        }
    }

    public function addRating($rating)
    {
        $this->events()->create([
            'event_type' => 'rating',
            'rating' => $rating,
            'created_by' => auth()->id(),
        ]);
    }
}
