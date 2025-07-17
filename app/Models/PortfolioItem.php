<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioItem extends Model
{
    use HasFactory;

    const TYPE_AUDIO = 'audio';

    const TYPE_YOUTUBE = 'youtube';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'item_type',
        'title',
        'description',
        'video_url',
        'video_id',
        'file_path',
        'file_name',
        'original_filename',
        'mime_type',
        'file_size',
        'display_order',
        'is_public',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'display_order' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Extract YouTube video ID if item_type is youtube and URL is provided
            if ($item->item_type === self::TYPE_YOUTUBE && ! empty($item->video_url)) {
                $item->video_id = self::extractYouTubeVideoId($item->video_url);
                // Nullify audio fields if item_type is youtube
                $item->file_path = null;
                $item->file_name = null;
                $item->original_filename = null;
                $item->mime_type = null;
                $item->file_size = null;
            } elseif ($item->item_type === self::TYPE_AUDIO) {
                // Nullify video fields if item_type is audio
                $item->video_url = null;
                $item->video_id = null;
            }
        });

        // Automatically set display_order for new items
        static::creating(function ($item) {
            if (is_null($item->display_order)) {
                $maxOrder = static::where('user_id', $item->user_id)->max('display_order');
                $item->display_order = $maxOrder !== null ? $maxOrder + 1 : 0;
            }
        });
    }

    /**
     * Get the user that owns the portfolio item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project linked to the portfolio item.
     */
    public function linkedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'linked_project_id');
    }

    /**
     * Helper function to extract YouTube video ID from various URL formats.
     */
    public static function extractYouTubeVideoId(string $url): ?string
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        preg_match($pattern, $url, $matches);

        return $matches[1] ?? null;
    }
}
