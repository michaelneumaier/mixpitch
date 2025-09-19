<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class PitchFileComment
 *
 * @deprecated This class is deprecated and kept for backwards compatibility.
 * Use FileComment instead which supports polymorphic relationships.
 *
 * This class extends FileComment to maintain compatibility during migration.
 * All existing code using PitchFileComment will continue to work.
 */
class PitchFileComment extends FileComment
{
    /**
     * The table associated with the model.
     * Override to use the new file_comments table
     *
     * @var string
     */
    protected $table = 'file_comments';

    /**
     * Override fillable to include pitch_file_id for backward compatibility
     */
    protected $fillable = [
        'pitch_file_id',
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
     * Boot method to set default values for polymorphic relationship
     */
    protected static function boot()
    {
        parent::boot();

        // When creating a PitchFileComment, set the polymorphic relationship
        static::creating(function ($comment) {
            if (isset($comment->pitch_file_id) && ! isset($comment->commentable_id)) {
                $comment->commentable_type = 'App\Models\PitchFile';
                $comment->commentable_id = $comment->pitch_file_id;
            }
        });
    }

    /**
     * Get pitch_file_id attribute for backward compatibility
     */
    public function getPitchFileIdAttribute()
    {
        if ($this->commentable_type === 'App\Models\PitchFile') {
            return $this->commentable_id;
        }

        return null;
    }

    /**
     * Set pitch_file_id attribute for backward compatibility
     */
    public function setPitchFileIdAttribute($value)
    {
        $this->commentable_type = 'App\Models\PitchFile';
        $this->commentable_id = $value;
    }

    /**
     * Override parent relationship to use PitchFileComment for compatibility
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PitchFileComment::class, 'parent_id');
    }

    /**
     * Override replies relationship to use PitchFileComment for compatibility
     */
    public function replies(): HasMany
    {
        return $this->hasMany(PitchFileComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }
}
