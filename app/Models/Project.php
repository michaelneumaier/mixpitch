<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

use Illuminate\Support\Facades\Storage;
use Sebdesign\SM\StateMachine\StateMachine;
use Sebdesign\SM\StateMachine\StateMachineInterface;

class Project extends Model
{
    use HasFactory;
    use Sluggable;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'genre',
        'status',
        'image_path',
        'slug',
        'artist_name',
        'project_type',
        'collaboration_type',
        'budget',
        'deadline',
        'preview_track',
        'notes'
    ];

    protected $casts = [
        'collaboration_type' => 'array',
    ];

    protected $attributes = ['status' => 'unpublished'];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOwnedByUser(User $user)
    {
        return $this->user_id == $user->id;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->save();
    }

    public function hasPreviewTrack()
    {
        if ($this->preview_track) {
            return true;
        } else {
            return false;
        }
    }

    public function previewTrack()
    {
        return $this->hasOne(ProjectFile::class, 'id', 'preview_track');
    }
    public function previewTrackPath()
    {
        if ($this->hasPreviewTrack()) {
            $track = $this->previewTrack;
            try {
                return asset('storage/' . $track->file_path);
            } catch (Exception $e) {
                return null;
            }
        } else {
            return null;
        }
    }

    public function deleteProjectImage()
    {
        try {
            return Storage::disk('public')->delete($this->image_path);
        } catch (Exception $e) {
            return $e;
        }
    }

    public function tracks()
    {
        return $this->hasMany(Track::class);
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function mixes()
    {
        return $this->hasMany(Mix::class);
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    // public function stateMachine(): StateMachineInterface
    // {
    //     return new StateMachine($this, 'project_status', config('state-machine'));
    // }
}
