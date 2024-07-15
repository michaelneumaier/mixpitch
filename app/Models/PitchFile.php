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

    public function name()
    {
        $pathInfo = pathinfo($this->file_name);
        return $pathInfo['filename'];
    }

    public function extension()
    {
        $pathInfo = pathinfo($this->file_name);
        return $pathInfo['extension'];
    }
}
