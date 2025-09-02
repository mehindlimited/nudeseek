<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'image'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = $tag->generateUniqueSlug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name')) {
                $tag->slug = $tag->generateUniqueSlug($tag->name);
            }
        });
    }

    public function generateUniqueSlug($name)
    {
        $slug = Str::slug($name); // Converts "Science Fiction" to "science-fiction"
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    public function videos()
    {
        return $this->belongsToMany(Video::class, 'tags_videos', 'tag_id', 'video_id');
    }
}
