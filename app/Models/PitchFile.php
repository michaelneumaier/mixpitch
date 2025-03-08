<?php

// app/Models/PitchFile.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LakM\Comments\Concerns\Commentable;
use LakM\Comments\Contracts\CommentableContract;
use Illuminate\Support\Facades\Storage;
use Exception;

class PitchFile extends Model implements CommentableContract
{
    use Commentable;
    protected $fillable = ['file_path', 'file_name', 'note', 'user_id', 'size'];

    /**
     * Format bytes to human-readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get the formatted file size
     *
     * @return string
     */
    public function getFormattedSizeAttribute()
    {
        // If size is stored in the database, use that
        if (isset($this->attributes['size']) && $this->attributes['size']) {
            return $this->formatBytes($this->attributes['size']);
        }
        
        // Otherwise try to get the size from storage
        try {
            $size = Storage::disk('public')->size($this->file_path);
            return $this->formatBytes($size);
        } catch (Exception $e) {
            return '-';
        }
    }

    /**
     * Get the full file path including storage path
     *
     * @return string|null
     */
    public function getFullFilePathAttribute()
    {
        try {
            return asset('storage/' . $this->file_path);
        } catch (Exception $e) {
            return null;
        }
    }

    public function pitch()
    {
        return $this->belongsTo(Pitch::class);
    }

    public function name()
    {
        $pathInfo = pathinfo($this->file_name);
        return $pathInfo['filename'];
    }

    public function extension()
    {
        $pathInfo = pathinfo($this->file_name);
        return $pathInfo['extension'];
    }
}
