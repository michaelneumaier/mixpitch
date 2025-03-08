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

    /**
     * Get human-readable status
     */
    public function getStatusLabelAttribute()
    {
        $statusMapping = [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'denied' => 'Denied',
            'revisions_requested' => 'Revisions Requested',
            'revision_addressed' => 'Revision Addressed',
            'completed' => 'Completed',
        ];

        return $statusMapping[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Check if the snapshot has changes requested
     */
    public function hasChangesRequested()
    {
        return $this->status === 'revisions_requested';
    }

    /**
     * Check if the snapshot is approved
     * 
     * @return bool
     */
    public function isApproved()
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if the snapshot is denied
     * 
     * @return bool
     */
    public function isDenied()
    {
        return $this->status === 'denied';
    }

    /**
     * Check if the snapshot is pending review
     * 
     * @return bool
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }
    
    /**
     * Check if the snapshot is for a completed pitch
     * 
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Change the status of the snapshot
     * 
     * @param string $status
     * @return bool
     */
    public function changeStatus($status)
    {
        $allowedStatuses = ['pending', 'accepted', 'denied', 'revisions_requested', 'revision_addressed', 'completed'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new \InvalidArgumentException("Invalid snapshot status: {$status}");
        }
        
        $this->status = $status;
        return $this->save();
    }
}
