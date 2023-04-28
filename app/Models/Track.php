<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class Track extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'genre',
        'file_path',
        'user_id',
    ];

    public function user()
{
    return $this->belongsTo(User::class);
}

public function project()
{
    return $this->belongsTo(Project::class);
}


}
