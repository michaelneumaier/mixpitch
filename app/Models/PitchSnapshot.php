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
        'status',
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

    public function getReadableStatusAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function isApproved()
    {
        if ($this->status == 'approved') {
            return true;
        }
        return false;
    }

    public function isDeclined()
    {
        if ($this->status == 'declined') {
            return true;
        }
        return false;
    }

    public function isRevise()
    {
        if ($this->status == 'revise') {
            return true;
        }
        return false;
    }

    public function changeStatus($status)
    {
        $this->status = $status;
        return $this->save();
    }
}
