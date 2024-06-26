<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pitch extends Model
{
    use HasFactory;
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = ['status'];

    protected $attributes = [
        'max_files' => 25,
    ];

    protected static $transitions = [
        'forward' => [
            self::STATUS_PENDING => self::STATUS_IN_PROGRESS,
            self::STATUS_IN_PROGRESS => self::STATUS_COMPLETED,
        ],
        'backward' => [
            self::STATUS_COMPLETED => self::STATUS_IN_PROGRESS,
            self::STATUS_IN_PROGRESS => self::STATUS_PENDING,
        ],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOwnedByUser(User $user)
    {
        return $this->user_id == $user->id;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function files()
    {
        return $this->hasMany(PitchFile::class);
    }

    public function changeStatus($direction)
    {
        if (!in_array($direction, ['forward', 'backward'])) {
            throw new \InvalidArgumentException("Invalid direction.");
        }

        $currentStatus = $this->status;

        if (isset(self::$transitions[$direction][$currentStatus])) {
            $newStatus = self::$transitions[$direction][$currentStatus];
            $this->status = $newStatus;
            $this->save();
        } else {
            throw new \Exception("Cannot change status in the $direction direction.");
        }
    }

    public function getReadableStatusAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }
}
