<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BulkDownload extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'file_ids',
        'archive_name',
        'status',
        'storage_path',
        'download_url',
        'download_url_expires_at',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'file_ids' => 'array',
        'download_url_expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generateDownloadUrl(int $minutes = 60): string
    {
        if (! $this->storage_path) {
            throw new \Exception('Archive not ready');
        }

        $url = Storage::disk('s3')->temporaryUrl(
            $this->storage_path,
            now()->addMinutes($minutes),
            ['ResponseContentDisposition' => 'attachment; filename="'.addslashes($this->archive_name).'"']
        );

        $this->update([
            'download_url' => $url,
            'download_url_expires_at' => now()->addMinutes($minutes),
        ]);

        return $url;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
