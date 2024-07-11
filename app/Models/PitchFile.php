<?php

// app/Models/PitchFile.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PitchFile extends Model
{
    protected $fillable = ['file_path', 'file_name', 'note', 'user_id'];

    public function pitch()
    {
        return $this->belongsTo(Pitch::class);
    }
}
