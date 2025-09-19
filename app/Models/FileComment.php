<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FileComment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'comment',
        'timestamp',
        'resolved',
        'client_email',
        'is_client_comment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'timestamp' => 'float',
        'resolved' => 'boolean',
        'is_client_comment' => 'boolean',
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
     * Get the commentable model (PitchFile, ProjectFile, etc.)
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that created the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(FileComment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(FileComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Check if the comment has replies.
     */
    public function getHasRepliesAttribute(): bool
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
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Format the timestamp to a readable format (MM:SS).
     */
    public function getFormattedTimestampAttribute(): string
    {
        $minutes = floor($this->timestamp / 60);
        $seconds = $this->timestamp % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Check if this is a client comment.
     */
    public function isClientComment(): bool
    {
        return $this->is_client_comment ?? false;
    }

    /**
     * Get the author name (client email or user name).
     */
    public function getAuthorName(): string
    {
        if ($this->isClientComment()) {
            return $this->client_email;
        }

        return $this->user?->name ?? 'Unknown';
    }

    /**
     * Backwards compatibility: Get the pitch file that the comment belongs to.
     * This maintains compatibility with existing code during migration.
     *
     * @deprecated Use commentable() instead
     */
    public function pitchFile(): BelongsTo
    {
        if ($this->commentable_type === 'App\Models\PitchFile') {
            return $this->belongsTo(PitchFile::class, 'commentable_id');
        }

        // Return an empty relationship if not a pitch file
        return $this->belongsTo(PitchFile::class, 'commentable_id')->whereRaw('1 = 0');
    }
}
