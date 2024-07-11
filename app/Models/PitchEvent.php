<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PitchEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'pitch_id',
        'event_type',
        'status',
        'comment',
        'rating',
        'created_by'
    ];

    public function pitch()
    {
        return $this->belongsTo(Pitch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
