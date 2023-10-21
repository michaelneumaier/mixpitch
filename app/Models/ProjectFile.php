<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'file_path', 'size'];

    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function getFormattedSizeAttribute()
    {
        // Assuming the column where you store the file size in bytes is called 'size'
        $bytes = $this->attributes['size'];

        // Use the helper function to format the bytes
        return $this->formatBytes($bytes);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
