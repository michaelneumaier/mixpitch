<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'storage_path',
        'status',
        'summary',
        'error_message',
        'total_rows',
        'imported_rows',
        'duplicate_rows',
        'error_rows',
    ];

    protected $casts = [
        'summary' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
