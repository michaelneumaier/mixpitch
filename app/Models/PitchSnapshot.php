<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PitchSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'pitch_id',
        'project_id',
        'user_id',
        'snapshot_data',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
    ];

    public function pitch()
    {
        return $this->belongsTo(Pitch::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
