<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all projects using this project type
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the full icon class for display
     */
    public function getIconClass(): string
    {
        return $this->icon ?: 'fas fa-folder';
    }

    /**
     * Get Tailwind color classes for this project type
     */
    public function getColorClasses(): array
    {
        $color = $this->color ?: 'blue';

        return [
            'bg' => "bg-{$color}-500",
            'hover_bg' => "hover:bg-{$color}-600",
            'text' => "text-{$color}-600",
            'border' => "border-{$color}-200",
            'ring' => "ring-{$color}-500",
            'gradient_from' => "from-{$color}-500",
            'gradient_to' => "to-{$color}-600",
        ];
    }

    /**
     * Get only active project types ordered by sort_order
     */
    public static function getActive()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get project types formatted for dropdown/select options
     */
    public static function getForDropdown()
    {
        return static::getActive()->pluck('name', 'id');
    }

    /**
     * Get project types formatted for validation rules
     */
    public static function getActiveIds()
    {
        return static::where('is_active', true)->pluck('id')->toArray();
    }

    /**
     * Find project type by slug
     */
    public static function findBySlug($slug)
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Scope to get only active types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
