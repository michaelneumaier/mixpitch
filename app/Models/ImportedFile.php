<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'link_import_id',
        'project_file_id',
        'source_filename',
        'source_url',
        'size_bytes',
        'mime_type',
        'imported_at',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    /**
     * Get the link import that owns this imported file.
     */
    public function linkImport(): BelongsTo
    {
        return $this->belongsTo(LinkImport::class);
    }

    /**
     * Get the project file that was created from this import.
     */
    public function projectFile(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class);
    }

    /**
     * Format the file size for display.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes;

        if ($bytes === null || $bytes <= 0) {
            return '0 bytes';
        }

        $units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
