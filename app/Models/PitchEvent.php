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
        'comment',
        'status', // Pitch status AT THE TIME of the event
        'snapshot_id',
        'created_by', // User ID who triggered the event
        'metadata', // For extra context like client email, feedback, etc.
        'rating', // <-- Add rating
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array', // Cast metadata JSON column to array
    ];

    public function pitch()
    {
        return $this->belongsTo(Pitch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the snapshot associated with this event
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function snapshot()
    {
        return $this->belongsTo(PitchSnapshot::class, 'snapshot_id');
    }

    /**
     * Create a status change event for a pitch
     *
     * @param  Pitch  $pitch  The pitch that had its status changed
     * @param  User  $user  The user who changed the status
     * @param  string  $oldStatus  The previous status
     * @param  string  $newStatus  The new status
     * @return PitchEvent
     */
    public static function createStatusChangeEvent(Pitch $pitch, User $user, string $oldStatus, string $newStatus)
    {
        return self::create([
            'pitch_id' => $pitch->id,
            'event_type' => 'status_change',
            'status' => $newStatus,
            'comment' => "Status changed from '{$oldStatus}' to '{$newStatus}'",
            'created_by' => $user->id,
        ]);
    }
}
