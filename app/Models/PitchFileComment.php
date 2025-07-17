<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PitchFileComment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pitch_file_id',
        'user_id',
        'parent_id',
        'comment',
        'timestamp',
        'resolved',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'timestamp' => 'float',
        'resolved' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'formatted_timestamp',
        'has_replies',
    ];

    /**
     * Get the pitch file that the comment belongs to.
     */
    public function pitchFile()
    {
        return $this->belongsTo(PitchFile::class);
    }

    /**
     * Get the user that created the comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment.
     */
    public function parent()
    {
        return $this->belongsTo(PitchFileComment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment.
     */
    public function replies()
    {
        return $this->hasMany(PitchFileComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Check if the comment has replies.
     *
     * @return bool
     */
    public function getHasRepliesAttribute()
    {
        return $this->replies()->count() > 0;
    }

    /**
     * Get all replies recursively
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllReplies()
    {
        $allReplies = collect();

        // Add direct replies
        $directReplies = $this->replies;
        $allReplies = $allReplies->merge($directReplies);

        // Add nested replies
        foreach ($directReplies as $reply) {
            $allReplies = $allReplies->merge($reply->getAllReplies());
        }

        return $allReplies;
    }

    /**
     * Check if the comment is a reply.
     *
     * @return bool
     */
    public function isReply()
    {
        return $this->parent_id !== null;
    }

    /**
     * Format the timestamp to a readable format (MM:SS).
     *
     * @return string
     */
    public function getFormattedTimestampAttribute()
    {
        $minutes = floor($this->timestamp / 60);
        $seconds = $this->timestamp % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
