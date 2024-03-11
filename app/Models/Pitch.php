<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pitch extends Model
{
    use HasFactory;

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
}
