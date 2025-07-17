<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'type'];

    /**
     * Define the relationship to users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users()
    {
        return $this->morphedByMany(User::class, 'taggable');
    }

    // Add other relationships here if tags are used for other models (e.g., projects)
    // public function projects()
    // {
    //     return $this->morphedByMany(Project::class, 'taggable');
    // }

    /**
     * Automatically create slug from name when saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) { // Optionally update slug if name changes and slug is empty
                $tag->slug = Str::slug($tag->name);
            }
        });
    }
}
