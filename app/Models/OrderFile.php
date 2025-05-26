<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrderFile extends Model
{
    use HasFactory;

    // File Type Constants
    const TYPE_REQUIREMENT = 'requirement';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_REFERENCE = 'reference';
    const TYPE_GENERAL = 'general';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'uploader_user_id',
        'file_path',
        'file_name',
        'mime_type',
        'size',
        'disk',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'integer',
    ];

    // Relationships

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_user_id');
    }

    // Accessors

    public function getUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }
        return Storage::disk($this->disk)->url($this->file_path);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size ?? 0;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    // Static Helpers

    public static function getTypes(): array
    {
        return [
            self::TYPE_REQUIREMENT,
            self::TYPE_DELIVERY,
            self::TYPE_REFERENCE,
            self::TYPE_GENERAL,
        ];
    }
}
